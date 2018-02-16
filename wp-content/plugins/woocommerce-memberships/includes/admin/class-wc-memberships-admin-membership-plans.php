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
 * @package   WC-Memberships/Admin
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Admin Membership Plans class
 *
 * This class handles all the admin-related functionality
 * for membership plans, like the list screen, meta boxes, etc.
 *
 * Note: it's not necessary to check for the post type, or `$typenow`
 * in this class, as this is already handled in WC_Memberships_Admin->init()
 *
 * @since 1.0.0
 */
class WC_Memberships_Admin_Membership_Plans {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// plans admin screen columns
		add_filter( 'manage_edit-wc_membership_plan_columns',        array( $this, 'customize_columns' ) );
		add_action( 'manage_wc_membership_plan_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );

		// disable some bulk features not applicable
		add_filter( 'bulk_actions-edit-wc_membership_plan', '__return_empty_array' );
		add_filter( 'months_dropdown_results',              '__return_empty_array' );

		// filter row actions
		add_filter( 'post_row_actions', array( $this, 'customize_row_actions' ), 10, 2 );

		// custom admin plan actions
		add_action( 'admin_action_duplicate_plan', array( $this, 'duplicate_membership_plan' ) );
		add_action( 'admin_action_grant_access',   array( $this, 'grant_access_to_membership' ) );

		// add/edit plan screen hooks
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
		add_action( 'post_submitbox_start',        array( $this, 'duplicate_button' ) );
		add_action( 'add_meta_boxes',              array( $this, 'customize_meta_boxes' ) );
	}


	/**
	 * Customize membership plan columns
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $columns
	 * @return array
	 */
	public function customize_columns( $columns ) {

		unset( $columns['date'], $columns['cb'] );

		$columns['slug']    = __( 'Slug', 'woocommerce-memberships' );

		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$columns['rules'] = __( 'Rules', 'woocommerce-memberships' );
		}

		$columns['length']  = __( 'Access length', 'woocommerce-memberships' );
		$columns['access']  = __( 'Access from', 'woocommerce-memberships' );
		$columns['members'] = __( 'Members', 'woocommerce-memberships' );

		return $columns;
	}


	/**
	 * Output custom column content
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param string $column
	 * @param int $post_id
	 */
	public function custom_column_content( $column, $post_id ) {
		global $post;

		$membership_plan = wc_memberships_get_membership_plan( $post );

		if ( $membership_plan ) {

			switch ( $column ) {

				case 'slug':
					echo $membership_plan->get_slug();
				break;

				case 'length':

					$has_products = $membership_plan->get_products( true );

					if ( 'purchase' === $membership_plan->get_access_method() && 0 === count( $has_products ) ) {
						echo '';
					} else {
						echo $membership_plan->get_human_access_length();
					}

				break;

				case 'rules':

					if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {

						$content_restriction_rules = $membership_plan->get_content_restriction_rules();
						$product_restriction_rules = $membership_plan->get_product_restriction_rules();
						$purchasing_discount_rules = $membership_plan->get_purchasing_discount_rules();

						$rules = array(
							__( 'Restrict content', 'woocommerce-memberships' )     => ! empty( $content_restriction_rules ),
							__( 'Restrict products', 'woocommerce-memberships' )    => ! empty( $product_restriction_rules ),
							__( 'Purchasing discounts', 'woocommerce-memberships' ) => ! empty( $purchasing_discount_rules ),
						);

						foreach ( $rules as $label => $active ) {

							$label = esc_html( $label );
							$class = $active ? 'has-rules' : 'has-not-rules';

							printf( "<span class='{$class}'>%s {$label}</span><br>", ! $active ? '&#x2717;' : '&#x2713;' );
						}
					}

				break;

				case 'access':

					$access_method = $membership_plan->get_access_method();

					if ( 'manual-only' === $access_method ) {
						esc_html_e( 'Assigned manually', 'woocommerce-memberships' );
					} elseif ( 'signup' === $access_method ) {
						esc_html_e( 'Account registration', 'woocommerce-memberships' );
					} elseif ( 'purchase' === $access_method ) {
						esc_html_e( 'Purchase', 'woocommerce-memberships' );
						$this->list_products_granting_access( $membership_plan );
					}

				break;

				case 'members':

					// TODO add an ajax/javascript control to break down counters and links to members by status {FN 2016-06-06}

					$view_members = admin_url( "edit.php?post_type=wc_user_membership?s&post_type=wc_user_membership&action=-1&post_parent={$post_id}" );

					echo '<a href="' . esc_url( $view_members ) . '" title="' . esc_html__( 'View Members', 'woocommerce-memberships' ) . '">';
					echo $membership_plan->get_memberships_count();
					echo '</a>';

				break;

			}
		}
	}


	/**
	 * List products that grant access to a Membership Plan
	 *
	 * @since 1.7.0
	 * @param WC_Memberships_Membership_Plan $membership_plan The membership plan
	 */
	private function list_products_granting_access( $membership_plan ) {

		$product_ids = $membership_plan->get_product_ids();

		if ( ! empty( $product_ids ) ) {

			echo '<ul class="access-from-list">';

			foreach ( $product_ids as $product_id ) {

				if ( $product = wc_get_product( $product_id ) )  {

					printf(
						'<li>%1$s%2$s</li>',
						$this->get_edit_product_link( $product ),
						$product->is_type( array( 'subscription', 'variable-subscription' ) ) ? ' <small>(' . strtolower( __( 'Subscription', 'woocommerce-memberships' ) ) . ')</small> ' : ''
					);
				}
			}

			echo '</ul>';
		}
	}


	/**
	 * Output a link to edit a product in admin
	 *
	 * @since 1.7.0
	 * @param \WC_Product|\WC_Product_Variation $product A product or variation
	 * @return string
	 */
	private function get_edit_product_link( $product ) {

		if ( $product->is_type( 'variation' ) ) {
			$product = $product instanceof WC_Product ? SV_WC_Product_Compatibility::get_parent( $product ) : null;
		}

		$product_id   = $product instanceof WC_Product ? $product->get_id() : null;
		$product_link = $product_id ? get_edit_post_link( $product_id ) : null;

		return $product_link ? sprintf( '<a href="%1$s">%2$s</a>', $product_link, $product->get_formatted_name() ) : '';
	}


	/**
	 * Customize membership plan row actions
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $actions
	 * @param \WP_Post $post
	 * @return array
	 */
	public function customize_row_actions( $actions, WP_Post $post ) {

		// remove quick edit action
		unset( $actions['inline hide-if-no-js'] );

		$plan = wc_memberships_get_membership_plan( $post );

		if ( $plan && isset( $actions['trash'] ) && $plan->has_active_memberships() ) {

			$tip = '';

			if ( 'trash' === $post->post_status ) {
				$tip = esc_attr__( 'This item cannot be restored because it has active members.', 'woocommerce-memberships' );
			} elseif ( EMPTY_TRASH_DAYS ) {
				$tip = esc_attr__( 'This item cannot be moved to trash because it has active members.', 'woocommerce-memberships' );
			}

			if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
				$tip = esc_attr__( 'This item cannot be permanently deleted because it has active members.', 'woocommerce-memberships' );
			}

			$actions['trash'] = '<span title="' . $tip . '" style="cursor: help;">' . strip_tags( $actions['trash'] ) . '</span>';

			// TODO: perhaps add an action to view members of the plan (redirects to user membership screen query) {FN 2016-07-20}
		}

		$duplicate_link_open  = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=wc_membership_plan&action=duplicate_plan&amp;post=' . $post->ID ), 'wc-memberships-duplicate-plan_' . $post->ID ) . '" title="' . __( 'Make a duplicate from this membership plan', 'woocommerce-memberships' ) . '" rel="permalink">';
		$duplicate_link_close = '</a>';

		// add duplicate plan action
		$actions['duplicate'] = $duplicate_link_open . _x( 'Duplicate', 'Duplicate a Membership Plan', 'woocommerce-memberships' ) . $duplicate_link_close;

		return $actions;
	}


	/**
	 * Membership plan submit box actions
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function post_submitbox_misc_actions() {
		global $post, $pagenow;

		// output on published plans only
		if ( 'post.php' === $pagenow ) :

			$plan = wc_memberships_get_membership_plan( $post );

			// grant access to existing purchase orders button
			?>
			<div class="misc-pub-section misc-pub-grant-access" <?php echo $plan->is_access_method( 'purchase' ) ? '' : 'style="display: none;"'; ?>>
				<span class="grant-access">
					<?php esc_html_e( 'Existing purchases:', 'woocommerce-memberships' ); ?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'grant_access', get_edit_post_link( $post->ID ) ), 'wc-memberships-grant-access-plan_' . $post->ID ) ); ?>" class="button" id="grant-access"><?php esc_html_e( 'Grant Access', 'woocommerce-memberships' ); ?></a>
				</span>
			</div>
			<?php

			// sends a browser alert when pushing the grant access button above
			wc_enqueue_js( "
				jQuery( '#grant-access' ).click( function( e ) {
					return confirm( '" . esc_html__( 'This action creates a membership for users who have previously purchased one of the products that grants access to the plan. If the user already has access to this plan, the original membership status and dates are preserved.\r\n\r\nSubscriptions: Only active subscribers will gain a membership.', 'woocommerce-memberships' ) . "' );
				} );
			" );

		endif;

		// hides the post visibility option in the publish panel metabox ?>
		<style type="text/css">
			#visibility { display: none !important; }
		</style>
		<?php
	}


	/**
	 * Add meta boxes to the membership plan edit page
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function customize_meta_boxes() {

		// remove the slug div
		remove_meta_box( 'slugdiv', 'wc_membership_plan', 'normal' );
	}


	/**
	 * Whether a user should be granted access to a free membership
	 * from a previous account sign up
	 *
	 * @see \WC_Memberships_Admin_Membership_Plans::grant_access_to_membership()
	 *
	 * @since 1.7.0
	 * @param int $user_id User id to grant access to
	 * @param int $plan_id Membership Plan id the user would access to
	 * @return bool Default true, filter in method may set to false
	 */
	private function grant_free_access_to_existing_user( $user_id, $plan_id ) {

		/**
		 * Filter whether existing users can be retroactively granted access
		 * to free membership plans created after a user registration occurred
		 *
		 * @since 1.7.0
		 * @param array $args
		 */
		$grant_access = apply_filters( 'wc_memberships_grant_access_to_existing_user', true, array(
			'user_id'    => $user_id,
			'plan_id'    => $plan_id,
		) );

		return (bool) $grant_access;
	}


	/**
	 * Grant access to a free membership plan
	 * to users which have not been already part of
	 *
	 * @see \WC_Memberships_Admin_Membership_Plans::grant_access_to_membership()
	 * TODO make sure this private method is used when we have background processing {FN 2016-08-09}
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $plan Membership Plan to grant users access to
	 * @return int The user memberships created or 0 if none or fail
	 */
	private function grant_access_to_free_membership_plan( $plan ) {

		$grant_count = 0;

		$users = get_users( array(
			'fields' => 'ID',
		) );

		if ( ! empty( $users ) ) {

			foreach ( $users as $user_id ) {

				if ( $this->grant_free_access_to_existing_user( $user_id, $plan->get_id() ) ) {

					$user_membership = wc_memberships()->get_plans_instance()->grant_access_to_free_membership( $user_id, false, $plan );

					if ( $user_membership instanceof WC_Memberships_User_Membership ) {
						$grant_count++;
					}
				}
			}
		}

		return $grant_count;
	}


	/**
	 * Whether a user should be granted access from an existing purchase
	 *
	 * @see \WC_Memberships_Admin_Membership_Plans::grant_access_to_membership()
	 *
	 * @since 1.7.0
	 * @param int $user_id User id to grant access to
	 * @param int $product_id Id of product that would be granting access
	 * @param int $order_id Id of order that contains the product
	 * @param int $plan_id Membership Plan id the user would access to
	 * @return bool Default true, filter in method may set to false
	 */
	private function grant_access_from_existing_purchase( $user_id, $product_id, $order_id, $plan_id ) {

		if ( wc_memberships_cumulative_granting_access_orders_allowed() ) {

			// if membership extensions by cumulative purchases are enabled
			// grant access if the order didn't grant access before
			$user_membership = wc_memberships_get_user_membership( $user_id, $plan_id );
			$grant_access    = ! ( $user_membership && wc_memberships_has_order_granted_access( $order_id, array( 'user_membership' => $user_membership ) ) );

		} else {

			// if instead cumulative granting access orders are disallowed,
			// grant access if user is not already a member
			$grant_access = ! wc_memberships_is_user_member( $user_id, $plan_id, false );
		}

		/**
		 * Filter whether an existing purchase of the product should grant access
		 * to the membership plan or not
		 *
		 * Allows third party code to override if a previously purchased product
		 * should retroactively grant access to a membership plan or not
		 *
		 * @since 1.0.0
		 * @param bool $grant_access Default true, grant access from existing purchase
		 * @param array $args Array of arguments connected with the access request
		 */
		$grant_access = apply_filters( 'wc_memberships_grant_access_from_existing_purchase', $grant_access, array(
			'user_id'    => $user_id,
			'product_id' => $product_id,
			'order_id'   => $order_id,
			'plan_id'    => $plan_id,
		) );

		return (bool) $grant_access;
	}


	/**
	 * Grant access to a non-free membership plan
	 * to users which have previously purchased a product that grants access
	 *
	 * TODO this method uses a direct DB query to fetch orders, it should be converted to use WC Data stores introduced in WC 3.0 {FN 2017-02-24}
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $plan Membership Plan to grant users access to
	 * @return int The user memberships created or 0 if none or fail
	 */
	private function grant_access_to_existing_purchases( $plan ) {

		$grant_count = 0;
		$product_ids = $plan->get_product_ids();

		if ( ! empty( $product_ids ) && $plan instanceof WC_Memberships_Membership_Plan ) {
			global $wpdb;

			$valid_order_statuses_for_grant = $this->get_valid_order_statuses_for_granting_access( $plan );

			foreach ( $product_ids as $product_id ) {

				$product   = wc_get_product( $product_id );
				$meta_key  = is_object( $product ) && $product->is_type( 'variation' ) ? '_variation_id' : '_product_id';
				$order_ids = $wpdb->get_col( $wpdb->prepare( "
						SELECT order_id 
						FROM {$wpdb->prefix}woocommerce_order_items 
						WHERE order_item_id 
						IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = %s AND meta_value = %d ) 
						AND order_item_type = 'line_item'
						", $meta_key, $product_id
				) );

				if ( empty( $order_ids ) ) {

					continue;
				}

				foreach ( $order_ids as $order_id ) {

					$order = wc_get_order( $order_id );

					// skip if purchase doesn't have a valid status
					if (    ! $order instanceof WC_Order
					     || ! $order->has_status( $valid_order_statuses_for_grant ) ) {

						continue;
					}

					$user_id = $order->get_user_id();

					// skip if no user id or existing purchase can't grant access or extension
					if (    ! $user_id > 0
					     || ! $this->grant_access_from_existing_purchase( $user_id, $product_id, $order_id, $plan->get_id() ) ) {

						continue;
					}

					// grant access and bump counter
					if ( $plan->grant_access_from_purchase( $user_id, $product_id, $order_id ) ) {

						$grant_count++;
					}
				}
			}
		}

		return $grant_count;
	}


	/**
	 * Get valid order statuses that allow granting access retroactively
	 * to a membership plan of product purchase access type
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $plan A membership plan object
	 * @return array
	 */
	private function get_valid_order_statuses_for_granting_access( $plan ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
			$paid_statuses = wc_get_is_paid_statuses();
		} else {
			$paid_statuses = apply_filters( 'woocommerce_order_is_paid_statuses', array( 'completed', 'processing' ) );
		}

		/**
		 * Filter the array of valid order statuses that grant access
		 *
		 * Allows to include additional custom order statuses
		 * that should grant access when the admin uses
		 * the "grant previous purchases access" action
		 *
		 * @since 1.0.0
		 * @param array $valid_order_statuses_for_grant array of order statuses
		 * @param \WC_Memberships_Membership_Plan $plan the associated membership plan object
		 */
		return (array) apply_filters( 'wc_memberships_grant_access_from_existing_purchase_order_statuses', $paid_statuses, $plan );
	}


	/**
	 * Grant access to a membership plan
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function grant_access_to_membership() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the plan id
		$plan_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'wc-memberships-grant-access-plan_' . $plan_id );

		// get the plan and set up variables
		$plan        = wc_memberships_get_membership_plan( $plan_id );
		$redirect_to = get_edit_post_link( $plan_id, 'redirect' );
		$grant_count = 0;

		// grant access to users
		if (    $plan instanceof WC_Memberships_Membership_Plan
		     && ( $access_method = $plan->get_access_method() ) ) {

			if ( 'signup' === $access_method ) {
				// grant access to free membership to previously registered users
				// TODO restore this when background processing is ready so we don't risk customer timeouts {FN 2016-08-04}
				// $grant_count += $this->grant_access_to_free_membership_plan( $plan );
			} elseif ( 'purchase' === $access_method ) {
				// grant access to non-free memberships to users that previously purchased
				// a product that grants access to the membership plan
				$grant_count += $this->grant_access_to_existing_purchases( $plan );
			}
		}

		// add admin notice with results
		if ( $grant_count > 0 ) {
			$message = sprintf( _n( '%d order found that granted or extended access from existing purchases.', '%d orders found that granted or extended access from existing purchases.', $grant_count, 'woocommerce-memberships' ), $grant_count );
			wc_memberships()->get_admin_instance()->get_message_handler()->add_message( $message );
		} else {
			$message = __( 'No orders found to grant or extend access from existing purchases.', 'woocommerce-memberships' );
			wc_memberships()->get_admin_instance()->get_message_handler()->add_error( $message );
		}

		// redirect back to the edit screen
		wp_safe_redirect( $redirect_to );
		exit;
	}


	/**
	 * Get a membership plan from the database to duplicate
	 *
	 * @since 1.0.0
	 * @param mixed $id
	 * @return \WP_Post|bool
	 */
	private function get_plan_to_duplicate( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$post = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $id ) );

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {

			$id   = $post->post_parent;
			$post = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $id ) );
		}

		return $post[0];
	}


	/**
	 * Show the duplicate plan link in admin edit screen
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function duplicate_button() {
		global $post;

		if ( ! is_object( $post ) ) {
			return;
		}

		if ( isset( $_GET['post'] ) ) {

			$url = wp_nonce_url( admin_url( 'edit.php?post_type=wc_membership_plan&action=duplicate_plan&post=' . $post->ID ), 'wc-memberships-duplicate-plan_' . $post->ID );

			?>
			<div id="duplicate-action">
				<a class="submitduplicate duplication" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Make a copy', 'woocommerce-memberships' ); ?></a>
			</div>
			<?php
		}
	}


	/**
	 * Duplicate a membership plan
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function duplicate_membership_plan() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the original post
		$id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'wc-memberships-duplicate-plan_' . $id );

		$post = $this->get_plan_to_duplicate( $id );

		// copy the plan and insert it
		if ( is_object( $post ) ) {

			$new_id = $this->duplicate_plan( $post );

			if ( $new_id > 0 ) {

				/**
				 * Fires after a membership plan has been duplicated
				 *
				 * If you have written a plugin which uses non-WP database tables to save
				 * information about a page you can hook this action to duplicate that data.
				 *
				 * @since 1.0.0
				 * @param int $new_id New plan ID
				 * @param \WP_Post $post Original plan object
				 */
				do_action( 'wc_memberships_duplicate_membership_plan', $new_id, $post );

				wc_memberships()->get_admin_instance()->get_message_handler()->add_message( __( 'Membership plan copied.', 'woocommerce-memberships' ) );

				// redirect to the edit screen for the new draft page
				wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
				exit;
			}
		}

		/* translators: Placeholder: %d - membership plan ID */
		wp_die( sprintf( __( 'Membership plan creation failed, could not find original plan to copy: %d', 'woocommerce-memberships' ), (int) $id ) );
	}


	/**
	 * Create a duplicate membership plan.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param mixed $post
	 * @param int $parent (default: 0)
	 * @param string $post_status (default: 'publish')
	 * @return int
	 */
	public function duplicate_plan( $post, $parent = 0, $post_status = 'publish' ) {

		$new_post_id = 0;

		if ( is_object( $post ) ) {

			$new_post_author   = wp_get_current_user();
			$new_post_date     = current_time( 'mysql' );
			$new_post_date_gmt = get_gmt_from_date( $new_post_date );

			if ( $parent > 0 ) {
				$post_parent = $parent;
				$suffix      = '';
			} else {
				$post_parent = $post->post_parent;
				$suffix      = ' ' . __( '(Copy)', 'woocommerce-memberships' );
			}

			// insert the new template in the post table
			$new_post_id = wp_insert_post(
				array(
					'post_author'               => $new_post_author->ID,
					'post_date'                 => $new_post_date,
					'post_date_gmt'             => $new_post_date_gmt,
					'post_content'              => $post->post_content,
					'post_content_filtered'     => $post->post_content_filtered,
					'post_title'                => $post->post_title . $suffix,
					'post_excerpt'              => $post->post_excerpt,
					'post_status'               => $post_status,
					'post_type'                 => $post->post_type,
					'comment_status'            => $post->comment_status,
					'ping_status'               => $post->ping_status,
					'post_password'             => $post->post_password,
					'to_ping'                   => $post->to_ping,
					'pinged'                    => $post->pinged,
					'post_modified'             => $new_post_date,
					'post_modified_gmt'         => $new_post_date_gmt,
					'post_parent'               => $post_parent,
					'menu_order'                => $post->menu_order,
					'post_mime_type'            => $post->post_mime_type
				),
				false
			);

			if ( $new_post_id > 0 ) {

				// copy the meta information
				$this->duplicate_post_meta( $post->ID, $new_post_id );
				// copy rules
				$this->duplicate_plan_rules( $post->ID, $new_post_id );
			}
		}

		return (int) $new_post_id;
	}


	/**
	 * Copy the meta information of a plan to another plan
	 *
	 * @since 1.0.0
	 * @param mixed $id
	 * @param mixed $new_id
	 */
	private function duplicate_post_meta( $id, $new_id ) {
		global $wpdb;

		$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_key, meta_value 
			FROM $wpdb->postmeta 
			WHERE post_id=%d
		", absint( $id ) ) );

		if ( count( $post_meta_infos ) > 0 ) {

			$sql_query_sel = array();
			$sql_query     = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

			foreach ( $post_meta_infos as $meta_info ) {

				$meta_key        = $meta_info->meta_key;
				$meta_value      = $meta_info->meta_value;
				$sql_query_sel[] = $wpdb->prepare( "SELECT %d, '$meta_key', '$meta_value'", $new_id );
			}

			$sql_query .= implode( " UNION ALL ", $sql_query_sel );

			$wpdb->query( $sql_query );
		}
	}


	/**
	 * Copy the plan rules from one plan to another
	 *
	 * @since 1.0.0
	 * @param mixed $id
	 * @param mixed $new_id
	 */
	private function duplicate_plan_rules( $id, $new_id ) {

		$rules     = get_option( 'wc_memberships_rules' );
		$new_rules = array();

		foreach ( $rules as $key => $rule ) {

			// copy rules to new plan
			if ( (int) $id === (int) $rule['membership_plan_id'] ) {

				$new_rule = $rule;
				$new_rule['id'] = uniqid( 'rule_' );
				$new_rule['membership_plan_id'] = (int) $new_id;

				$new_rules[] = $new_rule;
			}
		}

		update_option( 'wc_memberships_rules', array_merge( $rules, $new_rules ) );
	}


}
