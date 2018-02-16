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
 * Discounts integration class for WooCommerce Subscriptions
 *
 * @since 1.6.0
 */
class WC_Memberships_Integration_Subscriptions_Discounts {


	/** @var bool Whether to apply discounts to sign up fees (user setting) */
	private $apply_member_discounts_to_sign_up_fees = false;

	/** @var array Sign up fee discounting memoization (associative array) */
	private $sign_up_fee = array();


	/**
	 * Hook into Memberships Discounts to handle Subscription products.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		// process member discounts for Subscriptions after standard discounts
		add_action( 'init', array( $this, 'init' ), 20 );

		// create an option in settings to enable sign up fees discounts
		add_filter( 'wc_memberships_products_settings', array( $this, 'enable_discounts_to_sign_up_fees' ) );
	}


	/**
	 * Init member discounts for subscription products
	 *
	 * @see \WC_Memberships_Member_Discounts::init()
	 * @internal
	 *
	 * @since 1.8.3
	 */
	public function init() {

		// process discounts only if there's a member logged in
		if ( wc_memberships()->get_member_discounts_instance()->applying_discounts() ) {

			$this->apply_member_discounts_to_sign_up_fees = 'yes' === get_option( 'wc_memberships_enable_subscriptions_sign_up_fees_discounts', 'no' );

			// make sure the price of subscription renewal cart items is honoured (i.e. not discounted)
			add_action( 'woocommerce_before_calculate_totals',                     array( $this, 'disable_price_adjustments_for_renewal' ), 11 );
			add_action( 'wc_memberships_discounts_enable_price_adjustments',       array( $this, 'disable_price_adjustments_for_renewal' ), 11 );
			add_action( 'wc_memberships_discounts_enable_price_html_adjustments',  array( $this, 'disable_price_adjustments_for_renewal' ), 11 );

			// make sure the subscription product HTML price is right when discounted
			add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'get_subscription_product_price_html' ), 999, 2 );
			add_filter( 'wc_memberships_get_price_html_after_discount',   array( $this, 'handle_subscription_product_discounted_price_html' ), 10, 3 );
			add_filter( 'wc_memberships_get_price_html_before_discount',  array( $this, 'handle_subscription_product_discounted_price_html' ), 10, 3 );

			// make sure that product sign ups are handled according to discount setting
			add_filter( 'woocommerce_subscriptions_product_sign_up_fee', array( $this, 'maybe_adjust_product_sign_up_fee' ), 999, 2 );
		}
	}


	/**
	 * Filter the subscription product price string.
	 *
	 * TODO this method is a bit hacky and will require an update at the first possible chance as it overwrites the HTML price for Subscription products, not a good practice {FN 2017-04-19}
	 * @see \WC_Memberships_Integration_Subscriptions_Discounts::handle_subscription_product_discounted_price_html()
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 *
	 * @param string $html_price the price HTML
	 * @param \WC_Product_Subscription|\WC_Product_Variable_Subscription $product a subscription product
	 * @return string HTML
	 */
	public function get_subscription_product_price_html( $html_price, $product ) {

		// execute only on subscription products that have active member discounts
		if (      $product->is_type( array( 'subscription', 'variable-subscription', 'subscription_variation' ) )
		     && ! wc_memberships()->get_member_discounts_instance()->product_is_on_sale_before_discount( $product )
		     &&   wc_memberships()->get_rules_instance()->product_has_member_discount( $product->get_id() ) ) {

			do_action( 'wc_memberships_discounts_disable_price_adjustments' );

			$price_before_discount = $product->get_price();

			do_action( 'wc_memberships_discounts_enable_price_adjustments' );

			$price_after_discount  = $product->get_price();

			if ( $price_before_discount !== $price_after_discount ) {

				if ( 'variable-subscription' === $product->get_type() ) {

					// with variable subscription product we need to insert the before price after the "From:" string
					$from_text = SV_WC_Product_Compatibility::wc_get_price_html_from_text( $product );

					if ( SV_WC_Helper::str_starts_with( $html_price, $from_text ) || ( is_rtl() && SV_WC_Helper::str_ends_with( $html_price, $from_text ) ) ) {

						// strip the "From: " text from the price HTML string
						$html_price = str_replace( $from_text, '', $html_price );
						// for sanity remove the prices too, before reinserting them
						$html_price = str_replace( wc_price( $price_before_discount ), '', $html_price );
						$html_price = str_replace( wc_price( $price_after_discount ), '', $html_price );
						// rebuild the HTML string with the strikethrough discount text
						$html_price = $from_text . ' ' . '<del>' . wc_price( $price_before_discount ) . '</del> ' . wc_price( $price_after_discount ) . $html_price;
					}

				} else {

					$html_price = '<del>' . wc_price( $price_before_discount ) . '</del> ' . $html_price;
				}
			}
		}

		return $html_price;
	}


	/**
	 * Ensures there's no repeated string in subscription products that have discounts applied.
	 *
	 * @internal
	 *
	 * @since 1.8.0
	 *
	 * @param string $price_html the price HTML (before or after memberships discounts)
	 * @param \WC_Product $product the product, which might be a subscription product
	 * @param string $original_price_html the original price HTML
	 * @return string HTML
	 */
	public function handle_subscription_product_discounted_price_html( $price_html, $product, $original_price_html ) {
		return $product->is_type( array( 'subscription', 'subscription_variation' ) ) ? $original_price_html : $price_html;
	}


	/**
	 * Do not discount the price of subscription renewal items in the cart
	 *
	 * If the cart contains a renewal (which will be the entire contents of the cart,
	 * because it can only contain a renewal), disable the discounts applied
	 * by @see WC_Memberships_Member_Discounts::enable_price_adjustments() because
	 * we want to honour the renewal price.
	 *
	 * However, we also only want to disable prices for the renewal cart items only,
	 * not other products which should be discounted which may be displayed outside
	 * the cart, so we need to be selective about when we disable the price adjustments
	 * by checking a mix of cart/checkout constants and hooks to see if we're in
	 * something relating to the cart or not.
	 *
	 * @internal
	 *
	 * @since 1.6.1
	 */
	public function disable_price_adjustments_for_renewal() {

		if ( function_exists( 'wcs_cart_contains_renewal' ) && false !== wcs_cart_contains_renewal() ) {

			$disable_price_adjustments = false;

			if ( defined( 'WOOCOMMERCE_CHECKOUT' ) || is_checkout() || is_cart() ) {
				$disable_price_adjustments = true;
			} elseif ( did_action( 'woocommerce_before_mini_cart' ) > did_action( 'woocommerce_after_mini_cart' ) ) {
				$disable_price_adjustments = true;
			}

			if ( $disable_price_adjustments ) {
				do_action( 'wc_memberships_discounts_disable_price_adjustments' );
				do_action( 'wc_memberships_discounts_disable_price_html_adjustments' );
			}
		}
	}


	/**
	 * Add option to product settings
	 *
	 * Filters product settings fields and add a checkbox
	 * to let user choose to enable discounts for subscriptions sign up fees
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param $product_settings
	 * @return array
	 */
	public function enable_discounts_to_sign_up_fees( $product_settings ) {

		$new_option = array(
			array(
				'type'    => 'checkbox',
				'id'      => 'wc_memberships_enable_subscriptions_sign_up_fees_discounts',
				'name'    => __( 'Discounts apply to subscriptions sign up fees', 'woocommerce-memberships' ),
				'desc'    => __( 'If enabled, membership discounts will also apply to sign up fees of subscription products.', 'woocommerce-memberships' ),
				'default' => 'no',
			),
		);

		array_splice( $product_settings, 2, 0, $new_option );

		return $product_settings;
	}


	/**
	 * Maybe filter the sign up fee for handling member discounts.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param float|int $sign_up_fee the sign up fee which is probably discounted
	 * @param \WC_Product_Subscription $subscription_product the subscription product the sign up fee is for
	 * @return float|int
	 */
	public function maybe_adjust_product_sign_up_fee( $sign_up_fee, $subscription_product ) {

		$subscription_product_id = $subscription_product->get_id();
		$cache_key               = $subscription_product_id . ':' . $sign_up_fee;

		if ( ! isset( $this->sign_up_fee[ $cache_key ] ) ) {
			if ( $this->apply_member_discounts_to_sign_up_fees && wc_memberships()->get_member_discounts_instance()->user_has_member_discount( $subscription_product ) ) {
				// this is discounted already as it passed through price filters in WC_Memberships_Member_Discounts
				$this->sign_up_fee[ $cache_key ] = $sign_up_fee;
			} else {
				// since the sign up comes here already discounted, a workaround is to reverse the discount, based on the discounted price and discount rules that produced such price
				$this->sign_up_fee[ $cache_key ] = $this->get_original_sign_up_fee( $sign_up_fee, $subscription_product, get_current_user_id() );
			}
		}

		return $this->sign_up_fee[ $cache_key ];
	}


	/**
	 * Get the original sign up fee.
	 *
	 * Note: if you need to open this method to public, rather move it to the members discount main class so it can work with any original price.
	 *
	 * This is essentially a reversed discounted price method:
	 * @see \WC_Memberships_Member_Discounts::get_discounted_price()
	 *
	 * @since 1.8.1
	 *
	 * @param float $discounted_sign_up_fee the discounted sign up fee we need to retrieve the original of
	 * @param \WC_Product_Subscription $subscription_product the product the sign up fee is for
	 * @param int $member_id the current logged in user (member) ID
	 * @return int|float
	 */
	private function get_original_sign_up_fee( $discounted_sign_up_fee, $subscription_product, $member_id ) {

		$original_sign_up_fee = $discounted_sign_up_fee;
		$discount_rules       = array();

		if ( $subscription_product instanceof WC_Product && $member_id > 0 ) {
			$discount_rules = wc_memberships()->get_rules_instance()->get_user_product_purchasing_discount_rules( $member_id, $subscription_product->get_id() );
		}

		if ( ! empty( $discount_rules ) ) {

			/** this filter is documented in includes/class-wc-memberships-member-discounts.php */
			$cumulative_discounts  = apply_filters( 'wc_memberships_allow_cumulative_member_discounts', true, $member_id, $subscription_product );
			$original_sign_up_fees = array();
			$original_sign_up_fee  = 0;

			// find out the discounted price for the current user
			foreach ( $discount_rules as $rule ) {

				$discount_amount = (float) $rule->get_discount_amount();

				switch ( $rule->get_discount_type() ) {

					case 'percentage':
						$original_sign_up_fee = 100 * ( $discounted_sign_up_fee / ( 100 - $discount_amount ) );
					break;

					case 'amount':
						$original_sign_up_fee = $discounted_sign_up_fee + $discount_amount;
					break;
				}

				// make sure that the lowest price gets applied and doesn't become negative
				if ( $original_sign_up_fee > $discounted_sign_up_fee ) {
					if ( false === $cumulative_discounts ) {
						$original_sign_up_fee    = max( $original_sign_up_fee, 0 );
					} else {
						$original_sign_up_fees[] = max( $original_sign_up_fee, 0 );
					}
				}
			}

			// pick the highest price
			if ( ! empty( $original_sign_up_fees ) ) {
				$original_sign_up_fee = max( $original_sign_up_fees );
			}

			// sanity check
			if ( $original_sign_up_fee <= $discounted_sign_up_fee ) {
				$original_sign_up_fee = $discounted_sign_up_fee;
			}
		}

		return $original_sign_up_fee;
	}


}
