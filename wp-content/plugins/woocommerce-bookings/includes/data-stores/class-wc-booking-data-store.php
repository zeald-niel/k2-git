<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Booking Data Store: Stored in CPT.
 *
 * @todo When 2.6 support is dropped, implement WC_Object_Data_Store_Interface
 */
class WC_Booking_Data_Store extends WC_Data_Store_WP {

	/**
	 * Meta keys and how they transfer to CRUD props.
	 *
	 * @var array
	 */
	private $booking_meta_key_to_props = array(
		'_booking_all_day'                => 'all_day',
		'_booking_cost'                   => 'cost',
		'_booking_customer_id'            => 'customer_id',
		'_booking_order_item_id'          => 'order_item_id',
		'_booking_parent_id'              => 'parent_id',
		'_booking_persons'                => 'person_counts',
		'_booking_product_id'             => 'product_id',
		'_booking_resource_id'            => 'resource_id',
		'_booking_start'                  => 'start',
		'_booking_end'                    => 'end',
		'_wc_bookings_gcalendar_event_id' => 'google_calendar_event_id',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new booking in the database.
	 *
	 * @param WC_Booking $booking
	 */
	public function create( &$booking ) {
		if ( ! $booking->get_date_created( 'edit' ) ) {
			$booking->set_date_created( current_time( 'timestamp' ) );
		}

		// @codingStandardsIgnoreStart
		$id = wp_insert_post( apply_filters( 'woocommerce_new_booking_data', array(
			'post_date'     => date( 'Y-m-d H:i:s', $booking->get_date_created( 'edit' ) ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $booking->get_date_created( 'edit' ) ) ),
			'post_type'     => 'wc_booking',
			'post_status'   => $booking->get_status( 'edit' ),
			'post_author'   => $booking->get_customer_id( 'edit' ),
			'post_title'    => sprintf( __( 'Booking &ndash; %s', 'woocommerce-bookings' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Booking date parsed by strftime', 'woocommerce-bookings' ) ) ),
			'post_parent'   => $booking->get_order_id( 'edit' ),
			'ping_status'   => 'closed',
		) ), true );
		// @codingStandardsIgnoreEnd

		if ( $id && ! is_wp_error( $id ) ) {
			$booking->set_id( $id );
			$this->update_post_meta( $booking );
			$booking->save_meta_data();
			$booking->apply_changes();
			WC_Cache_Helper::get_transient_version( 'bookings', true );

			do_action( 'woocommerce_new_booking', $booking->get_id() );
		}
	}

	/**
	 * Method to read an order from the database.
	 *
	 * @param WC_Booking
	 */
	public function read( &$booking ) {
		$booking->set_defaults();

		if ( ! $booking->get_id() || ! ( $post_object = get_post( $booking->get_id() ) ) || 'wc_booking' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid booking.', 'woocommerce' ) );
		}

		$set_props = array();

		// Read post data.
		$set_props['date_created']  = $post_object->post_date;
		$set_props['date_modified'] = $post_object->post_modified;
		$set_props['status']        = $post_object->post_status;
		$set_props['order_id']      = $post_object->post_parent;

		// Read meta data.
		foreach ( $this->booking_meta_key_to_props as $key => $prop ) {
			$value = get_post_meta( $booking->get_id(), $key, true );

			switch ( $prop ) {
				case 'end' :
				case 'start' :
					$set_props[ $prop ] = $value ? strtotime( $value ) : '';
					break;
				case 'all_day' :
					$set_props[ $prop ] = wc_bookings_string_to_bool( $value );
					break;
				default :
					$set_props[ $prop ] = $value;
					break;
			}
		}

		$booking->set_props( $set_props );
		$booking->set_object_read( true );
	}

	/**
	 * Method to update an order in the database.
	 *
	 * @param WC_Booking $booking
	 */
	public function update( &$booking ) {
		wp_update_post( array(
			'ID'            => $booking->get_id(),
			'post_date'     => date( 'Y-m-d H:i:s', $booking->get_date_created( 'edit' ) ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $booking->get_date_created( 'edit' ) ) ),
			'post_status'   => $booking->get_status( 'edit' ),
			'post_author'   => $booking->get_customer_id( 'edit' ),
			'post_parent'   => $booking->get_order_id( 'edit' ),
		) );
		$this->update_post_meta( $booking );
		$booking->save_meta_data();
		$booking->apply_changes();
		WC_Cache_Helper::get_transient_version( 'bookings', true );
	}

	/**
	 * Method to delete an order from the database.
	 * @param WC_Booking
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$booking, $args = array() ) {
		$id   = $booking->get_id();
		$args = wp_parse_args( $args, array(
			'force_delete' => false,
		) );

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$booking->set_id( 0 );
			do_action( 'woocommerce_delete_booking', $id );
		} else {
			wp_trash_post( $id );
			$booking->set_status( 'trash' );
			do_action( 'woocommerce_trash_booking', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for a booking based on it's settings in the WC_Booking class.
	 *
	 * @param WC_Booking
	 */
	protected function update_post_meta( &$booking ) {
		foreach ( $this->booking_meta_key_to_props as $key => $prop ) {
			if ( is_callable( array( $booking, "get_$prop" ) ) ) {
				$value = $booking->{ "get_$prop" }( 'edit' );

				switch ( $prop ) {
					case 'all_day' :
						update_post_meta( $booking->get_id(), $key, $value ? 1 : 0 );
						break;
					case 'end' :
					case 'start' :
						update_post_meta( $booking->get_id(), $key, $value ? date( 'YmdHis', $value ) : '' );
						break;
					default :
						update_post_meta( $booking->get_id(), $key, $value );
						break;
				}
			}
		}
	}

	/**
	 * For a given order ID, get all bookings that belong to it.
	 *
	 * @param  int|array $order_id
	 * @return int
	 */
	public static function get_booking_ids_from_order_id( $order_id ) {
		global $wpdb;

		$order_ids = wp_parse_id_list( is_array( $order_id ) ? $order_id : array( $order_id ) );

		return wp_parse_id_list( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wc_booking' AND post_parent IN (" . implode( ',', array_map( 'esc_sql', $order_ids ) ) . ");" ) );
	}

	/**
	 * For a given order item ID, get all bookings that belong to it.
	 *
	 * @param  int $order_item_id
	 * @return array
	 */
	public static function get_booking_ids_from_order_item_id( $order_item_id ) {
		global $wpdb;
		return wp_parse_id_list(
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d;",
					$order_item_id
				)
			)
		);
	}

	/**
	 * Get booking ids for an object  by ID. e.g. product.
	 *
	 * @param  array
	 * @return array
	 */
	public static function get_booking_ids_by( $filters = array() ) {
		global $wpdb;

		$filters = wp_parse_args( $filters, array(
			'object_id'    => 0,
			'object_type'  => 'product',
			'status'       => false,
			'limit'        => -1,
			'offset'       => 0,
			'order_by'     => 'date_created',
			'order'        => 'DESC',
			'date_before'  => false,
			'date_after'   => false,
			'date_between' => array(
				'start' => false,
				'end'   => false,
			),
		) );

		$meta_keys            = array();
		$query_where          = array( 'WHERE 1=1', "p.post_type = 'wc_booking'" );
		$filters['object_id'] = array_filter( wp_parse_id_list( is_array( $filters['object_id'] ) ? $filters['object_id'] : array( $filters['object_id'] ) ) );

		if ( ! empty( $filters['object_id'] ) ) {
			switch ( $filters['object_type'] ) {
				case 'product' :
					$meta_keys[]   = '_booking_product_id';
					$query_where[] = "_booking_product_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['object_id'] ) ) . "')";
					break;
				case 'resource' :
					$meta_keys[]   = '_booking_resource_id';
					$query_where[] = "_booking_resource_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['object_id'] ) ) . "')";
					break;
				case 'product_or_resource' :
					$meta_keys[]   = '_booking_product_id';
					$meta_keys[]   = '_booking_resource_id';
					$query_where[] = "(
						_booking_product_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['object_id'] ) ) . "') OR _booking_resource_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['object_id'] ) ) . "')
					)";
					break;
				case 'customer' :
					$meta_keys[]   = '_booking_customer_id';
					$query_where[] = "_booking_customer_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['object_id'] ) ) . "')";
					break;
			}
		}

		if ( ! empty( $filters['status'] ) ) {
			$query_where[] = "p.post_status IN ('" . implode( "','", $filters['status'] ) . "')";
		}

		if ( ! empty( $filters['date_between']['start'] ) && ! empty( $filters['date_between']['end'] ) ) {
			$meta_keys[]   = '_booking_start';
			$meta_keys[]   = '_booking_end';
			$meta_keys[]   = '_booking_all_day';
			$query_where[] = "( (
				_booking_start.meta_value <= '" . esc_sql( date( 'YmdHis', $filters['date_between']['end'] ) ) . "' AND
				_booking_end.meta_value >= '" . esc_sql( date( 'YmdHis', $filters['date_between']['start'] ) ) . "' AND
				_booking_all_day.meta_value = '0'
			) OR (
				_booking_start.meta_value <= '" . esc_sql( date( 'Ymd000000', $filters['date_between']['end'] ) ) . "' AND
				_booking_end.meta_value >= '" . esc_sql( date( 'Ymd000000', $filters['date_between']['start'] ) ) . "' AND
				_booking_all_day.meta_value = '1'
			) )";
		}

		if ( ! empty( $filters['date_after'] ) ) {
			$meta_keys[]   = '_booking_start';
			$query_where[] = "_booking_start.meta_value >= '" . esc_sql( date( 'YmdHis', $filters['date_after'] ) ) . "'";
		}

		if ( ! empty( $filters['date_before'] ) ) {
			$meta_keys[]   = '_booking_end';
			$query_where[] = "_booking_end.meta_value <= '" . esc_sql( date( 'YmdHis', $filters['date_before'] ) ) . "'";
		}

		if ( ! empty( $filters['order_by'] ) ) {
			switch ( $filters['order_by'] ) {
				case 'date_created' :
					$filters['order_by'] = 'p.post_date';
					break;
				case 'start_date' :
					$meta_keys[]   = '_booking_start';
					$filters['order_by'] = '_booking_start.meta_value';
					break;
			}
			$query_order = ' ORDER BY ' . esc_sql( $filters['order_by'] ) . ' ' . esc_sql( $filters['order'] );
		} else {
			$query_order = '';
		}

		if ( $filters['limit'] > 0 ) {
			$query_limit = ' LIMIT ' . absint( $filters['offset'] ) . ',' . absint( $filters['limit'] );
		} else {
			$query_limit = '';
		}

		$query_select = "SELECT p.ID FROM {$wpdb->posts} p";
		$meta_keys    = array_unique( $meta_keys );
		$query_where  = implode( ' AND ', $query_where );

		foreach ( $meta_keys as $index => $meta_key ) {
			$key           = esc_sql( $meta_key );
			$query_select .= " LEFT JOIN {$wpdb->postmeta} {$key} ON p.ID = {$key}.post_id AND {$key}.meta_key = '{$key}'";
		}

		return array_filter( wp_parse_id_list( $wpdb->get_col( "{$query_select} {$query_where} {$query_order} {$query_limit};" ) ) );
	}

	/**
	 * For a given booking ID, get it's linked order ID if set.
	 *
	 * @param  int $booking_id
	 * @return int
	 */
	public static function get_booking_order_id( $booking_id ) {
		return absint( wp_get_post_parent_id( $booking_id ) );
	}

	/**
	 * For a given booking ID, get it's linked order item ID if set.
	 *
	 * @param  int $booking_id
	 * @return int
	 */
	public static function get_booking_order_item_id( $booking_id ) {
		return absint( get_post_meta( $booking_id, '_booking_order_item_id', true ) );
	}

	/**
	 * For a given booking ID, get it's linked order item ID if set.
	 *
	 * @param  int $booking_id
	 * @return int
	 */
	public static function get_booking_customer_id( $booking_id ) {
		return absint( get_post_meta( $booking_id, '_booking_customer_id', true ) );
	}
}
