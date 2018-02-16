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
 * Helper object to get subscription-specific properties of a membership plan.
 *
 * @since 1.7.0
 */
class WC_Memberships_Integration_Subscriptions_Membership_Plan extends WC_Memberships_Membership_Plan {


	/** @var string Installment plan meta key */
	protected $installment_plan_meta = '';


	/**
	 * Subscription-tied Membership Plan constructor.
	 *
	 * @since 1.7.0
	 * @param int|\WP_Post $membership_plan id or post object.
	 */
	public function __construct( $membership_plan ) {

		parent::__construct( $membership_plan );

		// set meta keys
		$this->access_length_meta     = '_subscription_access_length';
		$this->access_start_date_meta = '_subscription_access_start_date';
		$this->access_end_date_meta   = '_subscription_access_end_date';
		$this->installment_plan_meta  = '_subscription_installment_plan';

		// set the default access method
		$this->default_access_method = 'subscription';
	}


	/**
	 * Get access length type (overrides parent method).
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_access_length_type() {

		$access_length = $this->default_access_method;
		$access_end    = $this->get_access_end_date_meta();

		if ( ! empty( $access_end ) ) {
			$access_length = 'fixed';
		} elseif ( $this->has_access_length() ) {
			$access_length = 'specific';
		} elseif ( $this->has_installment_plan() ) {
			return 'unlimited';
		}

		return $access_length;
	}


	/**
	 * Check if the plan has a subscription product that can grant access.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function has_subscription() {
		return count( $this->get_subscription_products() ) > 0;
	}


	/**
	 * Whether the subscription-tied membership follows an installment plan option.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function has_installment_plan() {
		return 'yes' === get_post_meta( $this->id, $this->installment_plan_meta, true );
	}


	/**
	 * Set the subscription-tied membership to have an installment plan
	 * regulated by the subscription's length and billing cycle.
	 *
	 * @since 1.7.0
	 */
	public function set_installment_plan() {

		update_post_meta( $this->id, $this->installment_plan_meta, 'yes' );
	}


	/**
	 * Delete the installment plan option.
	 *
	 * @since 1.7.0
	 */
	public function delete_installment_plan() {

		delete_post_meta( $this->id, $this->installment_plan_meta );
	}


	/**
	 * Get subscription products ids.
	 *
	 * @since 1.7.0
	 * @return int[] Array of subscription product ids.
	 */
	public function get_subscription_product_ids() {

		$subscription_products = $this->get_subscription_products();

		return ! empty( $subscription_products ) ? array_map( 'intval', array_keys( $subscription_products ) ) : array();
	}


	/**
	 * Get subscription products that grant access to plan.
	 *
	 * @since 1.7.0
	 * @return \WC_Product[] Array of subscription products.
	 */
	public function get_subscription_products() {

		$products = array();

		if ( $this->has_products() ) {

			foreach ( $this->get_product_ids() as $product_id ) {

				if ( ! is_numeric( $product_id ) || ! $product_id ) {
					continue;
				}

				$product = wc_get_product( $product_id );

				if ( ! $product ) {
					continue;
				}

				if ( $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {
					$products[ $product_id ] = $product;
				}
			}
		}

		return $products;
	}


	/**
	 * Check if this plan has any products that grant access.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_subscription_products() {

		$subscription_product_ids = $this->get_subscription_product_ids();

		return ! empty( $subscription_product_ids );
	}


	/**
	 * Get membership expiration date (overrides parent method).
	 *
	 * Calculates when a subscription-tied membership plan will expire relatively to a start date.
	 *
	 * @since 1.7.0
	 * @param int|string $start Optional: a date string or timestamp (default current time).
	 * @param array $args Optional: additional arguments.
	 * @return string Date in Y-m-d H:i:s format or empty for unlimited plans.
	 */
	public function get_expiration_date( $start = '', $args = array() ) {

		// Get the start time to get the relative end time later.
		if ( empty( $start ) ) {
			if ( ! empty( $args['start'] ) ) {
				$start = is_numeric( $args['start'] ) ? (int) $args['start'] : strtotime( $args['start'] );
			} else {
				$start = current_time( 'timestamp', true );
			}
		} elseif ( is_string( $start ) && ! is_numeric( $start ) ) {
			$start = strtotime( $start );
		} else {
			$start = is_numeric( $start ) ? (int) $start : current_time( 'timestamp', true );
		}

		$access_length_type = $this->get_access_length_type();

		if ( ! $this->has_subscription() ) {

			// If there's no subscription product, then use the parent method.
			$end_date = parent::get_expiration_date( $start, $args );

		} elseif ( isset( $args['product_id'] ) && is_numeric( $args['product_id'] ) && ! $this->has_installment_plan() ) {

			// Purchase type: get the product that granted access.
			$access_product = wc_get_product( $args['product_id'] );

			// Check if the product that grants access is a subscription.
			if ( $access_product && $access_product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) {

				$expiration_date = WC_Subscriptions_Product::get_expiration_date( $access_product->get_id(), date( 'Y-m-d H:i:s', (int) $start ) );
				// Note: undefined subscription expiration date is 0 in WC Subscriptions
				// but in WC Memberships we use empty string to mark "unlimited" time.
				$expiration_date = ! empty( $expiration_date ) ? wc_memberships_parse_date( (string) $expiration_date, 'mysql' ) : '';
				$end_date        = ! empty( $expiration_date ) ? $expiration_date : '';

			} else {

				// If not a subscription product, then must be a regular product.
				$end_date = parent::get_expiration_date( $start, $args );
			}

		} elseif ( 'unlimited' === $access_length_type ) {

			$end_date = '';

		} elseif ( 'fixed' === $access_length_type ) {

			$end_date = wc_memberships_parse_date( $this->get_access_end_date_meta(), 'mysql' );

		} elseif ( 'specific' === $access_length_type ) {

			$access_length = $this->get_access_length();

			if ( false !== strpos( $this->get_access_length_period(), 'month' ) ) {
				$end = wc_memberships_add_months_to_timestamp( (int) $start, $this->get_access_length_amount() );
			} else {
				$end = strtotime( '+ ' . $access_length, (int) $start );
			}

			$end_date = date( 'Y-m-d H:i:s', $end );

		} else {

			// Sanity fallback to standard method in parent class.
			$end_date = parent::get_expiration_date( $start, $args );
		}

		return $end_date;
	}


}
