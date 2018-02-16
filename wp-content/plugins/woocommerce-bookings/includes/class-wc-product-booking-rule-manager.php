<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that parses and returns rules for bookable products.
 */
class WC_Product_Booking_Rule_Manager {

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_custom_range( $from, $to, $value ) {
		$availability = array();
		$from_date    = strtotime( $from );
		$to_date      = strtotime( $to );

		if ( empty( $to ) || empty( $from ) || $to_date < $from_date ) {
			return;
		}
		// We have at least 1 day, even if from_date == to_date
		$number_of_days = 1 + ( $to_date - $from_date ) / 60 / 60 / 24;

		for ( $i = 0; $i < $number_of_days; $i ++ ) {
			$year  = date( 'Y', strtotime( "+{$i} days", $from_date ) );
			$month = date( 'n', strtotime( "+{$i} days", $from_date ) );
			$day   = date( 'j', strtotime( "+{$i} days", $from_date ) );

			$availability[ $year ][ $month ][ $day ] = $value;
		}

		return $availability;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_months_range( $from, $to, $value ) {
		$months = array();
		$diff   = $to - $from;
		$diff   = ( $diff < 0 ) ? 12 + $diff : $diff;
		$month  = $from;

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$months[ $month ] = $value;

			$month ++;

			if ( $month > 52 ) {
				$month = 1;
			}
		}

		return $months;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_weeks_range( $from, $to, $value ) {
		$weeks = array();
		$diff  = $to - $from;
		$diff  = ( $diff < 0 ) ? 52 + $diff : $diff;
		$week  = $from;

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$weeks[ $week ] = $value;

			$week ++;

			if ( $week > 52 ) {
				$week = 1;
			}
		}

		return $weeks;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_days_range( $from, $to, $value ) {
		$day_of_week  = $from;
		$diff         = $to - $from;
		$diff         = ( $diff < 0 ) ? 7 + $diff : $diff;
		$days         = array();

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$days[ $day_of_week ] = $value;

			$day_of_week ++;

			if ( $day_of_week > 7 ) {
				$day_of_week = 1;
			}
		}

		return $days;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_time_range( $from, $to, $value, $day = 0 ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
			'day'  => $day,
		);
	}

	/**
	 * Get a time range for a set of custom dates
	 * @param  string $from_date
	 * @param  string $to_date
	 * @param  string $from_time
	 * @param  string $to_time
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_time_range_for_custom_date( $from_date, $to_date, $from_time, $to_time, $value ) {
		$time_range = array(
			'from' => $from_time,
			'to'   => $to_time,
			'rule' => $value,
		);
		return self::get_custom_range( $from_date, $to_date, $time_range );
	}

	/**
	 * Get duration range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private static function get_duration_range( $from, $to, $value ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
			);
	}

	/**
	 * Get Persons range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private static function get_persons_range( $from, $to, $value ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
			);
	}

	/**
	 * Get blocks range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private static function get_blocks_range( $from, $to, $value ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
			);
	}

	/**
	 * Process and return formatted cost rules
	 * @param  $rules array
	 * @return array
	 */
	public static function process_cost_rules( $rules ) {
		$costs = array();
		$index = 1;
		// Go through rules
		foreach ( $rules as $key => $fields ) {
			if ( empty( $fields['cost'] ) && empty( $fields['base_cost'] ) && empty( $fields['override_block'] ) ) {
				continue;
			}

			$cost           = apply_filters( 'woocommerce_bookings_process_cost_rules_cost', $fields['cost'], $fields, $key );
			$modifier       = $fields['modifier'];
			$base_cost      = apply_filters( 'woocommerce_bookings_process_cost_rules_base_cost', $fields['base_cost'], $fields, $key );
			$base_modifier  = $fields['base_modifier'];
			$override_block = apply_filters( 'woocommerce_bookings_process_cost_rules_override_block', ( isset( $fields['override_block'] ) ? $fields['override_block'] : '' ), $fields, $key );

			$cost_array = array(
				'base'     => array( $base_modifier, $base_cost ),
				'block'    => array( $modifier, $cost ),
				'override' => $override_block,
			);

			$type_function = self::get_type_function( $fields['type'] );
			if ( 'get_time_range_for_custom_date' === $type_function ) {
				$type_costs = self::$type_function( $fields['from_date'], $fields['to_date'], $fields['from'], $fields['to'], $cost_array );
			} else {
				$type_costs = self::$type_function( $fields['from'], $fields['to'], $cost_array );
			}

			// Ensure day gets specified for time: rules
			if ( strrpos( $fields['type'], 'time:' ) === 0 && 'time:range' !== $fields['type'] ) {
				list( , $day ) = explode( ':', $fields['type'] );
				$type_costs['day'] = absint( $day );
			}

			if ( $type_costs ) {
				$costs[ $index ] = array( $fields['type'], $type_costs );
				$index ++;
			}
		}

		return $costs;
	}

	/**
	 * Returns a function name (for this class) that returns our time or date range
	 * @param  string $type rule type
	 * @return string       function name
	 */
	public static function get_type_function( $type ) {
		if ( 'time:range' === $type ) {
			return 'get_time_range_for_custom_date';
		}
		return strrpos( $type, 'time:' ) === 0 ? 'get_time_range' : 'get_' . $type . '_range';
	}

	/** 
	 * Process and return formatted availability rules 
	 * @param  $rules array 
	 * @param string $level. Resource, Product or Globally 
	 * @return array 
	 */
	public static function process_availability_rules( $rules, $level ) {
		$processed_rules = array();

		if ( empty( $rules ) ) {
			return $processed_rules;
		}

		// Go through rules
		foreach ( $rules as $order_on_product => $fields ) {
			if ( empty( $fields['bookable'] ) ) {
				continue;
			}

			// Do not include dates that are in the past.
			if ( in_array( $fields['type'], array( 'custom', 'time:range' ) ) ) {
				$to_date = ! empty( $fields['to_date'] ) ? $fields['to_date'] : $fields['to'];
			 	if ( strtotime( $to_date ) < current_time( 'timestamp' ) ) {
					continue;
				}
			}

			$type_function = self::get_type_function( $fields['type'] );
			$bookable = 'yes' === $fields['bookable'] ? true : false;
			if ( 'get_time_range_for_custom_date' === $type_function ) {
				$type_availability = self::$type_function( $fields['from_date'], $fields['to_date'], $fields['from'], $fields['to'],$bookable );
			} else {
				$type_availability = self::$type_function( $fields['from'], $fields['to'], $bookable );
			}

			$priority = intval( ( isset( $fields['priority'] ) ? $fields['priority'] : 10 ) );

			// Ensure day gets specified for time: rules
			if ( strrpos( $fields['type'], 'time:' ) === 0 && 'time:range' !== $fields['type'] ) {
				list( , $day ) = explode( ':', $fields['type'] );
				$type_availability['day'] = absint( $day );
			}

			if ( $type_availability ) {
				$processed_rule = array(
					'type'     => $fields['type'],
					'range'    => $type_availability,
					'priority' => $priority,
					'level'    => $level,
					'order'    => $order_on_product,
				);

				if ( 'resource' === $level ) {
					$processed_rule['resource_id'] = $fields['resource_id'];
				}
				$processed_rules[] = $processed_rule;
			}
		}

		return $processed_rules;
	}

	/**
	 * Get the minutes that should be available based on the rules and the date to check.
	 *
	 * The minutes are returned in a range from the start incrementing minutes right up to the last available minute.
	 *
	 * @since 1.9.14 moved from WC_Product_Booking.
	 *
	 * @param array $rules
	 * @param int $check_date
	 *
	 * @return array $bookable_minutes
	 */
	public static function get_minutes_from_rules( $rules, $check_date ) {
		$bookable_minutes = array();
		$resource_minutes = array();

		foreach ( $rules as $rule ) {
			$data_for_rule = array(
				'is_bookable' => false,
				'minutes'     => array(),
			);

			if ( strpos( $rule['type'], 'time' ) > -1 ) {
				$data_for_rule = self::get_rule_minutes_for_time( $rule, $check_date );
			} elseif ( 'days' === $rule['type'] ) {
				$data_for_rule = self::get_rule_minutes_for_days( $rule, $check_date );
			} elseif ( 'weeks' === $rule['type'] ) {
				$data_for_rule = self::get_rule_minutes_for_weeks( $rule, $check_date );
			} elseif ( 'months' === $rule['type'] ) {
				$data_for_rule = self::get_rule_minutes_for_months( $rule, $check_date );
			} elseif ( 'custom' === $rule['type'] ) {
				$data_for_rule = self::get_rule_minutes_for_custom( $rule, $check_date );
			}

			// split up the rules on a resource level to be dealt with independently
			// after the rules loop. This ensure resource do not affect one another
			if ( isset( $rule['level'] ) && 'resource' === $rule['level'] ) {
				$resource_id                        = $rule['resource_id'];
				$availability_key                        = $data_for_rule['is_bookable'] ? 'bookable' : 'not_bookable';
				// adding minutes in the order of the rules received, higher index higher override power.
				$resource_minutes[ $resource_id ][] = array( $availability_key => $data_for_rule['minutes'] );
			} else {
				if ( $data_for_rule['is_bookable'] ) {
					// If this time range is bookable, add to bookable minutes
					$bookable_minutes = array_merge( $bookable_minutes, $data_for_rule['minutes'] );
				} else {
					// If this time range is not bookable, remove from bookable minutes
					$bookable_minutes = array_diff( $bookable_minutes, $data_for_rule['minutes'] );
				}
			}
		}

//		 one resource should not override the other, when automatically assigned: as long as one is available.
		foreach ( $resource_minutes as $resource_id => $minutes_for_rule_order ) {
			$resource_minutes     = array();

			foreach ( $minutes_for_rule_order as $rule_minutes_with_availability ) {
				$is_bookable = isset( $rule_minutes_with_availability['bookable'] );
				if ( $is_bookable ) {
					$resource_minutes = array_merge( $resource_minutes, $rule_minutes_with_availability['bookable'] );
				} else {
					$resource_minutes = array_diff( $resource_minutes, $rule_minutes_with_availability['not_bookable'] );
				}
			}
			// @todo this may be a problem as resource minutes may now affect product and global availability at this point.
			$bookable_minutes = array_merge( $resource_minutes, $bookable_minutes );
		}




		$bookable_minutes = array_unique( array_values( $bookable_minutes ) );

		sort( $bookable_minutes );
		return $bookable_minutes;
	}

	/**
	 * Get minutes from rules for a time rule type.
	 *
	 * @since 1.9.14
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_time( $rule, $check_date ) {

		$minutes = array( 'is_bookable' => false, 'minutes' => array() );
		$type    = $rule['type'];
		$range   = $rule['range'];

		$year        = date( 'Y', $check_date );
		$month       = date( 'n', $check_date );
		$day         = date( 'j', $check_date );
		$day_of_week = date( 'N', $check_date );

		$day_modifier = 0;

		if ( 'time:range' === $type ) { // type: date range with time

			if ( ! isset( $range[ $year ][ $month ][ $day ] ) ) {
				return  $minutes;
			} else {
				$range = $range[ $year ][ $month ][ $day ];
			}

			$from                   = $range['from'];
			$to                     = $range['to'];
			$minutes['is_bookable'] = $range['rule'];

		} elseif ( strpos( $rule['type'], 'time:' ) > -1 ) { // type: single week day with time

			if (  $day_of_week != $range['day'] ) {
				return  $minutes;
			}

			$from                   = $range['from'];
			$to                     = $range['to'];
			$minutes['is_bookable'] = $range['rule'];

		} else {  // type: time all week per day

			$from                   = $range['from'];
			$to                     = $range['to'];
			$minutes['is_bookable'] = $range['rule'];

		}

		$from_hour    = absint( date( 'H', strtotime( $from ) ) );
		$from_min     = absint( date( 'i', strtotime( $from ) ) );
		$to_hour      = absint( date( 'H', strtotime( $to ) ) );
		$to_min       = absint( date( 'i', strtotime( $to ) ) );

		// If "to" is set to midnight, it is safe to assume they mean the end of the day
		// php wraps 24 hours to "12AM the next day"
		if ( 0 === $to_hour ) {
			$to_hour = 24;
		}

		$minute_range = array( ( ( $from_hour * 60 ) + $from_min ) + $day_modifier, ( ( $to_hour * 60 ) + $to_min ) + $day_modifier );
		$merge_ranges = array();

		// if first time in range is larger than second, we
		// assume they want to go over midnight
		if ( $minute_range[0] > $minute_range[1] ) {
			$merge_ranges[] = array( $minute_range[0], 1440 );
			// fix for https://github.com/woothemes/woocommerce-bookings/issues/710
			$merge_ranges[] = array( $minute_range[0], ( 1440 + $minute_range[1] ) );
		} else {
			$merge_ranges[] = array( $minute_range[0], $minute_range[1] );
		}

		foreach ( $merge_ranges as $range ) {
				// Add ranges to minutes this rule affects.
				$minutes['minutes'] = array_merge( $minutes['minutes'], range( $range[0], $range[1] ) );
		}

		return $minutes;
	}

	/**
	 * Get minutes from rules for days rule type.
	 *
	 * @since 1.9.14
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_days( $rule, $check_date ) {
		$_rules      = $rule['range'];
		$minutes     = array();
		$is_bookable = false;
		$day_of_week = intval( date( 'N', $check_date ) );

		if ( isset( $_rules[ $day_of_week ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_bookable = $_rules[ $day_of_week ];
		}

		return array( 'is_bookable' => $is_bookable, 'minutes' => $minutes );
	}

	/**
	 * Get minutes from rules for a weeks rule type.
	 *
	 * @since 1.9.14
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_weeks( $rule, $check_date ) {

		$range       = $rule['range'];
		$week_number = intval( date( 'W', $check_date ) );
		$minutes     = array();
		$is_bookable = false;

		if ( isset( $range[ $week_number ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_bookable = $range[ $week_number ];
		}

		return array( 'is_bookable' => $is_bookable, 'minutes' => $minutes );
	}

	/**
	 * Get minutes from rules for a months rule type.
	 *
	 * @since 1.9.14
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_months( $rule, $check_date ) {

		$range       = $rule['range'];
		$month       = date( 'n', $check_date );
		$minutes     = array();
		$is_bookable = false;
		if ( isset( $range[ $month ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_bookable = $range[ $month ];
		}

		return array( 'is_bookable' => $is_bookable, 'minutes' => $minutes );
	}

	/**
	 * Get minutes from rules for custom rule type.
	 * @since 1.9.14
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_custom( $rule, $check_date ) {

		$range = $rule['range'];
		$year  = date( 'Y', $check_date );
		$month = date( 'n', $check_date );
		$day   = date( 'j', $check_date );

		$minutes     = array();
		$is_bookable = false;

		if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_bookable = $range[ $year ][ $month ][ $day ];
		}

		return array( 'is_bookable' => $is_bookable, 'minutes' => $minutes );
	}

	/**
	 * Sort rules in order of precedence.
	 *
	 * @version 1.9.14 sort order reversed
	 * The order produced will be from the lowest to the highest.
	 * The elements with higher indexes overrides those with lower indexes e.g. `4` overrides `3`
	 * Index corresponds to override power. The higher the element index the higher the override power
	 *
	 * Level    : `global` > `product` > `product` (greater in terms off override power)
	 * Priority : within a level
	 * Order    : Within a priority The lower the order index higher the override power.
	 *
	 * @param array $rule1
	 * @param array $rule2
	 *
	 * @return integer
	 */
	public static function sort_rules_callback( $rule1, $rule2 ) {
		$level_weight = array(
			'resource' => 1,
			'product'  => 3,
			'global'   => 5,
		);

		// The override power goes from the outside inward.
		// Priority is outside which means it has the most weight when sorting.
		// Then level(global, product, resource)
		// Lastly order is applied within the level.
		if ( $rule1['priority'] === $rule2['priority'] ) {
			if ( $level_weight[ $rule1['level'] ] === $level_weight[ $rule2['level'] ] ) {
				// if `order index of 1` < `order index of 2` $rule1 one has a higher override power. So we
				// increase the index for $rule1 which corresponds to override power.
				return ( $rule1['order'] < $rule2['order'] ) ? 1 : -1;
			}

			// if `level of 1` < `level of 2` $rule1 must have lower override power. So we
			// decrease the index for 1 which corresponds to override power.
			return $level_weight[ $rule1['level'] ] < $level_weight[ $rule2['level'] ] ? -1 : 1;
		}

		// if `priority of 1` < `priority of 2` $rule1 must have lower override power. So we
		// decrease the index for 1 which corresponds to override power.
		return $rule1['priority'] < $rule2['priority'] ? 1 : -1;
	}

	/**
	 * Filter out all but time rules.
	 * @param  array $rule
	 * @return boolean
	 */
	private static function filter_time_rules( $rule ) {
		return ! empty( $rule['type'] ) && ! in_array( $rule['type'], array( 'days', 'custom', 'months', 'weeks' ) );
	}

	/**
	 * Check a bookable product's availability rules against a time range and return if bookable or not.
	 *
	 * @param  WC_Product_Booking $bookable_product
	 * @param  int $resource_id
	 * @param  int $start timestamp
	 * @param  int $end timestamp
	 * @return boolean
	 */
	public static function check_range_availability_rules( $bookable_product, $resource_id, $start, $end ) {
		// This is a time range.
		if ( in_array( $bookable_product->get_duration_unit(), array( 'minute', 'hour' ) ) ) {
			return self::check_availability_rules_against_time( $start, $end, $resource_id, $bookable_product );
		} // Else this is a date range (days).
		else {
			$timestamp = $start;

			while ( $timestamp < $end ) {
				if ( ! self::check_availability_rules_against_date( $bookable_product, $resource_id, $timestamp ) ) {
					return false;
				}
				if ( $bookable_product->get_check_start_block_only() ) {
					break; // Only need to check first day
				}
				$timestamp = strtotime( '+1 day', $timestamp );
			}
		}

		return true;
	}

	/**
	 * Check a time against the time specific availability rules
	 *
	 * @param integer $slot_start_time
	 * @param integer $slot_end_time
	 * @param integer $resource_id
	 * @param WC_Product_Booking $bookable_product
	 * @param bool|null If not null, it will default to the boolean value. If null, it will use product default availability.
	 *
	 * @return bool available or not
	 */
	public static function check_availability_rules_against_time( $slot_start_time, $slot_end_time, $resource_id, $bookable_product, $bookable = null ) {
		$slot_start_time = is_numeric( $slot_start_time ) ? $slot_start_time : strtotime( $slot_start_time );
		$slot_end_time   = is_numeric( $slot_end_time ) ? $slot_end_time : strtotime( $slot_end_time );

		$rules           = $bookable_product->get_availability_rules( $resource_id );

		if ( is_null( $bookable ) ) {
			$bookable = $bookable_product->get_default_availability();
		}

		// Get the date values for the slots being checked
		$slot_year   = intval( date( 'Y', $slot_start_time ) );
		$slot_month  = intval( date( 'n', $slot_start_time ) );
		$slot_date   = intval( date( 'j', $slot_start_time ) );
		$slot_day_no = intval( date( 'N', $slot_start_time ) );
		$slot_week   = intval( date( 'W', $slot_start_time ) );

		// default from and to for the whole day
		$from = strtotime( 'midnight', $slot_start_time );
		$to   = strtotime( 'midnight + 1 day', $slot_start_time );

		foreach ( $rules as $rule ) {
			$type  = $rule['type'];
			$range = $rule['range'];

			// handling none time specific rules first
			if ( in_array( $type, array( 'days', 'custom', 'months', 'weeks' ) ) ) {
				if ( 'days' === $type ) {
					if ( ! isset( $range[ $slot_day_no ] ) ) {
						continue;
					}
				} elseif ( 'custom' === $type ) {
					if ( ! isset( $range[ $slot_year ][ $slot_month ][ $slot_date ] ) ) {
						continue;
					}
				} elseif ( 'months' === $type ) {
					if ( ! isset( $range[ $slot_month ] ) ) {
						continue;
					}
				} elseif ( 'weeks' === $type ) {
					if ( ! isset( $range[ $slot_week ] ) ) {
						continue;
					}
				}
				$rule_val = self::check_timestamp_against_rule( $slot_start_time, $rule, $bookable_product->get_default_availability() );
			}

			// Handling all time specific rules
			if ( 'time:range' === $type ) {
				if ( ! isset( $range[ $slot_year ][ $slot_month ][ $slot_date ] ) ) {
					continue;
				}
				$time_range_rule = $range[ $slot_year ][ $slot_month ][ $slot_date ];
				$rule_val = $time_range_rule['rule'];
				$from     = $time_range_rule['from'];
				$to       = $time_range_rule['to'];
			} elseif ( false !== strpos( $type, 'time' ) ) {
				// if the day doesn't match and the day is not zero skip the rule
				// zero means all days. SO rule only apply for zero or a matching day.
				if ( ! empty( $range['day'] ) && $slot_day_no != $range['day'] ) {
					continue;
				}


				// check that the rule should be applied to the current slot
				// if not time it must be time:day_number
				if ( 'time' !== $type ) {
					if ( ! strpos( $type, (string) $slot_day_no ) ) {
						continue;
					}
				}

				$rule_val = $range['rule'];
				$from     = $range['from'];
				$to       = $range['to'];
			}

			$rule_start_time = strtotime( $from, $slot_start_time );
			$rule_end_time   = strtotime( $to, $slot_start_time );

			// Reverse time rule - The end time is tomorrow e.g. 16:00 today - 12:00 tomorrow
			if ( $rule_end_time <= $rule_start_time ) {
				if ( $slot_end_time > $rule_start_time ) {
					$bookable = $rule_val;
					continue;
				}
				if ( $slot_start_time >= $rule_start_time && $slot_end_time >= $rule_end_time ) {
					$bookable = $rule_val;
					continue;
				}
				// does this rule apply?
				// does slot start before rule start and end after rules start time {goes over start time}
				if ( $slot_start_time < $rule_start_time && $slot_end_time > $rule_start_time ) {
					$bookable = $rule_val;
					continue;
				}
			} else {
				// Normal rule.
				if ( $slot_start_time < $rule_end_time && $slot_end_time > $rule_start_time ) {
					$bookable = $rule_val;
					continue;
				}

				// specific to hour duration types. If start time is in between
				// rule start and end times the rule should be applied.
				if ( 'hour' == $bookable_product->get_duration_unit()
				     && $slot_start_time > $rule_start_time
				     && $slot_start_time < $rule_end_time ) {

					$bookable = $rule_val;
					continue;

				}
			}
		}

		return $bookable;
	}

	/**
	 * Check a date against the availability rules
	 *
	 * @version 1.10.0 Moved to this class from WC_Product_Booking
	 *                 only apply rules if within their scope
	 *                 keep booking value alive within the loop to ensure the next rule with higher power can override
	 * @version 1.9.14 removed all calls to break 2 to ensure we get to the highest
	 *                 priority rules, otherwise higher order/priority rules will not
	 *                 override lower ones and the function exit with the wrong value.
	 *
	 *
	 * @param  WC_Product_Booking $bookable_product
	 * @param  int $resource_id
	 * @param  int $check_date timestamp
	 * @return bool available or not
	 */
	public static function check_availability_rules_against_date( $bookable_product, $resource_id, $check_date ) {
		$bookable = $bookable_product->get_default_availability();
		foreach ( $bookable_product->get_availability_rules( $resource_id ) as $rule ) {
			if ( self::does_rule_apply( $rule, $check_date ) ) {
				// passing $bookable into the next check as it overrides the previous value
				$bookable = self::check_timestamp_against_rule( $check_date, $rule, $bookable );
			}
		}
		return $bookable;
	}

	/**
	 * Does the time stamp fall within the scope of the rule?
	 *
	 * @param $rule
	 * @param $timestamp
	 * @return bool
	 */
	public static function does_rule_apply( $rule, $timestamp ) {
		$year        = intval( date( 'Y', $timestamp ) );
		$month       = intval( date( 'n', $timestamp ) );
		$day         = intval( date( 'j', $timestamp ) );
		$day_of_week = intval( date( 'N', $timestamp ) );
		$week        = intval( date( 'W', $timestamp ) );

		$range = $rule['range'];

		switch ( $rule['type'] ) {
			case 'months' :
				if ( isset( $range[ $month ] ) ) {
					return true;
				}
				break;
			case 'weeks':
				if ( isset( $range[ $week ] ) ) {
					return true;
				}
				break;
			case 'days' :
				if ( isset( $range[ $day_of_week ] ) ) {
					return true;
				}
				break;
			case 'custom' :
				if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
					return true;
				}
				break;
			case 'time':
			case 'time:1':
			case 'time:2':
			case 'time:3':
			case 'time:4':
			case 'time:5':
			case 'time:6':
			case 'time:7':
				if ( $day_of_week === $range['day'] || 0 === $range['day'] ) {
					return true;
				}
				break;
			case 'time:range':
				if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Given a timestamp and rule check to see if the time stamp is bookable based on the rule.
	 *
	 * @since 1.10.0
	 *
	 * @param integer $timestamp
	 * @param array $rule
	 * @param boolean $default
	 * @return boolean
	 */
	public static function check_timestamp_against_rule( $timestamp, $rule, $default ) {
		$year        = intval( date( 'Y', $timestamp ) );
		$month       = intval( date( 'n', $timestamp ) );
		$day         = intval( date( 'j', $timestamp ) );
		$day_of_week = intval( date( 'N', $timestamp ) );
		$week        = intval( date( 'W', $timestamp ) );

		$type  = $rule['type'];
		$range = $rule['range'];
		$bookable = $default;

		switch ( $type ) {
			case 'months' :
				if ( isset( $range[ $month ] ) ) {
					$bookable = $range[ $month ];
				}
				break;
			case 'weeks':
				if ( isset( $range[ $week ] ) ) {
					$bookable = $range[ $week ];
				}
				break;
			case 'days' :
				if ( isset( $range[ $day_of_week ] ) ) {
					$bookable = $range[ $day_of_week ];
				}
				break;
			case 'custom' :
				if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
					$bookable = $range[ $year ][ $month ][ $day ];
				}
				break;
			case 'time':
			case 'time:1':
			case 'time:2':
			case 'time:3':
			case 'time:4':
			case 'time:5':
			case 'time:6':
			case 'time:7':
				if ( false === $default && ( $day_of_week === $range['day'] || 0 === $range['day'] ) ) {
					$bookable = $range['rule'];
				}
				break;
			case 'time:range':
				if ( false === $default && ( isset( $range[ $year ][ $month ][ $day ] ) ) ) {
					$bookable = $range[ $year ][ $month ][ $day ]['rule'];
				}
				break;
		}

		return $bookable;
	}


}
