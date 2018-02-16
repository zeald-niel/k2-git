<?php
/**
 * Booster for WooCommerce - Settings - SKU
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    tags (check SKU plugin); template: '{category_prefix}{tag_prefix}{prefix}{sku_number}{suffix}{tag_suffix}{category_suffix}{variation_suffix}'
 * @todo    add "Sequential Number Generation - By Category" to SKU plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'SKU Format Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_sku_format_options',
	),
	array(
		'title'    => __( 'Number Generation', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_number_generation',
		'default'  => 'product_id',
		'type'     => 'select',
		'options'  => array(
			'product_id' => __( 'From product ID', 'woocommerce-jetpack' ),
			'sequential' => __( 'Sequential', 'woocommerce-jetpack' ),
			'hash_crc32' => __( 'Pseudorandom - Hash (max 10 digits)', 'woocommerce-jetpack' ),
		),
		'desc_tip' => __( 'Possible values: from product ID, sequential or pseudorandom.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Sequential Number Generation - Counter', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_number_generation_sequential',
		'default'  => 1,
		'type'     => 'number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
	array(
		'title'    => __( 'Sequential Number Generation - By Category', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_number_generation_sequential_by_cat',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Prefix', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_prefix',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Minimum Number Length', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_minimum_number_length',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Suffix', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_suffix',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Template', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replaced values:', 'woocommerce-jetpack' ) . '{category_prefix}, {category_suffix}, {prefix}, {suffix}, {variation_suffix}, {sku_number}.',
		'id'       => 'wcj_sku_template',
		'default'  => '{category_prefix}{prefix}{sku_number}{suffix}{category_suffix}{variation_suffix}',
		'type'     => 'text',
		'css'      => 'width:99%;',
	),
	array(
		'title'    => __( 'Variable Products Variations', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Please note, that on new variable product creation, variations will get same SKUs as parent product, and if you want variations to have different SKUs, you will need to run "Autogenerate SKUs" tool manually.' ),
		'id'       => 'wcj_sku_variations_handling',
		'default'  => 'as_variable',
		'type'     => 'select',
		'options'  => array(
			'as_variable'             => __( 'SKU same as parent\'s product', 'woocommerce-jetpack' ),
			'as_variation'            => __( 'Generate different SKU for each variation', 'woocommerce-jetpack' ),
			'as_variable_with_suffix' => __( 'SKU same as parent\'s product + variation letter suffix', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_sku_format_options',
	),
);
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Categories Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_sku_categories_options',
	),
) );
$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ){
	foreach ( $product_categories as $product_category ) {
		$settings = array_merge( $settings, array(
			array(
				'title'    => $product_category->name,
				'desc'     => __( 'Prefix', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_prefix_cat_' . $product_category->term_id,
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
			),
			array(
				'title'    => '',
				'desc'     => __( 'Suffix', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_suffix_cat_' . $product_category->term_id,
				'default'  => '',
				'type'     => 'text',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Counter (Sequential)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sku_counter_cat_' . $product_category->term_id,
				'default'  => 1,
				'type'     => 'number',
			),
		) );
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_sku_categories_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'More Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_sku_more_options',
	),
	array(
		'title'    => __( 'Automatically Generate SKU for New Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Alternatively you can use Autogenerate SKUs tool.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_new_products_generate_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Allow Duplicate SKUs', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_allow_duplicates_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Search by SKU', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Add product searching by SKU on frontend.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_search_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Generate SKUs Only for Products with Empty SKU', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_generate_only_for_empty_sku',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Add SKU to Customer Emails', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_add_to_customer_emails',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable SKUs', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_sku_disabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_sku_more_options',
	),
) );
return $settings;
