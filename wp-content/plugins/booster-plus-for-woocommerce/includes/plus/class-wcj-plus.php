<?php
/**
 * Booster for WooCommerce - Plus
 *
 * @version 3.0.0
 * @since   3.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Plus' ) ) :

class WCJ_Plus {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function __construct() {
		require_once( 'class-wcj-plus-functions.php' );
		require_once( 'class-wcj-plus-filters.php' );
		require_once( 'class-wcj-plus-site-key-section.php' );
		require_once( 'class-wcj-plus-site-key-manager.php' );
	}

}

endif;

return new WCJ_Plus();
