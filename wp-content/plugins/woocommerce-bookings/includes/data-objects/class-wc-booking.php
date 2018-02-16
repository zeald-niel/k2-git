<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main model class for all bookings.
 */
class WC_Booking extends WC_Bookings_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'all_day'                  => false,
		'cost'                     => 0,
		'customer_id'              => 0,
		'date_created'             => '',
		'date_modified'            => '',
		'end'                      => '',
		'google_calendar_event_id' => 0,
		'order_id'                 => 0,
		'order_item_id'            => 0,
		'parent_id'                => 0,
		'person_counts'            => array(),
		'product_id'               => 0,
		'resource_id'              => 0,
		'start'                    => '',
		'status'                   => 'unpaid',
	);

	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $cache_group = 'booking';

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'booking';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'booking';


	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @since 1.10.0
	 *
	 * @version 1.10.0
	 *
	 * @var bool|array False if it's not transitioned. Otherwise an array containing
	 *                 transitioned status 'from' and 'to'.
	 */
	protected $status_transitioned = false;

	/**
	 * Constructor, possibly sets up with post or id belonging to existing booking
	 * or supplied with an array to construct a new booking.
	 *
	 * @param int|array|obj $booking
	 */
	public function __construct( $booking = 0 ) {
		parent::__construct( $booking );

		if ( is_array( $booking ) ) {
			if ( isset( $booking['user_id'] ) ) {
				$booking['customer_id'] = $booking['user_id'];
			}

			if ( isset( $booking['start_date'] ) ) {
				$booking['start'] = $booking['start_date'];
			}

			if ( isset( $booking['end_date'] ) ) {
				$booking['end'] = $booking['end_date'];
			}

			if ( isset( $booking['persons'] ) ) {
				$booking['person_counts'] = $booking['persons'];
			}

			// Inherit data from parent.
			if ( ! empty( $booking['parent_id'] ) ) {
				if ( ! $booking['order_item_id'] ) {
					$booking['order_item_id'] = $this->data_store->get_booking_order_item_id( $booking['parent_id'] );
				}
				if ( ! $booking['customer_id'] ) {
					$booking['customer_id'] = $this->data_store->get_booking_customer_id( $booking['parent_id'] );
				}
			}

			// Get order ID from order item
			if ( ! empty( $booking['order_item_id'] ) ) {
				if ( function_exists( 'wc_get_order_id_by_order_item_id' ) ) {
					$booking['order_id'] = wc_get_order_id_by_order_item_id( $booking['order_item_id'] );
				} else {
					global $wpdb;
					$booking['order_id'] = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
						$booking['order_item_id']
					) );
				}
			}

			// Get user ID.
			if ( empty( $booking['customer_id'] ) && is_user_logged_in() && ! is_admin() ) {
				$booking['customer_id'] = get_current_user_id();
			}

			// Setup the required data for the current user
			if ( empty( $booking['user_id'] ) ) {
				if ( is_user_logged_in() && ! is_admin() ) {
					$booking['user_id'] = get_current_user_id();
				}
			}

			$this->set_props( $booking );
			$this->set_object_read( true );
		} elseif ( is_numeric( $booking ) && $booking > 0 ) {
			$this->set_id( $booking );
		} elseif ( $booking instanceof self ) {
			$this->set_id( $booking->get_id() );
		} elseif ( ! empty( $booking->ID ) ) {
			$this->set_id( $booking->ID );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Save data to the database.
	 *
	 * @return int booking ID
	 */
	public function save() {
		if ( $this->data_store ) {
			// Trigger action before saving to the DB. Allows you to adjust object props before save.
			do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}
		}
		WC_Cache_Helper::get_transient_version( 'bookings', true );
		$this->status_transition();
		$this->schedule_events();
		return $this->get_id();
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		if ( $this->status_transitioned ) {
			$allowed_statuses = array(
				'was-in-cart' => __( 'Was In Cart', 'woocommerce-bookings' ),
			);

			$allowed_statuses = array_unique( array_merge(
				$allowed_statuses,
				get_wc_booking_statuses( null, true ),
				get_wc_booking_statuses( 'user', true ),
				get_wc_booking_statuses( 'cancel', true )
			) );

			$from = ! empty( $allowed_statuses[ $this->status_transitioned['from'] ] )
				? $allowed_statuses[ $this->status_transitioned['from'] ]
				: false;

			$to = ! empty( $allowed_statuses[ $this->status_transitioned['to'] ] )
				? $allowed_statuses[ $this->status_transitioned['to'] ]
				: false;

			if ( $from && $to ) {
				$this->status_transitioned_handler( $from, $to );
			}

			// This has ran, so reset status transition variable.
			$this->status_transitioned = false;
		}
	}

	/**
	 * Skip status transition events.
	 *
	 * Allows self::status_transition to be bypassed before calling self::save().
	 *
	 * @since 1.10.0
	 *
	 * @version 1.10.0
	 */
	public function skip_status_transition_events() {
		$this->status_transitioned = false;
	}

	/**
	 * Handler when booking status is transitioned.
	 *
	 * @since 1.10.0
	 *
	 * @param string $from Status from.
	 * @param string $to   Status to.
	 */
	protected function status_transitioned_handler( $from, $to ) {
		// Fire the events of valid status has been transitioned.
		do_action( 'woocommerce_booking_' . $this->status_transitioned['to'], $this->get_id(), $this );
		do_action( 'woocommerce_booking_' . $this->status_transitioned['from'] . '_to_' . $this->status_transitioned['to'], $this->get_id(), $this );

		// add note to related order
		if ( $order = $this->get_order() ) {
			$order->add_order_note( sprintf( __( 'Booking #%1$d status changed from "%2$s" to "%3$s"', 'woocommerce-bookings' ), $this->get_id(), $from, $to ) );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Getters and setters.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all_day.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_all_day( $context = 'view' ) {
		return $this->get_prop( 'all_day', $context );
	}

	/**
	 * Set all_day.
	 *
	 * @param boolean $value
	 */
	public function set_all_day( $value ) {
		$this->set_prop( 'all_day', wc_bookings_string_to_bool( $value ) );
	}

	/**
	 * Get cost.
	 *
	 * @param  string $context
	 * @return float
	 */
	public function get_cost( $context = 'view' ) {
		return $this->get_prop( 'cost', $context );
	}

	/**
	 * Set cost.
	 *
	 * @param float $value
	 */
	public function set_cost( $value ) {
		$this->set_prop( 'cost', wc_format_decimal( $value ) );
	}

	/**
	 * Get customer_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_customer_id( $context = 'view' ) {
		return $this->get_prop( 'customer_id', $context );
	}

	/**
	 * Set customer_id.
	 *
	 * @param integer $value
	 */
	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', absint( $value ) );
	}

	/**
	 * Get date_created.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set date_created.
	 *
	 * @param string $timestamp Timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_created( $timestamp ) {
		$this->set_prop( 'date_created', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Get date_modified.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set date_modified.
	 *
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_modified( $timestamp ) {
		$this->set_prop( 'date_modified', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Get end_time.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_end( $context = 'view', $deprecated = '' ) {
		return $this->get_prop( 'end', $context );
	}

	/**
	 * Set end_time.
	 *
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_end( $timestamp ) {
		$this->set_prop( 'end', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Get google_calendar_event_id.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_google_calendar_event_id( $context = 'view' ) {
		return $this->get_prop( 'google_calendar_event_id', $context );
	}

	/**
	 * Set google_calendar_event_id
	 *
	 * @param string $value
	 */
	public function set_google_calendar_event_id( $value ) {
		$this->set_prop( 'google_calendar_event_id', $value );
	}

	/**
	 * Get order ID.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Set order_id
	 *
	 * @param  int $value
	 */
	public function set_order_id( $value ) {
		$this->set_prop( 'order_id', absint( $value ) );
	}

	/**
	 * Get order_item_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_order_item_id( $context = 'view' ) {
		return $this->get_prop( 'order_item_id', $context );
	}

	/**
	 * Set order_item_id.
	 *
	 * @param integer $value
	 */
	public function set_order_item_id( $value ) {
		$this->set_prop( 'order_item_id', absint( $value ) );
	}

	/**
	 * Get parent ID.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Set parent ID.
	 *
	 * @param  int $value
	 */
	public function set_parent_id( $value ) {
		$this->set_prop( 'parent_id', absint( $value ) );
	}

	/**
	 * Get person_counts.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_person_counts( $context = 'view' ) {
		return $this->get_prop( 'person_counts', $context );
	}

	/**
	 * Set person_counts.
	 *
	 * @param integer $value
	 */
	public function set_person_counts( $value ) {
		$this->set_prop( 'person_counts', array_map( 'absint', array_filter( (array) $value ) ) );
	}

	/**
	 * Get product_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Set product_id.
	 *
	 * @param integer $value
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Get resource_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_resource_id( $context = 'view' ) {
		return $this->get_prop( 'resource_id', $context );
	}

	/**
	 * Set resource_id.
	 *
	 * @param integer $value
	 */
	public function set_resource_id( $value ) {
		$this->set_prop( 'resource_id', absint( $value ) );
	}

	/**
	 * Get start_time.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_start( $context = 'view', $deprecated = '' ) {
		return $this->get_prop( 'start', $context );
	}

	/**
	 * Set start_time.
	 *
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_start( $timestamp ) {
		$this->set_prop( 'start', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Return the status without wc- internal prefix.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Set status.
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @return array details of change
	 */
	public function set_status( $new_status ) {
		$old_status = $this->get_status();

		$this->set_prop( 'status', $new_status );

		if ( $new_status !== $old_status ) {
			$this->status_transitioned = array(
				'from' => $old_status,
				'to'   => $new_status,
			);
		}

		return array(
			'from' => $old_status,
			'to'   => $new_status,
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Conditonals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Checks the booking status against a passed in status.
	 *
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'woocommerce_booking_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}

	/**
	 * Return if all day event.
	 *
	 * @return boolean
	 */
	public function is_all_day() {
		return $this->get_all_day();
	}

	/**
	 * See if this booking is within a block.
	 *
	 * @return boolean
	 */
	public function is_within_block( $block_start, $block_end ) {
		if ( ! $this->get_start() || ! $this->get_end() || $this->get_start() >= $block_end || $this->get_end() <= $block_start ) {
			return false;
		}
		return true;
	}

	/**
	 * See if this booking is booked on a date.
	 *
	 * @return boolean
	 */
	public function is_booked_on_day( $block_start, $block_end ) {
		$is_booked        = false;
		$loop_date        = $this->get_start();
		$multiday_booking = date( 'Y-m-d', $this->get_start() ) < date( 'Y-m-d', $this->get_end() );

		if ( $multiday_booking ) {
			if ( date( 'YmdHi', $block_end ) > date( 'YmdHi', $this->get_start() ) && date( 'YmdHi', $block_start ) < date( 'YmdHi', $this->get_end() ) ) {
				$is_booked = true;
			} else {
				$is_booked = false;
			}
		} else {
			while ( $loop_date <= $this->get_end() ) {
				if ( date( 'Y-m-d', $loop_date ) === date( 'Y-m-d', $block_start ) ) {
					$is_booked = true;
				}
				$loop_date = strtotime( '+1 day', $loop_date );
			}
		}

		/**
		 * Filter the booking objects is_booked_on_day method return result.
		 *
		 * @since 1.9.13
		 *
		 * @param bool $is_books
		 * @param WC_Booking $booking
		 * @param WC_Booking $block_start
		 * @param WC_Booking $block_end
		 */
		return apply_filters( 'woocommerce_booking_is_booked_on_day', $is_booked, $this, $block_start, $block_end );
	}

	/**
	 * See if this booking can still be cancelled by the user or not.
	 *
	 * @return boolean
	 */
	public function passed_cancel_day() {
		$booking = $this->get_product();

		if ( ! $booking || ! $booking->can_be_cancelled() ) {
			return true;
		}

		if ( false !== $booking ) {
			$cancel_limit      = $booking->get_cancel_limit();
			$cancel_limit_unit = $cancel_limit > 1 ? $booking->get_cancel_limit_unit() . 's' : $booking->get_cancel_limit_unit();
			$cancel_string     = sprintf( '%s +%d %s', current_time( 'd F Y H:i:s' ), $cancel_limit, $cancel_limit_unit );

			if ( strtotime( $cancel_string ) >= $this->get_start() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns if persons are enabled/needed for the booking product
	 * @return boolean
	 */
	public function has_persons() {
		return $this->get_product() ? $this->get_product()->has_persons() : false;
	}

	/**
	 * Returns if resources are enabled/needed for the booking product
	 * @return boolean
	 */
	public function has_resources() {
		return $this->get_product() ? $this->get_product()->has_resources() : false;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD getters/helpers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns booking start date.
	 *
	 * @return string Date formatted via date_i18n
	 */
	public function get_start_date( $date_format = null, $time_format = null ) {
		if ( $this->get_start() ) {
			if ( is_null( $date_format ) ) {
				$date_format = apply_filters( 'woocommerce_bookings_date_format', wc_date_format() );
			}
			if ( is_null( $time_format ) ) {
				$time_format = apply_filters( 'woocommerce_bookings_time_format', ', ' . wc_time_format() );
			}
			if ( $this->is_all_day() ) {
				return date_i18n( $date_format, $this->get_start() );
			} else {
				return apply_filters( 'woocommerce_bookings_get_start_date_with_time', date_i18n( $date_format . $time_format, $this->get_start() ), $this );
			}
		}
		return false;
	}

	/**
	 * Returns booking end date.
	 *
	 * @return string Date formatted via date_i18n
	 */
	public function get_end_date( $date_format = null, $time_format = null ) {
		if ( $this->get_end() ) {
			if ( is_null( $date_format ) ) {
				$date_format = apply_filters( 'woocommerce_bookings_date_format', wc_date_format() );
			}
			if ( is_null( $time_format ) ) {
				$time_format = apply_filters( 'woocommerce_bookings_time_format', ', ' . wc_time_format() );
			}
			if ( $this->is_all_day() ) {
				return date_i18n( $date_format, $this->get_end() );
			} else {
				return apply_filters( 'woocommerce_bookings_get_end_date_with_time', date_i18n( $date_format . $time_format, $this->get_end() ), $this );
			}
		}
		return false;
	}

	/**
	 * Return the amount of persons for this booking.
	 *
	 * @return int
	 */
	public function get_persons() {
		return $this->get_person_counts();
	}

	/**
	 * Return the amount of persons for this booking.
	 *
	 * @return int
	 */
	public function get_persons_total() {
		return array_sum( $this->get_person_counts() );
	}

	/**
	 * Returns the object of the order corresponding to this booking.
	 *
	 * @return WC_Product|Boolean
	 */
	public function get_product() {
		try {
			if ( $this->get_product_id() ) {
				return new WC_Product_Booking( $this->get_product_id() );
			}
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns the object of the order corresponding to this booking.
	 *
	 * @return WC_Order|Boolean
	 */
	public function get_order() {
		return $this->get_order_id() ? wc_get_order( $this->get_order_id() ) : false;
	}

	/**
	 * Returns information about the customer of this order.
	 *
	 * @return object containing customer information
	 */
	public function get_customer() {
		$name    = '';
		$email   = '';
		$user_id = 0;

		if ( $order = $this->get_order() ) {
			$first_name = is_callable( array( $order, 'get_billing_first_name' ) ) ? $order->get_billing_first_name() : $order->billing_first_name;
			$last_name  = is_callable( array( $order, 'get_billing_last_name' ) ) ? $order->get_billing_last_name()   : $order->billing_last_name;
			$name       = trim( $first_name . ' ' . $last_name );
			$email      = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email()           : $order->billing_email;
			$user_id    = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id()               : $order->customer_user;
			$name 		= 0 !== absint( $user_id ) ? $name : sprintf( _x( '%s (Guest)', 'Guest string with name from booking order in brackets', 'woocommerce-bookings' ), $name );
		} elseif ( $this->get_customer_id() ) {
			$user    = get_user_by( 'id', $this->get_customer_id() );
			$name    = $user->display_name;
			$email   = $user->user_email;
			$user_id = $this->get_customer_id();
		}
		return (object) array(
			'name'    => $name,
			'email'   => $email,
			'user_id' => $user_id,
		);
	}

	/**
	 * Get the resource/type for this booking if applicable.
	 *
	 * @return bool|object WP_Post
	 */
	public function get_resource() {
		$resource_id = $this->get_resource_id();

		if ( ! $resource_id || ! ( $product = $this->get_product() ) || ! method_exists( $product, 'get_resource' ) ) {
			return false;
		}

		return $product->get_resource( $resource_id );
	}

	/**
	 * Schedule events for this booking.
	 */
	public function schedule_events() {
		$order = $this->get_order();

		if ( in_array( $this->get_status(), get_wc_booking_statuses( 'scheduled' ) ) ) {
			$tz_addition = 'yes' !== get_option( 'woocommerce_bookings_tz_calculation' ) ? 0 : - wc_booking_timezone_offset();

			if ( $this->get_start() ) {
				$order_status = $order ? $order->get_status() : null;

				// If there is no order, or the order is not in one of the statuses then schedule events.
				if ( ! in_array( $order_status, array( 'cancelled', 'refunded', 'pending', 'on-hold' ) ) ) {
					wp_clear_scheduled_hook( 'wc-booking-reminder', array( $this->get_id() ) );
					wp_schedule_single_event( $tz_addition + strtotime( '-' . absint( apply_filters( 'woocommerce_bookings_remind_before_days', 1 ) ) . ' day', $this->get_start() ), 'wc-booking-reminder', array( $this->get_id() ) );
				}
			}
			if ( $this->get_end() ) {
				wp_clear_scheduled_hook( 'wc-booking-complete', array( $this->get_id() ) );
				wp_schedule_single_event( $tz_addition + $this->get_end(), 'wc-booking-complete', array( $this->get_id() ) );
			}
		} else {
			wp_clear_scheduled_hook( 'wc-booking-reminder', array( $this->get_id() ) );
			wp_clear_scheduled_hook( 'wc-booking-complete', array( $this->get_id() ) );
		}
	}

	/**
	 * Returns the cancel URL for a booking
	 *
	 * @param string $redirect
	 * @return string
	 */
	public function get_cancel_url( $redirect = '' ) {
		$cancel_page = get_permalink( wc_get_page_id( 'myaccount' ) );

		if ( ! $cancel_page ) {
			$cancel_page = home_url();
		}

		return apply_filters( 'bookings_cancel_booking_url', wp_nonce_url( add_query_arg( array( 'cancel_booking' => 'true', 'booking_id' => $this->get_id(), 'redirect' => $redirect ), $cancel_page ), 'woocommerce-bookings-cancel_booking' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Legacy.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Actualy create for the new booking belonging to an order.
	 *
	 * @param string Status for new order
	 */
	public function create( $status = 'unpaid' ) {
		$this->set_status( $status );
		$this->save();
	}

	/**
	 * Will change the booking status once the order is paid for.
	 *
	 * @return bool
	 */
	public function paid() {
		if ( in_array( $this->get_status(), array( 'unpaid', 'confirmed', 'wc-partial-payment' ) ) ) {
			$this->set_status( 'paid' );
			$this->save();
			return true;
		}
		return false;
	}

	/**
	 * Populate the data with the id of the booking provided
	 * Will query for the post belonging to this booking and store it
	 *
	 * @return boolean
	 * @param int $booking_id
	 */
	public function populate_data( $booking_id ) {
		$this->set_defaults();
		$this->set_id( $booking_id );
		$this->data_store->read( $this );
		return 0 < $this->get_id();
	}

	/**
	 * Set the new status for this booking.
	 *
	 * @param string $status
	 * @return bool
	 */
	public function update_status( $status ) {
		$current_status                  = $this->get_status( 'edit' );
		$allowed_statuses                = array_unique( array_merge( get_wc_booking_statuses( null, true ), get_wc_booking_statuses( 'user', true ), get_wc_booking_statuses( 'cancel', true ) ) );
		$allowed_statuses['was-in-cart'] = __( 'Was In Cart', 'woocommerce-bookings' );
		$allowed_status_keys             = array_keys( $allowed_statuses );

		if ( in_array( $status, $allowed_status_keys ) ) {
			$this->set_status( $status );
			$this->save();

			return true;
		}
		return false;
	}

	/**
	 * Magic __isset method for backwards compatibility.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		$legacy_props = array( 'booking_date', 'modified_date', 'populated', 'post', 'custom_fields' );
		return $this->get_id() ? ( in_array( $key, $legacy_props ) || is_callable( array( $this, "get_{$key}" ) ) ) : false;
	}

	/**
	 * Magic __get method for backwards compatibility.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		// wc_doing_it_wrong( $key, 'Booking properties should not be accessed directly.', '1.10.0' ); @todo deprecated when 2.6.x dropped
		if ( 'booking_date' === $key ) {
			return $this->get_date_created();
		} elseif ( 'modified_date' === $key ) {
			return $this->get_date_modified();
		} elseif ( 'populated' === $key ) {
			return $this->get_object_read();
		} elseif ( 'post' === $key ) {
			return get_post( $this->get_id() );
		} elseif ( 'custom_fields' === $key ) {
			return get_post_meta( $this->get_id() );
		} elseif ( is_callable( array( $this, "get_{$key}" ) ) ) {
			return $this->{"get_{$key}"}();
		} else {
			return get_post_meta( $this->get_id(), '_' . $key, true );
		}
	}
}
