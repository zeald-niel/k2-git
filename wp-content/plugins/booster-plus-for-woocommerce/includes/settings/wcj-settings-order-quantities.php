<?php
/**
 * Booster for WooCommerce - Settings - Order Min/Max Quantities
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 * @todo    maybe generate settings in loop ( min / max )
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Minimum Quantity Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_quantities_min_options',
	),
	array(
		'title'    => __( 'Minimum Quantity', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_min_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_min_cart_total_quantity',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Message - Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%min_cart_total_quantity%', '%cart_total_quantity%' ) ),
		'id'       => 'wcj_order_quantities_min_cart_total_message',
		'default'  => __( 'Minimum allowed order quantity is %min_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Per Item Quantity', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_min_per_item_quantity',
		'default'  => 0,
		'type'     => 'number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => ( '' === apply_filters( 'booster_get_message', '', 'readonly' ) ? array( 'min' => 0 ) : apply_filters( 'booster_get_message', '', 'readonly' ) ),
	),
	array(
		'title'    => __( 'Message - Per Item Quantity', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%product_title%', '%min_per_item_quantity%', '%item_quantity%' ) ),
		'id'       => 'wcj_order_quantities_min_per_item_message',
		'default'  => __( 'Minimum allowed quantity for %product_title% is %min_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_quantities_min_options',
	),
	array(
		'title'    => __( 'Maximum Quantity Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_quantities_max_options',
	),
	array(
		'title'    => __( 'Maximum Quantity', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_max_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_max_cart_total_quantity',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Message - Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%max_cart_total_quantity%', '%cart_total_quantity%' ) ),
		'id'       => 'wcj_order_quantities_max_cart_total_message',
		'default'  => __( 'Maximum allowed order quantity is %max_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Per Item Quantity', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_max_per_item_quantity',
		'default'  => 0,
		'type'     => 'number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => ( '' === apply_filters( 'booster_get_message', '', 'readonly' ) ? array( 'min' => 0 ) : apply_filters( 'booster_get_message', '', 'readonly' ) ),
	),
	array(
		'title'    => __( 'Message - Per Item Quantity', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%product_title%', '%max_per_item_quantity%', '%item_quantity%' ) ),
		'id'       => 'wcj_order_quantities_max_per_item_message',
		'default'  => __( 'Maximum allowed quantity for %product_title% is %max_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_quantities_max_options',
	),
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_quantities_general_options',
	),
	array(
		'title'    => __( 'Enable Cart Notices', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_cart_notice_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Stop Customer from Seeing Checkout on Wrong Quantities', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will be redirected to cart page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_stop_from_seeing_checkout',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_quantities_general_options',
	),
);
