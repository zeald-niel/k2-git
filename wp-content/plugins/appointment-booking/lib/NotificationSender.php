<?php
namespace Bookly\Lib;

/**
 * Class NotificationSender
 * @package Bookly\Lib
 */
abstract class NotificationSender
{
    /** @var SMS */
    private static $sms = null;

    /**
     * Send instant notifications.
     *
     * @param Entities\CustomerAppointment $ca
     * @param mixed[] extra data for templates
     */
    public static function send( Entities\CustomerAppointment $ca, array $data = array() )
    {
        list ( $codes, $appointment, $customer, $staff ) = self::_prepareData( $ca );

        if ( isset( $data['cancellation_reason'] ) ) {
            $codes->set( 'cancellation_reason', $data['cancellation_reason'] );
        }

        $status = $ca->get( 'status' );

        // Notify staff by email.
        if ( $notification = self::_getEmailNotification( 'staff', $status ) ) {
            self::_sendEmailToStaff( $notification, $codes, $staff->get( 'email' ), $customer );
        }
        // Notify staff by SMS.
        if ( $notification = self::_getSmsNotification( 'staff', $status ) ) {
            self::_sendSmsToStaff( $notification, $codes, $staff->get( 'phone' ) );
        }
        // Client time zone offset.
        if ( $ca->get( 'time_zone_offset' ) !== null ) {
            $codes->set( 'appointment_start', Utils\DateTime::applyTimeZoneOffset( $appointment->get( 'start_date' ), $ca->get( 'time_zone_offset' ) ) );
            $codes->set( 'appointment_end', Utils\DateTime::applyTimeZoneOffset( $appointment->get( 'end_date' ), $ca->get( 'time_zone_offset' ) ) );
        }
        // Notify client by email.
        if ( $notification = self::_getEmailNotification( 'client', $status ) ) {
            self::_sendEmailToClient( $notification, $codes, $customer->get( 'email' ) );
        }
        // Notify client by SMS.
        if ( $notification = self::_getSmsNotification( 'client', $status ) ) {
            self::_sendSmsToClient( $notification, $codes, $customer->get( 'phone' ) );
        }
    }

    /**
     * Send notification for recurring appointment list
     * @todo support cart
     *
     * @param array $recurring_list [appointments[], customers[]]
     * @param mixed[] extra data for templates
     */
    public static function sendRecurring( array $recurring_list, array $data = array() )
    {
        if ( $recurring_list['appointments'] ) {
            $first_ca = null;
            $staff    = null;
            $schedule_data = array( 'appointments' => array() );
            /** @var Entities\Appointment $appointment */
            foreach ( $recurring_list['appointments'] as $appointment ) {
                if ( $first_ca === null ) {
                    $first_ca = current( $appointment->getCustomerAppointments( true ) );
                    $staff    = Entities\Staff::find( $appointment->get( 'staff_id' ) );
                }
                $schedule_data['appointments'][ $appointment->get( 'id' ) ] = array(
                    'start' => $appointment->get( 'start_date' ),
                );
            }
            $customers_id = array();
            foreach ( $recurring_list['customers'] as $customer_data ) {
                $customers_id[] = $customer_data['id'];
            }
            list ( $codes ) = self::_prepareData( $first_ca );
            $codes->set( 'cancellation_reason', $data['cancellation_reason'] );
            $cas_token = Entities\CustomerAppointment::query( 'ca' )
                ->select( 'ca.appointment_id,ca.token,ca.customer_id' )
                ->whereIn( 'ca.appointment_id', array_keys( $schedule_data['appointments'] ) )
                ->whereIn( 'ca.customer_id', $customers_id )
                ->fetchArray();

            $time_zone_offset = $first_ca->get( 'time_zone_offset' );
            $original_start = $codes->get( 'appointment_start' );
            $original_end   = $codes->get( 'appointment_end' );
            foreach ( $recurring_list['customers'] as $customer_data ) {
                $customer_id = $customer_data['id'];
                $status   = $customer_data['status'];
                $customer = Entities\Customer::find( $customer_id );
                // Codes for first ca, set codes for current customer
                $codes->set( 'client_email', $customer->get( 'email' ) );
                $codes->set( 'client_name',  $customer->get( 'name' ) );
                $codes->set( 'client_phone', $customer->get( 'phone' ) );

                $schedule_codes = $schedule_data;
                foreach ( $cas_token as $appointment ) {
                    if ( $appointment['customer_id'] == $customer_id ) {
                        // Set token for customer appointment
                        $schedule_codes['appointments'][ $appointment['appointment_id'] ]['token'] = $appointment['token'];
                    }
                }
                /* schedule_codes = [
                 *      appointments = [
                 *          appointment_id = [
                 *              start => Y-m-d H:i:s
                 *              token => ca.token
                 *          ]
                 *          ...
                 *      ]
                 * ]
                 */
                $codes->set( 'schedule_codes', $schedule_codes );

                // Notify staff by email.
                if ( $notification = self::_getEmailNotification( 'staff', $status, true ) ) {
                    self::_sendEmailToStaff( $notification, $codes, $staff->get( 'email' ), $customer );
                }
                // Notify staff by SMS.
                if ( $notification = self::_getSmsNotification( 'staff', $status, true ) ) {
                    self::_sendSmsToStaff( $notification, $codes, $staff->get( 'phone' ) );
                }
                if ( $time_zone_offset !== null ) {
                    // For client need apply client time zone offset.
                    foreach ( $schedule_codes['appointments'] as $appointment ) {
                        $appointment['start'] = Utils\DateTime::applyTimeZoneOffset( $appointment['start'], $time_zone_offset );
                    }
                    $codes->set( 'appointment_start', Utils\DateTime::applyTimeZoneOffset( $codes->get( 'appointment_start' ), $time_zone_offset ) );
                    $codes->set( 'appointment_end', Utils\DateTime::applyTimeZoneOffset( $codes->get( 'appointment_end' ), $time_zone_offset ) );
                }
                // Notify client by email.
                if ( $notification = self::_getEmailNotification( 'client', $status, true ) ) {
                    self::_sendEmailToClient( $notification, $codes, $customer->get( 'email' ) );
                }
                // Notify client by SMS.
                if ( $notification = self::_getSmsNotification( 'client', $status, true ) ) {
                    self::_sendSmsToClient( $notification, $codes, $customer->get( 'phone' ) );
                }
                if ( $time_zone_offset !== null && count( $recurring_list['customers'] ) > 1 ) {
                    // Restore appointment_start & appointment_end for staff notifications
                    // When sending notifications for customers values was changed
                    $codes->set( 'appointment_start', $original_start );
                    $codes->set( 'appointment_end', $original_end );
                }
            }
        }
    }

    /**
     * Send notification from cart.
     *
     * @param Entities\CustomerAppointment[] $ca_list
     */
    public static function sendFromCart( array $ca_list )
    {
        if ( Config::areCombinedNotificationsEnabled() && ! empty( $ca_list ) ) {
            $status    = get_option( 'bookly_gen_default_appointment_status' );
            $cart_info = array();
            $payments  = array();
            $customer  = null;
            $codes     = null;
            $total     = 0.0;
            $compound_tokens = array();
            $email_to_staff  = self::_getEmailNotification( 'staff', $status );
            $sms_to_staff    = self::_getSmsNotification( 'staff', $status );

            foreach ( $ca_list as $ca ) {
                if ( ! isset( $compound_tokens[ $ca->get( 'compound_token' ) ] ) ) {
                    if ( $ca->get( 'compound_token' ) ) {
                        $compound_tokens[ $ca->get( 'compound_token' ) ] = true;
                    }
                    list ( $codes, $appointment, $customer, $staff ) = self::_prepareData( $ca );

                    if ( $email_to_staff ) {
                        // Send email to staff member (and admins if necessary).
                        self::_sendEmailToStaff( $email_to_staff, $codes, $staff->get( 'email' ), $customer );
                    }
                    if ( $sms_to_staff ) {
                        // Send SMS to staff member (and admins if necessary).
                        self::_sendSmsToStaff( $sms_to_staff, $codes, $staff->get( 'phone' ) );
                    }

                    // Prepare data for {cart_info} || {cart_info_c}.
                    $cart_info[] = array(
                        'appointment_price' => ( $codes->get( 'service_price' ) + $codes->get( 'extras_total_price', 0 ) )  * $codes->get( 'number_of_persons' ),
                        'appointment_start' => $ca->get( 'time_zone_offset' ) !== null
                            ? Utils\DateTime::applyTimeZoneOffset( $appointment->get( 'start_date' ), $ca->get( 'time_zone_offset' ) )
                            : $codes->get( 'appointment_start' ),
                        'cancel_url'   => admin_url( 'admin-ajax.php?action=bookly_cancel_appointment&token=' . $codes->get( 'appointment_token' ) ),
                        'service_name' => $codes->get( 'service_name' ),
                        'staff_name'   => $codes->get( 'staff_name' ),
                        'extras'       => (array) Proxy\ServiceExtras::getInfo( $ca->get( 'extras' ), true ),
                    );
                    if ( ! isset( $payments[ $ca->get( 'payment_id' ) ] ) ) {
                        if ( $ca->get( 'payment_id' ) ) {
                            $payments[ $ca->get( 'payment_id' ) ] = true;
                        }
                        $total += $codes->get( 'total_price' );
                    }
                }
            }
            $codes->set( 'total_price', $total );
            $codes->set( 'cart_info',   $cart_info );
            // Send notifications to client.
            if ( $to_client = self::_getCombinedEmailNotification( $status ) ) {
                self::_sendEmailToClient( $to_client, $codes, $customer->get( 'email' ) );
            }
            if ( $to_client = self::_getCombinedSmsNotification( $status ) ) {
                self::_sendSmsToClient( $to_client, $codes, $customer->get( 'phone' ) );
            }
        } else { // Combined notifications disabled.
            $recurrings_lists = array();
            foreach ( $ca_list as $ca ) {
                $appointment = new Entities\Appointment();
                $appointment->load( $ca->get( 'appointment_id' ) );
                if ( $appointment->get( 'series_id' ) ) {
                    $recurrings_lists[ $appointment->get( 'series_id' ) ][ 'appointments' ][] = $appointment;
                    $recurrings_lists[ $appointment->get( 'series_id' ) ][ 'customers' ][ $ca->get( 'customer_id' ) ] = array(
                        'id'     => $ca->get( 'customer_id' ),
                        'status' => $ca->get( 'status' ),
                    );
                } else {
                    self::send( $ca );
                }
            }
            foreach ( $recurrings_lists as $recurrings_list ) {
                self::sendRecurring( $recurrings_list );
            }
        }
    }

    /**
     * Send reminder (email or SMS) to client.
     *
     * @param Entities\Notification $notification
     * @param Entities\CustomerAppointment $ca
     * @return bool
     */
    public static function sendFromCronToClient( Entities\Notification $notification, Entities\CustomerAppointment $ca )
    {
        list ( $codes, $appointment, $customer ) = self::_prepareData( $ca );

        // Client time zone offset.
        if ( $ca->get( 'time_zone_offset' ) !== null ) {
            $codes->set( 'appointment_start', Utils\DateTime::applyTimeZoneOffset( $appointment->get( 'start_date' ), $ca->get( 'time_zone_offset' ) ) );
            $codes->set( 'appointment_end', Utils\DateTime::applyTimeZoneOffset( $appointment->get( 'end_date' ), $ca->get( 'time_zone_offset' ) ) );
        }

        // Send notification to client.
        return $notification->get( 'gateway' ) == 'email'
            ? self::_sendEmailToClient( $notification, $codes, $customer->get( 'email' ), $ca->get( 'locale' ) )
            : self::_sendSmsToClient( $notification, $codes, $customer->get( 'phone' ), $ca->get( 'locale' ) );
    }

    /**
     * Send reminder (email or SMS) to staff.
     *
     * @param Entities\Notification $notification
     * @param NotificationCodes $codes
     * @param string $email
     * @param string $phone
     * @return bool
     */
    public static function sendFromCronToStaff( Entities\Notification $notification, NotificationCodes $codes, $email, $phone )
    {
        return $notification->get( 'gateway' ) == 'email'
            ? self::_sendEmailToStaff( $notification, $codes, $email, null, false )
            : self::_sendSmsToStaff( $notification, $codes, $phone );
    }

    /**
     * Send birthday greeting to client.
     *
     * @param Entities\Notification $notification
     * @param array $customer
     * @return bool
     */
    public static function sendFromCronBirthdayGreeting( Entities\Notification $notification, array $customer )
    {
        $codes = new NotificationCodes();
        $codes->set( 'client_email', $customer['email'] );
        $codes->set( 'client_name',  $customer['name'] );
        $codes->set( 'client_phone', $customer['phone'] );

        return $notification->get( 'gateway' ) == 'email'
            ? self::_sendEmailToClient( $notification, $codes, $customer['email'] )
            : self::_sendSmsToClient( $notification, $codes, $customer['phone'] );
    }

    /**
     * Send email/sms with username and password for newly created WP user.
     *
     * @param Entities\Customer $customer
     * @param $username
     * @param $password
     */
    public static function sendNewUserCredentials( Entities\Customer $customer, $username, $password )
    {
        $codes = new NotificationCodes();
        $codes->set( 'client_email', $customer->get( 'email' ) );
        $codes->set( 'client_name',  $customer->get( 'name' ) );
        $codes->set( 'client_phone', $customer->get( 'phone' ) );
        $codes->set( 'new_password', $password );
        $codes->set( 'new_username', $username );
        $codes->set( 'site_address', site_url() );

        $to_client = new Entities\Notification();
        if ( $to_client->loadBy( array( 'type' => 'client_new_wp_user', 'gateway' => 'email', 'active' => 1 ) ) ) {
            self::_sendEmailToClient( $to_client, $codes, $customer->get( 'email' ) );
        }
        if ( $to_client->loadBy( array( 'type' => 'client_new_wp_user', 'gateway' => 'sms', 'active' => 1 ) ) ) {
            self::_sendSmsToClient( $to_client, $codes, $customer->get( 'phone' ) );
        }
    }

    /**
     * Send test notification emails.
     *
     * @param string $to_mail
     * @param array  $notification_types
     * @param string $send_as
     */
    public static function sendTestEmailNotifications( $to_mail, array $notification_types, $send_as )
    {
        $start_date  = date_create( '-1 month' );
        $event_start = $start_date->format( 'Y-m-d 12:00:00' );
        $event_end = $start_date->format( 'Y-m-d 13:00:00' );
        $cart_info = array( array(
            'service_name'      => 'Service Name',
            'appointment_start' => $event_start,
            'staff_name'        => 'Staff Name',
            'appointment_price' => 24,
            'cancel_url'        => '#',
        ) );

        $codes = new NotificationCodes();
        $codes->set( 'amount_due',          '' );
        $codes->set( 'amount_paid',         '' );
        $codes->set( 'appointment_end',     $event_end );
        $codes->set( 'appointment_start',   $event_start );
        $codes->set( 'cart_info',           $cart_info );
        $codes->set( 'category_name',       'Category Name' );
        $codes->set( 'client_email',        'client@example.com' );
        $codes->set( 'client_name',         'Client Name' );
        $codes->set( 'client_phone',        '12345678' );
        $codes->set( 'extras',              'Extras 1, Extras 2' );
        $codes->set( 'extras_total_price',  '4' );
        $codes->set( 'new_password',        'New Password' );
        $codes->set( 'new_username',        'New User' );
        $codes->set( 'next_day_agenda',     '' );
        $codes->set( 'number_of_persons',   '1' );
        $codes->set( 'payment_type',        Entities\Payment::typeToString( Entities\Payment::TYPE_LOCAL ) );
        $codes->set( 'service_info',        'Service info text' );
        $codes->set( 'service_name',        'Service Name' );
        $codes->set( 'service_price',       '10' );
        $codes->set( 'staff_email',         'staff@example.com' );
        $codes->set( 'staff_info',          'Staff info text' );
        $codes->set( 'staff_name',          'Staff Name' );
        $codes->set( 'staff_phone',         '23456789' );
        $codes->set( 'staff_photo',         'https://dummyimage.com/100/dddddd/000000' );
        $codes->set( 'total_price',         '24' );
        $codes->set( 'cancellation_reason', 'Some Reason' );

        $codes = Proxy\Shared::prepareTestNotificationCodes( $codes );

        $notification = new Entities\Notification();
        $customer     = new Entities\Customer();

        foreach ( $notification_types as $type ) {
            $notification->loadBy( array( 'type' => $type, 'gateway' => 'email' ) );

            switch ( $type ) {
                case 'client_pending_appointment':
                case 'client_approved_appointment':
                case 'client_cancelled_appointment':
                case 'client_rejected_appointment':
                case 'client_pending_appointment_cart':
                case 'client_approved_appointment_cart':
                case 'client_new_wp_user':
                case 'client_reminder':
                case 'client_follow_up':
                    self::_sendEmailToClient( $notification, $codes, $to_mail, null, $send_as );
                    break;
                case 'staff_pending_appointment':
                case 'staff_approved_appointment':
                case 'staff_cancelled_appointment':
                case 'staff_rejected_appointment':
                    self::_sendEmailToStaff( $notification, $codes, $to_mail, $customer, null, $send_as );
                    break;
                case 'staff_agenda':
                    self::_sendEmailToStaff( $notification, $codes, $to_mail, null, false, $send_as );
                    break;
                // Recurring Appointments email notifications.
                case 'client_pending_recurring_appointment':
                case 'client_approved_recurring_appointment':
                case 'client_cancelled_recurring_appointment':
                case 'client_rejected_recurring_appointment':
                    self::_sendEmailToClient( $notification, $codes, $to_mail, null, $send_as );
                    break;
                case 'staff_pending_recurring_appointment':
                case 'staff_approved_recurring_appointment':
                case 'staff_cancelled_recurring_appointment':
                case 'staff_rejected_recurring_appointment':
                    self::_sendEmailToStaff( $notification, $codes, $to_mail, $customer, null, $send_as );
                    break;
            }
        }
    }

    /******************************************************************************************************************
     * Private methods                                                                                                *
     ******************************************************************************************************************/

    /**
     * Prepare data for email.
     *
     * @param Entities\CustomerAppointment $ca
     * @param mixed[] extra data for templates
     * @return array [ NotificationCodes, Entities\Appointment, Entities\Customer, Entities\Staff ]
     */
    private static function _prepareData( Entities\CustomerAppointment $ca, array $data = array() )
    {
        global $sitepress;

        if ( $sitepress instanceof \SitePress ) {
            $sitepress->switch_lang( $ca->get( 'locale' ), true );
        }
        $appointment = new Entities\Appointment();
        $appointment->load( $ca->get( 'appointment_id' ) );

        $customer = new Entities\Customer();
        $customer->load( $ca->get( 'customer_id' ) );

        $staff = new Entities\Staff();
        $staff->load( $appointment->get( 'staff_id' ) );

        $service = new Entities\Service();
        $staff_service = new Entities\StaffService();
        if ( $ca->get( 'compound_service_id' ) ) {
            $service->load( $ca->get( 'compound_service_id' ) );
            $staff_service->loadBy( array( 'staff_id' => $staff->get( 'id' ), 'service_id' => $service->get( 'id' ) ) );
            $price = $service->get( 'price' );
            // The appointment ends when the last service ends in the compound service.
            $bounding = Entities\Appointment::query( 'a' )
                ->select( 'MIN(a.start_date) AS start, MAX(DATE_ADD(a.end_date, INTERVAL a.extras_duration SECOND)) AS end' )
                ->leftJoin( 'CustomerAppointment', 'ca', 'ca.appointment_id = a.id' )
                ->where( 'ca.compound_token', $ca->get( 'compound_token' ) )
                ->groupBy( 'ca.compound_token' )
                ->fetchRow();
            $appointment_start = $bounding['start'];
            $appointment_end   = $bounding['end'];
        } else {
            $service->load( $appointment->get( 'service_id' ) );
            $staff_service->loadBy( array( 'staff_id' => $staff->get( 'id' ), 'service_id' => $service->get( 'id' ) ) );
            $price = $staff_service->get( 'price' );
            $appointment_end   = date_create( $appointment->get( 'end_date' ) )->modify( '+' . $appointment->get( 'extras_duration' ) . ' sec' )->format( 'Y-m-d H:i:s' );
            $appointment_start = $appointment->get( 'start_date' );
        }

        $staff_photo = wp_get_attachment_image_src( $staff->get( 'attachment_id' ), 'full' );

        $codes = new NotificationCodes();
        $codes->set( 'appointment_end',     $appointment_end );
        $codes->set( 'appointment_start',   $appointment_start );
        $codes->set( 'appointment_token',   $ca->get( 'token' ) );
        $codes->set( 'booking_number' ,     $appointment->get( 'id' ) );
        $codes->set( 'category_name',       $service->getCategoryName() );
        $codes->set( 'client_email',        $customer->get( 'email' ) );
        $codes->set( 'client_name',         $customer->get( 'name' ) );
        $codes->set( 'client_phone',        $customer->get( 'phone' ) );
        $codes->set( 'custom_fields',       $ca->getFormattedCustomFields( 'text' ) );
        $codes->set( 'custom_fields_2c',    $ca->getFormattedCustomFields( 'html' ) );
        $codes->set( 'number_of_persons',   $ca->get( 'number_of_persons' ) );
        $codes->set( 'service_info',        $service->getInfo() );
        $codes->set( 'service_name',        $service->getTitle() );
        $codes->set( 'service_price',       $price );
        $codes->set( 'staff_email',         $staff->get( 'email' ) );
        $codes->set( 'staff_info',          $staff->getInfo() );
        $codes->set( 'staff_name',          $staff->getName() );
        $codes->set( 'staff_phone',         $staff->get( 'phone' ) );
        $codes->set( 'staff_photo',         $staff_photo ? $staff_photo[0] : '' );

        $codes = Proxy\Shared::prepareNotificationCodes( $codes, $ca );

        if ( $ca->get( 'payment_id' ) ) {
            $payment = Entities\Payment::find( $ca->get( 'payment_id' ) );
            $codes->set( 'amount_paid',  $payment->get( 'paid' ) );
            $codes->set( 'amount_due',   $payment->get( 'total' ) - $payment->get( 'paid' ) );
            $codes->set( 'payment_type', Entities\Payment::typeToString( $payment->get( 'type' ) ) );
            $codes->set( 'total_price',  $payment->get( 'total' ) );
        } else {
            $codes->set( 'amount_paid', '' );
            $codes->set( 'amount_due',  '' );
            $codes->set( 'total_price', ( $codes->get( 'service_price' ) + $codes->get( 'extras_total_price', 0 ) ) * $codes->get( 'number_of_persons' ) );
        }

        return array( $codes, $appointment, $customer, $staff );
    }

    /**
     * Send email notification to client.
     *
     * @param Entities\Notification $notification
     * @param NotificationCodes $codes
     * @param string $email
     * @param string|null $language_code
     * @param string|null $send_as
     * @return bool
     */
    private static function _sendEmailToClient( Entities\Notification $notification, NotificationCodes $codes, $email, $language_code = null, $send_as = null )
    {
        $subject = $codes->replace( Utils\Common::getTranslatedString(
            'email_' . $notification->get( 'type' ) . '_subject',
            $notification->get( 'subject' ),
            $language_code
        ), 'text' );

        $message = Utils\Common::getTranslatedString(
            'email_' . $notification->get( 'type' ),
            $notification->get( 'message' ),
            $language_code
        );

        $send_as_html = $send_as === null ? Config::sendEmailAsHtml() : $send_as == 'html';
        if ( $send_as_html ) {
            $message = wpautop( $codes->replace( $message, 'html' ) );
        } else {
            $message = $codes->replace( $message, 'text' );
        }

        return wp_mail( $email, $subject, $message, Utils\Common::getEmailHeaders() );
    }

    /**
     * Send email notification to staff.
     *
     * @param Entities\Notification $notification
     * @param NotificationCodes $codes
     * @param string $email
     * @param Entities\Customer|null $customer
     * @param bool $reply_to_customer
     * @param string|null $send_as
     * @return bool
     */
    private static function _sendEmailToStaff( Entities\Notification $notification, NotificationCodes $codes, $email, Entities\Customer $customer = null, $reply_to_customer = null, $send_as = null )
    {
        // Subject.
        $subject = $codes->replace( $notification->get( 'subject' ), 'text' );

        // Message.
        $message = self::_getMessageForStaff( $notification, 'staff', $grace );
        $send_as_html = $send_as === null ? Config::sendEmailAsHtml() : $send_as == 'html';
        if ( $send_as_html ) {
            $message = wpautop( $codes->replace( $message, 'html' ) );
        } else {
            $message = $codes->replace( $message, 'text' );
        }

        // Headers.
        if ( $reply_to_customer === null ) {
            $reply_to_customer = get_option( 'bookly_email_reply_to_customers' );
        }
        $headers = Utils\Common::getEmailHeaders(
            $reply_to_customer
                ? array( 'reply-to' => array( 'email' => $customer->get( 'email' ), 'name' => $customer->get( 'name' ) ) )
                : array()
        );

        // Send email to staff.
        $result = wp_mail( $email, $subject, $message, $headers );

        // Send copy to administrators.
        if ( $notification->get( 'copy' ) ) {
            $admin_emails = Utils\Common::getAdminEmails();

            if ( ! empty ( $admin_emails ) ) {
                if ( $grace ) {
                    $message = self::_getMessageForStaff( $notification, 'admin' );
                    if ( $send_as_html ) {
                        $message = wpautop( $codes->replace( $message, 'html' ) );
                    } else {
                        $message = $codes->replace( $message, 'text' );
                    }
                }

                wp_mail( $admin_emails, $subject, $message, $headers );
            }
        }

        return $result;
    }

    /**
     * Send SMS notification to client.
     *
     * @param Entities\Notification $notification
     * @param NotificationCodes $codes
     * @param string $phone
     * @param string|null $language_code
     * @return bool
     */
    private static function _sendSmsToClient( Entities\Notification $notification, NotificationCodes $codes, $phone, $language_code = null )
    {
        $message = $codes->replace( Utils\Common::getTranslatedString(
            'sms_' . $notification->get( 'type' ),
            $notification->get( 'message' ),
            $language_code
        ), 'text' );

        if ( self::$sms === null ) {
            self::$sms = new SMS();
        }

        return self::$sms->sendSms( $phone, $message, $notification->getTypeId() );
    }

    /**
     * Send SMS notification to staff.
     *
     * @param Entities\Notification $notification
     * @param NotificationCodes $codes
     * @param string $phone
     * @return bool
     */
    private static function _sendSmsToStaff( Entities\Notification $notification, NotificationCodes $codes, $phone )
    {
        // Message.
        $message = $codes->replace( self::_getMessageForStaff( $notification, 'staff', $grace ), 'text' );

        // Send SMS to staff.
        if ( self::$sms === null ) {
            self::$sms = new SMS();
        }

        $result = self::$sms->sendSms( $phone, $message, $notification->getTypeId() );

        // Send copy to administrators.
        if ( $notification->get( 'copy' ) ) {
            if ( $grace ) {
                $message = $codes->replace( self::_getMessageForStaff( $notification, 'admin' ), 'text' );
            }

            self::$sms->sendSms( get_option( 'bookly_sms_administrator_phone', '' ), $message, $notification->getTypeId() );
        }

        return $result;
    }

    /**
     * Get email notification for given recipient and status.
     *
     * @param string $recipient
     * @param string $status
     * @param bool $is_recurring
     * @return Entities\Notification|bool
     */
    private static function _getEmailNotification( $recipient, $status, $is_recurring = false )
    {
        $postfix = $is_recurring ? '_recurring' : '';
        return self::_getNotification( "{$recipient}_{$status}{$postfix}_appointment", 'email' );
    }

    /**
     * Get SMS notification for given recipient and appointment status.
     *
     * @param string $recipient
     * @param string $status
     * @param bool $is_recurring
     * @return Entities\Notification|bool
     */
    private static function _getSmsNotification( $recipient, $status, $is_recurring = false )
    {
        $postfix = $is_recurring ? '_recurring' : '';
        return self::_getNotification( "{$recipient}_{$status}{$postfix}_appointment", 'sms' );
    }

    /**
     * Get combined email notification for given appointment status.
     *
     * @param string $status
     * @return Entities\Notification|bool
     */
    private static function _getCombinedEmailNotification( $status )
    {
        return self::_getNotification( "client_{$status}_appointment_cart", 'email' );
    }

    /**
     * Get combined SMS notification for given appointment status.
     *
     * @param string $status
     * @return Entities\Notification|bool
     */
    private static function _getCombinedSmsNotification( $status )
    {
        return self::_getNotification( "client_{$status}_appointment_cart", 'sms' );
    }

    /**
     * Get notification object.
     *
     * @param string $type
     * @param string $gateway
     * @return Entities\Notification|bool
     */
    private static function _getNotification( $type, $gateway )
    {
        $notification = new Entities\Notification();
        if ( $notification->loadBy( array(
            'type'    => $type,
            'gateway' => $gateway,
            'active'  => 1
        ) ) ) {
            return $notification;
        }

        return false;
    }

    private static function _getMessageForStaff( Entities\Notification $notification, $recipient, &$grace = null )
    {
        $states = Config::getPluginVerificationStates();

        $grace = true;

        if ( $states['bookly'] == 'expired' ) {
            if ( $recipient == 'staff' ) {
                return $notification->get( 'gateway' ) == 'email'
                    ? __( 'A new appointment has been created. To view the details of this appointment, please contact your website administrator in order to verify Bookly license.', 'bookly' )
                    : __( 'You have a new appointment. To view it, contact your admin to verify Bookly license.', 'bookly' );
            } else {
                return $notification->get( 'gateway' ) == 'email'
                    ? __( 'A new appointment has been created. To view the details of this appointment, please verify Bookly license in the administrative panel.', 'bookly' )
                    : __( 'You have a new appointment. To view it, please verify Bookly license.', 'bookly' );
            }
        } elseif ( ! empty ( $states['bookly']['add-ons']['expired'] ) ) {
            if ( $recipient == 'staff' ) {
                return $notification->get( 'message' ) . PHP_EOL . ( $notification->get( 'gateway' ) == 'email'
                    ? __( 'Bookly add-ons have been disabled. To enable the add-ons, please contact your website administrator in order to verify the license.', 'bookly' )
                    : __( 'Bookly add-ons have been disabled. Contact your admin to verify the license.', 'bookly' ) );
            } else {
                return $notification->get( 'message' ) . PHP_EOL . ( $notification->get( 'gateway' ) == 'email'
                    ? __( 'Bookly add-ons have been disabled. To enable the add-ons, please verify the license in the administrative panel.', 'bookly' )
                    : __( 'Bookly add-ons have been disabled. Please verify the license.', 'bookly' ) );
            }
        } elseif ( ! empty ( $states['grace_remaining_days'] ) ) {
            $days_text = sprintf( _n( '%d day', '%d days', $states['grace_remaining_days'], 'bookly' ), $states['grace_remaining_days'] );
            $replace   = array( '{days}' => $days_text );
            if ( $states['bookly'] == 'in_grace' ) {
                if ( $recipient == 'staff' ) {
                    return $notification->get( 'message' ) . PHP_EOL . ( $notification->get( 'gateway' ) == 'email'
                        ? strtr( __( 'Please contact your website administrator to verify Bookly license. If you do not verify the license within {days}, access to your bookings will be disabled.', 'bookly' ), $replace )
                        : strtr( __( 'Contact your admin to verify Bookly license; {days} remaining.', 'bookly' ), $replace ) );
                } else {
                    return $notification->get( 'message' ) . PHP_EOL . ( $notification->get( 'gateway' ) == 'email'
                        ? strtr( __( 'Please verify Bookly license in the administrative panel. If you do not verify the license within {days}, access to your bookings will be disabled.', 'bookly' ), $replace )
                        : strtr( __( 'Please verify Bookly license; {days} remaining.', 'bookly' ), $replace ) );
                }
            } else {
                if ( $recipient == 'staff' ) {
                    return $notification->get( 'message' ) . PHP_EOL . ( $notification->get( 'gateway' ) == 'email'
                        ? strtr( __( 'Please contact your website administrator in order to verify the license for Bookly add-ons. If you do not verify the license within {days}, the respective add-ons will be disabled.', 'bookly' ), $replace )
                        : strtr( __( 'Contact your admin to verify Bookly add-ons license; {days} remaining.', 'bookly' ), $replace ) );
                } else {
                    return $notification->get( 'message' ) . PHP_EOL . ( $notification->get( 'gateway' ) == 'email'
                        ? strtr( __( 'Please verify the license for Bookly add-ons in the administrative panel. If you do not verify the license within {days}, the respective add-ons will be disabled.', 'bookly' ), $replace )
                        : strtr( __( 'Please verify Bookly add-ons license; {days} remaining.', 'bookly' ), $replace ) );
                }
            }
        }

        $grace = false;

        return $notification->get( 'message' );
    }

}