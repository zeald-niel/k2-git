<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * User Membership class
 *
 * This class represents a single user's membership, ie. a user belonging
 * to a User Membership. A single user can have multiple memberships.
 *
 * @since 1.0.0
 */
class WC_Memberships_User_Membership {


	/** @var int User Membership (post) ID */
	public $id;

	/** @var int User Membership plan id */
	public $plan_id;

	/** @var \WC_Memberships_Membership_Plan User Membership plan */
	public $plan;

	/** @var int User Membership user (author) id */
	public $user_id;

	/** @var string User Membership (post) status */
	public $status;

	/** @var \WP_Post User Membership post object */
	public $post;

	/** @var \WC_Product the product that granted access */
	private $product;

	/** @var string Membership type */
	protected $type = '';

	/** @var string start date meta */
	protected $start_date_meta = '';

	/** @var string end date meta */
	protected $end_date_meta = '';

	/** @var string cancelled date meta */
	protected $cancelled_date_meta = '';

	/** @var string paused date meta */
	protected $paused_date_meta = '';

	/** @var string paused intervals meta */
	protected $paused_intervals_meta = '';

	/** @var string product id meta */
	protected $product_id_meta = '';

	/** @var string order id meta */
	protected $order_id_meta = '';

	/** @var string previous owners meta */
	protected $previous_owners_meta = '';


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param int|\WP_Post|\WC_Memberships_User_Membership $id User Membership ID or post object
	 * @param int $user_id Optional User / Member ID, used only for new memberships
	 */
	public function __construct( $id, $user_id = null ) {

		if ( ! $id ) {
			return;
		}

		if ( is_numeric( $id ) ) {
			$this->post = get_post( $id );
		} elseif ( is_object( $id ) ) {
			$this->post = $id;
		}

		if ( $this->post ) {

			// load in post data...
			$this->id      = $this->post->ID;
			$this->user_id = $this->post->post_author;
			$this->plan_id = $this->post->post_parent;
			$this->status  = $this->post->post_status;

		} elseif ( $user_id ) {

			// ...or at least user ID, if provided
			$this->user_id = $user_id;
		}

		// set meta keys
		$this->start_date_meta       = '_start_date';
		$this->end_date_meta         = '_end_date';
		$this->cancelled_date_meta   = '_cancelled_date';
		$this->paused_date_meta      = '_paused_date';
		$this->paused_intervals_meta = '_paused_intervals';
		$this->product_id_meta       = '_product_id';
		$this->order_id_meta         = '_order_id';
		$this->previous_owners_meta  = '_previous_owners';

		// set membership type
		$this->type = $this->get_type();
	}


	/**
	 * Get the ID
	 *
	 * @since 1.0.0
	 * @return int User Membership ID
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Get the user ID
	 *
	 * @since 1.0.0
	 * @return int User ID
	 */
	public function get_user_id() {
		return $this->user_id;
	}


	/**
	 * Get the plan ID
	 *
	 * @since 1.0.0
	 * @return int Membership Plan id
	 */
	public function get_plan_id() {
		return $this->plan_id;
	}


	/**
	 * Get the plan object
	 *
	 * @since 1.0.0
	 * @return \WC_Memberships_Membership_Plan
	 */
	public function get_plan() {

		if ( ! $this->plan ) {
			// get the plan if not already set
			$this->plan = $plan = wc_memberships_get_membership_plan( $this->plan_id, $this );
		} else {
			// get the plan already set but make sure it comes out filtered
			$plan = $this->plan;
			$post = ! empty( $this->plan ) ? $plan->post : null;
			/** this filter is documented in /includes/class-wc-memberships-membership-plans.php */
			$plan = apply_filters( 'wc_memberships_membership_plan', $plan, $post, $this );
		}

		return $plan;
	}


	/**
	 * Return the membership status without wc- internal prefix
	 *
	 * @since 1.0.0
	 * @return string Status slug
	 */
	public function get_status() {
		return 0 === strpos( $this->status, 'wcm-' ) ? substr( $this->status, 4 ) : $this->status;
	}


	/**
	 * Get the membership type
	 *
	 * @since 1.7.0
	 */
	public function get_type() {

		$type = 'manually-assigned';
		$plan = $this->get_plan();

		if ( $plan ) {

			$access_method = $plan->get_access_method();

			if ( 'signup' === $access_method ) {
				$type = 'free';
			} elseif ( 'purchase' === $access_method ) {
				// if there is no order or product, this must have been admin assigned
				$type = $this->get_order_id() && $this->get_product_id() ? 'purchased' : $type;
			}
		}

		/**
		 * Filter a user membership type
		 *
		 * @since 1.7.0
		 * @param string $type Membership type
		 * @param \WC_Memberships_User_Membership $user_membership The membership object
		 */
		$this->type = apply_filters( 'wc_memberships_user_membership_type', $type, $this );

		return $this->type;
	}


	/**
	 * Check if the membership is of the specified type
	 *
	 * @since 1.7.0
	 * @param array|string $type The membership type to check
	 * @return bool
	 */
	public function is_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_type(), $type, true ) : $type === $this->get_type();
	}


	/**
	 * Set the membership start datetime
	 *
	 * @since 1.6.2
	 * @param string $date Date in MySQL format
	 */
	public function set_start_date( $date ) {

		$start_date = wc_memberships_parse_date( $date, 'mysql' );
		$now        = current_time( 'timestamp', true );

		if ( ! $start_date ) {
			$start_date = date( 'Y-m-d H:i:s', $now );
		}

		update_post_meta( $this->id, $this->start_date_meta, $start_date );

		if ( 'delayed' !== $this->get_status() && strtotime( 'today', strtotime( $start_date ) ) > $now ) {

			$this->update_status( 'delayed' );
		}
	}


	/**
	 * Get the membership start datetime
	 *
	 * @since 1.0.0
	 * @param string $format Optional, defaults to 'mysql'
	 * @return null|int|string Start date in the chosen format
	 */
	public function get_start_date( $format = 'mysql' ) {

		$date = get_post_meta( $this->id, $this->start_date_meta, true );

		return ! empty( $date ) ? wc_memberships_format_date( $date, $format ) : null;
	}


	/**
	 * Get the membership start local datetime
	 *
	 * @since 1.3.8
	 * @param string $format Optional, defaults to 'mysql'
	 * @return null|int|string Localized start date in the chosen format
	 */
	public function get_local_start_date( $format = 'mysql' ) {

		// get the date timestamp
		$date = $this->get_start_date( 'timestamp' );

		// adjust the date to the site's local timezone
		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Set the membership end datetime
	 *
	 * @since 1.0.0
	 * @param string|int $date End date either as a unix timestamp or mysql datetime string
	 *                         Defaults to empty string (unlimited membership, no end date)
	 */
	public function set_end_date( $date = '' ) {

		$end_timestamp = '';
		$end_date      = '';

		if ( is_numeric( $date ) ) {
			$end_timestamp = (int) $date;
		} elseif ( is_string( $date ) ) {
			$end_timestamp = strtotime( $date );
		}

		if ( ! empty( $end_timestamp ) ) {

			// for fixed date memberships set end date to the end of the day
			$end_timestamp = $this->get_plan() && $this->plan->is_access_length_type( 'fixed' ) ? wc_memberships_adjust_date_by_timezone( strtotime( 'midnight', $end_timestamp ), 'timestamp', wc_timezone_string() ) : $end_timestamp;

			$end_date = date( 'Y-m-d H:i:s', (int) $end_timestamp );
		}

		// update end date in post meta
		update_post_meta( $this->id, $this->end_date_meta, $end_date );

		// set expiration scheduled events
		$this->schedule_expiration_events( $end_timestamp );
	}


	/**
	 * Get the membership end datetime
	 *
	 * @since 1.0.0
	 * @param string $format Optional, defaults to 'mysql'
	 * @param bool $include_paused Optional: whether to include the time this membership
	 *                             has been paused (defaults to true)
	 * @return null|int|string The end date in the chosen format
	 */
	public function get_end_date( $format = 'mysql', $include_paused = true ) {

		$date = get_post_meta( $this->id, $this->end_date_meta, true );

		// adjust end/expiry date if paused date exists
		if ( $date && $include_paused && $paused_date = $this->get_paused_date( 'timestamp' ) ) {

			$difference    = current_time( 'timestamp', true ) - $paused_date;
			$end_timestamp = strtotime( $date ) + $difference;

			$date = date( 'Y-m-d H:i:s', $end_timestamp );
		}

		return ! empty( $date ) ? wc_memberships_format_date( $date, $format ) : null;
	}


	/**
	 * Get the membership end local datetime
	 *
	 * @since 1.3.8
	 * @param string $format Optional, defaults to 'mysql'
	 * @param bool $include_paused Optional: whether to include the time this membership
	 *                             has been paused (defaults to true)
	 * @return null|int|string The localized end date in the chosen format
	 */
	public function get_local_end_date( $format = 'mysql', $include_paused = true ) {

		// get the date timestamp
		$date = $this->get_end_date( 'timestamp', $include_paused );

		// adjust the date to the site's local timezone
		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Get the membership cancelled datetime
	 *
	 * @since 1.6.2
	 * @param string $format Optional, defaults to 'mysql'
	 * @return null|int|string The cancelled date in the chosen format
	 */
	public function get_cancelled_date( $format = 'mysql' ) {

		$date = get_post_meta( $this->id, $this->cancelled_date_meta, true );

		return ! empty( $date ) ? wc_memberships_format_date( $date, $format ) : null;
	}


	/**
	 * Get the membership cancelled local datetime
	 *
	 * @since 1.6.2
	 * @param string $format Optional, defaults to 'mysql'
	 * @return null|int|string The localized cancelled date in the chosen format
	 */
	public function get_local_cancelled_date( $format = 'mysql' ) {

		// get the date timestamp
		$date = $this->get_cancelled_date( 'timestamp' );

		// adjust the date to the site's local timezone
		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Set the membership cancelled datetime
	 *
	 * @since 1.6.2
	 * @param string $date Date in MySQL format
	 */
	public function set_cancelled_date( $date ) {

		if ( $cancelled_date = wc_memberships_parse_date( $date, 'mysql' ) ) {

			update_post_meta( $this->id, $this->cancelled_date_meta, $cancelled_date );
		}
	}


	/**
	 * Get the membership paused datetime
	 *
	 * @since 1.0.0
	 * @param string $format Optional, defaults to 'mysql'
	 * @return null|int|string The paused date in the chosen format
	 */
	public function get_paused_date( $format = 'mysql' ) {

		$date = get_post_meta( $this->id, $this->paused_date_meta, true );

		return ! empty( $date ) ? wc_memberships_format_date( $date, $format ) : null;
	}


	/**
	 * Get the membership end local datetime
	 *
	 * @since 1.3.8
	 * @param string $format Optional, defaults to 'mysql'
	 * @return null|int|string The localized paused date in the chosen format
	 */
	public function get_local_paused_date( $format = 'mysql' ) {

		// get the date timestamp
		$date = $this->get_paused_date( 'timestamp' );

		// adjust the date to the site's local timezone
		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Set the membership paused datetime
	 *
	 * @since 1.6.2
	 * @param string $date Date in MySQL format
	 */
	public function set_paused_date( $date ) {

		if ( $paused_date = wc_memberships_parse_date( $date, 'mysql' ) ) {

			update_post_meta( $this->id, $this->paused_date_meta, $paused_date );
		}
	}


	/**
	 * Removes the membership paused datetime information
	 *
	 * @since 1.6.2
	 */
	public function delete_paused_date() {

		delete_post_meta( $this->id, $this->paused_date_meta );
	}


	/**
	 * Get the memberships paused periods as an associative array of timestamps
	 *
	 * @since 1.6.2
	 * @return array Associative array of start => end ranges of paused intervals
	 */
	public function get_paused_intervals() {

		$intervals = get_post_meta( $this->id, $this->paused_intervals_meta, true );

		return is_array( $intervals ) ? $intervals : array();
	}


	/**
	 * Add a record to the membership pausing registry
	 *
	 * @since 1.6.2
	 * @param string $interval Either 'start' or 'end'
	 * @param int $time A valid timestamp in UTC
	 */
	public function set_paused_interval( $interval, $time ) {

		if ( ! is_numeric( $time ) || (int) $time <= 0 ) {
			return;
		}

		$intervals = $this->get_paused_intervals();

		if ( 'start' === $interval ) {

			// sanity check to avoid overwriting an existing key
			if ( ! array_key_exists( $time, $intervals ) ) {
				$intervals[ (int) $time ] = '';
			}

		} elseif ( 'end' === $interval ) {

			if ( ! empty( $intervals ) ) {

				// get the last timestamp when the membership was paused
				end( $intervals );
				$last = key( $intervals );

				// sanity check to avoid overwriting an existing value
				if ( is_numeric( $last ) && empty( $intervals[ $last ] ) ) {
					$intervals[ (int) $last ] = (int) $time;
				}

			// this might be the case where a paused membership didn't have interval tracking yet
			} elseif ( $this->is_paused() && $paused_date = $this->get_paused_date( 'timestamp' ) ) {

				$intervals[ (int) $paused_date ] = (int) $time;
			}
		}

		update_post_meta( $this->id, $this->paused_intervals_meta, $intervals );
	}


	/**
	 * Deletes the paused intervals data
	 *
	 * @since 1.7.0
	 */
	public function delete_paused_intervals() {

		delete_post_meta( $this->id, $this->paused_intervals_meta );
	}


	/**
	 * Get the total active or inactive time of a membership
	 *
	 * @since 1.6.2
	 * @param string $type Either 'active' or 'inactive'
	 * @param string $format Optional, can be either 'timestamp' (default) or 'human'
	 * @return null|int|string Timestamp or human readable string
	 */
	private function get_total_time( $type, $format = 'timestamp' ) {

		$total  = null;
		$time   = 0; // time as 0 seconds
		$start  = $this->get_start_date( 'timestamp' );
		$pauses = $this->get_paused_intervals();

		// set 'time' as now or the most recent time when the membership was active
		if ( 'active' === $type ) {

			if ( $this->is_expired() ) {
				$time = $this->get_end_date( 'timestamp' );
			} elseif ( $this->is_cancelled() ) {
				$time = $this->get_cancelled_date( 'timestamp' );
			}

			if ( empty( $total ) ) {
				$time = current_time( 'timestamp', true );
			}
		}

		if ( ! empty( $pauses ) ) {

			end( $pauses );
			$last = key( $pauses );

			// if the membership is currently paused, add the time until now
			if ( isset( $pauses[ $last ] ) && '' === $pauses[ $last ] && $this->is_paused() ) {
				$pauses[ $last ] = current_time( 'timestamp', true );
			}

			reset( $pauses );

			$previous_start = (int) $start;

			foreach ( $pauses as $pause_start => $pause_end ) {

				// sanity check, see if there is a previous interval without an end record
				// or if the start record in the key is invalid
				if ( empty( $pause_end ) || $pause_start < $previous_start ) {
					continue;
				}

				if ( 'active' === $type ) {
					// subtract from the most recent active time paused intervals
					$time -= max( 0, (int) $pause_end - (int) $pause_start );
				} elseif ( 'inactive' === $type ) {
					// add up from 0s the time this membership has been inactive
					$time += max( 0, (int) $pause_end - (int) $pause_start );
				}

				$previous_start = (int) $pause_start;
			}
		}

		// get the total as a difference
		if ( 'active' === $type ) {
			$total = max( 0, $time - $start );
		} elseif ( 'inactive' === $type ) {
			$total = max( 0, $time );
		}

		// maybe humanize the output
		if ( 'human' === $format && is_int( $total ) ) {

			$time_diff = max( $start, $start + $total );
			$total     = $time_diff !== $start && $time_diff > 0 ? human_time_diff( $start, $time_diff ) : 0;
		}

		return $total;
	}


	/**
	 * Get the total amount of time the membership has been active
	 * since its start date
	 *
	 * @since 1.6.2
	 * @param string $format Optional, can be either 'timestamp' (default) or 'human'
	 *                       for a human readable span relative to the start date
	 * @return int|string Timestamp or human readable string
	 */
	public function get_total_active_time( $format = 'timestamp' ) {
		return $this->get_total_time( 'active', $format );
	}


	/**
	 * Get the total amount of time the membership has been inactive
	 * since its start date
	 *
	 * @since 1.6.2
	 * @param string $format Optional, can be either 'timestamp' (default) or 'human'
	 *                       for a human readable inactive time span
	 * @return int|string Timestamp or human readable string
	 */
	public function get_total_inactive_time( $format = 'timestamp' ) {
		return $this->get_total_time( 'inactive', $format );
	}


	/**
	 * Unschedule expiration events
	 *
	 * @since 1.7.0
	 */
	public function unschedule_expiration_events() {

		$hook_args = array( 'user_membership_id' => $this->id );

		// unschedule any previous expiry hooks
		if ( wc_next_scheduled_action( 'wc_memberships_user_membership_expiry', $hook_args, 'woocommerce-memberships'  ) ) {
			wc_unschedule_action( 'wc_memberships_user_membership_expiry', $hook_args, 'woocommerce-memberships' );
		}

		// unschedule any previous expiring soon hooks
		if ( wc_next_scheduled_action( 'wc_memberships_user_membership_expiring_soon', $hook_args, 'woocommerce-memberships' ) ) {
			wc_unschedule_action( 'wc_memberships_user_membership_expiring_soon', $hook_args, 'woocommerce-memberships' );
		}

		// unschedule any previous renewal reminder hooks
		if ( wc_next_scheduled_action( 'wc_memberships_user_membership_renewal_reminder', $hook_args, 'woocommerce-memberships' ) ) {
			wc_unschedule_action( 'wc_memberships_user_membership_renewal_reminder', $hook_args, 'woocommerce-memberships' );
		}
	}


	/**
	 * Set expiration events for this membership.
	 *
	 * Note: the renewal reminder is only set contextually when the membership is expired.
	 *
	 * @see \WC_Memberships_User_Membership::set_end_date()
	 * @see \WC_Memberships_User_Membership::expire_membership()
	 * @see WC_Memberships_User_Memberships::trigger_expiration_events()
	 *
	 * @since 1.7.0
	 * @param int|null $end_timestamp membership end date timestamp: when empty (unlimited membership), it will just clear any existing scheduled event
	 */
	public function schedule_expiration_events( $end_timestamp = null ) {

		$now = current_time( 'timestamp', true );

		// always unschedule events for the same membership first
		$this->unschedule_expiration_events();

		// schedule membership expiration hooks, provided there's an end date and it's after the beginning of today's date
		if ( is_numeric( $end_timestamp ) && (int) $end_timestamp > strtotime( 'today', $now ) ) {

			$hook_args = array( 'user_membership_id' => $this->id );

			// Schedule the membership expiration event:
			wc_schedule_single_action( $end_timestamp, 'wc_memberships_user_membership_expiry', $hook_args, 'woocommerce-memberships' );

			// Schedule the membership ending soon event:
			$days_before_expiry = $this->get_expiring_soon_time_before( $end_timestamp );
			if ( $end_timestamp - $days_before_expiry >= DAY_IN_SECONDS ) {
				if ( $days_before_expiry > $now ) {
					// if there's at least one day before the expiry date, use the email setting (days before)
					wc_schedule_single_action( $days_before_expiry, 'wc_memberships_user_membership_expiring_soon', $hook_args, 'woocommerce-memberships' );
				} elseif ( $end_timestamp > $now && $median_time = absint( ( $now + $end_timestamp ) / 2 ) ) {
					// if it's less than one day, schedule as a median time between now and the effective end date (in the course of the last remaining day)
					wc_schedule_single_action( $median_time, 'wc_memberships_user_membership_expiring_soon', $hook_args, 'woocommerce-memberships' );
				}

			}
		}
	}


	/**
	 * Get timestamp for days before expiry date
	 *
	 * TODO consider moving to WC_Memberships_User_Memberships class especially if opening this method to public or moving the setting away from the WC email {FN 2016-09-14}
	 *
	 * @see \WC_Memberships_User_Membership::schedule_expiration_events()
	 *
	 * @since 1.7.0
	 * @param int $expiry_date Timestamp when the membership expires
	 * @return int Timestamp
	 */
	private function get_expiring_soon_time_before( $expiry_date ) {

		// the email that stores the setting
		$email = 'WC_Memberships_User_Membership_Ending_Soon_Email';

		/** @see \WC_Memberships_User_Membership_Ending_Soon_Email */
		$email_setting = get_option( "woocommerce_{$email}_settings" );

		if (    $email_setting
		     && isset( $email_setting['send_days_before'] )
		     && $days_before = absint( $email_setting['send_days_before'] ) ) {

			$time_before = $expiry_date - ( max( 1, $days_before ) * DAY_IN_SECONDS );

			// sanity check: the future can't be in the past :)
			return $time_before > current_time( 'timestamp', true ) ? $time_before : $expiry_date - DAY_IN_SECONDS;
		}

		// default value (3 days before)
		return $expiry_date - ( 3 * DAY_IN_SECONDS );
	}


	/**
	 * Get timestamp for days before expiry date
	 *
	 * TODO consider moving to WC_Memberships_User_Memberships class especially if opening this method to public or moving the setting away from the WC email {FN 2016-09-14}
	 *
	 * @see \WC_Memberships_User_Membership::expire_membership()
	 *
	 * @since 1.7.0
	 * @param int $expiry_date Timestamp when the membership expires
	 * @return int Timestamp
	 */
	private function get_expired_time_after( $expiry_date ) {

		// the email that stores the setting
		$email = 'WC_Memberships_User_Membership_Renewal_Reminder_Email';

		/** @see \WC_Memberships_User_Membership_Renewal_Reminder_Email */
		$email_setting = get_option( "woocommerce_{$email}_settings" );

		if (    $email_setting
		     && isset( $email_setting['send_days_after'] )
		     && $days_after = absint( $email_setting['send_days_after'] ) ) {

			// ensures at least one day after expiry date
			return $expiry_date + ( max( 1, $days_after ) * DAY_IN_SECONDS );
		}

		// default value (1 day after)
		return $expiry_date + DAY_IN_SECONDS;
	}


	/**
	 * Set the order id that granted access
	 *
	 * @since 1.7.0
	 * @param int $order_id WC_Order id
	 */
	public function set_order_id( $order_id ) {

		$order_id = is_numeric( $order_id ) ? (int) $order_id : 0;

		if ( $order = $order_id > 0 ? wc_get_order( $order_id ) : null ) {

			update_post_meta( $this->id, $this->order_id_meta, $order_id );

			// sanity check, ensures the matching order has a grant access record
			if ( ! wc_memberships_has_order_granted_access( $order, array( 'user_membership' => $this ) ) ) {

				wc_memberships_set_order_access_granted_membership( $order, $this, array(
					'already_granted'       => 'yes',
					'granting_order_status' => $order->get_status(),
				) );
			}
		}
	}


	/**
	 * Get the order id that granted access
	 *
	 * @since 1.0.0
	 * @return null|int Order id
	 */
	public function get_order_id() {

		$order_id = get_post_meta( $this->id, $this->order_id_meta, true );

		return $order_id ? (int) $order_id : null;
	}


	/**
	 * Get the order that granted access
	 *
	 * @since 1.0.0
	 * @return \WC_Order|false|null
	 */
	public function get_order() {

		$order_id = $this->get_order_id();

		return $order_id ? wc_get_order( $order_id ) : null;
	}


	/**
	 * Delete the order information
	 *
	 * @since 1.7.0
	 */
	public function delete_order_id() {

		delete_post_meta( $this->id, $this->order_id_meta );
	}


	/**
	 * Set the product id that granted access
	 *
	 * @since 1.7.0
	 * @param int $product_id WC_Product id
	 */
	public function set_product_id( $product_id ) {

		$product_id = is_numeric( $product_id ) ? (int) $product_id : 0;

		// check that the id belongs to an actual product
		if ( $product_id > 0 && wc_get_product( $product_id ) ) {

			update_post_meta( $this->id, $this->product_id_meta, $product_id );
			unset( $this->product );
		}
	}


	/**
	 * Get the product id that granted access
	 *
	 * @since 1.0.0
	 * @return int|null Product id
	 */
	public function get_product_id() {

		$product_id = get_post_meta( $this->id, $this->product_id_meta, true );

		return $product_id ? (int) $product_id : null;
	}


	/**
	 * Get the product that granted access
	 *
	 * @since 1.0.0
	 * @return \WC_Product|false|null
	 */
	public function get_product() {

		$product_id = $this->get_product_id();

		if ( ! isset( $this->product ) ) {
			$this->product = $product_id ? wc_get_product( $product_id ) : null;
		}

		return $this->product;
	}


	/**
	 * Delete the granting access product id information
	 *
	 * @since 1.7.0
	 */
	public function delete_product_id() {

		delete_post_meta( $this->id, $this->product_id_meta );
		unset( $this->product );
	}


	/**
	 * Returns true if the membership has the given status
	 *
	 * @since 1.0.0
	 * @param string|array $status single status or array of statuses
	 * @return bool
	 */
	public function has_status( $status ) {

		$has_status = ( ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status );

		/**
		 * Filter if User Membership has a status
		 *
		 * @since 1.0.0
		 * @param bool $has_status Whether the User Membership has a certain status
		 * @param \WC_Memberships_User_Membership $user_membership Instance of the User Membership object
		 * @param array|string $status One (string) status or any statuses (array) to check
		 */
		return (bool) apply_filters( 'woocommerce_memberships_membership_has_status', $has_status, $this, $status );
	}


	/**
	 * Updates status of membership
	 *
	 * @since 1.0.0
	 * @param string $new_status Status to change the order to. No internal wcm- prefix is required.
	 * @param string $note (default: '') Optional note to add
	 */
	public function update_status( $new_status, $note = '' ) {

		if ( ! $this->id ) {
			return;
		}

		// standardise status names
		$new_status = 0 === strpos( $new_status, 'wcm-' ) ? substr( $new_status, 4 ) : $new_status;
		$old_status = $this->get_status();

		// get valid statuses
		$valid_statuses = wc_memberships_get_user_membership_statuses();

		// only update if they differ - and ensure post_status is a 'wcm' status.
		if ( $new_status !== $old_status && array_key_exists( 'wcm-' . $new_status, $valid_statuses ) ) {

			// note will be added to the membership by the general User_Memberships utility class,
			// so that we add only 1 note instead of 2 when updating the status
			wc_memberships()->get_user_memberships_instance()->set_membership_status_transition_note( $note );

			// update the order
			wp_update_post( array(
				'ID'          => $this->id,
				'post_status' => 'wcm-' . $new_status,
			) );

			$this->status = 'wcm-' . $new_status;
		}
	}


	/**
	 * Check if membership has been cancelled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_cancelled() {
		return 'cancelled' === $this->get_status();
	}


	/**
	 * Check if membership is expired
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_expired() {
		return 'expired' === $this->get_status();
	}


	/**
	 * Check if membership is paused
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_paused() {
		return 'paused' === $this->get_status();
	}


	/**
	 * Check if membership has a delayed activation
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function is_delayed() {

		if ( 'delayed' === $this->get_status() ) {

			// always perform a check until start date is in the past...
			if ( $this->get_start_date( 'timestamp' ) < current_time( 'timestamp', true ) ) {
				// ... so we can activate the membership finally
				$this->activate_membership();
			} else {
				return true;
			}
		}

		return false;
	}


	/***
	 * Check if a membership is active
	 *
	 * If the membership is not in the active period it will move to expired
	 *
	 * Note: this checks whether member has access, according to plan rules, 'active'
	 * status is not the only status that can grant access to membership holder
	 *
	 * @since 1.6.4
	 * @return bool
	 */
	public function is_active() {

		$current_status = $this->get_status();
		$active_period  = $this->is_in_active_period();
		$is_active      = in_array( $current_status, wc_memberships()->get_user_memberships_instance()->get_active_access_membership_statuses(), true );

		// sanity check: an active membership should always lie within the active period
		if ( $is_active && ! $active_period ) {

			if ( $this->get_start_date( 'timestamp' ) > current_time( 'timestamp', true ) ) {
				// if we're before the start date, membership should be delayed
				$this->update_status( 'delayed' );
			} else {
				// if we're beyond the end date, the membership should expire
				$this->expire_membership();
			}

			$is_active = false;

		} elseif ( $active_period ) {

			if ( 'delayed' === $current_status ) {

				// the time has come and membership is ready for activation
				$this->activate_membership();

				$is_active = true;

			} elseif ( 'expired' ===  $current_status ) {

				// if the membership is expired, it can't be in active period
				$this->set_end_date( current_time( 'mysql', true ) );

				$is_active = false;
			}
		}

		return $is_active;
	}


	/**
	 * Check if membership has started, but not expired
	 *
	 * Note: this does not check the User Membership access status itself
	 * @see \WC_Memberships_User_Membership::is_active()
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_in_active_period() {

		$start = $this->get_start_date( 'timestamp' );
		$now   = current_time( 'timestamp', true );
		$end   = $this->get_end_date( 'timestamp' );

		return ( $start ? $start <= $now : true ) && ( $end ? $now <= $end : true );
	}


	/**
	 * Pause membership
	 *
	 * @since 1.0.0
	 * @param string $note Optional note to add
	 */
	public function pause_membership( $note = null ) {

		// bail out if paused already
		if ( $this->is_paused() ) {
			return;
		}

		$this->update_status( 'paused', ! empty( $note ) ? $note : __( 'Membership paused.', 'woocommerce-memberships' ) );
		$this->set_paused_date( current_time( 'mysql', true ) );

		/**
		 * Upon User Membership pausing
		 *
		 * @since 1.7.0
		 * @param \WC_Memberships_User_Membership $user_membership
		 */
		do_action( 'wc_memberships_user_membership_paused', $this );
	}


	/**
	 * Cancel membership
	 *
	 * @since 1.0.0
	 * @param string $note Optional note to add
	 */
	public function cancel_membership( $note = null ) {

		// bail out if cancelled already
		if ( $this->is_cancelled() ) {
			return;
		}

		$this->update_status( 'cancelled', ! empty( $note ) ? $note : __( 'Membership cancelled.', 'woocommerce-memberships' ) );
		$this->set_cancelled_date( current_time( 'mysql', true ) );

		/**
		 * Upon User Membership cancellation
		 *
		 * @since 1.7.0
		 * @param \WC_Memberships_User_Membership $user_membership
		 */
		do_action( 'wc_memberships_user_membership_cancelled', $this );
	}


	/**
	 * Expire membership
	 *
	 * (also schedules the renewal reminder expiration event)
	 *
	 * @see \WC_Memberships_User_Membership::schedule_expiration_events()
	 * @see \WC_Memberships_User_Memberships::trigger_expiration_events()
	 *
	 * @since 1.6.2
	 */
	public function expire_membership() {

		// bail out if expired already
		if ( $this->is_expired() ) {
			return;
		}

		/**
		 * Confirm expire User Membership
		 *
		 * @since 1.5.4
		 * @param bool $expire True: expire this membership, False: retain, Default: true, expire it
		 * @param \WC_Memberships_User_Membership $user_membership The User Membership object
		 */
		if ( true === apply_filters( 'wc_memberships_expire_user_membership', true, $this ) ) {

			$current_time = current_time( 'timestamp', true );
			$event_args   = array( 'user_membership_id' => $this->id );

			// expire the membership
			$this->update_status( 'expired', __( 'Membership expired.', 'woocommerce-memberships' ) );

			// set the expiration date to always match the current time,
			// since this could have been forcefully expired before the planned end date
			update_post_meta( $this->id, $this->end_date_meta, date( 'Y-m-d H:i:s', $current_time ) );

			// unschedule any previously set renewal reminder event
			if ( wc_next_scheduled_action( 'wc_memberships_user_membership_renewal_reminder', $event_args, 'woocommerce-memberships' ) ) {
				wc_unschedule_action( 'wc_memberships_user_membership_renewal_reminder', $event_args, 'woocommerce-memberships' );
			}

			// now that the membership expired, set the renewal reminder event
			if ( $this->can_be_renewed() ) {
				wc_schedule_single_action( $this->get_expired_time_after( $current_time ), 'wc_memberships_user_membership_renewal_reminder', $event_args, 'woocommerce-memberships' );
			}

			/**
			 * Upon User Membership expiration
			 *
			 * @since 1.7.0
			 * @param int $user_membership_id The expired user membership id
			 */
			do_action( 'wc_memberships_user_membership_expired', $this->id );
		}
	}


	/**
	 * Activate membership
	 *
	 * @since 1.0.0
	 * @param null|string $note Optional note to add
	 */
	public function activate_membership( $note = null ) {

		$previous_status = $this->get_status();
		$was_paused      = 'paused'  === $previous_status;
		$was_delayed     = 'delayed' === $previous_status;

		if ( ! $was_delayed && $this->is_active() ) {
			// bail out if already active (check for delay prevents infinite loops)
			return;
		} elseif ( $was_paused ) {
			// reactivation
			$default_note = __( 'Membership resumed.', 'woocommerce-memberships' );
			$this->set_paused_interval( 'end', current_time( 'timestamp', true ) );
		} else {
			// activation
			$default_note = __( 'Membership activated.', 'woocommerce-memberships' );
		}

		$start_date = $this->get_start_date();
		$start_time = $start_date ? strtotime( 'today', strtotime( $start_date ) ) : null;
		$is_delayed = $start_time && $start_time > current_time( 'timestamp', true );

		// sanity check for delayed start
		if ( ! $was_delayed && $is_delayed ) {
			$this->update_status( 'delayed', empty( $note ) ? $default_note : $note );
		} elseif ( 'active' !== $previous_status && ! $is_delayed ) {
			$this->update_status( 'active', empty( $note )  ? $default_note : $note );
		} else {
			return;
		}

		/**
		 * Upon User Membership activation or re-activation
		 *
		 * @since 1.7.0
		 * @param \WC_Memberships_User_Membership $user_membership Membership object
		 * @param bool $was_paused Whether this is a reactivation of a paused membership
		 * @param string $previous_status Status the Membership was before activation
		 */
		do_action( 'wc_memberships_user_membership_activated', $this, $was_paused, $previous_status );
	}


	/**
	 * Whether the user membership can be cancelled by the user
	 *
	 * Note: does not check whether the user has capability to cancel
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function can_be_cancelled() {

		// check if membership has eligible status for cancellation
		$can_be_cancelled = in_array( $this->get_status(), wc_memberships()->get_user_memberships_instance()->get_valid_user_membership_statuses_for_cancellation(), true );

		/**
		 * Whether a user membership can be cancelled
		 *
		 * This does not imply that it will be cancelled
		 * but should meet the characteristics to be cancelled by a user
		 * that has capability to cancel
		 *
		 * @since 1.7.0
		 * @param bool $can_be_cancelled Whether can be cancelled by a user
		 * @param \WC_Memberships_User_Membership $user_membership The Membership
		 */
		return (bool) apply_filters( 'wc_memberships_user_membership_can_be_cancelled', $can_be_cancelled, $this );
	}


	/**
	 * Get cancel membership URL for frontend
	 *
	 * @since 1.0.0
	 * @return string Cancel URL
	 */
	public function get_cancel_membership_url() {

		$cancel_endpoint = wc_get_page_permalink( 'myaccount' );

		if ( false === strpos( $cancel_endpoint, '?' ) ) {
			$cancel_endpoint = trailingslashit( $cancel_endpoint );
		}

		$cancel_url = wp_nonce_url(
			add_query_arg( array(
				'cancel_membership' => $this->id,
			), $cancel_endpoint ),
			'wc_memberships-cancel_membership_' . $this->id
		);

		/**
		 * Filter the cancel membership URL
		 *
		 * @since 1.0.0
		 * @param string $url
		 * @param \WC_Memberships_User_Membership $user_membership
		 */
		return apply_filters( 'wc_memberships_get_cancel_membership_url', $cancel_url, $this );
	}


	/**
	 * Get the first product suitable to renew the membership
	 * (ideally will pick the one that originally granted access)
	 *
	 * @see \WC_Memberships_User_Membership::get_products_for_renewal()
	 *
	 * @since 1.7.0
	 * @return null|\WC_Product
	 */
	public function get_product_for_renewal() {

		$products_for_renewal = $this->get_products_for_renewal();
		$product_for_renewal  = ! empty( $products_for_renewal ) && is_array( $products_for_renewal ) ? reset( $products_for_renewal ) : null;

		return $product_for_renewal instanceof WC_Product ? $product_for_renewal : null;
	}


	/**
	 * Get products suitable to renew this membership
	 *
	 * @since 1.7.0
	 * @return \WC_Product[] Array of products
	 */
	public function get_products_for_renewal() {

		$renewal_products = array();
		$original_product = $this->get_product();

		// make sure the original product is the first in array
		if ( $original_product && $original_product->is_purchasable() ) {

			$renewal_products[ $original_product->get_id() ] = $original_product;
		}

		$plan = $this->get_plan();

		// get all the other purchasable products according to the plan settings
		if ( $plan && ( $products = $plan->get_products() ) ) {

			foreach ( $products as $product_id => $product ) {

				if ( $product->is_purchasable() ) {

					$renewal_products[ $product_id ] = $product;
				}
			}
		}

		return $renewal_products;
	}


	/**
	 * Whether the user membership can be renewed by the user
	 *
	 * Note: does not check whether the user has capability to renew
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function can_be_renewed() {

		// check first if the status allows renewal
		$membership_plan = $this->plan instanceof WC_Memberships_Membership_Plan ? $this->plan : $this->get_plan();
		$can_be_renewed  = $membership_plan && in_array( $this->get_status(), wc_memberships()->get_user_memberships_instance()->get_valid_user_membership_statuses_for_renewal(), true );

		if ( $can_be_renewed ) {

			if ( $membership_plan->is_access_method( 'manual-only' ) ) {

				// if membership has no other access method than manual assignment
				// then it shouldn't be renewed by the user, but only by an admin
				// (note we don't check for the membership $type property
				// but the plan's access method)
				$can_be_renewed = false;
			}

			if ( $membership_plan->is_access_length_type( 'fixed' ) ) {

				$fixed_end_date = $membership_plan->get_access_end_date( 'timestamp' );

				// fixed length memberships with an end date in the past
				// shouldn't be renewable (unless an admin changes the plan end date)
				if ( ! empty( $fixed_end_date ) && current_time( 'timestamp', true ) > $fixed_end_date ) {
					$can_be_renewed = false;
				}
			}

			if ( $membership_plan->has_products() ) {

				// plan has products but let's see if any are purchasable
				if ( ! $this->get_product_for_renewal() ) {
					$can_be_renewed = false;
				}

			} else {

				// if plan has no products, can't be renewed via purchase
				$can_be_renewed = false;
			}
		}

		/**
		 * Whether a user membership can be renewed
		 *
		 * This does not imply that it will be renewed
		 * but should meet the characteristics to be renewable by a user
		 * that has capability to renew
		 *
		 * @since 1.7.0
		 * @param bool $can_be_renewed Whether can be renewed by a user
		 * @param \WC_Memberships_User_Membership $user_membership The Membership
		 */
		return (bool) apply_filters( 'wc_memberships_user_membership_can_be_renewed', $can_be_renewed, $this );
	}


	/**
	 * Get renew membership URL for frontend
	 *
	 * @since 1.0.0
	 * @return string Renew URL
	 */
	public function get_renew_membership_url() {

		$renew_endpoint = wc_get_page_permalink( 'myaccount' );

		if ( false === strpos( $renew_endpoint, '?' ) ) {
			$renew_endpoint = trailingslashit( $renew_endpoint );
		}

		$renew_url = wp_nonce_url(
			add_query_arg( array(
				'renew_membership' => $this->id,
			), $renew_endpoint ),
			'wc_memberships-renew_membership_' . $this->id
		);

		/**
		 * Filter the renew membership URL
		 *
		 * @since 1.0.0
		 * @param string $url
		 * @param \WC_Memberships_User_Membership $user_membership
		 */
		return apply_filters( 'wc_memberships_get_renew_membership_url', $renew_url, $this );
	}


	/**
	 * Transfer the User Membership to another user
	 *
	 * If a transfer is successful it will also record
	 * the ownership passage in a post meta
	 *
	 * @since 1.6.0
	 * @param \WP_User|int $to_user User to transfer membership to
	 * @return bool Whether the transfer was successful
	 */
	public function transfer_ownership( $to_user ) {

		if ( is_numeric( $to_user ) ) {
			$to_user = get_user_by( 'id', (int) $to_user );
		}

		$user_membership_id = (int) $this->id;
		$previous_owner     = (int) $this->get_user_id();
		$new_owner          = $to_user;

		if ( ! $new_owner instanceof WP_User || ! $previous_owner || ! $user_membership_id ) {
			return false;
		}

		$updated = wp_update_post( array(
			'ID'          => $user_membership_id,
			'post_type'   => 'wc_user_membership',
			'post_author' => $new_owner->ID,
		) );

		if ( (int) $this->id !== (int) $updated ) {
			return false;
		}

		// update the user id for the current instance of this membership
		$this->user_id = $new_owner->ID;

		$owners     = $this->get_previous_owners();
		$last_owner = array( current_time( 'timestamp', true ) => $previous_owner );

		$previous_owners = ! empty( $owners ) && is_array( $owners ) ? array_merge( $owners, $last_owner ) : $last_owner;

		update_post_meta( $user_membership_id, $this->previous_owners_meta, $previous_owners );

		$this->add_note(
			/* translators: Membership transferred from user %1$s to user %2$s */
			sprintf( __( 'Membership transferred from %1$s to %2$s.', 'woocommerce-memberships' ),
				get_user_by( 'id', $previous_owner )->user_nicename,
				$new_owner->user_nicename
			)
		);

		return true;
	}


	/**
	 * Get User Membership previous owners
	 *
	 * If the User Membership has been previously transferred
	 * from an user to another, this method will return its
	 * ownership history as an associative array of
	 * timestamps (time of transfer) and user ids
	 *
	 * @since 1.6.0
	 * @return array Associative array of timestamps (keys) and user ids (values)
	 */
	public function get_previous_owners() {

		$previous_owners = get_post_meta( $this->id, $this->previous_owners_meta, true );

		return ! empty( $previous_owners ) && is_array( $previous_owners ) ? $previous_owners : array();
	}


	/**
	 * Get notes
	 *
	 * @since 1.0.0
	 * @param string $filter Optional: 'customer' or 'private', default 'all'
	 * @param int $paged Optional: pagination
	 * @return \WP_Comment[] Array of comment (membership notes) objects
	 */
	public function get_notes( $filter = 'all', $paged = 1 ) {

		$args = array(
			'post_id' => $this->id,
			'approve' => 'approve',
			'type'    => 'user_membership_note',
			'paged'   => (int) $paged,
		);

		remove_filter( 'comments_clauses', array( wc_memberships()->get_query_instance(), 'exclude_membership_notes_from_queries' ), 10 );

		$comments = (array) get_comments( $args );
		$notes    = array();

		if ( in_array( $filter, array( 'customer', 'private' ), true ) ) {

			foreach ( $comments as $note ) {

				$notified = get_comment_meta( $note->comment_ID, 'notified', true );

				if ( $notified && 'customer' === $filter )  {
					$notes[] = $note;
				} elseif ( ! $notified && 'private' === $filter ) {
					$notes[] = $note;
				}
			}

		} else {

			$notes = $comments;
		}

		return $notes;
	}


	/**
	 * Add note
	 *
	 * @since 1.0.0
	 * @param string $note Note to add
	 * @param bool $notify Optional. Whether to notify member or not. Defaults to false
	 * @return int|false Note (comment) ID, false on error
	 */
	public function add_note( $note, $notify = false ) {

		$note = trim( $note );

		if ( empty( $note ) ) {

			// a note can't be empty
			return false;

		} if ( is_user_logged_in() && current_user_can( 'edit_post', $this->id ) ) {

			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;

		} else {

			$comment_author       = __( 'WooCommerce', 'woocommerce-memberships' );

			$comment_author_email = strtolower( __( 'WooCommerce', 'woocommerce-memberships' ) ) . '@';
			$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';

			$comment_author_email = sanitize_email( $comment_author_email );
		}

		$comment_post_ID    = $this->id;
		$comment_author_url = '';
		$comment_content    = $note;
		$comment_agent      = 'WooCommerce';
		$comment_type       = 'user_membership_note';
		$comment_parent     = 0;
		$comment_approved   = 1;

		/**
		 * Filter new user membership note data
		 *
		 * @since 1.0.0
		 * @param array $commentdata Array of arguments to insert the note as a comment to the user membership
		 * @param array $args Extra arguments like user membership id and whether to notify member of the new note...
		 */
		$commentdata = apply_filters( 'wc_memberships_new_user_membership_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), array( 'user_membership_id' => $this->id, 'notify' => $notify ) );

		$comment_id = wp_insert_comment( $commentdata );

		// set whether the member has received an email notification for this note
		add_comment_meta( $comment_id, 'notified', $notify );

		// prepare args for filter and send email notification
		$new_membership_note_args =  array(
			'user_membership_id' => $this->id,
			'membership_note'    => $note,
			'notify'             => $notify,
		);

		/**
		 * Fires after a new membership note is added
		 *
		 * @since 1.0.0
		 * @param array $new_membership_note_args Arguments
		 */
		do_action( 'wc_memberships_new_user_membership_note', $new_membership_note_args );

		// maybe notify the member
		if ( true === $notify ) {
			wc_memberships()->get_emails_instance()->send_new_membership_note_email( $new_membership_note_args );
		}

		return $comment_id;
	}


}
