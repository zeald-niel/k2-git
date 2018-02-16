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
 * @package   WC-Memberships/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Membership Plans class
 *
 * This class handles general membership plans related functionality
 *
 * @since 1.0.0
 */
class WC_Memberships_Membership_Plans {


	/** @var array helper for lazy membership plans getter */
	private $membership_plans = array();


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		require_once( wc_memberships()->get_plugin_path() . '/includes/class-wc-memberships-membership-plan.php' );

		// delete related data upon plan deletion
		add_action( 'delete_post', array( $this, 'delete_related_data' ) );

		// trigger free memberships access upon user registration event
		add_action( 'user_register', array( $this, 'grant_access_to_free_membership' ), 10, 2 );

		// trigger memberships access upon products purchases
		add_action( 'woocommerce_order_status_completed',  array( $this, 'grant_access_to_membership_from_order' ), 11 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'grant_access_to_membership_from_order' ), 11 );
	}


	/**
	 * Get a single membership plan
	 *
	 * @since 1.0.0
	 * @param int|\WP_Post|null $post Optional, post object or post id of the membership plan
	 * @param \WC_Memberships_User_Membership|int|null Optional, user membership object or id, used in filter
	 * @return \WC_Memberships_Membership_Plan|false
	 */
	public function get_membership_plan( $post = null, $user_membership = null ) {

		if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {

			$post = $GLOBALS['post'];

		} elseif ( is_numeric( $post ) ) {

			$post = get_post( $post );

		} elseif ( $post instanceof WC_Memberships_Membership_Plan ) {

			$post = get_post( $post->get_id() );

		} elseif ( is_string( $post ) ) {

			$posts = get_posts( array(
				'name'           => $post,
				'post_type'      => 'wc_membership_plan',
				'posts_per_page' => 1,
			));

			if ( ! empty( $posts ) ) {
				$post = $posts[0];
			}

		} elseif ( ! ( $post instanceof WP_Post ) ) {

			$post = null;
		}

		// if no acceptable post is found, bail out
		if ( ! $post || 'wc_membership_plan' !== get_post_type( $post ) ) {
			return false;
		}

		if ( is_numeric( $user_membership ) ) {
			$user_membership = wc_memberships_get_user_membership( $user_membership );
		}

		$membership_plan = new WC_Memberships_Membership_Plan( $post );

		/**
		 * Get a membership plan
		 *
		 * @since 1.7.0
		 * @param \WC_Memberships_Membership_Plan $membership_plan The membership plan
		 * @param \WP_Post $membership_plan_post The membership plan post object
		 * @param \WC_Memberships_User_Membership|null $user_membership Optional, when calling this filter from a user membership
		 */
		return apply_filters( 'wc_memberships_membership_plan', $membership_plan, $post, $user_membership );
	}


	/**
	 * Get all membership plans
	 *
	 * @since 1.0.0
	 * @param array $args Optional array of arguments, to pass to `get_posts()`
	 * @return \WC_Memberships_Membership_Plan[] $plans Array of membership plans
	 */
	public function get_membership_plans( $args = array() ) {

		$defaults = array(
			'posts_per_page' => -1,
		);

		$args = wp_parse_args( $args, $defaults );
		$args['post_type'] = 'wc_membership_plan';

		// unique key for caching the applied rule results
		$cache_key = http_build_query( $args );

		if ( ! isset( $this->membership_plans[ $cache_key ] ) ) {

			$membership_plan_posts = get_posts( $args );

			$this->membership_plans[ $cache_key ] = array();

			if ( ! empty( $membership_plan_posts ) ) {

				foreach ( $membership_plan_posts as $post ) {
					$this->membership_plans[ $cache_key ][] = wc_memberships_get_membership_plan( $post );
				}
			}
		}

		return $this->membership_plans[ $cache_key ];
	}


	/**
	 * Get membership plans accessed upon user registration
	 *
	 * @since 1.7.0
	 * @param array $args Optional array of arguments, to pass to `get_posts()`
	 * @return \WC_Memberships_Membership_Plan[]
	 */
	public function get_free_membership_plans( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'meta_query' => array(
				array(
					'key'   => '_access_method',
					'value' => 'signup',
				),
			),
		) );

		return $this->get_membership_plans( $args );
	}


	/**
	 * Get membership plans possible access methods
	 *
	 * @since 1.7.0
	 * @param bool $with_labels Whether to return labels along with access method keys
	 * @return array Indexed or Associative array
	 */
	public function get_membership_plans_access_methods( $with_labels = false ) {

		$access_methods = array(
			/* translators: A User Membership is manually created */
			'manual-only' => __( 'Manual assignment only', 'woocommerce-memberships' ),
			/* translators: A User Membership is created when a user registers an account */
			'signup'      => __( 'User account registration', 'woocommerce-memberships' ),
			/* translators: A User Membership is created when a customer purchases a product that grants access */
			'purchase'    => __( 'Product(s) purchase', 'woocommerce-memberships' ),
		);

		return true !== $with_labels ? array_keys( $access_methods ) : $access_methods;
	}


	/**
	 * Get membership plans possible access length types
	 *
	 * @since 1.7.0
	 * @param bool $with_labels Whether to return labels along with access length keys
	 * @return array Indexed or Associative array
	 */
	public function get_membership_plans_access_length_types( $with_labels = false ) {

		$access_length_types = array(
			/* translators: Membership of an unlimited length */
			'unlimited' => __( 'Unlimited', 'woocommerce-memberships' ),
			/* translators: Specify the length of a membership */
			'specific'  => __( 'Specific length', 'woocommerce-memberships' ),
			/* translators: Membership set to expire in a specified date */
			'fixed'     => __( 'Fixed dates', 'woocommerce-memberships' )
		);

		return true !== $with_labels ? array_keys( $access_length_types ) : $access_length_types;
	}


	/**
	 * Get membership plans possible access length periods
	 *
	 * @since 1.7.0
	 * @param bool $with_labels Whether to return labels along with access length keys
	 * @return array Indexed or Associative array
	 */
	public function get_membership_plans_access_length_periods( $with_labels = false ) {

		$access_length_periods = array(
			'days'   => __( 'Day(s)', 'woocommerce-memberships' ),
			'weeks'  => __( 'Week(s)', 'woocommerce-memberships' ),
			'months' => __( 'Month(s)', 'woocommerce-memberships' ),
			'years'  => __( 'Year(s)', 'woocommerce-memberships' ),
		);

		/**
		 * Filter plan access length periods
		 *
		 * Note: acceptable keys should be time values recognizable by `strtotime()`
		 *
		 * @since 1.6.1
		 * @param array $access_length_periods Associative array of keys and labels
		 */
		$access_length_periods = apply_filters( 'wc_memberships_plan_access_period_options', $access_length_periods );

		return true !== $with_labels ? array_keys( $access_length_periods ) : $access_length_periods;
	}


	/**
	 * Delete any related data if membership plan is deleted
	 *
	 * Deletes any related user memberships and plan rules
	 *
	 * @since 1.0.0
	 * @param int $post_id Deleted post ID
	 */
	public function delete_related_data( $post_id ) {
		global $wpdb;

		// bail out if the post being deleted is not a membership plan
		if ( 'wc_membership_plan' !== get_post_type( $post_id ) ) {
			return;
		}

		// find related membership IDs
		$membership_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d", $post_id ) );

		// delete each membership plan
		if ( ! empty( $membership_ids ) ) {
			foreach ($membership_ids as $membership_id) {

				wp_delete_post( $membership_id, true );
			}
		}

		// find related restriction rules and delete them
		$rules = (array) get_option( 'wc_memberships_rules' );

		foreach ( $rules as $key => $rule ) {

			// remove related rule
			if ( $rule['membership_plan_id'] == $post_id ) {
				unset( $rules[ $key ] );
			}
		}

		update_option( 'wc_memberships_rules', array_values( $rules ) );
	}


	/**
	 * Grant access to free membership plans
	 * to users that just signed up for an account
	 *
	 * @since 1.7.0
	 * @param int $user_id Newly registered WP_User id
	 * @param bool $renew Whether to renew a membership if the user is already a member, default true
	 * @param int|\WC_Memberships_Membership_Plan|null $plan Optional plan to grant access to,
	 *                                                        will otherwise run through all free plans
	 * @return void|null|\WC_Memberships_User_Membership The newly created membership or null if none created or fail
	 */
	public function grant_access_to_free_membership( $user_id, $renew = true, $plan = null ) {

		$user_membership = null;

		// no need to run this for admins and users that can access everything anyway
		if ( ! user_can( $user_id, 'wc_memberships_access_all_restricted_content' ) ) {

			if ( null !== $plan ) {

				if ( is_numeric( $plan ) ) {
					$plan = wc_memberships_get_membership_plan( (int) $plan );
				}

				if ( $plan instanceof WC_Memberships_Membership_Plan ) {
					$free_membership_plans = array( $plan );
				}

			} else {

				$free_membership_plans = wc_memberships_get_free_membership_plans();
			}

			if ( ! empty( $free_membership_plans ) ) {

				foreach ( $free_membership_plans as $membership_plan ) {

					// sanity check
					if ( $membership_plan->is_access_method( 'signup' ) ) {

						$action = wc_memberships_is_user_member( $user_id, $membership_plan->get_id() ) ? 'renew' : 'create';

						if ( ! $renew && 'renew' === $action ) {
							continue;
						}

						// used in filter and `wc_memberships_create_user_membership()`
						$access_args = array(
							'user_id' => (int) $user_id,
							'plan_id' => $membership_plan->get_id(),
						);

						/**
						 * Confirm grant access to a free membership
						 *
						 * @since 1.7.0
						 * @param bool $grant_access true by default
						 * @param array $args {
						 *      @type int $user_id User id being granted access
						 *      @type int $plan_id Id of the free plan accessing to
						 * }
						 */
						$grant_access = (bool) apply_filters( 'wc_memberships_grant_access_to_free_membership', true, $access_args );

						if ( $grant_access ) {
							// assign a membership to this user
							$user_membership = wc_memberships_create_user_membership( $access_args, $action );
						}
					}
				}
			}
		}

		// when used as hook callback, doesn't need to return anything
		if ( 'user_register' === current_action() ) {
			return;
		}

		return $user_membership;
	}


	/**
	 * Grant customer access to membership when making a purchase.
	 *
	 * Note: this method runs also when an order is manually added in WC admin.
	 *
	 * @since 1.7.0
	 *
	 * @param int|\WC_Order $order WC_Order id or object
	 */
	public function grant_access_to_membership_from_order( $order ) {

		$order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$order_items      = $order->get_items();
		$user_id          = $order->get_user_id();
		$membership_plans = $this->get_membership_plans();

		// skip if guest user, no order items or no membership plans to begin with
		if ( ! $user_id || empty( $order_items ) || empty( $membership_plans ) ) {
			return;
		}

		// loop over all available membership plans
		foreach ( $membership_plans as $plan ) {

			// skip if no products grant access to this plan
			if ( ! $plan->has_products() ) {
				continue;
			}

			$access_granting_product_ids = wc_memberships_get_order_access_granting_product_ids( $plan, $order, $order_items );

			if ( ! empty( $access_granting_product_ids ) ) {

				// We check if the order has granted access already before looping products,
				// so we can allow the purchase of multiple access granting products to extend the duration of a plan,
				// should multiple products grant access to the same plan having a specific end date (relative to now).
				/** @see wc_memberships_cumulative_granting_access_orders_allowed() */
				/** @var \WC_Memberships_Membership_Plan::grant_access_from_purchase() */
				$order_granted_access_already = wc_memberships_has_order_granted_access( $order, array( 'membership_plan' => $plan ) );

				foreach ( $access_granting_product_ids as $product_id ) {

					// sanity check: make sure the selected product ID in fact does grant access
					if ( ! $plan->has_product( $product_id ) ) {
						continue;
					}

					/**
					 * Confirm grant access from new purchase to paid plan
					 *
					 * @since 1.3.5
					 * @param bool $grant_access by default true unless the order already granted access to the plan
					 * @param array $args {
					 *      @type int $user_id Customer id for purchase order
					 *      @type int $product_id Id of product that grants access
					 *      @type int $order_id Order id containing the product
					 * }
					 */
					$grant_access = (bool) apply_filters( 'wc_memberships_grant_access_from_new_purchase', ! $order_granted_access_already, array(
						'user_id'    => (int) $user_id,
						'product_id' => (int) $product_id,
						'order_id'   => (int) SV_WC_Order_Compatibility::get_prop( $order, 'id' ),
					) );

					if ( $grant_access ) {
						// delegate granting access to the membership plan instance
						$plan->grant_access_from_purchase( $user_id, $product_id, (int) SV_WC_Order_Compatibility::get_prop( $order, 'id' ) );
					}
				}
			}
		}
	}


}
