<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class Mollie
 */
class Mollie
{
    // Array for cleaning Mollie request
    public static $remove_parameters = array( 'bookly_action', 'bookly_fid', 'error_msg' );

    public static function renderForm( $form_id, $page_url )
    {
        $userData = new Lib\UserBookingData( $form_id );
        if ( $userData->load() ) {
            $replacement = array(
                '%form_id%' => $form_id,
                '%gateway%' => Lib\Entities\Payment::TYPE_MOLLIE,
                '%response_url%' => esc_attr( $page_url ),
                '%back%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ),
                '%next%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ),
            );
            $form = '<form method="post" class="bookly-%gateway%-form">
                <input type="hidden" name="bookly_fid" value="%form_id%"/>
                <input type="hidden" name="bookly_action" value="mollie-checkout"/>
                <input type="hidden" name="response_url" value="%response_url%"/>
                <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40"><span class="ladda-label">%back%</span></button>
                <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">%next%</span></button>
             </form>';
            echo strtr( $form, $replacement );
        }
    }

    /**
     * Handles IPN messages
     */
    public static function ipn()
    {
        $payment_details = self::_getApi()->payments->get( $_REQUEST['id'] );
        Mollie::handlePayment( $payment_details );
    }

    /**
     * Check gateway data and if ok save payment info
     *
     * @param \Mollie_API_Object_Payment $details
     */
    public static function handlePayment( \Mollie_API_Object_Payment $details )
    {
        $payment = Lib\Entities\Payment::query()->where( 'type', Lib\Entities\Payment::TYPE_MOLLIE )
            ->where( 'id', $details->metadata->payment_id )->findOne();
        if ( $details->isPaid() ) {
            // Handle completed card & bank transfers here
            $total    = (float) $payment->get( 'paid' );
            $received = (float) $details->amount;

            if ( $payment->get( 'status' ) == Lib\Entities\Payment::STATUS_COMPLETED
                 || $received != $total
            ) {
                wp_send_json_success();
            } else {
                $payment->set( 'status', Lib\Entities\Payment::STATUS_COMPLETED )->save();
                $ca_list = Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $details->metadata->payment_id )->find();
                Lib\NotificationSender::sendFromCart( $ca_list );
            }
        } elseif ( ! $details->isOpen() ) {
            $payment->delete();
            /** @var Lib\Entities\CustomerAppointment $ca */
            foreach ( Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $details->metadata->payment_id )->find() as $ca ) {
                $ca->deleteCascade();
            }
        }
        wp_send_json_success();
    }

    /**
     * Redirect to Mollie Payment page, or step payment.
     *
     * @param $form_id
     * @param Lib\UserBookingData $userData
     * @param string $page_url
     */
    public static function paymentPage( $form_id, Lib\UserBookingData $userData, $page_url )
    {
        if ( get_option( 'bookly_pmt_currency' ) != 'EUR' ) {
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'error', __( 'Mollie accepts payments in Euro only.', 'bookly' ) );
            @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
        list( $total, $deposit ) = $userData->cart->getInfo();
        $coupon  = $userData->getCoupon();
        $payment = new Lib\Entities\Payment();
        $payment->set( 'type', Lib\Entities\Payment::TYPE_MOLLIE )
            ->set( 'status',   Lib\Entities\Payment::STATUS_PENDING )
            ->set( 'created',  current_time( 'mysql' ) )
            ->set( 'total',    $total )
            ->set( 'paid',     $deposit )
            ->save();
        $ca_list = $userData->save( $payment->get( 'id' ) );
        try {
            $api = self::_getApi();
            $mollie_payment = $api->payments->create( array(
                'amount'      => $deposit,
                'description' => $userData->cart->getItemsTitle( 125 ),
                'redirectUrl' => add_query_arg( array( 'bookly_action' => 'mollie-response', 'bookly_fid' => $form_id ), $page_url ),
                'webhookUrl'  => add_query_arg( array( 'bookly_action' => 'mollie-ipn' ), $page_url ),
                'metadata'    => array( 'payment_id' => $payment->get( 'id' ) ),
                'issuer'      => null
            ) );
            if ( $mollie_payment->isOpen() ) {
                if ( $coupon ) {
                    $coupon->claim();
                    $coupon->save();
                }
                $payment->setDetails( $ca_list, $coupon )->save();
                $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'pending', $mollie_payment->id );
                header( 'Location: ' . $mollie_payment->getPaymentUrl() );
                exit;
            } else {
                $payment->delete();
                self::_deleteAppointments( $ca_list );
                self::_redirectTo( $userData, 'error', __( 'Mollie error.', 'bookly' ) );
            }
        } catch ( \Exception $e ) {
            $payment->delete();
            self::_deleteAppointments( $ca_list );
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'error', $e->getMessage() );
            @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
    }

    /**
     * @param Lib\Entities\CustomerAppointment[] $customer_appointments
     */
    private static function _deleteAppointments( $customer_appointments )
    {
        foreach ( $customer_appointments as $customer_appointment ) {
            $customer_appointment->deleteCascade();
        }
    }

    private static function _getApi()
    {
        include_once Lib\Plugin::getDirectory() . '/lib/payment/Mollie/API/Autoloader.php';
        $mollie = new \Mollie_API_Client();
        $mollie->setApiKey( get_option( 'bookly_pmt_mollie_api_key' ) );

        return $mollie;
    }

    /**
     * Notification for customer
     *
     * @param Lib\UserBookingData $userData
     * @param string $status    success || error || processing
     * @param string $message
     */
    private static function _redirectTo( Lib\UserBookingData $userData, $status = 'success', $message = '' )
    {
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, $status, $message );
        @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Get Mollie Payment
     *
     * @param string $tr_id
     * @return \Mollie_API_Object_Payment
     */
    public static function getPayment( $tr_id )
    {
        $api = self::_getApi();

        return $api->payments->get( $tr_id );
    }

}