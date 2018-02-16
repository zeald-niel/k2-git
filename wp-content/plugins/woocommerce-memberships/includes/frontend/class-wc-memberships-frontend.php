<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Frontend
 * @author    SkyVerge
 * @category  Frontend
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Frontend class, handles general frontend functionality
 *
 * @since 1.0.0
 */
class WC_Memberships_Frontend {


	/** @var \WC_Memberships_Checkout instance */
	protected $checkout;

	/** @var \WC_Memberships_Members_Area instance */
	protected $members_area;

	/** @var \WC_Memberships_Restrictions instance */
	protected $restrictions;

	/** @var null|array Cart items with member discounts helper **/
	private $cart_items_with_member_discounts;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// load classes
		$this->members_area = wc_memberships()->load_class( '/includes/frontend/class-wc-memberships-members-area.php', 'WC_Memberships_Members_Area' );
		$this->checkout     = wc_memberships()->load_class( '/includes/frontend/class-wc-memberships-checkout.php',     'WC_Memberships_Checkout' );
		$this->restrictions = wc_memberships()->load_class( '/includes/frontend/class-wc-memberships-restrictions.php', 'WC_Memberships_Restrictions' );

		// enqueue JS and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

		// handle frontend actions
		add_action( 'template_redirect', array( $this, 'cancel_membership' ) );
		add_action( 'template_redirect', array( $this, 'renew_membership' ) );

		// add cart & checkout notices
		add_action( 'wp', array( $this, 'add_cart_member_login_notice' ) );

		// optional login/link buttons on checkout / thank you pages
		add_action( 'woocommerce_before_template_part', array( $this, 'maybe_render_checkout_member_login_notice' ) );

		// redirects to restricted content or product upon login
		add_filter( 'woocommerce_login_redirect', array( $this, 'restricted_content_redirect' ), 40, 1 );

		add_action( 'woocommerce_thankyou', array( $this, 'maybe_render_thank_you_content' ), 9 );
	}


	/**
	 * Get Checkout instance
	 *
	 * @since 1.6.0
	 * @return \WC_Memberships_Checkout
	 */
	public function get_checkout_instance() {
		return $this->checkout;
	}


	/**
	 * Get the Members Area instance.
	 *
	 * @since 1.7.4
	 * @return \WC_Memberships_Members_Area
	 */
	public function get_members_area_instance() {
		return $this->members_area;
	}


	/**
	 * Get Restrictions instance
	 *
	 * @since 1.6.0
	 * @return \WC_Memberships_Restrictions
	 */
	public function get_restrictions_instance() {
		return $this->restrictions;
	}


	/**
	 * Enqueue frontend scripts & styles
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'wc-memberships-frontend', wc_memberships()->get_plugin_url() . '/assets/css/frontend/wc-memberships-frontend.min.css', '', WC_Memberships::VERSION );
	}


	/**
	 * Cancel a membership
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function cancel_membership() {

		if ( ! isset( $_REQUEST['cancel_membership'] ) ) {
			return;
		}

		$user_membership_id = (int) $_REQUEST['cancel_membership'];
		$user_membership    = wc_memberships_get_user_membership( $user_membership_id );

		if ( ! $user_membership ) {

			$notice_message = __( 'Invalid membership.', 'woocommerce-memberships' );
			$notice_type    = 'error';

		} else {

			if (     current_user_can( 'wc_memberships_cancel_membership', $user_membership_id )
			      && $user_membership->can_be_cancelled()
			      && isset( $_REQUEST['_wpnonce'] )
			      && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wc_memberships-cancel_membership_' . $user_membership_id ) ) {

				$user_membership->cancel_membership( __( 'Membership cancelled by customer.', 'woocommerce-memberships' ) );

				/**
				 * Filter the user cancelled membership message on frontend
				 *
				 * @since 1.0.0
				 * @param string $notice
				 */
				$notice_message =  apply_filters( 'wc_memberships_user_membership_cancelled_notice', __( 'Your membership was cancelled.', 'woocommerce-memberships' ) );
				$notice_type    = 'notice';

				/**
				 * Fires right after a membership has been cancelled by a customer
				 *
				 * @since 1.0.0
				 * @param int $user_membership_id
				 */
				do_action( 'wc_memberships_cancelled_user_membership', $user_membership_id );

			} else {

				$notice_message = __( 'Cannot cancel this membership.', 'woocommerce-memberships' );
				$notice_type    = 'error';
			}
		}

		if ( isset( $notice_message, $notice_type ) ) {
			wc_add_notice( $notice_message, $notice_type );
		}

		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}


	/**
	 * Log in a member
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_User_Membership $user_membership Membership the member to log in belongs to
	 */
	private function log_member_in( $user_membership ) {

		$log_in_user_id    = $user_membership->get_user_id();
		$user_is_not_admin = ! user_can( $log_in_user_id, 'edit_others_posts' ) && ! user_can( $log_in_user_id, 'edit_users' );

		// maybe log in the membership owner
		if ( is_user_logged_in() ) {

			// another user is logged in
			if ( (int) $log_in_user_id !== (int) get_current_user_id() ) {

				// log out existing user
				wp_logout();

				// do not log in a user with high privileges
				if ( $user_is_not_admin ) {

					wp_set_current_user( $log_in_user_id );
					wp_set_auth_cookie( $log_in_user_id );
				}
			}

		} elseif ( $user_is_not_admin ) {

			// log the member in automatically if has low privileges
			wp_set_current_user( $log_in_user_id );
			wp_set_auth_cookie( $log_in_user_id );
		}
	}


	/**
	 * Renew a membership
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function renew_membership() {

		if ( ! isset( $_REQUEST['renew_membership'] ) ) {
			return;
		}

		$redirect_url       = wc_get_page_permalink( 'myaccount' );
		$user_membership_id = (int) $_REQUEST['renew_membership'];
		$user_membership    = wc_memberships_get_user_membership( $user_membership_id );

		if ( ! $user_membership ) {

			$notice_message = __( 'Invalid membership.', 'woocommerce-memberships' );
			$notice_type    = 'error';

		} elseif ( $user_membership->can_be_renewed() ) {

			// makes sure the member is logged in
			$this->log_member_in( $user_membership );

			// get the renewal product to be added to cart
			$product_for_renewal = $user_membership->get_product_for_renewal();

			/* this filter is documented in /includes/class-wc-memberships-membership-plan.php */
			$renew = (bool) apply_filters( 'wc_memberships_renew_membership', (bool) $product_for_renewal, $user_membership->get_plan(), array(
				'user_id'    => $user_membership->get_user_id(),
				'product_id' => $product_for_renewal->get_id(),
				'order_id'   => $user_membership->get_order_id(),
			) );

			if ( true === $renew && current_user_can( 'wc_memberships_renew_membership', $user_membership_id ) ) {

				/**
				 * Filter whether to add to cart the renewal product and redirect to checkout,
				 * or redirect to the product page without adding it to cart.
				 *
				 * @since 1.7.4
				 * @param bool $add_to_cart Whether to add to cart the product and redirect to checkout (true, default) or redirect to product page instead (false).
				 * @param \WC_Product $product_for_renewal The product that would renew access if purchased again.
				 * @param int $user_membership_id The membership being renewed upon purchase.
				 */
				if ( true === (bool) apply_filters( 'wc_memberships_add_to_cart_renewal_product', true, $product_for_renewal, $user_membership_id ) ) {

					// empty the cart and add the one product to renew this membership
					wc_empty_cart();

					// set up variation data (if needed) before adding to the cart
					$product_id           = $product_for_renewal->is_type( 'variation' ) ? SV_WC_Product_Compatibility::get_prop( $product_for_renewal, 'parent_id' ) : $product_for_renewal->get_id();
					$variation_id         = $product_for_renewal->is_type( 'variation' ) ? $product_for_renewal->get_id() : 0;
					$variation_attributes = $product_for_renewal->is_type( 'variation' ) ? wc_get_product_variation_attributes( $variation_id ) : array();


					// add the product to the cart
					WC()->cart->add_to_cart( $product_id, 1, $variation_id, $variation_attributes );

					// then redirect to checkout instead of my account page
					$redirect_url = wc_get_checkout_url();

				} else {

					$redirect_url = get_permalink( $product_for_renewal->is_type( 'variation' ) ? SV_WC_Product_Compatibility::get_prop( $product_for_renewal, 'parent_id' ) : $product_for_renewal->get_id() );
				}

				/* translators: Placeholder: %s - a product to purchase to renew a membership */
				$notice_message  = sprintf( __( 'Renew your membership by purchasing %s.', 'woocommerce-memberships' ) . ' ', $product_for_renewal->get_title() );
				$notice_message .= is_user_logged_in() ? ' ' : __( 'You must be logged to renew your membership.', 'woocommerce-memberships' );
				$notice_type = 'success';

			} else {

				$notice_message = __( 'Cannot renew this membership. Please contact us if you need assistance.', 'woocommerce-memberships' );
				$notice_type    = 'error';
			}
		}

		if ( isset( $notice_message, $notice_type ) ) {

			wc_add_notice( $notice_message, $notice_type );

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}


	/**
	 * Add a notice for members that they can get a discount when logged in
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_cart_member_login_notice() {

		$display_in = get_option( 'wc_memberships_display_member_login_notice' );

		if (    ! is_user_logged_in()
		     &&   is_cart()
		     &&   in_array( $display_in, array( 'cart', 'both' ), true )
		     &&   ( $this->cart_has_items_with_member_discounts() && ! is_ajax() ) ) {

			wc_add_notice( sprintf( $this->get_member_login_message(), '<a href="' . esc_url( $this->get_restricted_content_redirect_url() ) . '">', '</a>' ), 'notice' );
		}
	}


	/**
	 * Renders a thank you message on the Order Received page when a membership is purchased.
	 *
	 * @since 1.8.4
	 *
	 * @param int $order_id the order ID
	 */
	public function maybe_render_thank_you_content( $order_id ) {
		echo wp_kses_post( wc_memberships_get_order_thank_you_links( $order_id ) );
	}


	/**
	 * Maybe render checkout member login notice
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param string $template_name template being loaded by WC
	 */
	public function maybe_render_checkout_member_login_notice( $template_name ) {

		// separate notice at checkout
		if ( ! is_user_logged_in() && 'checkout/form-login.php' === $template_name ) {

			$display_in = get_option( 'wc_memberships_display_member_login_notice' );

			if (    in_array( $display_in, array( 'checkout', 'both' ), true )
			     && $this->cart_has_items_with_member_discounts() ) {

				wc_print_notice( sprintf( $this->get_member_login_message(), '', '' ), 'notice' );
			}
		}
	}


	/**
	 * Get member login message
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_member_login_message() {

		// default message defined in settings
		if ( $message = get_option( 'wc_memberships_member_login_message' ) ) {

			$message = str_replace( '{login_url}', esc_url( wc_get_page_permalink( 'myaccount' ) ), $message );

		} elseif ( count( $this->get_cart_items_with_member_discounts() ) > 1 ) {

			/* translators: used when there are multiple membership-discounted items in the cart. Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = __( 'Some items in you cart are discounted for members. %1$sLog in%2$s to claim them.', 'woocommerce-memberships' );

		} elseif ( count( WC()->cart->get_cart() ) > 1 ) {

			/* translators: used when a membership-discounted item is one of many other items in the cart. Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = __( 'An item in your cart is discounted for members. %1$sLog in%2$s to claim it.', 'woocommerce-memberships' );

		} else {

			/* translators: used when a membership-discounted item is the only item in the cart. Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = __( 'This item is discounted for members. %1$sLog in%2$s to claim it.', 'woocommerce-memberships' );
		}

		/**
		 * Filter the member login notice message.
		 *
		 * @since 1.3.8
		 * @param string $message The message text.
		 */
		$message = apply_filters( 'wc_memberships_member_login_message', $message );

		return $message;
	}


	/**
	 * Get items in cart with member discounts
	 *
	 * @since 1.0.0
	 * @return array Array of Product IDs in cart with member discounts
	 */
	private function get_cart_items_with_member_discounts() {

		if ( ! isset( $this->cart_items_with_member_discounts ) ) {

			$this->cart_items_with_member_discounts = array();

			foreach ( WC()->cart->get_cart() as $item_key => $item ) {

				$product_id = isset( $item['variation_id'] ) && $item['variation_id'] ? $item['variation_id'] : $item['product_id'];

				if (      wc_memberships()->get_rules_instance()->product_has_member_discount( $product_id )
				     && ! wc_memberships()->get_member_discounts_instance()->is_product_excluded_from_member_discounts( $product_id ) ) {

					$this->cart_items_with_member_discounts[] = $product_id;
				}
			}
		}

		return $this->cart_items_with_member_discounts;
	}


	/**
	 * Check if cart has any items with member discounts
	 *
	 * @since 1.0.0
	 * @return bool True, if has items with member discounts, false otherwise
	 */
	private function cart_has_items_with_member_discounts() {

		$cart_items = $this->get_cart_items_with_member_discounts();

		return ! empty( $cart_items );
	}


	/**
	 * Get a list of products that grant access to a piece of content
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param string $rule_type
	 * @return array|null
	 */
	private function get_products_that_grant_access( $post_id = null, $rule_type = null ) {

		// Default to the 'current' post
		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		// Get applied rules
		if ( 'purchasing_discount' === $rule_type ) {
			$rules = wc_memberships()->get_rules_instance()->get_product_purchasing_discount_rules( $post_id );
		} else if ( in_array( get_post_type( $post_id ), array( 'product', 'product_variation' ), true ) ) {
			$rules = wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $post_id );
		} else {
			$rules = wc_memberships()->get_rules_instance()->get_post_content_restriction_rules( $post_id );
		}

		// Find products that grant access
		$processed_plans = array(); // holder for membership plans that have been processed already
		$products        = array();

		foreach ( $rules as $rule ) {

			// Skip further checks if this membership plan has already been processed
			if ( in_array( $rule->get_membership_plan_id(), $processed_plans, true ) ) {
				continue;
			}

			// Skip inactive purchasing discount rules
			if ( 'purchasing_discount' === $rule->get_rule_type() && ! $rule->is_active() ) {
				continue;
			}

			$plan = wc_memberships_get_membership_plan( $rule->get_membership_plan_id() );

			if ( $plan && $plan->has_products() ) {

				foreach ( $plan->get_product_ids() as $product_id ) {

					$product = wc_get_product( $product_id );

					if ( $product instanceof WC_Product && $product->is_purchasable() && $product->is_visible() ) {

						// double-check for WC 3.0, as a variation is always visible, but the parent could be hidden
						if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() && $product->is_type( 'variation' ) && ! SV_WC_Product_Compatibility::get_parent( $product )->is_visible() ) {
							continue;
						}

						$products[] = $product_id;
					}
				}
			}

			// Mark this plan as processed, we do not need look into it any further,
			// because we already know if it has any products that grant access or not.
			$processed_plans[] = $rule->get_membership_plan_id();
		}

		/**
		 * Filter the list of products that grant access to a piece of content.
		 *
		 * @since 1.4.0
		 * @param array $products The products that grant access.
		 * @param int $post_id The product ID.
		 * @param null|string $rule_type The desired rule type. Null if none set.
		 */
		$products = apply_filters( 'wc_memberships_products_that_grant_access', $products, $post_id, $rule_type );

		return ! empty( $products ) ? array_map( 'intval', (array) $products ) : null;
	}


	/**
	 * Get valid restriction message types
	 *
	 * @since 1.7.0
	 * @return array
	 */
	public function get_valid_restriction_message_types() {

		/**
		 * Filter valid restriction message types
		 *
		 * @since 1.0.0
		 * @param array
		 */
		return (array) apply_filters( 'wc_memberships_valid_restriction_message_types', array(
			'content_restricted',
			'product_viewing_restricted',
			'product_purchasing_restricted'
		) );
	}


	/**
	 * Get and parse a restriction message
	 *
	 * General wrapper around different types of restriction messages
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Restriction type
	 * @param int $post_id Post ID that is being restricted
	 * @param array $products List of product IDs that grant access. Optional
	 * @return bool|string Restriction message
	 */
	private function get_restriction_message( $type, $post_id, $products = null ) {

		if ( ! $type ) {
			return false;
		}

		// don't show discount notices for products that are excluded from discounts
		if ( 'product_discount' === $type && wc_memberships()->get_member_discounts_instance()->is_product_excluded_from_member_discounts( $post_id ) ) {
			return false;
		}

		$products = $this->format_products( $products );

		if ( ! empty( $products ) ) {

			// Check that the message type is valid for custom messages.
			// For example, purchasing_discount messages cannot be customized per-product
			// so we must leave them out
			if (    'yes' === wc_memberships_get_content_meta( $post_id, "_wc_memberships_use_custom_{$type}_message", true )
			     && in_array( $type, $this->get_valid_restriction_message_types(), true ) ) {

				$message = wc_memberships_get_content_meta( $post_id, "_wc_memberships_{$type}_message", true );

			} else {

				$message = get_option( "wc_memberships_{$type}_message" );
			}

			$message = str_replace( '{products}', '<span class="wc-memberships-products-grant-access">' . wc_memberships_list_items( $products ) . '</span>', $message );

		} else {

			if ( 'yes' === wc_memberships_get_content_meta( $post_id, "_wc_memberships_use_custom_{$type}_message", true ) ) {
				$message = wc_memberships_get_content_meta( $post_id, "_wc_memberships_{$type}_message", true );
			} else {
				$message = get_option( "wc_memberships_{$type}_message_no_products" );
			}
		}

		if ( SV_WC_Helper::str_exists( $message, '{login_url}' ) ) {

			$message = str_replace( '{login_url}', $this->get_restricted_content_redirect_url( $post_id ), $message );
		}

		return do_shortcode( $message );
	}


	/**
	 * Get a formatted login url with restricted content redirect URL
	 *
	 * If content is neither a singular content or a taxonomy term will default to user account page
	 *
	 * @since 1.4.0
	 * @param int|null $redirect_id Optional: the id of the post to redirect to,
	 *                              defaults to current queried object
	 * @return string Escaped url
	 */
	private function get_restricted_content_redirect_url( $redirect_id = null ) {

		$redirect_to = 'post';
		$login_url   = wc_get_page_permalink( 'myaccount' );

		if ( null === $redirect_id ) {

			$redirect_id = get_queried_object_id();
			$redirect_to = '';

			if ( is_singular() ) {
				$redirect_to = 'post';
			} elseif ( isset( get_queried_object()->term_id ) ) {
				$redirect_to = get_queried_object()->taxonomy;
			}
		}

		if ( ! empty( $redirect_to ) ) {

			$login_url = add_query_arg( array(
				'wcm_redirect_to' => $redirect_to,
				'wcm_redirect_id' => $redirect_id,
			), $login_url );
		}

		return esc_url( $login_url );
	}


	/**
	 * Redirect user to restricted content after successful login
	 *
	 * @since 1.4.0
	 * @param string $redirect_to URL to redirect to
	 * @return string
	 */
	public function restricted_content_redirect( $redirect_to ) {

		$content = null;

		if ( isset( $_GET['wcm_redirect_to'], $_GET['wcm_redirect_id'] ) ) {

			if ( in_array( $_GET['wcm_redirect_to'], array( 'post', 'page' ), true ) ) {
				$content = get_post( (int) $_GET['wcm_redirect_id'] );
			} elseif ( taxonomy_exists( $_GET['wcm_redirect_to'] ) ) {
				$content = get_term_link( (int) $_GET['wcm_redirect_id'], $_GET['wcm_redirect_to'] );
			}
		}

		if ( ! empty( $content ) ) {

			$permalink = get_permalink( $content );

			return $permalink ? $permalink : $redirect_to;
		}

		return $redirect_to;
	}


	/**
	 * Takes an array of product ids and returns an array of formatted titles with links
	 *
	 * @since 1.4.0
	 * @param array $products Array of product IDs
	 * @return array Formatted array of product titles
	 */
	private function format_products( $products ){

		if( ! is_array( $products ) ){
			return array();
		}

		return array_filter( array_map( array( $this, 'format_product' ), array_unique( $products ) ) );
	}


	/**
	 * Takes a product id and returns formatted title with link
	 *
	 * @since 1.4.0
	 * @param int $product_id Product ID
	 * @return string Formatted product title
	 */
	private function format_product( $product_id ){

		$product = wc_get_product( $product_id );

		if( ! $product ) {
			return '';
		}

		$link    = $product->get_permalink();
		$title   = $product->get_title();

		// Special handling for variations
		if ( $product->is_type( array( 'variation', 'subscription_variation' ) ) ) {

			$attributes = $product->get_variation_attributes();

			foreach ( $attributes as $attr_key => $attribute ) {
				$attributes[ $attr_key ] = ucfirst( $attribute );
			}

			$title .= ' &ndash; ' . implode( ', ', $attributes );
		}

		return sprintf( '<a href="%s">%s</a>', esc_url( $link ), wp_kses_post( $title ) );
	}


	/**
	 * Get the product viewing restricted message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post.
	 * @return string
	 */
	public function get_product_viewing_restricted_message( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id );
		$message  = $this->get_restriction_message( 'product_viewing_restricted', $post_id, $products );

		/**
		 * Filter the product viewing restricted message
		 *
		 * @since 1.0.0
		 * @param string $message The restriction message
		 * @param int $product_id ID of the product being restricted
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_product_viewing_restricted_message', $message, $post_id, $products );
	}


	/**
	 * Get the product purchasing restricted message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post.
	 * @return string
	 */
	public function get_product_purchasing_restricted_message( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id );
		$message  = $this->get_restriction_message( 'product_purchasing_restricted', $post_id, $products );

		/**
		 * Filter the product purchasing restricted message
		 *
		 * @since 1.0.0
		 * @param string $message The restriction message
		 * @param int $product_id ID of the product being restricted
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_product_purchasing_restricted_message', $message, $post_id, $products );
	}


	/**
	 * Get the content restricted message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post.
	 * @return string
	 */
	public function get_content_restricted_message( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id );
		$message  = $this->get_restriction_message( 'content_restricted', $post_id, $products );

		/**
		 * Filter the product purchasing restricted message
		 *
		 * @since 1.0.0
		 * @param string $message The restriction message
		 * @param int $product_id ID of the product being restricted
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_content_restricted_message', $message, $post_id, $products );
	}


	/**
	 * Get the delayed content message
	 *
	 * @since 1.0.0
	 * @param int $user_id Optional. Defaults to current user ID.
	 * @param int $post_id Optional. Defaults to current post ID.
	 * @param string $access_type Optional. Defaults to "view". Applies to products only.
	 * @return string
	 */
	public function get_content_delayed_message( $user_id = null, $post_id = null, $access_type = 'view' ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}


		$access_time = wc_memberships()->get_capabilities_instance()->get_user_access_start_time_for_post( $user_id, $post_id, $access_type );

		switch ( get_post_type( $post_id ) ) {

			case 'product':
			case 'product_variation':

				if ( 'view' === $access_type ) {
					/* translators: {date} is a merge tag to display the Membership access date */
					$message = __( 'This product is part of your membership, but not yet! You will gain access on {date}', 'woocommerce-memberships' );
				} else {
					/* translators: {date} is a merge tag to display the Membership access date */
					$message = __( 'This product is part of your membership, but not yet! You can purchase it on {date}', 'woocommerce-memberships' );
				}

			break;

			case 'page':
				/* translators: {date} is a merge tag to display the Membership access date */
				$message = __( 'This page is part of your membership, but not yet! You will gain access on {date}', 'woocommerce-memberships' );
			break;

			case 'post':
				/* translators: {date} is a merge tag to display the Membership access date */
				$message = __( 'This post is part of your membership, but not yet! You will gain access on {date}', 'woocommerce-memberships' );
			break;

			default:
				/* translators: {date} is a merge tag to display the Membership access date */
				$message = __( 'This content is part of your membership, but not yet! You will gain access on {date}', 'woocommerce-memberships' );
			break;

		}

		/**
		 * Filter the delayed content message
		 *
		 * @since 1.3.1
		 * @param string $message Delayed content message
		 * @param int $post_id Post ID that the message applies to
		 * @param string $access_time Access time timestamp
		 */
		$message = apply_filters( 'wc_memberships_get_content_delayed_message', $message, $post_id, $access_time );

		return str_replace( '{date}', date_i18n( wc_date_format(), $access_time ), $message );
	}


	/**
	 * Get restricted message for display on product category page
	 *
	 * @since 1.4.0
	 * @param string $taxonomy
	 * @param int $term_id
	 * @return string
	 */
	public function get_product_taxonomy_term_viewing_restricted_message( $taxonomy, $term_id ) {

		$post_id        = $this->get_post_id_from_taxonomy_term( $taxonomy, $term_id );
		$products_array = $this->get_products_that_grant_access( $post_id );
		$products       = $this->format_products( $products_array );

		if ( ! empty( $products ) ) {
			$text = sprintf( /* translators: %1$s - {products} merge tag, %2$s - link to 'My Account' page opening <a> tag, %3$s - closing </a> link tag */
				__( 'This product category can only be viewed by members. To view this category, sign up by purchasing %1$s, or %2$slog in%3$s if you are a member.', 'woocommerce-memberships' ),
				'{products}',
				'<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) .'">', '</a>'
			);
		} else {
			$text = __( 'This product category can only be viewed by members.', 'woocommerce-memberships' );
		}

		/**
		 * Filter the product term viewing restricted message
		 *
		 * @since 1.4.0
		 * @param string $message The restriction message
		 * @param string $taxonomy Product taxonomy
		 * @param int $term_id Product taxonomy term id
		 * @param array $products Array of product IDs that grant access to products taxonomy
		 */
		$message = apply_filters( 'wc_memberships_product_taxonomy_viewing_restricted_message', $text, $taxonomy, $term_id, $products );

		return str_replace( '{products}', '<span class="wc-memberships-products-grant-access">' . wc_memberships_list_items( $products ) . '</span>', $message );
	}


	/**
	 * Get restricted message for display on product category page
	 *
	 * @since 1.4.0
	 * @param int $user_id
	 * @param string $taxonomy
	 * @param int $term_id
	 * @return string
	 */
	public function get_product_taxonomy_term_delayed_message( $user_id, $taxonomy, $term_id ) {

		$post_id     = $this->get_post_id_from_taxonomy_term( $taxonomy, $term_id );
		$text        = __( 'This product category is part of your membership, but not yet! You will gain access on {date}', 'woocommerce-memberships' );
		$access_time = wc_memberships()->get_capabilities_instance()->get_user_access_start_time_for_post( $user_id, $post_id );

		/**
		 * Filter the product term viewing delayed access message
		 *
		 * @since 1.4.0
		 * @param string $message The delayed access message
		 * @param string $taxonomy Product taxonomy
		 * @param int $term_id Product taxonomy term id
		 */
		$message = apply_filters( 'wc_memberships_product_term_viewing_delayed_message', $text, $taxonomy, $term_id );

		return str_replace( '{date}', date_i18n( get_option( 'date_format' ), $access_time ), $message );
	}


	/**
	 * Get post id from a taxonomy term
	 *
	 * This is a helper method to get a post under a taxonomy term
	 * We use the resulting id to get access time or products that grant access in restriction messages.
	 * @see get_product_taxonomy_term_viewing_restricted_message
	 * @see get_product_taxonomy_term_delayed_message
	 *
	 * TODO: this method should be removed and replaced by a different approach to retrieve a post id
	 *
	 * @since 1.4.0
	 * @param string $taxonomy
	 * @param int $term_id
	 * @return int First matching post id for the given taxonomy term
	 */
	private function get_post_id_from_taxonomy_term( $taxonomy, $term_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare("
				SELECT $wpdb->posts.ID FROM $wpdb->posts
				INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
				INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
				WHERE $wpdb->term_taxonomy.taxonomy = %s
				AND $wpdb->term_taxonomy.term_id = %d
				AND $wpdb->posts.post_status = 'publish'
				LIMIT 1
				",
				$taxonomy,
				$term_id
			)
		);
	}


	/**
	 * Get the member discount message
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Defaults to current post ID.
	 * @return string
	 */
	public function get_member_discount_message( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = $post->ID;
		}

		$products = $this->get_products_that_grant_access( $post_id, 'purchasing_discount' );
		$message  = $this->get_restriction_message( 'product_discount', $post_id, $products );

		/**
		 * Filter the product member discount message
		 *
		 * @since 1.0.0
		 * @param string $message The discount message
		 * @param int $product_id ID of the product that has member discounts
		 * @param array $products Array of product IDs that grant access to this product
		 */
		return apply_filters( 'wc_memberships_member_discount_message', $message, $post_id, $products );
	}


	/**
	 * Backwards compatibility handler for deprecated methods
	 *
	 * TODO by version 2.0.0 these backward compatibility calls could be removed {FN 2016-04-26}
	 *
	 * @since 1.6.0
	 * @param string $method Method called
	 * @param void|string|array|mixed $args Optional argument(s)
	 * @return null|void|mixed
	 */
	public function __call( $method, $args ) {

		$class = 'wc_memberships()->get_frontend_instance()';

		$deprecated_since_1_6_0 = '1.6.0';
		$deprecated_since_1_7_4 = '1.7.4';

		switch( $method ) {

			/** @deprecated since 1.6.0 */
			case 'filter_breadcrumbs' :
				_deprecated_function( "{$class}->{$method}()", $deprecated_since_1_6_0, "{$class}->get_members_area_instance()->{$method}()" );
				return $this->get_members_area_instance()->filter_breadcrumbs( $args );

			/** @deprecated since 1.6.0 */
			case 'my_account_memberships' :
				_deprecated_function( "{$class}->{$method}()", $deprecated_since_1_6_0, "{$class}->get_members_area_instance()->{$method}()" );
				$this->get_members_area_instance()->my_account_memberships();
				return null;

			/** @deprecated since 1.6.0 */
			case 'render_members_area_content' :
				_deprecated_function( "{$class}->{$method}()", $deprecated_since_1_6_0, "{$class}->get_members_area_instance()->{$method}()" );
				return $this->get_members_area_instance()->render_member_area_content( $args );

			/** @deprecated since 1.7.4 */
			case 'get_member_area_instance' :
				_deprecated_function( "{$class}->{$method}()", $deprecated_since_1_7_4, "{$class}->get_members_area_instance()" );
				return $this->get_members_area_instance();

			default :
				// you're probably doing it wrong
				trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $method, E_USER_ERROR );
				return null;

		}
	}


}
