<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://zeald.com
 * @since      1.0.0
 *
 * @package    Zeald_Ssl_Warning
 * @subpackage Zeald_Ssl_Warning/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Zeald_Ssl_Warning
 * @subpackage Zeald_Ssl_Warning/includes
 * @author     Zeald <info@zeald.com>
 */
class Zeald_Ssl_Warning_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'zeald-ssl-warning',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
