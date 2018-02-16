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
 * Get Users Memberships from a Subscription
 *
 * Returns empty array if no User Memberships are found or Subscriptions is inactive
 *
 * @since 1.5.4
 * @param int|\WP_Post $subscription A Subscription post object or id
 * @return \WC_Memberships_User_Membership[] Array of User Membership objects or empty array if none found
 */
function wc_memberships_get_memberships_from_subscription( $subscription ) {

	$integrations = wc_memberships()->get_integrations_instance();

	if ( ! $integrations || true !== $integrations->is_subscriptions_active() ) {
		return array();
	}

	$subscriptions = $integrations->get_subscriptions_instance();

	return $subscriptions ? $subscriptions->get_memberships_from_subscription( $subscription ) : array();
}


/**
 * Is the product that granted access a subscription.
 *
 * @since 1.8.0
 * @param \WC_Memberships_User_Membership $user_membership User Membership
 * @return bool
 */
function wc_memberships_has_subscription_granted_access( $user_membership ) {

	$is_subscription_tied = false;

	if ( $user_membership instanceof WC_Memberships_User_Membership ) {

		if ( $subscription_id = get_post_meta( $user_membership->get_id(), '_subscription_id', true ) ) {

			$is_subscription_tied = ! empty( $subscription_id ) && wcs_get_subscription( $subscription_id );

		} else {

			$product_that_grants_access = $user_membership->get_product();

			if ( $product_that_grants_access && $product_that_grants_access->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {

				$is_subscription_tied = true;
			}
		}
	}

	return $is_subscription_tied;
}


/**
 * Get a Subscription by order_id and product_id
 *
 * @since 1.8.0
 * @param int $order_id WC_Order id
 * @param int $product_id WC_Product id
 * @return null|\WC_Subscription Subscription object or null if not found
 */
function wc_memberships_get_order_subscription( $order_id, $product_id ) {

	$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'product_id' => $product_id ) );
	$subscription  = is_array( $subscriptions ) ? reset( $subscriptions ) : null;

	// If undetermined it may be that the subscription was created directly in admin
	// as there might be no attached order ($order_id is from a WC_Subscription).
	return $subscription instanceof WC_Subscription ? $subscription : wcs_get_subscription( $order_id );
}
