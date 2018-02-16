<?php

/*
  Plugin Name: Easy Sidebar Menu Widget
  Plugin URI: https://wordpress.org/plugins/easy-sidebar-menu-widget/
  Description: Add sidebar dropdown menu via widget.
  Author: phpbits
  Version: 1.0
  Author URI: https://phpbits.net/

  Text Domain: easy-sidebar-menu-widget
 */

//avoid direct calls to this file

if (!function_exists('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}
define( 'EASY_SIDEBAR_MENU_WIDGET_VERSION', '1.0' );

/*##################################
  REQUIRE
################################## */
require_once( dirname( __FILE__ ) . '/core/functions.enqueue.php' );
require_once( dirname( __FILE__ ) . '/core/functions.widget.php' );
require_once( dirname( __FILE__ ) . '/core/functions.menu.walker.php' );
require_once( dirname( __FILE__ ) . '/core/functions.notices.php' );


/**
 * Install
 *
 * Runs on plugin install to populates the settings fields for those plugin
 * pages.
 */
if( !function_exists( 'easy_sidebar_menu_widget_install' ) ){
	register_activation_hook( __FILE__, 'easy_sidebar_menu_widget_install' );
	function easy_sidebar_menu_widget_install() {
		if( !get_option( 'easy_sidebar_menu_widget_installDate' ) ){
			add_option( 'easy_sidebar_menu_widget_installDate', date( 'Y-m-d h:i:s' ) );
		}
	}
}
?>
