<?php
/**
 * Booster for WooCommerce - Module - Orders
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Orders' ) ) :

class WCJ_Orders extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 */
	function __construct() {

		$this->id         = 'orders';
		$this->short_desc = __( 'Orders', 'woocommerce-jetpack' );
		$this->desc       = __( 'Orders auto-complete. Custom admin order list columns. Admin order currency. Admin order list multiple status filtering.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-orders';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Order auto complete
			if ( 'yes' === get_option( 'wcj_order_auto_complete_enabled', 'no' ) ) {
				add_action( 'woocommerce_thankyou',         array( $this, 'auto_complete_order' ), PHP_INT_MAX );
				add_action( 'woocommerce_payment_complete', array( $this, 'auto_complete_order' ), PHP_INT_MAX );
			}

			// Custom columns
			add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_columns' ),   PHP_INT_MAX - 1 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_column' ), PHP_INT_MAX );
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) || 'yes' === get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
				// Billing country or Currency filtering
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
				add_filter( 'parse_query',           array( $this, 'parse_query' ) );
			}

			// Order currency
			if ( 'yes' === get_option( 'wcj_order_admin_currency', 'no' ) ) {
				$this->meta_box_screen = 'shop_order';
				add_action( 'add_meta_boxes',       array( $this, 'add_meta_box' ) );
				add_action( 'save_post_shop_order', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				if ( 'filter' === get_option( 'wcj_order_admin_currency_method', 'filter' ) ) {
					$woocommerce_get_order_currency_filter = ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_get_order_currency' : 'woocommerce_order_get_currency' );
					add_filter( $woocommerce_get_order_currency_filter, array( $this, 'change_order_currency' ), PHP_INT_MAX, 2 );
				}
			}

			// Multiple status
			if ( 'yes' === get_option( 'wcj_order_admin_list_multiple_status_not_completed_link', 'no' ) ) {
				add_filter( 'views_edit-shop_order', array( $this, 'add_shop_order_multiple_statuses_not_completed_link' ) );
				add_action( 'pre_get_posts',         array( $this, 'filter_shop_order_multiple_statuses_not_completed_link' ), PHP_INT_MAX, 1 );
			}
			if ( 'no' != get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) ) {
				add_action( 'restrict_manage_posts', array( $this, 'add_shop_order_multiple_statuses' ), PHP_INT_MAX, 2 );
				add_action( 'pre_get_posts',         array( $this, 'filter_shop_order_multiple_statuses' ), PHP_INT_MAX, 1 );
			}
			if ( 'yes' === get_option( 'wcj_order_admin_list_hide_default_statuses_menu', 'no' ) ) {
				add_action( 'admin_head', array( $this, 'hide_default_statuses_menu' ), PHP_INT_MAX );
			}

			// Columns Order
			if ( 'yes' === get_option( 'wcj_order_admin_list_columns_order_enabled', 'no' ) ) {
				add_filter( 'manage_edit-shop_order_columns', array( $this, 'rearange_order_columns' ), PHP_INT_MAX - 1 );
			}

			// Maybe make sortable custom columns
			add_filter( 'manage_edit-shop_order_sortable_columns',  array( $this, 'shop_order_sortable_columns' ) );
			add_action( 'pre_get_posts',                            array( $this, 'shop_order_pre_get_posts_order_by_column' ), 1 );
		}
	}

	/**
	 * shop_order_pre_get_posts_order_by_column.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    add sortable to "Billing Country" and "Currency Code"
	 * @todo    move custom columns section (probably with reordering and multiple status sections) to new module (e.g. (Admin) Order(s) List) - same with products custom columns
	 * @todo    (maybe) add filtering to custom columns (as it's done for "Billing Country" and "Currency Code")
	 */
	function shop_order_pre_get_posts_order_by_column( $query ) {
		if (
			$query->is_main_query() &&
			( $orderby = $query->get( 'orderby' ) ) &&
			isset( $query->query['post_type'] ) && 'shop_order' === $query->query['post_type'] &&
			isset( $query->is_admin ) && 1 == $query->is_admin
		) {
			if ( 'wcj_orders_custom_column_' === substr( $orderby, 0, 25 ) ) {
				$index = substr( $orderby, 25 );
				$query->set( 'orderby',  get_option( 'wcj_orders_list_custom_columns_sortable_'     . $index, 'no' ) ); // 'meta_value' or 'meta_value_num'
				$query->set( 'meta_key', get_option( 'wcj_orders_list_custom_columns_sortable_key_' . $index, '' ) );
			}
		}
	}

	/**
	 * Make columns sortable.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @param   array $columns
	 * @return  array
	 */
	function shop_order_sortable_columns( $columns ) {
		$custom = array();
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
				if ( 'no' != get_option( 'wcj_orders_list_custom_columns_sortable_' . $i, 'no' ) && '' != get_option( 'wcj_orders_list_custom_columns_sortable_key_' . $i, '' ) ) {
					$custom[ 'wcj_orders_custom_column_' . $i ] = 'wcj_orders_custom_column_' . $i;
				}
			}
		}
		return ( ! empty( $custom ) ? wp_parse_args( $custom, $columns ) : $columns );
	}

	/**
	 * hide_default_statuses_menu.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function hide_default_statuses_menu() {
		echo '<style>body.post-type-shop_order ul.subsubsub {display: none !important;}</style>';
	}

	/**
	 * get_orders_default_columns_in_order.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_orders_default_columns_in_order() {
		$columns = array(
			'cb',
			'order_status',
			'order_title',
			'order_items',
			'billing_address',
			'shipping_address',
			'customer_message',
			'order_notes',
			'order_date',
			'order_total',
			'order_actions',
		);
		return implode( PHP_EOL, $columns );
	}

	/**
	 * add_shop_order_multiple_statuses_not_completed_link.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_shop_order_multiple_statuses_not_completed_link( $views ) {
		global $wp_query;
		if ( ! current_user_can( 'edit_others_pages' ) ) {
			return $views;
		}
		$all_not_completed_statuses          = wc_get_order_statuses();
		unset( $all_not_completed_statuses['wc-completed'] );
		$all_not_completed_statuses          = array_keys( $all_not_completed_statuses );
		$all_not_completed_statuses_param    = urlencode( implode( ',', $all_not_completed_statuses ) );
		$class                               = ( isset( $wp_query->query['post_status'] ) && is_array( $wp_query->query['post_status'] ) && $all_not_completed_statuses === $wp_query->query['post_status'] ) ? 'current' : '';
		$query_string                        = remove_query_arg( array( 'post_status', 'wcj_admin_filter_statuses' ) );
		$query_string                        = add_query_arg( 'post_status', $all_not_completed_statuses_param, $query_string );
		$views['wcj_statuses_not_completed'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __( 'Not Completed', 'woocommerce-jetpack' ) . '</a>';
		return $views;
	}

	/**
	 * filter_shop_order_multiple_statuses_not_completed_link.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function filter_shop_order_multiple_statuses_not_completed_link( $query ) {
		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-admin/edit.php' ) && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {
			if ( current_user_can( 'edit_others_pages' ) ) {
				if ( isset( $_GET['post_status'] ) && false !== strpos( $_GET['post_status'], ',' ) ) {
					$post_statuses = explode( ',', $_GET['post_status'] );
					$query->query['post_status']      = $post_statuses;
					$query->query_vars['post_status'] = $post_statuses;
				}
			}
		}
	}

	/**
	 * multiple_shop_order_statuses.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function multiple_shop_order_statuses( $type ) {
		$checked_post_statuses = isset( $_GET['wcj_admin_filter_statuses'] ) ? $_GET['wcj_admin_filter_statuses'] : array();
		$html = '';
		$html .= ( 'checkboxes' === $type ) ?
			'<span id="wcj_admin_filter_shop_order_statuses">' :
			'<select multiple name="wcj_admin_filter_statuses[]" id="wcj_admin_filter_shop_order_statuses">';
		$num_posts = wp_count_posts( 'shop_order', 'readable' );
		foreach ( wc_get_order_statuses() as $status_id => $status_title ) {
			$total_number = ( isset( $num_posts->{$status_id} ) ) ? $num_posts->{$status_id} : 0;
			if ( $total_number > 0 ) {
				$html .= ( 'checkboxes' === $type ) ?
					'<input type="checkbox" name="wcj_admin_filter_statuses[]" value="' . $status_id . '"' . checked( in_array( $status_id, $checked_post_statuses ), true, false ) . '>' . $status_title . ' (' . $total_number . ') ' :
					'<option value="' . $status_id . '"' . selected( in_array( $status_id, $checked_post_statuses ), true, false ) . '>' . $status_title . ' (' . $total_number . ') ' . '</option>';
			}
		}
		$html .= ( 'checkboxes' === $type ) ?
			'</span>' :
			'</select>';
		return $html;
	}

	/**
	 * add_shop_order_multiple_statuses.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_shop_order_multiple_statuses( $post_type, $which ) {
		if ( 'shop_order' === $post_type ) {
			echo $this->multiple_shop_order_statuses( get_option( 'wcj_order_admin_list_multiple_status_filter', 'no' ) );
		}
	}

	/**
	 * filter_shop_order_multiple_statuses.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function filter_shop_order_multiple_statuses( $query ) {
		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-admin/edit.php' ) && isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'] ) {
			if ( current_user_can( 'edit_others_pages' ) ) {
				if ( isset( $_GET['wcj_admin_filter_statuses'] ) ) {
					$post_statuses = $_GET['wcj_admin_filter_statuses'];
					$query->query['post_status']      = $post_statuses;
					$query->query_vars['post_status'] = $post_statuses;
				}
			}
		}
	}

	/**
	 * change_order_currency.
	 *
	 * @version 2.7.0
	 * @since   2.5.6
	 */
	function change_order_currency( $order_currency, $_order ) {
		return ( '' != ( $wcj_order_currency = get_post_meta( wcj_get_order_id( $_order ), '_' . 'wcj_order_currency', true ) ) ) ? $wcj_order_currency : $order_currency;
	}

	/**
	 * Filter the orders in admin based on options.
	 *
	 * @version 2.8.0
	 * @access  public
	 * @param   mixed $query
	 * @return  void
	 */
	function parse_query( $query ) {
		global $typenow, $wp_query;
		if ( $typenow != 'shop_order' ) {
			return;
		}
		if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) && isset( $_GET['country'] ) && 'all' != $_GET['country'] ) {
			$query->query_vars['meta_query'][] = array(
				'key'   => '_billing_country',
				'value' => $_GET['country'],
			);
		}
		if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) && isset( $_GET['currency'] ) && 'all' != $_GET['currency'] ) {
			$query->query_vars['meta_query'][] = array(
				'key'   => '_order_currency',
				'value' => $_GET['currency'],
			);
		}
	}

	/**
	 * Filters for post types.
	 *
	 * @version 2.8.0
	 */
	function restrict_manage_posts() {
		global $typenow, $wp_query;
		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) {
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
				$selected_coutry = isset( $_GET['country'] ) ? $_GET['country'] : 'all';
				$countries = array_merge( array( 'all' => __( 'All countries', 'woocommerce-jetpack' ) ), wcj_get_countries() );
				echo '<select id="country" name="country">';
				foreach ( $countries as $code => $name ) {
					echo '<option value="' . $code . '" ' . selected( $code, $selected_coutry, false ) . '>' . $name . '</option>';
				}
				echo '</select>';
			}
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
				$selected_currency = isset( $_GET['currency'] ) ? $_GET['currency'] : 'all';
				$currencies = array_merge( array( 'all' => __( 'All currencies', 'woocommerce-jetpack' ) ), wcj_get_currencies_names_and_symbols() );
				echo '<select id="currency" name="currency">';
				foreach ( $currencies as $code => $name ) {
					echo '<option value="' . $code . '" ' . selected( $code, $selected_currency, false ) . '>' . $name . '</option>';
				}
				echo '</select>';
			}
		}
	}

	/**
	 * rearange_order_columns.
	 *
	 * @version 2.5.7
	 * @version 2.5.7
	 */
	function rearange_order_columns( $columns ) {
		$reordered_columns = get_option( 'wcj_order_admin_list_columns_order', $this->get_orders_default_columns_in_order() );
		$reordered_columns = explode( PHP_EOL, $reordered_columns );
		$reordered_columns_result = array();
		if ( ! empty( $reordered_columns ) ) {
			foreach ( $reordered_columns as $column_id ) {
				$column_id = str_replace( "\n", '', $column_id );
				$column_id = str_replace( "\r", '', $column_id );
				if ( '' != $column_id && isset( $columns[ $column_id ] ) ) {
					$reordered_columns_result[ $column_id ] = $columns[ $column_id ];
					unset( $columns[ $column_id ] );
				}
			}
		}
		return array_merge( $reordered_columns_result, $columns );
	}

	/**
	 * add_order_columns.
	 *
	 * @version 2.8.0
	 */
	function add_order_columns( $columns ) {
		if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
			$columns['country'] = __( 'Billing Country', 'woocommerce-jetpack' );
		}
		if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
			$columns['currency'] = __( 'Currency Code', 'woocommerce-jetpack' );
		}
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
				$columns[ 'wcj_orders_custom_column_' . $i ] = get_option( 'wcj_orders_list_custom_columns_label_' . $i, '' );
			}
		}
		return $columns;
	}

	/**
	 * Output custom columns for orders
	 *
	 * @version 2.8.0
	 * @param   string $column
	 */
	function render_order_column( $column ) {
		if ( 'country' === $column && 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
			$country_code = do_shortcode( '[wcj_order_checkout_field field_id="billing_country"]' );
			echo ( 2 == strlen( $country_code ) )
				? wcj_get_country_flag_by_code( $country_code ) . ' ' . wcj_get_country_name_by_code( $country_code )
				: wcj_get_country_name_by_code( $country_code );
		} elseif ( 'currency' === $column && 'yes' === get_option( 'wcj_orders_list_custom_columns_currency', 'no' ) ) {
			echo do_shortcode( '[wcj_order_currency]' );
		} else {
			$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_orders_list_custom_columns_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_enabled_' . $i, 'no' ) ) {
					if ( 'wcj_orders_custom_column_' . $i === $column ) {
						echo do_shortcode( get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' ) );
					}
				}
			}
		}
	}

	/**
	* Auto Complete all WooCommerce orders.
	*
	* @version 2.7.0
	*/
	function auto_complete_order( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		$order->update_status( 'completed' );
	}

}

endif;

return new WCJ_Orders();
