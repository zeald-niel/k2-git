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
 * Main function for returning a user membership
 *
 * Supports getting user membership by membership ID, Post object
 * or a combination of the user ID and membership plan id/slug/Post object.
 *
 * If no $id is provided, defaults to getting the membership for the current user.
 *
 * @since 1.0.0
 * @param mixed $id Optional. Post object or post ID of the user membership, or user ID
 * @param mixed $plan Optional. Membership Plan slug, post object or related post ID
 * @return \WC_Memberships_User_Membership|\WC_Memberships_Integration_Subscriptions_User_Membership|false The User Membership or false if not found
 */
function wc_memberships_get_user_membership( $id = null, $plan = null ) {
	return wc_memberships()->get_user_memberships_instance()->get_user_membership( $id, $plan );
}


/**
 * Get all memberships for a user
 *
 * @since 1.0.0
 * @param int $user_id Optional, defaults to current user
 * @param array $args Optional arguments
 * @return \WC_Memberships_User_Membership[]|\WC_Memberships_Integration_Subscriptions_User_Membership[]|null array of user memberships
 */
function wc_memberships_get_user_memberships( $user_id = null, $args = array() ) {
	return wc_memberships()->get_user_memberships_instance()->get_user_memberships( $user_id, $args );
}


/**
 * Get all active memberships for a user
 *
 * Note: does not include just memberships in purely 'active' status,
 * but memberships that give active access to a plan
 *
 * @since 1.7.0
 * @param int $user_id Optional, defaults to current user
 * @param array $args Optional arguments
 * @return \WC_Memberships_User_Membership[]|\\WC_Memberships_Integration_Subscriptions_User_Membership[]|null array of user memberships
 */
function wc_memberships_get_user_active_memberships( $user_id = null, $args = array() ) {

	$args['status'] = wc_memberships()->get_user_memberships_instance()->get_active_access_membership_statuses();

	return wc_memberships()->get_user_memberships_instance()->get_user_memberships( $user_id, $args );
}


/**
 * Get all user membership statuses
 *
 * @since 1.0.0
 * @return array
 */
function wc_memberships_get_user_membership_statuses() {
	return wc_memberships()->get_user_memberships_instance()->get_user_membership_statuses();
}


/**
 * Get the nice name for a user membership status
 *
 * @since  1.0.0
 * @param  string $status
 * @return string
 */
function wc_memberships_get_user_membership_status_name( $status ) {

	$statuses = wc_memberships_get_user_membership_statuses();
	$status   = 0 === strpos( $status, 'wcm-' ) ? substr( $status, 4 ) : $status;
	$status   = isset( $statuses[ 'wcm-' . $status ] ) ? $statuses[ 'wcm-' . $status ] : $status;

	return is_array( $status ) && isset( $status['label'] ) ? $status['label'] : $status;
}


/**
 * Check if user is a member of either any or a particular membership plan, with any status
 *
 * @since 1.0.0
 * @param int $user_id Optional, defaults to current user
 * @param int|string $membership_plan Membership Plan slug, post object or related post ID
 * @param bool $cache Whether to use cache results (default true)
 * @return bool
 */
function wc_memberships_is_user_member( $user_id = null, $membership_plan = null, $cache = true ) {
	return wc_memberships()->get_user_memberships_instance()->is_user_member( $user_id, $membership_plan, false, $cache );
}


/**
 * Check if user is an active member of either any or a particular membership plan
 *
 * @since 1.0.0
 * @param int|\WP_User $user_id Optional, defaults to current user
 * @param int|string $plan Membership Plan slug, post object or related post ID
 * @param bool $cache Whether to use cache results (default true)
 * @return bool
 */
function wc_memberships_is_user_active_member( $user_id = null, $plan = null, $cache = true ) {
	return wc_memberships()->get_user_memberships_instance()->is_user_active_member( $user_id, $plan, $cache );
}


/**
 * Check if user is a delayed member of either any or  a particular membership plan
 *
 * @since 1.7.0
 * @param int|\WP_User $user_id Optional, defaults to current user
 * @param int|string $plan Membership Plan slug, post object or related post ID
 * @param bool $cache Whether to use cache results (default true)
 * @return bool
 */
function wc_memberships_is_user_delayed_member( $user_id = null, $plan = null, $cache = true ) {
	return wc_memberships()->get_user_memberships_instance()->is_user_delayed_member( $user_id, $plan, $cache );
}


/**
 * Check if user is a member with either active or delayed status
 * of either a particular or any membership plan
 *
 * @since 1.8.0
 * @param int|\WP_User $user_id Optional, defaults to current user
 * @param int|string $plan Membership Plan slug, post object or related post ID
 * @param bool $cache Whether to use cache results (default true)
 * @return bool
 */
function wc_memberships_is_user_active_or_delayed_member( $user_id = null, $plan = null, $cache = true ) {
	return wc_memberships()->get_user_memberships_instance()->is_user_active_or_delayed_member( $user_id, $plan, $cache );
}


/**
 * Check if a product is accessible (viewable or purchaseable)
 *
 * TODO for now `$target` only supports a simple array like  'post' => id  or  'product' => id  - in future we could extend this to take arrays or different/multiple args {FN 2016-04-26}
 *
 * @since 1.4.0
 * @param int $user_id User to check if has access
 * @param string|array Type of capabilities: 'view', 'purchase' (products only)
 * @param array $target Associative array of content type and content id to access to
 * @param int|string UTC timestamp to compare for content access (optional, defaults to now)
 * @return bool|null
 */
function wc_memberships_user_can( $user_id, $action, $target, $when = '' ) {
	return wc_memberships()->get_capabilities_instance()->user_can( $user_id, $action, $target, $when );
}


/**
 * Create a new user membership programmatically.
 *
 * Returns a new user membership object on success which can then be used to add
 * additional data, but will return WP_Error on failure.
 *
 * @since 1.3.0
 * @param array $args Array of arguments
 * @param string $action Action - either 'create' or 'renew' -- when in doubt, use 'create'.
 * @return \WC_Memberships_User_Membership|\WP_Error
 */
function wc_memberships_create_user_membership( $args = array(), $action = 'create' ) {

	$args = wp_parse_args( $args, array(
		'user_membership_id' => 0,
		'plan_id'            => 0,
		'user_id'            => 0,
		'product_id'         => 0,
		'order_id'           => 0,
	) );

	$new_membership_data = array(
		'post_parent'    => (int) $args['plan_id'],
		'post_author'    => (int) $args['user_id'],
		'post_type'      => 'wc_user_membership',
		'post_status'    => 'wcm-active',
		'comment_status' => 'open',
	);

	$updating = false;

	if ( (int) $args['user_membership_id'] > 0 ) {
		$updating                  = true;
		$new_membership_data['ID'] = (int) $args['user_membership_id'];
	}

	/**
	 * Filter new membership data, used when a product purchase grants access.
	 *
	 * @since 1.0.0
	 * @param array $data
	 * @param array $args {
	 *     Array of User Membership arguments
	 *
	 *     @type int $user_id The user id the membership is assigned to.
	 *     @type int $product_id The product id that grants access (optional).
	 *     @type int $order_id The order id that contains the product that granted access (optional).
	 * }
	 */
	$new_post_data = apply_filters( 'wc_memberships_new_membership_data', $new_membership_data, array(
		'user_id'    => (int) $args['user_id'],
		'product_id' => (int) $args['product_id'],
		'order_id'   => (int) $args['order_id'],
	) );

	if ( $updating ) {

		// Do not modify the post status yet on renewals.
		unset( $new_post_data['post_status'] );

		$user_membership_id = wp_update_post( $new_post_data );

	} else {

		$user_membership_id = wp_insert_post( $new_post_data );
	}

	// Bail out on error.
	if ( is_wp_error( $user_membership_id ) ) {
		return $user_membership_id;
	}

	// Get the user membership object to set properties on.
	$user_membership = wc_memberships_get_user_membership( $user_membership_id );

	// Save/Update product id that granted access.
	if ( (int) $args['product_id'] > 0 ) {
		$user_membership->set_product_id( $args['product_id'] );
	}

	// Save/Update the order id that contained the access granting product.
	if ( (int) $args['order_id'] > 0 ) {
		$user_membership->set_order_id( $args['order_id'] );
	}

	// Get the user membership object again, since the product and the order
	// just set might influence the object filtering (e.g. subscriptions)
	/** @see \WC_Memberships_Integration_Subscriptions_Abstract::get_user_membership() */
	$user_membership = wc_memberships_get_user_membership( $user_membership_id );
	// Get the membership plan object to get some properties from.
	$membership_plan = wc_memberships_get_membership_plan( (int) $args['plan_id'], $user_membership );

	// Save or update the membership start date,
	// but only if the membership is not active yet (ie. is not being renewed);
	// also do a sanity check for delayed memberships:
	if ( 'renew' !== $action ) {

		$start_date = $membership_plan->is_access_length_type( 'fixed' ) ? $membership_plan->get_access_start_date() : current_time( 'mysql', true );

		$user_membership->set_start_date( $start_date );

	} elseif ( 'delayed' !== $user_membership->get_status() && $user_membership->get_start_date( 'timestamp' ) > strtotime( 'tomorrow', current_time( 'timestamp', true ) ) ) {

		$user_membership->update_status( 'delayed' );
	}

	// Calculate membership end date based on membership length,
	// early renewals add to the existing membership length,
	// normal cases calculate membership length from "now" (UTC).
	$now        = current_time( 'timestamp', true );
	$is_expired = $user_membership->is_expired();

	if ( 'renew' === $action && ! $is_expired ) {
		$end = $user_membership->get_end_date( 'timestamp' );
		$now = ! empty( $end ) ? $end : $now;
	}

	// Obtain the relative end date based on the membership plan.
	$end_date = $membership_plan->get_expiration_date( $now, $args );

	// Save/Update the membership end date.
	$user_membership->set_end_date( $end_date );

	// Finally re-activate successfully renewed memberships after setting new dates.
	if ( 'renew' === $action && $is_expired && $user_membership->is_in_active_period() ) {
		$user_membership->update_status( 'active' );
	}

	/**
	 * Fires after a user has been granted membership access
	 *
	 * This action hook is similar to `wc_memberships_user_membership_saved`
	 * but doesn't fire when memberships are manually created from admin
	 * @see \WC_Memberships_User_Memberships::save_user_membership()
	 *
	 * @since 1.3.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan The plan that user was granted access to
	 * @param array $args {
	 *     Array of User Membership arguments
	 *
	 *     @type int $user_id The user id the membership is assigned to
	 *     @type int $user_membership_id The user membership id being saved
	 *     @type bool $is_update Whether this is a post update or a newly created membership
	 * }
	 */
	do_action( 'wc_memberships_user_membership_created', $membership_plan, array(
		'user_id'            => $args['user_id'],
		'user_membership_id' => $user_membership->get_id(),
		'is_update'          => $updating,
	) );

	return $user_membership;
}
