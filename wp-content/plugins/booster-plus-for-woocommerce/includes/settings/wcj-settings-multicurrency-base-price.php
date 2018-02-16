<?php
/**
 * Booster for WooCommerce - Settings - Multicurrency Product Base Price
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$currency_from  = get_woocommerce_currency();
$all_currencies = wcj_get_currencies_names_and_symbols();
/* if ( isset( $all_currencies[ $currency_from ] ) ) {
	unset( $all_currencies[ $currency_from ] );
} */
$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_multicurrency_base_price_options',
	),
	array(
		'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_multicurrency_base_price_exchange_rate_update',
		'default'  => 'manual',
		'type'     => 'select',
		'options'  => array(
			'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
			'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
		),
		'desc'     => ( '' == apply_filters( 'booster_get_message', '', 'desc' ) ) ?
			__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
			:
			apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_multicurrency_base_price_options',
	),
	array(
		'title'    => __( 'Currencies Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_multicurrency_base_price_currencies_options',
	),
	array(
		'title'    => __( 'Total Currencies', 'woocommerce-jetpack' ),
		'id'       => 'wcj_multicurrency_base_price_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1', )
		),
	),
);
$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_to = get_option( 'wcj_multicurrency_base_price_currency_' . $i, $currency_from );
	$custom_attributes = array(
		'currency_from'        => $currency_from,
		'currency_to'          => $currency_to,
		'multiply_by_field_id' => 'wcj_multicurrency_base_price_exchange_rate_' . $i,
	);
	if ( $currency_from == $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Currency', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_multicurrency_base_price_currency_' . $i,
			'default'  => $currency_from,
			'type'     => 'select',
			'options'  => $all_currencies,
			'css'      => 'width:250px;',
		),
		array(
			'title'                    => '',
			'id'                       => 'wcj_multicurrency_base_price_exchange_rate_' . $i,
			'default'                  => 1,
			'type'                     => 'exchange_rate',
			'custom_attributes'        => array( 'step' => '0.000001', 'min'  => '0', ),
			'custom_attributes_button' => $custom_attributes,
			'css'                      => 'width:100px;',
			'value'                    => $currency_from . '/' . $currency_to,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_multicurrency_base_price_currencies_options',
	),
) );
return $settings;
