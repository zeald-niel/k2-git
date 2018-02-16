<?php
namespace Bookly\Lib;

/**
 * Class Slots
 * @package Bookly\Lib
 */
class Slots
{
    /** @var AvailableTime */
    private $at;
    /** @var \DateTime */
    private $date;
    /** @var int */
    private $number_of_persons;
    /** @var int */
    private $extras_duration;
    /** @var int */
    private $time_slot_length;
    /** @var int */
    private $time_from;
    /** @var int */
    private $time_to;
    /** @var array */
    private $service_schedule = array();
    /** @var array */
    private $excluded_staff = array();

    /** @var array */
    public $data = array();
    /** @var Entities\Service */
    public $service;
    /** @var Slots */
    public $next_slots;
    /** @var bool */
    public $has_more_slots = false;
    /** @var int Service duration in seconds */
    public $srv_duration;
    /** @var int Service duration in full days */
    public $srv_duration_days;

    /**
     * Constructor.
     *
     * @param AvailableTime $at
     * @param Entities\Service $service
     * @param int $number_of_persons
     * @param int $extras_duration
     */
    public function __construct( AvailableTime $at, Entities\Service $service, $number_of_persons, $extras_duration )
    {
        $this->at                 = $at;
        $this->service            = $service;
        $this->srv_duration       = $service->get( 'duration' );
        $this->srv_duration_days  = (int) ( $this->srv_duration / DAY_IN_SECONDS );
        $this->number_of_persons  = $number_of_persons;
        $this->extras_duration    = $this->srv_duration_days < 1 ? $extras_duration : 0;
        $this->time_slot_length   = Config::useServiceDurationAsSlotLength() ? $this->srv_duration : Config::getTimeSlotLength();
        $this->date               = clone $at->start_date;
        // Check a day before start date to fetch night slots for the first day (night slots fix)
        $this->date->sub($at->one_day);
        // Find time constraints.
        $this->time_from        = Utils\DateTime::timeToSeconds( $at->userData->get( 'time_from' ) );
        $this->time_to          = Utils\DateTime::timeToSeconds( $at->userData->get( 'time_to' ) );
        $this->service_schedule = (array) Proxy\ServiceSchedule::getSchedule( $service->get( 'id' ) );

        // Make sure that service duration and time slot length do not exceed DAY_IN_SECONDS (max search step).
        $this->srv_duration     = min( DAY_IN_SECONDS, $this->srv_duration );
        $this->time_slot_length = min( DAY_IN_SECONDS, $this->time_slot_length );
    }

    /**
     * Load slots.
     */
    public function load()
    {
        $this->_findSlots();

        // Detect if there are more slots.
        if ( ! $this->at->show_calendar ) {
            while ( $this->_findNextDay() ) {
                $available_time = $this->_findFrames();
                if ( ! empty ( $available_time ) ) {
                    $this->has_more_slots = true;
                    break;
                }
                $this->date->add( $this->at->one_day );
            }
        }
    }

    /**
     * Get slot (with loading more slots if requested slot is out of range).
     *
     * @param string $group
     * @param int $timestamp
     * @return array|false
     */
    private function get( $group, $timestamp )
    {
        do {
            $find_more_slots = false;

            end( $this->data );
            $last_group = key( $this->data );

            if ( $group > $last_group ) {
                $find_more_slots = true;
            } elseif ( $group == $last_group ) {
                end( $this->data[ $group ] );
                $last_timestamp = key( $this->data[ $group ] );
                if ( $timestamp > $last_timestamp ) {
                    $find_more_slots = true;
                }
            }

            if ( $find_more_slots ) {
                if ( $this->_findSlots() == false ) {
                    break;
                }
            } else {
                break;
            }
        } while ( true );

        return isset ( $this->data[ $group ][ $timestamp ] ) ? $this->data[ $group ][ $timestamp ] : false;
    }

    /**
     * Find next group of available slots.
     *
     * @return bool
     */
    private function _findSlots()
    {
        $this->data = array();

        $slots  = 0; // number of handled slots
        $groups = 0; // number of handled groups

        $multi_day_data = array(); // temporary data for multi-day services

        $next_month = null;
        $stop_group = null;
        if ( $this->at->show_calendar ) {
            $next_month = clone $this->date;
            // Fix to get correct month date (night slots fix)
            // We checked a day before start date to get night slots for the first day
            $next_month->add($this->at->one_day);
            if ( Config::useClientTimeZone() ) {
                // Add one day since it was subtracted in AvailableTime::_prepareDates
                //(when _findSlots is called the next time, this one day does not break anything).
                $next_month->add( $this->at->one_day );
            }
            $next_month->modify( 'first day of next month' );
            if ( $this->srv_duration_days > 1 ) {
                // For multi-day services check first days of the next month.
                $next_month->modify( ( $this->srv_duration_days - 1 ) . ' days' );
            }
            $stop_group = $next_month->format( 'Y-m-d' );
        }

        while ( true ) {
            $continue = false;
            // Check loop end conditions.
            if ( ! ( $this->at->params['break_on_first_fetched_day'] == true && $this->date > $this->at->start_date ) || $slots == 0 ) {
                if ( $this->_findNextDay() ) {
                    if ( $this->at->show_calendar ) {
                        if ( $this->date < $next_month ) {
                            $continue = true;
                        }
                    } else {
                        if ( $this->at->show_day_per_column ) {
                            $continue = true; // this loop will break when $groups reaches 10 (see loop body)
                        } elseif ( $slots < 100 ) {
                            $continue = true; // 10 slots/column * 10 columns
                        }
                    }
                }
            }
            if ( ! $continue ) {
                break;
            }

            foreach ( $this->_findFrames() as $frame ) {
                $blocked  = isset ( $frame['blocked'] );
                $not_full = isset ( $frame['not_full'] );
                // Loop from start to:
                //   1. end minus time slot length when 'blocked' or 'not_full' is set.
                //   2. end minus service duration when nothing is set.
                $end = null;
                if ( $blocked || $not_full ) {
                    $end = $frame['end'] - $this->time_slot_length;
                } else {
                    $end = $frame['end'] - $this->srv_duration - $this->extras_duration;
                }
                for ( $time = $frame['start']; $time <= $end; $time += $this->time_slot_length ) {

                    $timestamp        = $this->date->getTimestamp() + $time;
                    $client_timestamp = $timestamp - $this->at->client_diff;

                    if ( $timestamp < $this->at->current_timestamp || $client_timestamp < $this->at->req_timestamp ) {
                        // Skip all slots in the past.
                        // Also when we start 1 day before the requested date skip slots which do not fit the requested date in client's time zone.
                        continue;
                    }

                    $group = date( 'Y-m-d', ( $this->srv_duration_days && ! $this->at->show_calendar )
                        ? strtotime( 'first day of this month', $client_timestamp )  // group slots by months
                        : $client_timestamp                                          // group slots by days
                    );

                    if ( $this->at->show_calendar && $group == $stop_group ) {
                        break;
                    }

                    // Create/update slots.
                    if ( ! isset ( $this->data[ $group ][ $client_timestamp ] ) ) {
                        $data = null;

                        if ( $this->next_slots === null ) {
                            $data = array( array( (int) $this->service->get( 'id' ), $frame['staff_id'], $timestamp ) );
                        } else {
                            $next_data      = null;
                            $next_timestamp = $client_timestamp + $this->srv_duration + $this->extras_duration;
                            $next_group     = date( 'Y-m-d', ( $this->next_slots->srv_duration_days && ! $this->at->show_calendar )
                                ? strtotime( 'first day of this month', $next_timestamp )
                                : $next_timestamp
                            );
                            $padding = $this->service->get( 'padding_right' ) + $this->next_slots->service->get( 'padding_left' );
                            // Look for available slot for next service. There are 2 possible options:
                            // 1. next service is done by another staff, then do not take into account padding
                            // 2. next service is done by the same staff, then count padding
                            if ( ( $slot = $this->next_slots->get( $next_group, $next_timestamp ) ) &&
                                $slot['blocked'] == false &&
                                ( $padding == 0 || $slot['data'][0][1] != $frame['staff_id'] )
                            ) {
                                $next_data = $slot['data'];
                            } else {
                                $next_timestamp += $padding;
                                $next_group = date( 'Y-m-d', ( $this->next_slots->srv_duration_days && ! $this->at->show_calendar )
                                    ? strtotime( 'first day of this month', $next_timestamp )
                                    : $next_timestamp
                                );
                                if ( ( $slot = $this->next_slots->get( $next_group, $next_timestamp ) ) &&
                                    $slot['blocked'] == false &&
                                    $slot['data'][0][1] == $frame['staff_id']
                                ) {
                                    $next_data = $slot['data'];
                                }
                            }
                            // If slot is found, then add its data to the current slot data.
                            if ( $next_data !== null ) {
                                $data = array_merge( array( array( (int) $this->service->get( 'id' ), $frame['staff_id'], $timestamp ) ), $next_data );
                            }
                        }

                        if ( $data !== null ) {
                            $add_slot = true;
                            if ( $this->srv_duration_days > 1 && ! $not_full ) {
                                // Handle multi-day services.
                                $multi_day_data[ $timestamp ] = array(
                                    'data'    => $data,
                                    'blocked' => $blocked,
                                );
                                if ( ! $blocked ) {
                                    // Check if there are enough valid days for service duration in the past.
                                    for ( $d = 1; $d < $this->srv_duration_days; ++ $d ) {
                                        $timestamp -= DAY_IN_SECONDS;
                                        if ( ! isset ( $multi_day_data[ $timestamp ] ) || $multi_day_data[ $timestamp ]['blocked'] ) {
                                            $add_slot = false;
                                            break;
                                        }
                                    }
                                    if ( $add_slot ) {
                                        // Replace first slot with data from the day when service starts.
                                        $data[0] = $multi_day_data[ $timestamp ]['data'][0];
                                        // Re-calculate client timestamp and group.
                                        $client_timestamp = $timestamp - $this->at->client_diff;
                                        $group = date( 'Y-m-d', ! $this->at->show_calendar
                                            ? strtotime( 'first day of this month', $client_timestamp )  // group slots by months
                                            : $client_timestamp                                          // group slots by days
                                        );
                                    }
                                }
                            }
                            if ( $add_slot ) {
                                if ( ! isset ( $this->data[ $group ] ) ) {
                                    if ( ! $this->at->show_calendar && $this->at->show_day_per_column && $groups + 1 > 10 ) {
                                        // Break the while-loop.
                                        break ( 3 );
                                    }
                                    $this->data[ $group ] = array();
                                    ++ $slots;
                                    ++ $groups;
                                }
                                $this->data[ $group ][ $client_timestamp ] = array(
                                    'data'    => $data,
                                    'blocked' => $blocked,
                                );
                                ++ $slots;
                            }
                        }
                    } elseif ( ! $blocked ) {
                        if ( $this->data[ $group ][ $client_timestamp ]['blocked'] ) {
                            // Set slot to available if it was marked as 'blocked' before.
                            $this->data[ $group ][ $client_timestamp ]['data'][0][1] = $frame['staff_id'];
                            $this->data[ $group ][ $client_timestamp ]['blocked']    = false;
                        } else {
                            // Change staff member for this slot if the other staff member has higher price.
                            $staff_id = $this->data[ $group ][ $client_timestamp ]['data'][0][1];
                            if ( $this->at->staff_data[ $staff_id ]['services'][ $this->service->get( 'id' ) ]['price'] <
                                $this->at->staff_data[ $frame['staff_id'] ]['services'][ $this->service->get( 'id' ) ]['price'] ) {
                                $this->data[ $group ][ $client_timestamp ]['data'][0][1] = $frame['staff_id'];
                            }
                        }
                    }
                }
            }

            $this->date->add( $this->at->one_day );
        }

        // Arrange slots in time.
        foreach ( $this->data as $group => &$group_slots ) {
            ksort( $group_slots );
        }
        ksort( $this->data );

        return $slots > 0;
    }

    /**
     * Find a day which is available for booking based on
     * user requested set of days.
     *
     * @access private
     * @return bool
     */
    private function _findNextDay()
    {
        if (
            $this->at->params['exclude_staff_with_day_off'] &&
            count( $this->excluded_staff ) >= count( $this->at->staff_data )
        ) {
            // The search must stop if all staff have been excluded.
            return false;
        }

        $attempt = 0;
        // Find available day within requested days.
        $requested_days = $this->at->userData->get( 'days' );

        while ( ! in_array( (int) $this->date->format( 'w' ) + 1, $requested_days ) ) {
            $this->date->add( $this->at->one_day );
            if ( ++ $attempt >= 7 ) {
                return false;
            }
        }

        return $this->date >= $this->at->max_date ? false : true;
    }


    /**
     * Find array of frames available for booking.
     *
     * @return array
     */
    private function _findFrames()
    {
        $result      = array();
        $service_id  = $this->service->get( 'id' );
        $date_Ymd    = $this->date->format( 'Y-m-d' );
        $date_md     = $this->date->format( 'm-d' );
        $day_of_week = (int) $this->date->format( 'w' ) + 1; // 1-7
        $time_from   = $this->time_from;
        $time_to     = $this->time_to;

        $service_breaks = array();
        if ( Config::serviceScheduleEnabled() ) {
            if ( isset ( $this->service_schedule['daily'][ $date_Ymd ] ) ) {
                $time_from = max( $time_from, $this->service_schedule['daily'][ $date_Ymd ]['start_time'] );
                $time_to   = min( $time_to,   $this->service_schedule['daily'][ $date_Ymd ]['end_time'] );
                $service_breaks = $this->service_schedule['daily'][ $date_Ymd ]['breaks'];
            } elseif ( isset ( $this->service_schedule['weekly'][ $day_of_week ] ) ) {
                $time_from = max( $time_from, $this->service_schedule['weekly'][ $day_of_week ]['start_time'] );
                $time_to   = min( $time_to,   $this->service_schedule['weekly'][ $day_of_week ]['end_time'] );
                $service_breaks = $this->service_schedule['weekly'][ $day_of_week ]['breaks'];
            } else {
                if ( $this->at->params['exclude_staff_with_day_off'] && $this->date == $this->at->start_date ) {
                    // Stop the search if the starting day is off due to service schedule.
                    $this->excluded_staff = array_keys( $this->at->staff_data );
                }
                return $result;
            }
        }

        foreach ( $this->at->staff_data as $staff_id => $staff ) {
            if ( $this->at->params['exclude_staff_with_day_off'] && in_array( $staff_id, $this->excluded_staff ) ) {
                // Skip this staff if he or she has been excluded from the search.
                continue;
            }

            if ( ! isset ( $staff['services'][ $service_id ] ) ||
                $staff['services'][ $service_id ]['capacity'] < $this->number_of_persons ) {
                continue;
            }

            // Find schedule for this day.
            $schedule = null;
            if ( isset ( $staff['special_days'][ $date_Ymd ] ) ) {
                $schedule = $staff['special_days'][ $date_Ymd ];
            }
            if (
                ! $schedule &&
                isset ( $staff['working_hours'][ $day_of_week ] ) &&  // working day
                ! isset ( $staff['holidays'][ $date_Ymd ] ) &&        // no holiday
                ! isset ( $staff['holidays'][ $date_md ] )            // no repeating holiday
            ) {
                $schedule = $staff['working_hours'][ $day_of_week ];
            }

            if ( $schedule ) {
                if ( $this->srv_duration_days ) {
                    // For whole day services do not check staff working hours.
                    $intersection = array(
                        'start' => 0,
                        'end'   => DAY_IN_SECONDS,
                    );
                } else {
                    // Find intersection between staff schedule, service schedule and requested hours.
                    $intersection = $this->_findIntersection( $schedule['start_time'], $schedule['end_time'], $time_from, $time_to );
                }

                if ( $intersection ) {
                    if ( $intersection['end'] - $intersection['start'] >= $this->srv_duration ) {
                        // Initialize time frames.
                        $frames = array( array(
                            'start'    => $intersection['start'],
                            'end'      => $intersection['end'],
                            'staff_id' => $staff_id,
                        ) );
                        if ( ! $this->srv_duration_days ) {
                            // Remove breaks from time frames for non all day services only.
                            foreach ( array_merge( $schedule['breaks'], $service_breaks ) as $break ) {
                                $frames = $this->_removeTimePeriod( $frames, $break['start'], $break['end'], false );
                            }
                        }
                        // Remove bookings from time frames.
                        foreach ( $staff['bookings'] as $booking ) {
                            // Work with bookings which intersect with intersection.
                            $start_time = $booking['start_time'] - $this->date->getTimestamp();
                            $end_time   = $booking['end_time'] - $this->date->getTimestamp();
                            if ( $end_time > $intersection['start'] && $start_time < $intersection['end'] ) {
                                $frames = $this->_removeTimePeriod(
                                    $frames,
                                    $start_time - $booking['padding_left'] - $this->service->get( 'padding_right' ),
                                    $end_time + $booking['padding_right'] + $this->service->get( 'padding_left' ),
                                    true,
                                    $removed
                                );

                                if ( $removed ) {
                                    // Handle not full bookings (when number of bookings is less than capacity).
                                    if (
                                        $booking['from_google'] == false &&
                                        $booking['service_id'] == $this->service->get( 'id' ) &&
                                        $start_time >= $intersection['start'] &&
                                        $staff['services'][ $service_id ]['capacity'] - $booking['number_of_bookings'] >= $this->number_of_persons
                                    ) {
                                        if ( $booking['extras_duration'] >= $this->extras_duration ) {
                                            // Show the first slot as available.
                                            $frames[] = array(
                                                'start'    => $start_time,
                                                'end'      => $start_time + $this->time_slot_length,
                                                'staff_id' => $staff_id,
                                                'not_full' => true,
                                            );
                                        }
                                    }
                                    if ( $this->srv_duration_days ) {
                                        // For all day services we break the loop since there can be
                                        // just 1 booking per day for such services.
                                        break;
                                    }
                                }
                            }
                        }
                        $result = array_merge( $result, $frames );
                    }
                }
            }
            else if ( $this->at->params['exclude_staff_with_day_off'] && $this->date == $this->at->start_date ) {
                // Exclude staff from the search if his or her starting day is off.
                $this->excluded_staff[] = $staff_id;
            }
        }

        return $result;
    }

    /**
     * Find intersection between 2 time periods.
     *
     * @param mixed $p1_start
     * @param mixed $p1_end
     * @param mixed $p2_start
     * @param mixed $p2_end
     * @return array|null
     */
    private function _findIntersection( $p1_start, $p1_end, $p2_start, $p2_end )
    {
        $result = null;

        if ( $p1_start <= $p2_start && $p1_end > $p2_start && $p1_end <= $p2_end ) {
            $result = array( 'start' => $p2_start, 'end' => $p1_end );
        } elseif ( $p1_start <= $p2_start && $p1_end >= $p2_end ) {
            $result = array( 'start' => $p2_start, 'end' => $p2_end );
        } elseif ( $p1_start >= $p2_start && $p1_start < $p2_end && $p1_end >= $p2_end ) {
            $result = array( 'start' => $p1_start, 'end' => $p2_end );
        } elseif ( $p1_start >= $p2_start && $p1_end <= $p2_end ) {
            $result = array( 'start' => $p1_start, 'end' => $p1_end );
        }

        return $result;
    }

    /**
     * Remove time period from the set of time frames.
     *
     * @param array $frames
     * @param mixed $p_start
     * @param mixed $p_end
     * @param bool  $round
     * @param bool& $removed  Whether the period was removed or not
     * @return array
     */
    private function _removeTimePeriod(array $frames, $p_start, $p_end, $round, &$removed = false )
    {
        $result  = array();
        $removed = false;

        foreach ( $frames as $frame ) {
            $intersection = $this->_findIntersection(
                $frame['start'],
                $frame['end'],
                $p_start,
                $p_end
            );

            if ($intersection) {
                if ( $round ) {
                    // Round intersection start and end times to a multiple of the time slot length.
                    $extra = ( $intersection['start'] - $frame['start'] ) % $this->time_slot_length;
                    $intersection['start'] -= $extra;
                    $extra = ( $intersection['end'] - $frame['start'] ) % $this->time_slot_length;
                    if ( $extra ) {
                        $intersection['end'] += $this->time_slot_length - $extra;
                    }
                }

                $blocked_start = $frame['start'];
                $blocked_end   = $frame['end'];
                if ( $intersection['start'] - $frame['start'] >= $this->srv_duration ) {
                    $result[] = array_merge( $frame, array(
                        'end' => $intersection['start'],
                    ) );
                    $blocked_start = $intersection['start'];
                }
                if ( $frame['end'] - $intersection['end'] >= $this->srv_duration ) {
                    $result[] = array_merge( $frame, array(
                        'start' => $intersection['end'],
                    ) );
                    $blocked_end = $intersection['end'];
                }
                if ( $this->at->show_blocked_slots ) {
                    // Show removed period as 'blocked'.
                    $result[] = array_merge( $frame, array(
                        'start'   => $blocked_start,
                        'end'     => $this->srv_duration_days ? $this->time_slot_length : $blocked_end,
                        'blocked' => true,
                    ) );
                }
                $removed = true;
            } else {
                $result[] = $frame;
            }
        }

        return $result;
    }

}