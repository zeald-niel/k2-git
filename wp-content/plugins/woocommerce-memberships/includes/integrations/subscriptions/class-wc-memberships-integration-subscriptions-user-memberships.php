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
 * Subscription-tied user memberships handler.
 *
 * @since 1.8.0
 */
class WC_Memberships_Integration_Subscriptions_User_Memberships {


	/**
	 * Init hooks.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {

		// helper object for subscription-tied user memberships
		require( wc_memberships()->get_plugin_path() . '/includes/integrations/subscriptions/class-wc-memberships-integration-subscriptions-user-membership.php' );

		// Init hooks that need to be executed early.
		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'wc_memberships_new_membership_data',     array( $this, 'adjust_new_membership_data' ), 10, 2 );
		add_filter( 'wc_memberships_user_membership_created', array( $this, 'upon_new_membership_created' ) );
		add_filter( 'wc_memberships_renew_membership',        array( $this, 'renew_membership' ), 10, 3 );

		// Adjust Memberships access dates.
		add_filter( 'wc_memberships_access_from_time', array( $this, 'adjust_post_access_from_time' ), 10, 3 );

		// Handle Membership expiration by cron event.
		add_filter( 'wc_memberships_expire_user_membership', array( $this, 'handle_membership_expiry_by_scheduled_event' ), 5, 2 );

		// Skip Membership Ending Soon emails for memberships linked to a subscription
		add_filter( 'woocommerce_email_enabled_WC_Memberships_User_Membership_Ending_Soon_Email', array( $this, 'skip_ending_soon_emails' ), 20, 2 );
	}


	/**
	 * Init early hooks.
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 */
	public function init() {

		// Filter memberships objects.
		add_filter( 'wc_memberships_user_membership',      array( $this, 'get_user_membership' ), 2, 1 );
		// Set the user membership to subscription type if the membership is tied to a subscription.
		add_filter( 'wc_memberships_user_membership_type', array( $this, 'get_subscription_tied_membership_type' ), 1, 2 );
	}


	/**
	 * Filter a User Membership to return a subscription-tied User Membership.
	 *
	 * This method is a filter callback and should not be used directly.
	 * @see \wc_memberships_get_user_membership() instead.
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 * @param \WC_Memberships_User_Membership $user_membership The user membership object
	 * @return \WC_Memberships_Integration_Subscriptions_User_Membership|\WC_Memberships_User_Membership
	 */
	public function get_user_membership( $user_membership ) {
		return wc_memberships_has_subscription_granted_access( $user_membership ) ? new WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post ) : $user_membership;
	}


	/**
	 * Filter the membership type.
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 * @param string $membership_type The membership type to filter.
	 * @param \WC_Memberships_User_Membership $user_membership The user membership object.
	 * @return string
	 */
	public function get_subscription_tied_membership_type( $membership_type, $user_membership ) {
		return wc_memberships_has_subscription_granted_access( $user_membership ) ? 'subscription' : $membership_type;
	}


	/**
	 * Adjust user membership post scheduled content 'access from' time for subscription-based memberships
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 * @param int $from_time Access from time, as a timestamp.
	 * @param \WC_Memberships_Membership_Plan_rule $rule Related rule.
	 * @param \WC_Memberships_User_Membership $user_membership The user membership.
	 * @return int Modified from_time, as timestamp.
	 */
	public function adjust_post_access_from_time( $from_time, WC_Memberships_Membership_Plan_Rule $rule, WC_Memberships_User_Membership $user_membership ) {

		if ( 'yes' === $rule->get_access_schedule_exclude_trial() ) {

			$subscription_user_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->get_id() );

			if ( $subscription_user_membership->has_subscription() && ( $trial_end_date = $subscription_user_membership->get_free_trial_end_date( 'timestamp' ) ) ) {

				return $trial_end_date;
			}
		}

		return $from_time;
	}


	/**
	 * Adjust whether a membership should be renewed or not
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 * @param bool $renew
	 * @param \WC_Memberships_Membership_Plan $plan
	 * @param array $args
	 * @return bool
	 */
	public function renew_membership( $renew, $plan, $args ) {

		if ( $plan && ! empty( $args['product_id'] ) && ( $product = wc_get_product( $args['product_id'] ) ) ) {

			$plans_handler = wc_memberships()->get_integrations_instance()->get_subscriptions_instance()->get_plans_instance();

			if ( $plans_handler && $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {
				$renew = $plans_handler->grant_access_while_subscription_active( $plan );
			}
		}

		return $renew;
	}


	/**
	 * Adjust new membership data
	 *
	 * Sets the end date to match subscription end date
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 * @param array $data Original membership data
	 * @param array $args Array of arguments
	 * @return array Modified membership data
	 */
	public function adjust_new_membership_data( $data, $args ) {

		$product = wc_get_product( $args['product_id'] );

		if (    $product
		     && isset( $args['order_id'] )
		     && (int) $args['order_id'] > 0
		     && $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) )
		     && ( $subscription = wc_memberships_get_order_subscription($args['order_id'], $product->get_id() ) ) ) {

			$trial_end = wc_memberships()->get_integrations_instance()->get_subscriptions_instance()->get_subscription_event_time( $subscription, 'trial_end' );

			if ( $trial_end && $trial_end > current_time( 'timestamp', true ) ) {
				$data['post_status'] = 'wcm-free_trial';
			}
		}

		return $data;
	}


	/**
	 * When a new membership is created (not necessarily subscription tied).
	 *
	 * @internal
	 *
	 * @since 1.7.1
	 * @param \WC_Memberships_User_Membership $user_membership The new user membership.
	 */
	public function upon_new_membership_created( $user_membership ) {

		if ( ! empty( $user_membership->post ) && wc_memberships_has_subscription_granted_access( $user_membership ) ) {

			$subscription_tied_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );

			// Maybe set the free trial end date meta if subscription is on trial.
			if ( $subscription_tied_membership->has_status( 'free_trial' ) ) {

				$subscription_trial_end_date = wc_memberships()->get_integrations_instance()->get_subscriptions_instance()->get_subscription_event_date( $subscription_tied_membership->get_subscription(), 'trial_end' );

				$subscription_tied_membership->set_free_trial_end_date( $subscription_trial_end_date );
			}
		}
	}


	/**
	 * Check if a Subscription-tied membership should really expire by comparing
	 * it to either the Subscription's or User Membership's expiry date.
	 *
	 * @internal
	 *
	 * @since 1.5.4
	 * @param bool $maybe_expire Whether the User Membership is set to expire (true) or not (false).
	 * @param \WC_Memberships_User_Membership $user_membership The User Membership object set to expire.
	 * @return bool True to confirm expiration, false to prevent it.
	 */
	public function handle_membership_expiry_by_scheduled_event( $maybe_expire, $user_membership ) {

		$integration     = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
		$subscription    = $integration->get_subscription_from_membership( $user_membership->get_id() );
		$user_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );

		// sanity checks
		if (    ! $subscription
		     || ! $user_membership
		     || ! $user_membership->has_subscription() ) {

			return $maybe_expire;

		} elseif ( true === $maybe_expire && $user_membership->has_installment_plan() ) {

			$integration->unlink_membership( $user_membership->get_id(), $user_membership->get_subscription() );

			$maybe_expire = false;

		} else {

			$subscription_end_date = $integration->get_subscription_event_date( $subscription, 'end' );

			// expire only if the scheduled date matches the subscription end date...
			if ( $subscription_end_date === $user_membership->get_end_date() ) {

				$today = date( 'Y-m-d', current_time( 'timestamp', true ) );

				// ...and it's scheduled to expire today
				if ( 0 === strpos( $subscription_end_date, $today ) ) {
					$maybe_expire = true;
				}
			}
		}

		return $maybe_expire;
	}


	/**
	 * Disable Ending Soon emails for memberships tied to a subscription.
	 *
	 * Currently, a subscription cannot be renewed before its expiration date.
	 *
	 * TODO however this could change in the future if Subscriptions introduces early renewals {FN 2017-04-04}
	 *
	 * @internal
	 *
	 * @since 1.8.2
	 *
	 * @param bool $is_enabled Whether the email is enabled in the first place
	 * @param \WC_Memberships_Integration_Subscriptions_User_Membership $user_membership A user membership which could be tied to a subscription
	 *
	 * @return bool
	 */
	public function skip_ending_soon_emails( $is_enabled, $user_membership ) {

		if ( $is_enabled ) {

			if ( is_numeric( $user_membership ) ) {
				$user_membership = wc_memberships_get_user_membership( $user_membership );
			}

			// if it's linked to a subscription, skip
			$is_enabled = $user_membership instanceof WC_Memberships_User_Membership && wc_memberships()->get_integrations_instance()->get_subscriptions_instance()->is_membership_linked_to_subscription( $user_membership ) ? false : $is_enabled;
		}

		return $is_enabled;
	}


}
