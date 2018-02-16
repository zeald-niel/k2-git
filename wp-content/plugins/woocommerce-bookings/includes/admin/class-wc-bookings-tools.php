<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking tools
 */
class WC_Bookings_Tools {
	public static $id = 'bookings-tools';

	/*
	 * Clean person types tool.
	 */
	public static function clean_person_types() {
		global $wpdb;

		$unused_types = WC_Product_Booking_Data_Store_CPT::get_person_types_ids();
		$booking_products = WC_Bookings_Admin::get_booking_products();

		if ( class_exists( 'WC_Logger' ) ) {
			$logger = new WC_Logger();
		} else {
			$logger = WC()->logger();

		}

		$logger->add( self::$id, 'Called clean_person_types tool.' );

		foreach ( $booking_products as $product ) {
			$bookings = WC_Bookings_Controller::get_bookings_for_product( $product->get_id(), array() );

			$used_types = array();

			// get the person types from the product
			$used_types = array_merge( $used_types, array_keys( $product->get_person_types() ) );

			// get the person types from all the bookings related to the product
			foreach ( $bookings as $booking ) {
				$used_types = array_unique( array_merge( $used_types, array_keys( $booking->get_person_counts() ) ) );
			}

			$unused_types = array_diff( $unused_types, $used_types );
		}

		$logger->add( self::$id, 'Found ' . count( $unused_types ) . ' unused person types. Removing them from DB.' );

		foreach ( $unused_types as $unused_id ) {
			$wpdb->delete(
				$wpdb->posts,
				array(
					'ID' => $unused_id,
					'post_type' => 'bookable_person',
				)
			);
		}
	}
}
