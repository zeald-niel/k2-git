<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple structure for a MVP "working" accommodation product class.
 * Only to be used if the accommodtion plugin is missing so we don't fatal.
 */
class WC_Product_Accommodation_Booking extends WC_Product_Skeleton_Booking {
	public function __construct( $product ) {
		$this->product_type = 'accommodation-booking';
		parent::__construct( $product );
	}

	public function bookings_addon_title() {
		return __( 'Accommodation booking', 'woocommerce-bookings' );
	}
}
