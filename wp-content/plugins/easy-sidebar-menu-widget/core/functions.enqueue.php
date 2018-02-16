<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'easy_sidebar_menu_widget_enqueue' );

function easy_sidebar_menu_widget_enqueue(){
	wp_register_style( 'easy-sidebar-menu-widget-css', plugins_url( 'assets/css/easy-sidebar-menu-widget.css' , dirname(__FILE__) )  );

	wp_register_script(
		'jquery-easy-sidebar-menu-widget',
		plugins_url( 'assets/js/jquery.easy-sidebar-menu-widget.min.js' , dirname(__FILE__) ),
		array( 'jquery' ),
		'',
		true
	);
	wp_enqueue_style( 'easy-sidebar-menu-widget-css' );

	wp_enqueue_script('jquery-easy-sidebar-menu-widget');
}
add_action( 'admin_enqueue_scripts', 'easy_sidebar_menu_widget_enqueue_admin' );

function easy_sidebar_menu_widget_enqueue_admin(){
	wp_register_style( 'easy-sidebar-menu-widget-admin', plugins_url( 'assets/css/easy-sidebar-menu-admin.css' , dirname(__FILE__) )  );
	wp_enqueue_style( 'easy-sidebar-menu-widget-admin' );
}
?>
