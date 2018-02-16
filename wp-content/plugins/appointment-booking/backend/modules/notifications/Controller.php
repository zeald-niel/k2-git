<?php
namespace Bookly\Backend\Modules\Notifications;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Notifications
 */
class Controller extends Lib\Base\Controller
{
    const page_slug = 'bookly-notifications';

    public function index()
    {
        $this->enqueueStyles( array(
            'frontend' => array( 'css/ladda.min.css' ),
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
        ) );

        $this->enqueueScripts( array(
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/angular.min.js',
                'js/help.js'  => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
            ),
            'module'   => array(
                'js/notification.js' => array( 'jquery' ),
                'js/ng-app.js' => array( 'jquery', 'bookly-angular.min.js' ),
            ),
            'frontend' => array(
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            )
        ) );
        $cron_reminder = (array) get_option( 'bookly_cron_reminder_times' );
        $form  = new Forms\Notifications( 'email' );
        $alert = array( 'success' => array() );
        // Save action.
        if ( ! empty ( $_POST ) ) {
            $form->bind( $this->getPostParameters() );
            $form->save();
            $alert['success'][] = __( 'Settings saved.', 'bookly' );
            update_option( 'bookly_email_send_as',            $this->getParameter( 'bookly_email_send_as' ) );
            update_option( 'bookly_email_reply_to_customers', $this->getParameter( 'bookly_email_reply_to_customers' ) );
            update_option( 'bookly_email_sender',             $this->getParameter( 'bookly_email_sender' ) );
            update_option( 'bookly_email_sender_name',        $this->getParameter( 'bookly_email_sender_name' ) );
            foreach ( array( 'staff_agenda', 'client_follow_up', 'client_reminder', 'client_birthday_greeting' ) as $type ) {
                $cron_reminder[ $type ] = $this->getParameter( $type . '_cron_hour' );
            }
            update_option( 'bookly_cron_reminder_times', $cron_reminder );
        }
        $cron_uri = plugins_url( 'lib/utils/send_notifications_cron.php', Lib\Plugin::getMainFile() );
        wp_localize_script( 'bookly-alert.js', 'BooklyL10n',  array(
            'alert' => $alert,
            'sent_successfully' => __( 'Sent successfully.', 'bookly' )
        ) );
        $this->render( 'index', compact( 'form', 'cron_uri', 'cron_reminder' ) );
    }

    public function executeGetEmailNotificationsData()
    {
        $form = new Forms\Notifications( 'email' );

        $bookly_email_sender_name  = get_option( 'bookly_email_sender_name' ) == '' ?
            get_option( 'blogname' )    : get_option( 'bookly_email_sender_name' );

        $bookly_email_sender = get_option( 'bookly_email_sender' ) == '' ?
            get_option( 'admin_email' ) : get_option( 'bookly_email_sender' );

        $notifications = array();
        foreach ( $form->getData() as $notification ) {
            $notifications[] = array(
                'type'   => $notification['type'],
                'name'   => $notification['name'],
                'active' => $notification['active'],
            );
        }

        $result = array(
            'notifications' => $notifications,
            'sender_email'  => $bookly_email_sender,
            'sender_name'   => $bookly_email_sender_name,
            'send_as'       => get_option( 'bookly_email_send_as' ),
            'reply_to_customers' => get_option( 'bookly_email_reply_to_customers' ),
        );

        wp_send_json_success( $result );
    }

    public function executeTestEmailNotifications()
    {
        $to_email      = $this->getParameter( 'to_email' );
        $sender_name   = $this->getParameter( 'sender_name' );
        $sender_email  = $this->getParameter( 'sender_email' );
        $send_as       = $this->getParameter( 'send_as' );
        $notifications = $this->getParameter( 'notifications' );
        $reply_to_customers = $this->getParameter( 'reply_to_customers' );

        // Change 'Content-Type' and 'Reply-To' for test email notification.
        add_filter( 'bookly_email_headers', function ( $headers ) use ( $sender_name, $sender_email, $send_as, $reply_to_customers ) {
            $headers = array();
            if ( $send_as == 'html' ) {
                $headers[] = 'Content-Type: text/html; charset=utf-8';
            } else {
                $headers[] = 'Content-Type: text/plain; charset=utf-8';
            }
            $headers[] = 'From: ' . $sender_name . ' <' . $sender_email . '>';
            if ( $reply_to_customers ) {
                $headers[] = 'Reply-To: ' . $sender_name . ' <' . $sender_email . '>';
            }

            return $headers;
        }, 10, 1 );

        Lib\NotificationSender::sendTestEmailNotifications( $to_email, $notifications, $send_as );

        wp_send_json_success();
    }
}