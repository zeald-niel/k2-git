<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Numbering
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => strtoupper( $invoice_type['desc'] ),
			'type'     => 'title',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_options',
		),
		array(
			'title'    => __( 'Sequential', 'woocommerce-jetpack' ),
			'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_sequential_enabled',
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'title'    => __( 'Counter', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_counter',
			'default'  => 1,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Counter Width', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_counter_width',
			'default'  => 0,
			'type'     => 'number',
		),
		array(
			'title'    => __( 'Prefix', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_prefix',
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:300px',
		),
		array(
			'title'    => __( 'Suffix', 'woocommerce-jetpack' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_suffix',
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:300px',
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_numbering_options',
		),
	) );
}
return $settings;
