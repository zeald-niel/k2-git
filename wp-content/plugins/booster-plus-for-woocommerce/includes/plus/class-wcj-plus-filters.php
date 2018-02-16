<?php
/**
 * Booster for WooCommerce - Plus - Filters
 *
 * @version 3.0.0
 * @since   3.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Plus_Filters' ) ) :

class WCJ_Plus_Filters {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function __construct() {
		add_filter( 'booster_get_message', array( $this, 'booster_get_message' ), 101 );
		add_filter( 'booster_get_option',  array( $this, 'booster_get_option' ),  101, 2 );
	}

	/**
	 * booster_get_option.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function booster_get_option( $value1, $value2 ) {
		return $value2;
	}

	/**
	 * booster_get_message.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function booster_get_message() {
		return '';
	}
}

endif;

return new WCJ_Plus_Filters();
