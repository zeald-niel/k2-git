<?php
namespace Bookly\Lib;

/**
 * Class Google
 * @package Bookly\Lib
 */
class Google
{
    const EVENTS_PER_REQUEST = 250;

    /** @var \Google_Client */
    private $client;

    /** @var \Google_Service_Calendar */
    private $service;

    /** @var \Google_Service_Calendar_CalendarListEntry */
    private $calendar;

    /** @var \Google_Service_Calendar_Event */
    private $event;

    /** @var \Bookly\Lib\Entities\Staff */
    private $staff;

    private $errors = array();

    public function __construct()
    {
        include_once Plugin::getDirectory() . '/lib/google/autoload.php';

        $this->client = new \Google_Client();
        $this->client->setClientId( get_option( 'bookly_gc_client_id' ) );
        $this->client->setClientSecret( get_option( 'bookly_gc_client_secret' ) );
    }

    /**
     * Load Google and Calendar Service data by Staff
     *
     * @param Entities\Staff $staff
     * @return bool
     */
    public function loadByStaff( Entities\Staff $staff )
    {
        $this->staff = $staff;
        if ( ! Config::isBooklyExpired() && $staff->get( 'google_data' ) ) {
            try {
                $this->client->setAccessToken( $staff->get( 'google_data' ) );
                if ( $this->client->isAccessTokenExpired() ) {
                    $this->client->refreshToken( $this->client->getRefreshToken() );
                    $staff->set( 'google_data', $this->client->getAccessToken() );
                    $staff->save();
                }

                $this->service = new \Google_Service_Calendar( $this->client );

                return true;
            } catch ( \Exception $e ) {
                $this->errors[] = 'Google Calendar: ' . $e->getMessage();
            }
        }

        return false;
    }

    /**
     * Load Google and Calendar Service data by Staff ID
     *
     * @param int $staff_id
     * @return bool
     */
    public function loadByStaffId( $staff_id )
    {
        $staff = Entities\Staff::find( $staff_id );

        return $this->loadByStaff( $staff );
    }

    /**
     * Create Event and return id
     *
     * @param Entities\Appointment $appointment
     * @return mixed
     */
    public function createEvent( Entities\Appointment $appointment )
    {
        try {
            if ( in_array( $this->getCalendarAccess(), array( 'writer', 'owner' ) ) ) {
                $this->event = new \Google_Service_Calendar_Event();

                $this->handleEventData( $appointment );

                /** @var \Google_Service_Calendar_Event $createdEvent */
                $createdEvent = $this->service->events->insert( $this->getCalendarID(), $this->event );

                return $createdEvent->getId();
            }
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Update event
     *
     * @param Entities\Appointment $appointment
     * @return bool
     */
    public function updateEvent( Entities\Appointment $appointment )
    {
        try {
            if ( in_array( $this->getCalendarAccess(), array( 'writer', 'owner' ) ) ) {
                $this->event = $this->service->events->get( $this->getCalendarID(), $appointment->get( 'google_event_id' ) );

                $this->handleEventData( $appointment );

                $this->service->events->update( $this->getCalendarID(), $this->event->getId(), $this->event );

                return true;
            }
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Get list of Google Calendars.
     *
     * @return array
     */
    public function getCalendarList()
    {
        $result = array();
        try {
            $calendarList = $this->service->calendarList->listCalendarList();
            while ( true ) {
                /** @var \Google_Service_Calendar_CalendarListEntry $calendarListEntry */
                foreach ( $calendarList->getItems() as $calendarListEntry ) {
                    if ( in_array( $calendarListEntry->getAccessRole(), array( 'writer', 'owner' ) ) ) {
                        $result[ $calendarListEntry->getId() ] = array(
                            'primary' => $calendarListEntry->getPrimary(),
                            'summary' => $calendarListEntry->getSummary(),
                        );
                    }
                }
                $pageToken = $calendarList->getNextPageToken();
                if ( $pageToken ) {
                    $optParams    = array( 'pageToken' => $pageToken );
                    $calendarList = $this->service->calendarList->listCalendarList( $optParams );
                } else {
                    break;
                }
            }
        } catch ( \Exception $e ) {
            Session::set( 'staff_google_auth_error', json_encode( $e->getMessage() ) );
        }

        return $result;
    }

    /**
     * Returns a collection of Google calendar events
     *
     * @param \DateTime $startDate
     * @return array|false
     */
    public function getCalendarEvents( \DateTime $startDate )
    {
        // get all events from calendar, without timeMin filter (the end of the event can be later then the start of searched time period)
        $result = array();

        try {
            $calendar_access = $this->getCalendarAccess();
            $limit_events    = get_option( 'bookly_gc_limit_events' );

            $timeMin = clone $startDate;
            $timeMin = $timeMin->modify( '-1 day' )->format( \DateTime::RFC3339 );

            $events = $this->service->events->listEvents( $this->getCalendarID(), array(
                'singleEvents' => true,
                'orderBy'      => 'startTime',
                'timeMin'      => $timeMin,
                'maxResults'   => $limit_events ?: self::EVENTS_PER_REQUEST,
            ) );

            $timezone_object = false;
            if ( $timezone_string = get_option( 'timezone_string' ) ) {
                $timezone_object = timezone_open( $timezone_string );
            }

            while ( true ) {
                foreach ( $events->getItems() as $event ) {
                    /** @var \Google_Service_Calendar_Event $event */
                    // transparency = 'opaque'      - The event blocks time on the calendar.
                    //              = 'transparent' - The event does not block time on the calendar.
                    if ( $event->getStatus() !== 'cancelled' && ( $event->getTransparency() === null || $event->getTransparency() === 'opaque' ) ) {
                        // Skip events created by Bookly in non freeBusyReader calendar.
                        if ( $calendar_access != 'freeBusyReader' ) {
                            $ext_properties = $event->getExtendedProperties();
                            if ( $ext_properties !== null ) {
                                $private = $ext_properties->private;
                                if ( $private !== null && array_key_exists( 'service_id', $private ) ) {
                                    continue;
                                }
                            }
                        }

                        // Get start/end dates of event and transform them into WP timezone (Google doesn't transform whole day events into our timezone).
                        $event_start = $event->getStart();
                        $event_end   = $event->getEnd();

                        if ( $event_start->dateTime == null ) {
                            // All day event.
                            $eventStartDate = new \DateTime( $event_start->date, new \DateTimeZone( $this->getCalendarTimezone() ) );
                            $eventEndDate = new \DateTime( $event_end->date, new \DateTimeZone( $this->getCalendarTimezone() ) );
                        } else {
                            // Regular event.
                            $eventStartDate = new \DateTime( $event_start->dateTime );
                            $eventEndDate = new \DateTime( $event_end->dateTime );
                        }

                        // Check if event intersects with our datetime interval.
                        if ( $eventEndDate > $startDate ) {
                            // If event lasts for more than 1 day, then split it.
                            for ( $date = clone $eventStartDate; $date < $eventEndDate; $date->modify( '+1 day' ) ) {
                                if ( $eventEndDate->format( 'ymd' ) == $date->format( 'ymd' ) ) {
                                    $endDate = $eventEndDate;
                                } else {
                                    $endDate = clone $date;
                                    $endDate->setTime( 0, 0, 0 )->modify( '+1 day' );
                                }

                                $start_date_offset = $end_date_offset = 0;
                                if ( $timezone_object ) {
                                    $start_date_offset = timezone_offset_get( $timezone_object, $date );
                                    $end_date_offset   = timezone_offset_get( $timezone_object, $endDate );
                                }

                                $result[] = array(
                                    'from_google'        => true,
                                    'service_id'         => 0,
                                    'start_time'         => $date->getTimestamp() + $start_date_offset,
                                    'end_time'           => $endDate->getTimestamp() + $end_date_offset,
                                    'padding_left'       => 0,
                                    'padding_right'      => 0,
                                    'number_of_bookings' => 1,
                                    'extras'             => '[]',
                                );
                            }
                        }
                    }
                }

                if ( ! $limit_events && $events->getNextPageToken() ) {
                    $events = $this->service->events->listEvents( $this->getCalendarID(), array(
                        'singleEvents' => true,
                        'orderBy'      => 'startTime',
                        'timeMin'      => $timeMin,
                        'pageToken'    => $events->getNextPageToken()
                    ) );
                } else {
                    break;
                }
            }

            return $result;
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * @param $code
     * @return bool
     */
    public function authCodeHandler( $code )
    {
        $this->client->setRedirectUri( self::generateRedirectURI() );

        try {
            $this->client->authenticate( $code );

            return true;
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    /**
     * Log out from Google Calendar.
     */
    public function logout()
    {
        try {
            $this->client->revokeToken();
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        $this->staff->set( 'google_data', null );
        $this->staff->set( 'google_calendar_id', null );
        $this->staff->save();
    }

    /**
     * @param $staff_id
     * @return string
     */
    public function createAuthUrl( $staff_id )
    {
        $this->client->setRedirectUri( self::generateRedirectURI() );
        $this->client->addScope( 'https://www.googleapis.com/auth/calendar' );
        $this->client->setState( strtr( base64_encode( $staff_id ), '+/=', '-_,' ) );
        $this->client->setApprovalPrompt( 'force' );
        $this->client->setAccessType( 'offline' );

        return $this->client->createAuthUrl();
    }

    /**
     * Delete event by id
     *
     * @param $event_id
     * @return bool
     */
    public function delete( $event_id )
    {
        try {
            if ( in_array( $this->getCalendarAccess(), array( 'writer', 'owner' ) ) ) {
                $this->service->events->delete( $this->getCalendarID(), $event_id );

                return true;
            }
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param Entities\Appointment $appointment
     */
    private function handleEventData( Entities\Appointment $appointment )
    {
        $start_datetime = new \Google_Service_Calendar_EventDateTime();
        $start_datetime->setDateTime(
            \DateTime::createFromFormat( 'Y-m-d H:i:s', $appointment->get( 'start_date' ), new \DateTimeZone( Utils\Common::getTimezoneString() ) )
                     ->format( \DateTime::RFC3339 )
        );

        $end_datetime = new \Google_Service_Calendar_EventDateTime();
        $end_datetime->setDateTime(
            \DateTime::createFromFormat( 'Y-m-d H:i:s', $appointment->get( 'end_date' ), new \DateTimeZone( Utils\Common::getTimezoneString() ) )
                     ->modify( '+' . $appointment->get( 'extras_duration' ) . ' sec' )
                     ->format( \DateTime::RFC3339 )
        );

        $service = Entities\Service::find( $appointment->get( 'service_id' ) );
        $description  = __( 'Service', 'bookly' ) . ': ' . $service->get( 'title' ) . PHP_EOL;
        $client_names = array();
        foreach ( $appointment->getCustomerAppointments() as $ca ) {
            $description .= sprintf(
                "%s: %s\n%s: %s\n%s: %s\n",
                __( 'Name',  'bookly' ), $ca->customer->get( 'name' ),
                __( 'Email', 'bookly' ), $ca->customer->get( 'email' ),
                __( 'Phone', 'bookly' ), $ca->customer->get( 'phone' )
            );
            $description .= $ca->getFormattedCustomFields( 'text' ) . PHP_EOL;
            if ( $ca->get( 'extras' ) != '[]' ) {
                $extras = implode( ', ', array_map( function ( $extra ) {
                    return $extra->get( 'title' );
                }, (array) Proxy\ServiceExtras::findByIds( array_keys( json_decode( $ca->get( 'extras' ), true ) ) ) ) );
                if ( ! empty( $extras ) ) {
                    $description .= __( 'Extras', 'bookly' ) . ': ' . $extras . PHP_EOL;
                }
            }
            $client_names[] = $ca->customer->get( 'name' );
        }

        $staff = Entities\Staff::find( $appointment->get( 'staff_id' ) );

        $title = strtr( get_option( 'bookly_gc_event_title', '{service_name}' ), array(
            '{service_name}' => $service->get( 'title' ),
            '{client_names}' => implode( ', ', $client_names ),
            '{staff_name}'   => $staff->get( 'full_name' ),
            /** @deprecate [[CODE]] */
            '[[SERVICE_NAME]]' => $service->get( 'title' ),
            '[[CLIENT_NAMES]]' => implode( ', ', $client_names ),
            '[[STAFF_NAME]]'   => $staff->get( 'full_name' ),
        ) );

        $this->event->setStart( $start_datetime );
        $this->event->setEnd( $end_datetime );
        $this->event->setSummary( $title );
        $this->event->setDescription( $description );

        $extended_property = new \Google_Service_Calendar_EventExtendedProperties();
        $extended_property->setPrivate( array(
            'customers'      => json_encode( array_map( function( $ca ) { return $ca->customer->get( 'id' ); }, $appointment->getCustomerAppointments() ) ),
            'service_id'     => $service->get( 'id' ),
            'appointment_id' => $appointment->get( 'id' ),
        ) );
        $this->event->setExtendedProperties( $extended_property );
    }

    /**
     * @return string
     */
    private function getCalendarID()
    {
        return $this->staff->get( 'google_calendar_id' ) ?: 'primary';
    }

    /**
     * @return string [freeBusyReader, reader, writer, owner]
     */
    private function getCalendarAccess()
    {
        if ( $this->calendar === null ) {
            $this->calendar = $this->service->calendarList->get( $this->getCalendarID() );
        }

        return $this->calendar->getAccessRole();
    }

    /**
     * @return mixed
     */
    private function getCalendarTimezone()
    {
        if ( $this->calendar === null ) {
            $this->calendar = $this->service->calendarList->get( $this->getCalendarID() );
        }

        return $this->calendar->getTimeZone();
    }

    /**
     * Validate calendar
     *
     * @param null $calendar_id (send this parameter on unsaved form)
     * @return bool
     */
    public function validateCalendar( $calendar_id = null )
    {
        if ( !$this->service ) {
            return false;
        }

        try {
            $this->service->calendarList->get( $calendar_id ?: $this->getCalendarID() );

            return true;
        } catch ( \Exception $e ) {
            $this->errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * @return string
     */
    public static function generateRedirectURI()
    {
        return admin_url( 'admin.php?page=' . \Bookly\Backend\Modules\Staff\Controller::page_slug );
    }

}