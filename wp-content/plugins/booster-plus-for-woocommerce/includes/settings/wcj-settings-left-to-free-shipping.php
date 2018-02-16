<?php
/**
 * Booster for WooCommerce - Settings - Left to Free Shipping
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Left to Free Shipping Info Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you enable info on cart, mini cart and checkout pages.', 'woocommerce-jetpack' )
			. '<br>' . __( 'You can also use <em>Booster - Left to Free Shipping</em> widget, <em>[wcj_get_left_to_free_shipping content=""]</em> shortcode or <em>wcj_get_left_to_free_shipping( $content );</em> function.', 'woocommerce-jetpack' )
			. '<br>' . __( 'In content you can use: <em>%left_to_free%</em> and <em>%free_shipping_min_amount%</em> shortcodes.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_options',
	),
	array(
		'title'    => __( 'Info on Cart', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_enabled_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML and/or shortcodes (e.g. [wcj_wpml]) here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_content_cart',
		'default'  => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:30%;min-width:300px;height:100px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_position_cart',
		'default'  => 'woocommerce_after_cart_totals',
		'type'     => 'select',
		'options'  => wcj_get_cart_filters(),
		'css'      => 'width:250px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position Order (Priority)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_priority_cart',
		'default'  => 10,
		'type'     => 'number',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Info on Mini Cart', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_enabled_mini_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
	),
	array(
		'title'    => '',
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML and/or shortcodes (e.g. [wcj_wpml]) here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_content_mini_cart',
		'default'  => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:30%;min-width:300px;height:100px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_position_mini_cart',
		'default'  => 'woocommerce_after_mini_cart',
		'type'     => 'select',
		'options'  => array(
			'woocommerce_before_mini_cart'                    => __( 'Before mini cart', 'woocommerce-jetpack' ),
			'woocommerce_widget_shopping_cart_before_buttons' => __( 'Before buttons', 'woocommerce-jetpack' ),
			'woocommerce_after_mini_cart'                     => __( 'After mini cart', 'woocommerce-jetpack' ),
		),
		'css'      => 'width:250px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position Order (Priority)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_priority_mini_cart',
		'default'  => 10,
		'type'     => 'number',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Info on Checkout', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_enabled_checkout',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
	),
	array(
		'title'    => '',
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML and/or shortcodes (e.g. [wcj_wpml]) here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_content_checkout',
		'default'  => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:30%;min-width:300px;height:100px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_position_checkout',
		'default'  => 'woocommerce_checkout_after_order_review',
		'type'     => 'select',
		'options'  => array(
			'woocommerce_before_checkout_form'              => __( 'Before checkout form', 'woocommerce-jetpack' ),
			'woocommerce_checkout_before_customer_details'  => __( 'Before customer details', 'woocommerce-jetpack' ),
			'woocommerce_checkout_billing'                  => __( 'Billing', 'woocommerce-jetpack' ),
			'woocommerce_checkout_shipping'                 => __( 'Shipping', 'woocommerce-jetpack' ),
			'woocommerce_checkout_after_customer_details'   => __( 'After customer details', 'woocommerce-jetpack' ),
			'woocommerce_checkout_before_order_review'      => __( 'Before order review', 'woocommerce-jetpack' ),
			'woocommerce_checkout_order_review'             => __( 'Order review', 'woocommerce-jetpack' ),
			'woocommerce_checkout_after_order_review'       => __( 'After order review', 'woocommerce-jetpack' ),
			'woocommerce_after_checkout_form'               => __( 'After checkout form', 'woocommerce-jetpack' ),
		),
		'css'      => 'width:250px;',
	),
	array(
		'title'    => '',
		'desc'     => __( 'Position Order (Priority)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_priority_checkout',
		'default'  => 10,
		'type'     => 'number',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Message on Free Shipping Reached', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML and/or shortcodes (e.g. [wcj_wpml]) here.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Set empty to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_left_to_free_info_content_reached',
		'default'  => __( 'You have Free delivery', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'width:30%;min-width:300px;height:100px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_left_to_free_info_options',
	),
);
