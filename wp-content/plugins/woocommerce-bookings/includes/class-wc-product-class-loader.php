<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookings supports optional addons like the acommodation bookings plugin.
 * If these optional addons get removed but the data still exists, fatal errors can occur.
 * This class loader will load a dummy class for these bookings if the real one is not found.
 * This prevents purchasing of these products and prevents errors.
 * A notice will also be displayed to the user.
 */
class WC_Product_Class_Loader {
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_product_classes') );
	}

	public function load_product_classes() {
		if ( ! class_exists( 'WC_Product_Accommodation_Booking' ) ) {
			include_once( 'addons/accommodation-booking.php' );
		}
	}
}

new WC_Product_Class_Loader;

/**
 * Dummy class for our 'addon' classes to load from.
 */
class WC_Product_Skeleton_Booking extends WC_Product_Booking {

	public function __construct( $product ) {
		parent::__construct( $product );
	}

	public function is_purchasable() {
		return false;
	}

	public function is_skeleton() {
		return true;
	}

	public function is_bookings_addon() {
		return true;
	}

}
