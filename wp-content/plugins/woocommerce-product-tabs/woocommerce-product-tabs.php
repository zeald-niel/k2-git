<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://nilambar.net
 * @since             1.0.0
 * @package           Woocommerce_Product_Tabs
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce Product Tabs
 * Plugin URI: https://wordpress.org/plugins/woocommerce-product-tabs/
 * Description: Custom Product Tabs for WooCommerce.
 * Version: 2.0.0
 * Author: Nilambar Sharma
 * Author URI: http://nilambar.net/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woocommerce-product-tabs
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Check if WooCommerce is active
 **/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}
// Define
define( 'WOOCOMMERCE_PRODUCT_TABS_NAME', 'Woocommerce Product Tabs' );
define( 'WOOCOMMERCE_PRODUCT_TABS_SLUG', 'woocommerce-product-tabs' );
define( 'WOOCOMMERCE_PRODUCT_TABS_BASENAME', basename( dirname( __FILE__ ) ) );
define( 'WOOCOMMERCE_PRODUCT_TABS_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'WOOCOMMERCE_PRODUCT_TABS_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
define( 'WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB', 'woo_product_tab' );
/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-product-tabs-activator.php';

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-product-tabs-deactivator.php';

/** This action is documented in includes/class-woocommerce-product-tabs-activator.php */
register_activation_hook( __FILE__, array( 'Woocommerce_Product_Tabs_Activator', 'activate' ) );

/** This action is documented in includes/class-woocommerce-product-tabs-deactivator.php */
register_deactivation_hook( __FILE__, array( 'Woocommerce_Product_Tabs_Deactivator', 'deactivate' ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-product-tabs.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woocommerce_product_tabs() {

	$plugin = new Woocommerce_Product_Tabs();
	$plugin->run();

}
run_woocommerce_product_tabs();
