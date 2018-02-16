<?php
/**
 * Booster for WooCommerce - Shortcodes - General
 *
 * @version 2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_General_Shortcodes' ) ) :

class WCJ_General_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.9.1
	 */
	function __construct() {

		$this->the_shortcodes = array(
			'wcj_site_url',
			'wcj_current_date',
			'wcj_current_time',
			'wcj_current_datetime',
			'wcj_current_timestamp',
//			'wcj_image',
			'wcj_cart_items_total_weight',
			'wcj_cart_items_total_quantity',
			'wcj_cart_total',
			'wcj_wpml',
			'wcj_wpml_translate',
			'wcj_country_select_drop_down_list',
			'wcj_currency_select_drop_down_list',
			'wcj_currency_select_radio_list',
			'wcj_currency_select_link_list',
			'wcj_text',
			'wcj_tcpdf_pagebreak',
			'wcj_get_left_to_free_shipping',
			'wcj_wholesale_price_table',
			'wcj_customer_billing_country',
			'wcj_customer_shipping_country',
			'wcj_customer_meta',
			'wcj_empty_cart_button',
			'wcj_currency_exchange_rate',
			'wcj_currency_exchange_rates_table',
		);

		$this->the_atts = array(
			'date_format'           => get_option( 'date_format' ),
			'time_format'           => get_option( 'time_format' ),
			'datetime_format'       => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			'lang'                  => '',
			'form_method'           => 'post',//'get',
			'class'                 => '',
			'style'                 => '',
			'countries'             => '',
			'currencies'            => '',
			'content'               => '',
			'heading_format'        => 'from %level_min_qty% pcs.',
			'before_level_max_qty'  => '-',
			'last_level_max_qty'    => '+',
			'replace_with_currency' => 'no',
			'hide_if_zero_quantity' => 'no',
			'table_format'          => 'horizontal',
			'key'                   => '',
			'full_country_name'     => 'yes',
			'multiply_by'           => 1,
			'default'               => '',
			'from'                  => '',
			'to'                    => '',
		);

		parent::__construct();

	}

	/**
	 * wcj_site_url.
	 *
	 * @version 2.9.1
	 * @since   2.9.1
	 */
	function wcj_site_url( $atts ) {
		return site_url();
	}

	/**
	 * wcj_currency_exchange_rate.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) add similar function
	 */
	function wcj_currency_exchange_rate( $atts ) {
		return ( '' != $atts['from'] && '' != $atts['to'] ) ? get_option( 'wcj_currency_exchange_rates_' . sanitize_title( $atts['from'] . $atts['to'] ) ) : '';
	}

	/**
	 * wcj_currency_exchange_rates_table.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    (maybe) add similar function
	 */
	function wcj_currency_exchange_rates_table( $atts ) {
		$all_currencies = WCJ()->modules['currency_exchange_rates']->get_all_currencies_exchange_rates_settings();
		$table_data = array();
		foreach ( $all_currencies as $currency ) {
			$table_data[] = array( $currency['title'], get_option( $currency['id'] ) );
		}
		if ( ! empty( $table_data ) ) {
			return wcj_get_table_html( $table_data, array( 'table_class' => 'wcj_currency_exchange_rates_table', 'table_heading_type' => 'vertical' ) );
		} else {
			return '';
		}
	}

	/**
	 * wcj_empty_cart_button.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_empty_cart_button( $atts ) {
		if ( ! wcj_is_module_enabled( 'empty_cart' ) ) {
			return '<p>' . sprintf( __( '"%s" module is not enabled!', 'woocommerce-jetpack' ), __( 'Empty Cart Button', 'woocommerce-jetpack' ) ) . '</p>';
		}
		return wcj_empty_cart_button_html();
	}

	/**
	 * wcj_current_time.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_current_time( $atts ) {
		return date_i18n( $atts['time_format'], current_time( 'timestamp' ) );
	}

	/**
	 * wcj_current_datetime.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_current_datetime( $atts ) {
		return date_i18n( $atts['datetime_format'], current_time( 'timestamp' ) );
	}

	/**
	 * wcj_current_timestamp.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function wcj_current_timestamp( $atts ) {
		return current_time( 'timestamp' );
	}

	/**
	 * wcj_customer_billing_country.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function wcj_customer_billing_country( $atts ) {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( '' != ( $meta = get_user_meta( $current_user->ID, 'billing_country', true ) ) ) {
				return ( 'yes' === $atts['full_country_name'] ) ? wcj_get_country_name_by_code( $meta ) : $meta;
			}
		}
		return '';
	}

	/**
	 * wcj_customer_shipping_country.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function wcj_customer_shipping_country( $atts ) {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( '' != ( $meta = get_user_meta( $current_user->ID, 'shipping_country', true ) ) ) {
				return ( 'yes' === $atts['full_country_name'] ) ? wcj_get_country_name_by_code( $meta ) : $meta;
			}
		}
		return '';
	}

	/**
	 * wcj_customer_meta.
	 *
	 * @version 2.5.8
	 * @since   2.5.8
	 */
	function wcj_customer_meta( $atts ) {
		if ( '' != $atts['key'] && is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			if ( '' != ( $meta = get_user_meta( $current_user->ID, $atts['key'], true ) ) ) {
				return $meta;
			}
		}
		return '';
	}

	/**
	 * get_shortcode_currencies.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	private function get_shortcode_currencies( $atts ) {
		// Shortcode currencies
		$shortcode_currencies = $atts['currencies'];
		if ( '' == $shortcode_currencies ) {
			$shortcode_currencies = array();
		} else {
			$shortcode_currencies = str_replace( ' ', '', $shortcode_currencies );
			$shortcode_currencies = trim( $shortcode_currencies, ',' );
			$shortcode_currencies = explode( ',', $shortcode_currencies );
		}
		if ( empty( $shortcode_currencies ) ) {
			$total_number = apply_filters( 'booster_get_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$shortcode_currencies[] = get_option( 'wcj_multicurrency_currency_' . $i );
			}
		}
		return $shortcode_currencies;
	}

	/**
	 * wcj_wholesale_price_table (global only).
	 *
	 * @version 2.5.7
	 * @since   2.4.8
	 */
	function wcj_wholesale_price_table( $atts ) {

		if ( ! wcj_is_module_enabled( 'wholesale_price' ) ) {
			return '';
		}

		// Check for user role options
		$role_option_name_addon = '';
		$user_roles = get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
		if ( ! empty( $user_roles ) ) {
			$current_user_role = wcj_get_current_user_first_role();
			foreach ( $user_roles as $user_role_key ) {
				if ( $current_user_role === $user_role_key ) {
					$role_option_name_addon = '_' . $user_role_key;
					break;
				}
			}
		}

		$wholesale_price_levels = array();
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_wholesale_price_levels_number' . $role_option_name_addon, 1 ) ); $i++ ) {
			$level_qty                = get_option( 'wcj_wholesale_price_level_min_qty' . $role_option_name_addon . '_' . $i, PHP_INT_MAX );
			$discount                 = get_option( 'wcj_wholesale_price_level_discount_percent' . $role_option_name_addon . '_' . $i, 0 );
			$wholesale_price_levels[] = array( 'quantity' => $level_qty, 'discount' => $discount, );
		}

		$data_qty              = array();
		$data_discount         = array();
		$columns_styles        = array();
		$i = -1;
		foreach ( $wholesale_price_levels as $wholesale_price_level ) {
			$i++;
			if ( 0 == $wholesale_price_level['quantity'] && 'yes' === $atts['hide_if_zero_quantity'] ) {
				continue;
			}
			$level_max_qty = ( isset( $wholesale_price_levels[ $i + 1 ]['quantity'] ) ) ? $atts['before_level_max_qty'] . ( $wholesale_price_levels[ $i + 1 ]['quantity'] - 1 ) : $atts['last_level_max_qty'];
			$data_qty[] = str_replace(
				array( '%level_qty%', '%level_min_qty%', '%level_max_qty%' ), // %level_qty% is deprecated
				array( $wholesale_price_level['quantity'], $wholesale_price_level['quantity'], $level_max_qty ),
				$atts['heading_format']
			);
			$data_discount[]         = ( 'fixed' === get_option( 'wcj_wholesale_price_discount_type', 'percent' ) )
				? '-' . wc_price( $wholesale_price_level['discount'] ) : '-' . $wholesale_price_level['discount'] . '%';
			$columns_styles[]        = 'text-align: center;';
		}

		$table_rows = array( $data_qty, $data_discount, );

		if ( 'vertical' === $atts['table_format'] ) {
			$table_rows_modified = array();
			foreach ( $table_rows as $row_number => $table_row ) {
				foreach ( $table_row as $column_number => $cell ) {
					$table_rows_modified[ $column_number ][ $row_number ] = $cell;
				}
			}
			$table_rows = $table_rows_modified;
		}

		return wcj_get_table_html( $table_rows, array( 'table_class' => 'wcj_wholesale_price_table', 'columns_styles' => $columns_styles, 'table_heading_type' => $atts['table_format'] ) );
	}

	/**
	 * wcj_currency_select_link_list.
	 *
	 * @version 2.9.0
	 * @since   2.4.5
	 */
	function wcj_currency_select_link_list( $atts, $content ) {
		$html = '';
		$shortcode_currencies = $this->get_shortcode_currencies( $atts );
		// Options
		$currencies = wcj_get_currencies_names_and_symbols( 'names' );
		$selected_currency = '';
		if ( isset( $_SESSION['wcj-currency'] ) ) {
			$selected_currency = $_SESSION['wcj-currency'];
		} else {
			$module_roles = get_option( 'wcj_multicurrency_role_defaults_roles', '' );
			if ( ! empty( $module_roles ) ) {
				$current_user_role = wcj_get_current_user_first_role();
				if ( in_array( $current_user_role, $module_roles ) ) {
					$selected_currency = get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
				}
			}
		}
		if ( '' === $selected_currency && '' != $atts['default'] ) {
			$selected_currency = $atts['default'];
		}
		$links = array();
		$first_link = '';
		$switcher_template = get_option( 'wcj_multicurrency_switcher_template', '%currency_name% (%currency_symbol%)' );
		foreach ( $shortcode_currencies as $currency_code ) {
			if ( isset( $currencies[ $currency_code ] ) ) {
				$template_replaced_values = array(
					'%currency_name%'   => $currencies[ $currency_code ],
					'%currency_code%'   => $currency_code,
					'%currency_symbol%' => wcj_get_currency_symbol( $currency_code ),
				);
				$currency_switcher_output = str_replace( array_keys( $template_replaced_values ), array_values( $template_replaced_values ), $switcher_template );
				$the_link = '<a href="' . add_query_arg( 'wcj-currency', $currency_code ) . '">' . $currency_switcher_output . '</a>';
				if ( $currency_code != $selected_currency ) {
					$links[] = $the_link;
				} else {
					$first_link = $the_link;
				}
			}
		}
		if ( '' != $first_link ) {
			$links = array_merge( array( $first_link ), $links );
		}
		$html .= implode( '<br>', $links );
		return $html;
	}

	/**
	 * wcj_get_left_to_free_shipping.
	 *
	 * @version 2.5.8
	 * @since   2.4.4
	 */
	function wcj_get_left_to_free_shipping( $atts, $content ) {
		return wcj_get_left_to_free_shipping( $atts['content'], $atts['multiply_by'] );
	}

	/**
	 * wcj_tcpdf_pagebreak.
	 *
	 * @version 2.3.7
	 * @since   2.3.7
	 */
	function wcj_tcpdf_pagebreak( $atts, $content ) {
		return '<tcpdf method="AddPage" />';
	}

	/**
	 * get_currency_selector.
	 *
	 * @version 2.9.0
	 * @since   2.4.5
	 */
	private function get_currency_selector( $atts, $content, $type = 'select' ) {
		// Start
		$html = '';
		$form_method  = $atts['form_method'];
		$class = $atts['class'];
		$style = $atts['style'];
		$html .= '<form action="" method="' . $form_method . '">';
		if ( 'select' === $type ) {
			$html .= '<select name="wcj-currency" id="wcj-currency-select" style="' . $style . '" class="' . $class . '" onchange="this.form.submit()">';
		}
		$shortcode_currencies = $this->get_shortcode_currencies( $atts );
		// Options
		$currencies = wcj_get_currencies_names_and_symbols( 'names' );
		$selected_currency = '';
		if ( isset( $_SESSION['wcj-currency'] ) ) {
			$selected_currency = $_SESSION['wcj-currency'];
		} else {
			$module_roles = get_option( 'wcj_multicurrency_role_defaults_roles', '' );
			if ( ! empty( $module_roles ) ) {
				$current_user_role = wcj_get_current_user_first_role();
				if ( in_array( $current_user_role, $module_roles ) ) {
					$selected_currency = get_option( 'wcj_multicurrency_role_defaults_' . $current_user_role, '' );
				}
			}
		}
		if ( '' === $selected_currency && '' != $atts['default'] ) {
			$selected_currency = $atts['default'];
		}
		$switcher_template = get_option( 'wcj_multicurrency_switcher_template', '%currency_name% (%currency_symbol%)' );
		foreach ( $shortcode_currencies as $currency_code ) {
			if ( isset( $currencies[ $currency_code ] ) ) {
				$template_replaced_values = array(
					'%currency_name%'   => $currencies[ $currency_code ],
					'%currency_code%'   => $currency_code,
					'%currency_symbol%' => wcj_get_currency_symbol( $currency_code ),
				);
				$currency_switcher_output = str_replace( array_keys( $template_replaced_values ), array_values( $template_replaced_values ), $switcher_template );
				if ( '' == $selected_currency ) {
					$selected_currency = $currency_code;
				}
				if ( 'select' === $type ) {
					$html .= '<option value="' . $currency_code . '" ' . selected( $currency_code, $selected_currency, false ) . '>' . $currency_switcher_output . '</option>';
				} elseif ( 'radio' === $type ) {
					$html .= '<input type="radio" name="wcj-currency" id="wcj-currency-radio" value="' . $currency_code . '" ' . checked( $currency_code, $selected_currency, false ) . ' onclick="this.form.submit()"> ' . $currency_switcher_output . '<br>';
				}
			}
		}
		// End
		if ( 'select' === $type ) {
			$html .= '</select>';
		}
		$html .= '</form>';
		return $html;
	}

	/**
	 * wcj_currency_select_radio_list.
	 *
	 * @version 2.4.5
	 * @since   2.4.5
	 */
	function wcj_currency_select_radio_list( $atts, $content ) {
		return $this->get_currency_selector( $atts, $content, 'radio' );
	}

	/**
	 * wcj_currency_select_drop_down_list.
	 *
	 * @version 2.4.5
	 * @since   2.4.3
	 */
	function wcj_currency_select_drop_down_list( $atts, $content ) {
		return $this->get_currency_selector( $atts, $content, 'select' );
	}

	/**
	 * wcj_country_select_drop_down_list.
	 *
	 * @version 2.5.9
	 */
	function wcj_country_select_drop_down_list( $atts, $content ) {

		$html = '';

		$form_method  = $atts['form_method']; // get_option( 'wcj_price_by_country_country_selection_box_method', 'get' );
		$select_class = $atts['class'];       // get_option( 'wcj_price_by_country_country_selection_box_class', '' );
		$select_style = $atts['style'];       // get_option( 'wcj_price_by_country_country_selection_box_style', '' );

		$html .= '<form action="" method="' . $form_method . '">';

		$html .= '<select name="wcj-country" id="wcj-country" style="' . $select_style . '" class="' . $select_class . '" onchange="this.form.submit()">';
		$countries = wcj_get_countries();

		/* $shortcode_countries = get_option( 'wcj_price_by_country_shortcode_countries', array() );
		if ( '' == $shortcode_countries ) $shortcode_countries = array(); */
		$shortcode_countries = $atts['countries'];
		if ( '' == $shortcode_countries ) {
			$shortcode_countries = array();
		} else {
			$shortcode_countries = str_replace( ' ', '', $shortcode_countries );
			$shortcode_countries = trim( $shortcode_countries, ',' );
			$shortcode_countries = explode( ',', $shortcode_countries );
		}

		/* if ( 'get' == $form_method ) {
			$selected_country = ( isset( $_GET[ 'wcj-country' ] ) ) ? $_GET[ 'wcj-country' ] : '';
		} else {
			$selected_country = ( isset( $_POST[ 'wcj-country' ] ) ) ? $_POST[ 'wcj-country' ] : '';
		} */
		$selected_country = ( isset( $_SESSION[ 'wcj-country' ] ) ) ? $_SESSION[ 'wcj-country' ] : '';

		if ( 'yes' === $atts['replace_with_currency'] ) {
			$currencies_names_and_symbols = wcj_get_currencies_names_and_symbols();
		}

		if ( empty( $shortcode_countries ) ) {
			foreach ( $countries as $country_code => $country_name ) {

				$data_icon = '';
				if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
					$data_icon = ' data-icon="' . wcj_plugin_url() . '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png"';
				}

				$option_label = ( 'yes' === $atts['replace_with_currency'] ) ? $currencies_names_and_symbols[ wcj_get_currency_by_country( $country_code ) ] : $country_name;

				$html .= '<option' . $data_icon . ' value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $option_label . '</option>';
			}
		} else {
			foreach ( $shortcode_countries as $country_code ) {
				if ( isset( $countries[ $country_code ] ) ) {

					$data_icon = '';
					if ( 'yes' === get_option( 'wcj_price_by_country_jquery_wselect_enabled', 'no' ) ) {
						$data_icon = ' data-icon="' . wcj_plugin_url() . '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png"';
					}

					$option_label = ( 'yes' === $atts['replace_with_currency'] ) ? $currencies_names_and_symbols[ wcj_get_currency_by_country( $country_code ) ] : $countries[ $country_code ];

					$html .= '<option' . $data_icon . ' value="' . $country_code . '" ' . selected( $country_code, $selected_country, false ) . '>' . $option_label . '</option>';
				}
			}
		}

		$html .= '</select>';

		$html .= '</form>';

		return $html;
	}

	/**
	 * wcj_text.
	 *
	 * @since 2.2.9
	 */
	function wcj_text( $atts, $content ) {
		return /* do_shortcode(  */ $content /* ) */;
	}

	/**
	 * wcj_wpml.
	 *
	 * @version 2.2.9
	 */
	function wcj_wpml( $atts, $content ) {
		/* if ( '' == $atts['lang'] || ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE === $atts['lang'] ) ) return do_shortcode( $content );
		else return ''; */
		return do_shortcode( $content );
	}

	/**
	 * wcj_wpml_translate.
	 */
	function wcj_wpml_translate( $atts, $content ) {
		return $this->wcj_wpml( $atts, $content );
	}

	/**
	 * wcj_cart_items_total_quantity.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function wcj_cart_items_total_quantity( $atts ) {
		$total_quantity = 0;
		$_items = WC()->cart->get_cart();
		foreach( $_items as $_item ) {
			$total_quantity += $_item['quantity'];
		}
		return $total_quantity;
	}

	/**
	 * wcj_cart_items_total_weight.
	 */
	function wcj_cart_items_total_weight( $atts ) {
		$the_cart = WC()->cart;
		return $the_cart->cart_contents_weight;
	}

	/**
	 * wcj_cart_total.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function wcj_cart_total( $atts ) {
		if ( $_cart = WC()->cart ) {
			return $_cart->get_cart_total();
		}
		return '';
	}

	/**
	 * wcj_current_date.
	 *
	 * @version 2.6.0
	 */
	function wcj_current_date( $atts ) {
		return date_i18n( $atts['date_format'], current_time( 'timestamp' ) );
	}

	/**
	 * wcj_image.
	 */
	/*function wcj_image( $atts ) {
		return '<img src="' . $atts['url'] . '" class="' . $atts['class'] . '" width="' . $atts['width'] . '" height="' . $atts['height'] . '">';
	}*/
}

endif;

return new WCJ_General_Shortcodes();
