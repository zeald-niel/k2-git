<?php
/**
 * Gets bookings
 */
class WC_Bookings_Controller {

	/**
	 * Return all bookings for a product in a given range
	 * @param integer $start_date
	 * @param integer $end_date
	 * @param int  $product_or_resource_id
	 * @param bool $check_in_cart
	 *
	 * @return array
	 */
	public static function get_bookings_in_date_range( $start_date, $end_date, $product_or_resource_id = 0, $check_in_cart = true ) {
		$transient_name = 'book_dr_' . md5( http_build_query( array( $start_date, $end_date, $product_or_resource_id, $check_in_cart, WC_Cache_Helper::get_transient_version( 'bookings' ) ) ) );

		if ( false === ( $booking_ids = get_transient( $transient_name ) ) ) {
			$booking_ids = self::get_bookings_in_date_range_query( $start_date, $end_date, $product_or_resource_id, $check_in_cart );
			set_transient( $transient_name, $booking_ids, DAY_IN_SECONDS * 30 );
		}

		return array_map( 'get_wc_booking', wp_parse_id_list( $booking_ids ) );
	}

	/**
	 * Return an array of un-bookable buffer days
	 * @since 1.9.13
	 *
	 * @param  WC_Product_Booking|int $bookable_product
	 * @return array Days that are buffer days and therefor should be un-bookable
	 */
	public static function find_buffer_day_blocks( $bookable_product ) {
		if ( is_int( $bookable_product ) ) {
			$bookable_product = wc_get_product( $bookable_product );
		}
		if ( ! is_a( $bookable_product, 'WC_Product_Booking' ) ) {
			return array();
		}
		$booked = WC_Bookings_Controller::find_booked_day_blocks( $bookable_product );
		return WC_Bookings_Controller::get_buffer_day_blocks_for_booked_days( $bookable_product, $booked['fully_booked_days'] );
	}

	/**
	 * Return an array of un-bookable buffer days
	 * @since 1.9.13
	 *
	 * @param  WC_Product_Booking|int $bookable_product
	 * @return array Days that are buffer days and therefor should be un-bookable
	 */
	public static function get_buffer_day_blocks_for_booked_days( $bookable_product, $fully_booked_days ) {
		if ( is_int( $bookable_product ) ) {
			$bookable_product = wc_get_product( $bookable_product );
		}
		if ( ! is_a( $bookable_product, 'WC_Product_Booking' ) ) {
			return array();
		}

		$buffer_period     = $bookable_product->get_buffer_period();
		$buffer_days       = array();

		foreach ( $fully_booked_days as $date => $data ) {
			$next_day = strtotime( '+1 day', strtotime( $date ) );

			if ( array_key_exists( date( 'Y-n-j', $next_day ), $fully_booked_days ) ) {
				continue;
			}

			// x days after
			for ( $i = 1; $i < $buffer_period + 1; $i++ ) {
				$buffer_day = date( 'Y-n-j', strtotime( "+{$i} day", strtotime( $date ) ) );
				$buffer_days[ $buffer_day ] = $buffer_day;
			}
		}

		if ( $bookable_product->get_apply_adjacent_buffer() ) {
			foreach ( $fully_booked_days as $date => $data ) {
				$previous_day = strtotime( '-1 day', strtotime( $date ) );

				if ( array_key_exists( date( 'Y-n-j', $previous_day ), $fully_booked_days ) ) {
					continue;
				}

				// x days before
				for ( $i = 1; $i < $buffer_period + 1; $i++ ) {
					$buffer_day = date( 'Y-n-j', strtotime( "-{$i} day", strtotime( $date ) ) );
					$buffer_days[ $buffer_day ] = $buffer_day;
				}
			}
		}
		return $buffer_days;
	}

	/**
	 * Finds existing bookings for a product and its tied resources.
	 *
	 * @param  WC_Product_Booking $bookable_product
	 * @param  int                $min_date
	 * @param  int                $max_date
	 * @return array
	 */
	public static function get_all_existing_bookings( $bookable_product, $min_date = 0, $max_date = 0 ) {
		$find_bookings_for = array( $bookable_product->get_id() );

		if ( $bookable_product->has_resources() ) {
			foreach ( $bookable_product->get_resources() as $resource ) {
				$find_bookings_for[] = $resource->get_id();
			}
		}

		if ( empty( $min_date ) ) {
			// Determine a min and max date
			$min_date = $bookable_product->get_min_date();
			$min_date = empty( $min_date ) ? array( 'unit' => 'minute', 'value' => 1 ) : $min_date ;
			$min_date = strtotime( "midnight +{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
		}

		if ( empty( $max_date ) ) {
			$max_date = $bookable_product->get_max_date();
			$max_date = empty( $max_date ) ? array( 'unit' => 'month', 'value' => 12 ) : $max_date;
			$max_date = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time( 'timestamp' ) );
		}

		return self::get_bookings_for_objects( $find_bookings_for, get_wc_booking_statuses( 'fully_booked' ), $min_date, $max_date );
	}

	/**
	 * Finds days which are partially booked & fully booked already.
	 *
	 * This function will get a general min/max Booking date, which initially is [today, today + 1 year]
	 * Based on the Bookings retrieved from that date, it will shrink the range to the [Bookings_min, Bookings_max]
	 * For the newly generated range, it will determine availability of dates by calling `wc_bookings_get_time_slots` on it.
	 *
	 * Depending on the data returned from it we set:
	 * Fully booked days     - for those dates that there are no more slot available
	 * Partially booked days - for those dates that there are some slots available
	 *
	 * @param  WC_Product_Booking|int $bookable_product
	 * @return array( 'partially_booked_days', 'fully_booked_days' )
	 */
	public static function find_booked_day_blocks( $bookable_product ) {
		$booked_day_blocks = array(
			'partially_booked_days' => array(),
			'fully_booked_days'     => array(),
		);

		if ( is_int( $bookable_product ) ) {
			$bookable_product = wc_get_product( $bookable_product );
		}

		if ( ! is_a( $bookable_product, 'WC_Product_Booking' ) ) {
			return $booked_day_blocks;
		}

		// Get existing bookings and go through them to set partial/fully booked days
		$existing_bookings = self::get_all_existing_bookings( $bookable_product );

		if ( empty( $existing_bookings ) ) {
			return $booked_day_blocks;
		}

		$min_booking_date = INF;
		$max_booking_date = -INF;
		$bookings = array();

		// Find the minimum and maximum booking dates and store the booking data in an array for further processing.
		foreach ( $existing_bookings as $existing_booking ) {
			if ( ! is_a( $existing_booking, 'WC_Booking' ) ) {
				continue;
			}
			$check_date    = strtotime( 'midnight', $existing_booking->get_start() );
			$check_date_to = strtotime( 'midnight', $existing_booking->get_end() );
			$resource_id   = $existing_booking->get_resource_id();

			// If it's a booking on the same day, move it before the end of the current day
			if ( $check_date_to === $check_date ) {
				$check_date_to = strtotime( '+1 day', $check_date ) - 1;
			}

			$min_booking_date = min( $min_booking_date, $check_date );
			$max_booking_date = max( $max_booking_date, $check_date_to );

			$bookings[]   = array(
				'start'   => $check_date,
				'end'     => $check_date_to,
				'res'     => $resource_id,
				'all_day' => $existing_booking->is_all_day(),
			);
		}

		$max_booking_date = strtotime( '+1 day', $max_booking_date );

		// Call these for the whole chunk range for the bookings since they're expensive
		$blocks           = $bookable_product->get_blocks_in_range( $min_booking_date, $max_booking_date );
		$available_blocks = wc_bookings_get_time_slots( $bookable_product, $blocks, array(), 0, $min_booking_date, $max_booking_date );
		$available_slots  = array();

		foreach ( $available_blocks as $block => $quantity ) {
			foreach ( $quantity['resources'] as $resource_id => $availability ) {
				if ( $availability > 0 ) {
					$available_slots[ $resource_id ][] = date( 'Y-n-j', $block );
				}
			}
		}

		// Go through [start, end] of each of the bookings by chunking it in days: [start, start + 1d, start + 2d, ..., end]
		// For each of the chunk check the available slots.
		// If there are no slots or the booking is all day, it is fully booked, otherwise partially booked.
		foreach ( $bookings as $booking ) {
			$check_date = $booking['start'];

			while ( $check_date <= $booking['end'] ) {
				$date_format     = date( 'Y-n-j', $check_date );
				$booking_type    = isset( $available_slots[ $booking['res'] ] ) && in_array( $date_format, $available_slots[ $booking['res'] ] ) && ! $booking['all_day'] ? 'partially_booked_days' : 'fully_booked_days';
				$booked_day_blocks[ $booking_type ][ $date_format ][ $booking['res'] ] = 1;

				$check_date      = strtotime( '+1 day', $check_date );
			}
		}

		/**
		 * Filter the booked day blocks calculated per project.
		 * @since 1.9.13
		 *
		 * @param array $booked_day_blocks {
		 *  @type array $partially_booked_days
		 *  @type array $fully_booked_days
		 * }
		 * @param WC_Product $bookable_product
		 */
		return apply_filters( 'woocommerce_bookings_booked_day_blocks', $booked_day_blocks, $bookable_product );
	}

	/**
	 * Loop through given bookings to find those that are on or over lap the given date.
	 *
	 * @since 1.9.14
	 * @param  array $bookings
	 * @param  string $date
	 *
	 * @return array of booking ids
	 */
	public static function filter_bookings_on_date( $bookings, $date ) {
		$bookings_on_date = array();
		$date_start       = strtotime( 'midnight', $date ); // Midnight today.
		$date_end         = strtotime( 'tomorrow', $date ); // Midnight next day.

		foreach ( $bookings as $booking ) {
			// does the date we want to check fall on one of the days in the booking?
			if ( $booking->get_start() < $date_end && $booking->get_end() > $date_start ) {
				$bookings_on_date[] = $booking;
			}
		}
		return $bookings_on_date;
	}

	/**
	 * Gets bookings for product ids and resource ids
	 * @param  array  $ids
	 * @param  array  $status
	 * @param  integer  $date_from
	 * @param  integer  $date_to
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_objects( $ids = array(), $status = array(), $date_from = 0, $date_to = 0 ) {
		$transient_name = 'book_fo_' . md5( http_build_query( array( $ids, $status, WC_Cache_Helper::get_transient_version( 'bookings' ) ) ) );
		$status = ( ! empty( $status ) ) ? $status : get_wc_booking_statuses( 'fully_booked' );
		$date_from 	= ! empty( $date_from ) ? $date_from : strtotime( 'midnight', current_time( 'timestamp' ) );
		$date_to 	= ! empty( $date_to ) ? $date_to : strtotime( '+12 month', current_time( 'timestamp' ) );

		if ( false === ( $booking_ids = get_transient( $transient_name ) ) ) {
			$booking_ids = self::get_bookings_for_objects_query( $ids, $status, $date_from, $date_to );
			set_transient( $transient_name, $booking_ids, DAY_IN_SECONDS * 30 );
		}

		if ( ! empty( $booking_ids ) ) {
			return array_map( 'get_wc_booking', wp_parse_id_list( $booking_ids ) );
		}
		return array();
	}

	/**
	 * Gets bookings for product ids and resource ids
	 * @param  array  $ids
	 * @param  array  $status
	 * @param  integer  $date_from
	 * @param  integer  $date_to
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_objects_query( $ids, $status, $date_from = 0, $date_to = 0 ) {
		$status     = ( ! empty( $status ) ) ? $status   : get_wc_booking_statuses( 'fully_booked' );
		$date_from 	= ! empty( $date_from ) ? $date_from : strtotime( 'midnight', current_time( 'timestamp' ) );
		$date_to 	= ! empty( $date_to ) ? $date_to : strtotime( '+12 month', current_time( 'timestamp' ) );

		$booking_ids = WC_Booking_Data_Store::get_booking_ids_by( array(
			'status'       => $status,
			'object_id'    => $ids,
			'object_type'  => 'product_or_resource',
			'date_between' => array(
				'start' => $date_from,
				'end'   => $date_to,
			),
		) );
		return $booking_ids;
	}

	/**
	 * Gets bookings for a resource.
	 *
	 * @param  int $resource_id ID
	 * @param  array  $status
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_resource( $resource_id, $status = array( 'confirmed', 'paid' ) ) {
		$booking_ids = WC_Booking_Data_Store::get_booking_ids_by( array(
			'object_id'   => $resource_id,
			'object_type' => 'resource',
			'status'      => $status,
		) );
		return array_map( 'get_wc_booking', $booking_ids );
	}

	/**
	 * Gets bookings for a product by ID
	 *
	 * @param int $product_id The id of the product that we want bookings for
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_product( $product_id, $status = array( 'confirmed', 'paid' ) ) {
		$booking_ids = WC_Booking_Data_Store::get_booking_ids_by( array(
			'object_id'   => $product_id,
			'object_type' => 'product',
			'status'      => $status,
		) );
		return array_map( 'get_wc_booking', $booking_ids );
	}

	/**
	 * Return all bookings for a product in a given range - the query part (no cache)
	 * @param  integer $start_date
	 * @param  integer$end_date
	 * @param  int $product_or_resource_id
	 * @param  bool $check_in_cart
	 * @return array of booking ids
	 */
	private static function get_bookings_in_date_range_query( $start_date, $end_date, $product_or_resource_id = 0, $check_in_cart = true ) {
		$args = array(
			'status'       => get_wc_booking_statuses(),
			'object_id'    => $product_or_resource_id,
			'object_type'  => 'product_or_resource',
			'date_between' => array(
				'start' => $start_date,
				'end'   => $end_date,
			),
		);

		if ( ! $check_in_cart ) {
			$args['status'] = array_diff( $args['status'], array( 'in-cart' ) );
		}

		if ( $product_or_resource_id ) {
			if ( get_post_type( $product_or_resource_id ) === 'bookable_resource' ) {
				$args['resource_id'] = absint( $product_or_resource_id );
			} else {
				$args['product_id']  = absint( $product_or_resource_id );
			}
		}

		return apply_filters( 'woocommerce_bookings_in_date_range_query', WC_Booking_Data_Store::get_booking_ids_by( $args ) );
	}

	/**
	 * Get latest bookings
	 *
	 * @param int $number_of_items Number of objects returned (default to unlimited)
	 * @param int $offset The number of objects to skip (as a query offset)
	 * @return array of WC_Booking objects
	 */
	public static function get_latest_bookings( $number_of_items = 10, $offset = 0 ) {

		$booking_ids = get_posts( array(
			'numberposts' => $number_of_items,
			'offset'      => $offset,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'wc_booking',
			'post_status' => get_wc_booking_statuses(),
			'fields'      => 'ids',
		) );

		return array_map( 'get_wc_booking', $booking_ids );
	}

	/**
	 * Gets bookings for a user by ID
	 *
	 * @param  int   $user_id    The id of the user that we want bookings for
	 * @param  array $query_args The query arguments used to get booking IDs
	 * @return array             Array of WC_Booking objects
	 */
	public static function get_bookings_for_user( $user_id, $query_args = null ) {
		$booking_ids = WC_Booking_Data_Store::get_booking_ids_by( array_merge( $query_args, array(
			'status'      => get_wc_booking_statuses( 'user' ),
			'object_id'   => $user_id,
			'object_type' => 'customer',
		) ) );
		return array_map( 'get_wc_booking', $booking_ids );
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated Methods
	|--------------------------------------------------------------------------
	*/
	/**
	 * Get the start and end times for and array of bookings
	 *
	 * @param WC_Booking[] $bookings_objects
	 * @param int          $resource_id Whether to filter on a specific resource
	 * @deprecated  should be removed after other parts of the is optimised to use an array of bookings objects
	 * @since 1.10.0
	 *
	 * @return array
	 */
	public static function get_bookings_star_and_end_times( $bookings_objects, $resource_id = 0 ) {
		$bookings_start_and_end = array();
		foreach ( $bookings_objects as $booking ) {
			if ( ! empty( $resource_id ) && $booking->get_resource_id() !== $resource_id ) {
				continue;
			}

			$bookings_start_and_end[] = array( $booking->get_start(), $booking->get_end() );
		}
		return $bookings_start_and_end;
	}

}
