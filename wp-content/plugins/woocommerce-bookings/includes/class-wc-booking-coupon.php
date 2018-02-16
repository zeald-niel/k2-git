<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Booking_Coupon class.
 */
class WC_Booking_Coupon {

	/**
	 * Holds discount amounts for each applied coupon. Keys are coupon code,
	 * values are total discount amounts.
	 *
	 * @var array
	 */
	private $amounts = array();

	/**
	 * Holds booking items with coupon applied.
	 *
	 * @var array
	 */
	private $already_applied = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_coupon_discount_types', array( $this, 'add_coupon_type' ) );
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_coupon_valid' ), 10, 2 );
		add_filter( 'woocommerce_get_discounted_price', array( $this, 'apply_discount' ), 10, 3 );
		add_filter( 'woocommerce_coupon_discount_amount_html', array( $this, 'discount_amount_html' ), 10, 2 );
	}

	/**
	 * Adds a new coupon type that allows a discount per person booked.
	 *
	 * @param  array $types Coupon types
	 * @return array        Altered coupon types with booking_person added
	 */
	public function add_coupon_type( $types ) {
		$types['booking_person'] = esc_html__( 'Booking Person Discount (Amount Off Per Person)', 'woocommerce-bookings' );
		return $types;
	}

	/**
	 * Looks through our cart to see if we actually have a booking product
	 * before applying our coupon.
	 *
	 * @param bool      $is_valid  Whether a given coupon is valid
	 * @param WC_Coupon $wc_coupon Coupon object
	 *
	 * @return bool Returns true if coupon is valid
	 */
	public function is_coupon_valid( $is_valid, $wc_coupon ) {
		if ( 'booking_person' !== self::get_coupon_prop( $wc_coupon, 'discount_type' ) ) {
			return $is_valid;
		}

		if ( ! WC()->cart->is_empty() ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product = wc_get_product( $cart_item['product_id'] );
				if ( is_a( $product, 'WC_Product_Booking' ) && $product->has_persons() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Applies the discount to the cart.
	 *
	 * @param mixed   $original_price Original price
	 * @param array   $cart_item      Cart item
	 * @param WC_Cart $cart           Cart object
	 *
	 * @return float Discounted price
	 */
	public function apply_discount( $original_price, $cart_item, $cart ) {
		$product_id = is_callable( array( $cart_item['data'], 'get_id' ) ) ? $cart_item['data']->get_id() : $cart_item['data']->id;
		$product    = wc_get_product( $product_id );

		if ( ! $product->is_type( 'booking' ) ) {
			return $original_price;
		}

		$price = $original_price;
		if ( ! empty( WC()->cart->applied_coupons ) ) {
			foreach ( WC()->cart->applied_coupons as $code ) {
				$coupon = new WC_Coupon( $code );
				if ( $coupon->is_valid() ) {

					if ( in_array( $cart_item['booking']['_booking_id'], $this->already_applied ) ) {
						continue;
					}

					if ( 'booking_person' !== self::get_coupon_prop( $coupon, 'discount_type' ) ) {
						continue;
					}

					$discount_amount = ( $price < self::get_coupon_prop( $coupon, 'amount' ) ) ? $price : self::get_coupon_prop( $coupon, 'amount' );
					$total_persons   = array_sum( $cart_item['booking']['_persons'] );
					$discount_amount = $discount_amount * $total_persons;

					$price = $price - $discount_amount;
					if ( $price < 0 ) {
						$price = 0;
					}

					if ( empty( $this->amounts[ $code ] ) ) {
						$this->amounts[ $code ] = 0;
					}
					$this->amounts[ $code ] += $discount_amount;

					WC()->cart->discount_cart = WC()->cart->discount_cart + $discount_amount;
					$this->already_applied[]  = $cart_item['booking']['_booking_id'];
				}
			}
		}

		return $price;
	}

	/**
	 * The formatted string of how much we saved per coupon code.
	 *
	 * @param string    $discount_html Discount HTML
	 * @param WC_Coupon $coupon        Coupon object
	 *
	 * @return string Discount HTML
	 */
	public function discount_amount_html( $discount_html, $coupon ) {
		if ( 'booking_person' !== self::get_coupon_prop( $coupon, 'discount_type' ) ) {
			return $discount_html;
		}

		$discount_html = '-' . wc_price( $this->amounts[ self::get_coupon_prop( $coupon, 'code' ) ] );
		return $discount_html;
	}

	/**
	 * Get coupon property with compatibility check on order getter introduced
	 * in WC 3.0.
	 *
	 * @since 1.10.3
	 *
	 * @param WC_Coupon $coupon Coupon object.
	 * @param string    $prop   Property name.
	 *
	 * @return mixed Property value
	 */
	public static function get_coupon_prop( $coupon, $prop ) {
		$getter = array( $coupon, 'get_' . $prop );
		return is_callable( $getter ) ? call_user_func( $getter ) : $coupon->{ $prop };
	}

}

new WC_Booking_Coupon();
