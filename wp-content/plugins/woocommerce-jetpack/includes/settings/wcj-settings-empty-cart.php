<?php
/**
 * Booster for WooCommerce Settings - Empty Cart Button
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'desc'     => __( 'You can also use <strong>[wcj_empty_cart_button]</strong> shortcode to place the button anywhere on your site.', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_empty_cart_customization_options',
	),
	array(
		'title'    => __( 'Empty Cart Button Text', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_text',
		'default'  => 'Empty Cart',
		'type'     => 'text',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Wrapping DIV style', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Style for the button\'s div. Default is "float: right;"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_div_style',
		'default'  => 'float: right;',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Button position on the Cart page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_position',
		'default'  => 'woocommerce_after_cart',
		'type'     => 'select',
		'options'  => array(
			'disable'                         => __( 'Do not add', 'woocommerce-jetpack' ),
			'woocommerce_after_cart'          => __( 'After Cart', 'woocommerce-jetpack' ),
			'woocommerce_before_cart'         => __( 'Before Cart', 'woocommerce-jetpack' ),
			'woocommerce_proceed_to_checkout' => __( 'After Proceed to Checkout button', 'woocommerce-jetpack' ),
			'woocommerce_after_cart_totals'   => __( 'After Cart Totals', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Button position on the Checkout page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_checkout_position',
		'default'  => 'disable',
		'type'     => 'select',
		'options'  => array(
			'disable'                                       => __( 'Do not add', 'woocommerce-jetpack' ),
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
	),
	array(
		'title'    => __( 'Confirmation', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_confirmation',
		'default'  => 'no_confirmation',
		'type'     => 'select',
		'options'  => array(
			'no_confirmation'         => __( 'No confirmation', 'woocommerce-jetpack' ),
			'confirm_with_pop_up_box' => __( 'Confirm by pop up box', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Confirmation Text (if enabled)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_empty_cart_confirmation_text',
		'default'  => __( 'Are you sure?', 'woocommerce-jetpack' ),
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_empty_cart_customization_options',
	),
);
