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
 * Frontend integration class for WooCommerce Subscriptions
 *
 * @since 1.6.0
 */
class WC_Memberships_Integration_Subscriptions_Frontend {


	/**
	 * Add frontend hooks.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		// Restrict subscription product or variation purchase if rules/dripping apply.
		add_filter( 'woocommerce_subscription_is_purchasable',           array( $this, 'subscription_is_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_subscription_variation_is_purchasable', array( $this, 'subscription_is_purchasable' ), 10, 2 );

		// Frontend UI hooks.
		add_filter( 'wc_memberships_members_area_my-memberships_actions',           array( $this, 'my_membership_actions' ), 10, 2 );
		add_filter( 'wc_memberships_my_memberships_column_names',                   array( $this, 'my_memberships_subscriptions_columns' ), 20 );
		add_action( 'wc_memberships_my_memberships_column_membership-next-bill-on', array( $this, 'output_subscription_columns' ), 20 );
	}


	/**
	 * Restrict product purchasing based on restriction rules.
	 *
	 * @see \WC_Memberships_Restrictions::product_is_purchasable()
	 *
	 * @internal
	 *
	 * @since 1.6.5
	 * @param bool $purchasable whether Whether the subscription product is purchasable.
	 * @param \WC_Product_Subscription|\WC_Product_Subscription_Variation $subscription_product The subscription product.
	 * @return bool
	 */
	public function subscription_is_purchasable( $purchasable, $subscription_product ) {
		return wc_memberships()->get_frontend_instance()->get_restrictions_instance()->product_is_purchasable( $purchasable, $subscription_product );
	}


	/**
	 * Remove cancel action from memberships tied to a subscription.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 * @param array $actions
	 * @param \WC_Memberships_User_Membership $user_membership Post object.
	 * @return array
	 */
	public function my_membership_actions( $actions, WC_Memberships_User_Membership $user_membership ) {

		$integration = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();

		if ( $integration->is_membership_linked_to_subscription( $user_membership ) ) {

			// A Memberships tied to a subscription can only be cancelled
			// by cancelling the associated Subscription.
			unset( $actions['cancel'] );

			$subscription = $integration->get_subscription_from_membership( $user_membership->get_id() );
			$is_renewable = $integration->is_subscription_linked_to_membership_renewable( $subscription, $user_membership );

			if ( ! $is_renewable ) {
				unset( $actions['renew'] );
			}
		}

		return $actions;
	}


	/**
	 * Add subscription column headers in My Memberships on My Account page.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 * @param array $columns
	 * @return array
	 */
	public function my_memberships_subscriptions_columns( $columns ) {

		$columns = SV_WC_Helper::array_insert_after( $columns, 'membership-status', array( 'membership-next-bill-on' => __( 'Next Bill On', 'woocommerce-memberships' ) ) );
		return $columns;
	}


	/**
	 * Display subscription columns in My Memberships section.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 * @param \WC_Memberships_User_Membership $user_membership Post object.
	 */
	public function output_subscription_columns( WC_Memberships_User_Membership $user_membership ) {

		$integration  = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
		$subscription = $integration->get_subscription_from_membership( $user_membership->get_id() );

		if ( $subscription && in_array( $user_membership->get_status(), array( 'active', 'free_trial' ), true ) ) {
			$next_payment = $subscription->get_time( 'next_payment' );
		}

		if ( $subscription && ! empty( $next_payment ) ) {
			echo date_i18n( wc_date_format(), $next_payment );
		} else {
			esc_html_e( 'N/A', 'woocommerce-memberships' );
		}
	}


}
