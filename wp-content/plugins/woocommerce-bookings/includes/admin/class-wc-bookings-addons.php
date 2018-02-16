<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking Addons Screen.
 */
class WC_Bookings_Admin_Add_Ons {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_addons_sections', array( $this, 'add_section' ) );
	}

	/**
	 * Adds a new section for "bookings" add-ons
	 */
	public function add_section( $sections ) {
		$sections['bookings'] = new stdClass;
		$sections['bookings']->title = wc_clean( __( 'Bookings', 'woocommerce-bookings' ) );
		$sections['bookings']->endpoint = 'http://d3t0oesq8995hv.cloudfront.net/bookings-addons.json';
		return $sections;
	}
}

new WC_Bookings_Admin_Add_Ons();
