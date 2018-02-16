<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://zeald.com
 * @since             1.0.0
 * @package           Zeald_Ssl_Warning
 *
 * @wordpress-plugin
 * Plugin Name:       Zeald SSL Warning Message
 * Plugin URI:        http://zeald.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Zeald
 * Author URI:        http://zeald.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       zeald-ssl-warning
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PLUGIN_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-zeald-ssl-warning-activator.php
 */
function activate_zeald_ssl_warning() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-zeald-ssl-warning-activator.php';
	Zeald_Ssl_Warning_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-zeald-ssl-warning-deactivator.php
 */
function deactivate_zeald_ssl_warning() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-zeald-ssl-warning-deactivator.php';
	Zeald_Ssl_Warning_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_zeald_ssl_warning' );
register_deactivation_hook( __FILE__, 'deactivate_zeald_ssl_warning' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-zeald-ssl-warning.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_zeald_ssl_warning() {

	$plugin = new Zeald_Ssl_Warning();
	$plugin->run();

}
run_zeald_ssl_warning();
