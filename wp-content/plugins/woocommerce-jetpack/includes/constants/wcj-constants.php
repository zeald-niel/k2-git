<?php
/**
 * Booster for WooCommerce Constants
 *
 * @version 3.2.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'WCJ_WC_VERSION' ) ) {
	/**
	 * WooCommerce version.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_WC_VERSION', get_option( 'woocommerce_version', null ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3' ) ) {
	/**
	 * WooCommerce version - is below version 3.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_IS_WC_VERSION_BELOW_3', version_compare( WCJ_WC_VERSION, '3.0.0', '<' ) );
}

if ( ! defined( 'WCJ_IS_WC_VERSION_BELOW_3_2_0' ) ) {
	/**
	 * WooCommerce version - is below version 3.2.0.
	 *
	 * @version 3.2.0
	 * @since   3.2.0
	 */
	define( 'WCJ_IS_WC_VERSION_BELOW_3_2_0', version_compare( WCJ_WC_VERSION, '3.2.0', '<' ) );
}

if ( ! defined( 'WCJ_PRODUCT_GET_PRICE_FILTER' ) ) {
	/**
	 * Price filters - price.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_PRODUCT_GET_PRICE_FILTER', ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_price' : 'woocommerce_product_get_price' ) );
}

if ( ! defined( 'WCJ_PRODUCT_GET_SALE_PRICE_FILTER' ) ) {
	/**
	 * Price filters - sale price.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_PRODUCT_GET_SALE_PRICE_FILTER', ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_sale_price' : 'woocommerce_product_get_sale_price' ) );
}

if ( ! defined( 'WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER' ) ) {
	/**
	 * Price filters - regular price.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	define( 'WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER', ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_regular_price' : 'woocommerce_product_get_regular_price' ) );
}

if ( ! defined( 'WCJ_SESSION_TYPE' ) ) {
	/**
	 * Session type.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	define( 'WCJ_SESSION_TYPE', ( 'yes' === get_option( 'wcj_general_enabled', 'no' ) ? get_option( 'wcj_general_advanced_session_type', 'standard' ) : 'standard' ) );
}
