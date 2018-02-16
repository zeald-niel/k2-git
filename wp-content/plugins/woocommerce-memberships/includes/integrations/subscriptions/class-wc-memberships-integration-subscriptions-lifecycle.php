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
 * Integration class for WooCommerce Subscriptions lifecycle.
 *
 * @since 1.6.0
 */
class WC_Memberships_Integration_Subscriptions_Lifecycle {


	/**
	 * Add lifecycle hooks.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		// Upon Memberships or Subscription activation.
		add_action( 'wc_memberships_activated',              array( $this, 'handle_activation' ), 1 );
		add_action( 'woocommerce_subscriptions_activated',   array( $this, 'handle_activation' ), 1 );
		// Upon Subscriptions deactivation.
		add_action( 'woocommerce_subscriptions_deactivated', array( $this, 'handle_deactivation' ) );
	}


	/**
	 * Handle subscriptions activation.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function handle_activation() {
		$this->update_subscription_memberships();
	}


	/**
	 * Handle subscriptions deactivation.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function handle_deactivation() {
		$this->pause_free_trial_subscription_memberships();
	}


	/**
	 * Pause subscription-based memberships.
	 *
	 * Find any memberships that are on free trial and pause them.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function pause_free_trial_subscription_memberships() {

		// Get user memberships on free trial status.
		$posts = get_posts( array(
			'post_type'   => 'wc_user_membership',
			'post_status' => 'wcm-free_trial',
			'nopaging'    => true,
		) );

		// Bail out if there are no memberships on free trial.
		if ( empty( $posts ) ) {
			return;
		}

		// Pause the memberships found.
		foreach ( $posts as $post ) {

			$user_membership = wc_memberships_get_user_membership( $post );
			$user_membership->pause_membership( __( 'Membership paused because WooCommerce Subscriptions was deactivated.', 'woocommerce-memberships' ) );
		}
	}


	/**
	 * Re-activate subscription-based memberships.
	 *
	 * Find any memberships tied to a subscription that are paused,
	 * which may need to be re-activated or put back on trial.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function update_subscription_memberships() {

		// Get the Subscriptions integration instance.
		$integration = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();

		// Sanity check.
		if ( null === $integration ) {
			return;
		}

		$args = array(
			'post_type'    => 'wc_user_membership',
			'nopaging'     => true,
			'post_status'  => 'any',
			'meta_key'     => '_subscription_id',
			'meta_value'   => '0',
			'meta_compare' => '>',
		);

		$posts = get_posts( $args );

		// Bail out if there are no memberships to work with.
		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post ) {

			$user_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $post );

			// Get the related subscription.
			$subscription = $integration->get_subscription_from_membership( $user_membership->get_id() );

			if ( ! $subscription ) {
				continue;
			}

			$subscription_status = $integration->get_subscription_status( $subscription );

			// If statuses do not match, update.
			if ( ! $integration->has_subscription_same_status( $subscription, $user_membership ) ) {

				// Special handling for paused memberships which might be put on free trial.
				if ( 'active' === $subscription_status && 'paused' === $user_membership->get_status() ) {

					// Get trial end timestamp.
					$trial_end = $integration->get_subscription_event_time( $subscription, 'trial_end' );

					// If there is no trial end date or the trial end date is past
					// And the Subscription is active, activate the membership
					if ( ! $trial_end || current_time( 'timestamp', true ) >= $trial_end ) {
						$user_membership->activate_membership( __( 'Membership activated because WooCommerce Subscriptions was activated.', 'woocommerce-memberships' ) );
					// Otherwise, put the membership on free trial.
					} else {
						$user_membership->update_status( 'free_trial', __( 'Membership free trial activated because WooCommerce Subscriptions was activated.', 'woocommerce-memberships' ) );
						$user_membership->set_free_trial_end_date( date( 'Y-m-d H:i:s', $trial_end ) );
					}

				// All other membership statuses: simply update the status.
				} else {

					$integration->update_related_membership_status( $subscription, $user_membership, $subscription_status );
				}
			}

			$end_date = $integration->get_subscription_event_date( $subscription, 'end' );

			// End date has changed.
			if ( strtotime( $end_date ) !== $user_membership->get_end_date( 'timestamp' ) ) {
				$user_membership->set_end_date( $end_date );
			}
		}
	}


}
