<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class Payson
 * @package Bookly\Lib\Payment
 */
class Payson
{
    // Array for cleaning Payson request
    public static $remove_parameters = array( 'bookly_action', 'bookly_fid', 'error_msg', 'TOKEN' );

    public static function renderForm( $form_id, $page_url )
    {
        $userData = new Lib\UserBookingData( $form_id );
        if ( $userData->load() ) {
            $replacement = array(
                '%form_id%' => $form_id,
                '%response_url%' => esc_attr( $page_url ),
                '%gateway%' => Lib\Entities\Payment::TYPE_PAYSON,
                '%back%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ),
                '%next%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ),
            );
            $form = '<form method="post" class="bookly-%gateway%-form">
                <input type="hidden" name="bookly_fid" value="%form_id%"/>
                <input type="hidden" name="bookly_action" value="payson-checkout"/>
                <input type="hidden" name="response_url" value="%response_url%"/>
                <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40"><span class="ladda-label">%back%</span></button>
                <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">%next%</span></button>
             </form>';
            echo strtr( $form, $replacement );
        }
    }

    /**
     * Check gateway data and if ok save payment info
     *
     * @param \PaymentDetails          $details
     * @param bool|false               $ipn      When ipn false, this is request from browser and we use _redirectTo for notification customer
     * @param null|Lib\UserBookingData $userData
     */
    public static function handlePayment( \PaymentDetails $details, $ipn = false, $userData = null )
    {
        $payment_accepted = ( $details->getType() == 'TRANSFER' && $details->getStatus() == 'COMPLETED' );  // CARD
        if ( $payment_accepted == false &&          // INVOICE
             ( $details->getType() == 'INVOICE' && $details->getStatus() == 'PENDING'
               && in_array( $details->getInvoiceStatus(), array ( 'ORDERCREATED', 'DONE' ) )
             )
        ) {
            $payment_accepted = true;
        }

        $payment_id = $details->getCustom();
        $payment = Lib\Entities\Payment::query()->where( 'type', Lib\Entities\Payment::TYPE_PAYSON )
            ->where( 'id', $payment_id )
            ->findOne();
        if ( $payment_accepted ) {
            // Handle completed card & bank transfers here
            /** @var \OrderItem $product */
            $product  = current( $details->getOrderItems() );
            $paid     = (float) $payment->get( 'paid' );
            $received = (float) $product->getUnitPrice();

            if ( $received != $paid ) {
                // The big difference in the expected and received payment.
                if ( ! $ipn ) {
                    self::_redirectTo( $userData, 'error', __( 'Incorrect payment data', 'bookly' ) );
                }
                return;
            }

            if ( get_option( 'bookly_pmt_currency' ) == $details->getCurrencyCode() ) {
                if ( $payment->get( 'status' ) != Lib\Entities\Payment::STATUS_COMPLETED ) {
                    $payment->set( 'status', Lib\Entities\Payment::STATUS_COMPLETED )->save();
                    $ca_list = Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $payment->get( 'id' ) )->find();
                    Lib\NotificationSender::sendFromCart( $ca_list );
                }
                if ( ! $ipn ) {
                    self::_redirectTo( $userData, 'success' );
                } else {
                    wp_send_json_success();
                }
                exit;
            }

        } else {
            $payment->delete();
            /** @var Lib\Entities\CustomerAppointment $ca */
            foreach ( Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $payment_id )->find() as $ca ) {
                $ca->deleteCascade();
            }
            if ( ! $ipn && $details->getStatus() == 'ERROR' ) {
                self::_redirectTo( $userData, 'error', __( 'Error', 'bookly' ) );

            } elseif ( ! $ipn ) {
                if ( in_array( $details->getStatus(), array( 'CREATED', 'PENDING', 'PROCESSING', 'CREDITED' ) ) ) {
                    self::_redirectTo( $userData, 'processing' );
                } else {
                    header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                            'bookly_action' => 'payson-error',
                            'bookly_fid' => stripslashes( @$_REQUEST['bookly_fid'] ),
                            'error_msg'  => urlencode( __( 'Payment status', 'bookly' ) . ' ' . $details->getStatus() ),
                        ), Lib\Utils\Common::getCurrentPageURL()
                        ) ) );
                    exit;
                }
            }
        }
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
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYSON, $status, $message );
        @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    /**
     * Redirect to Payson Payment page, or step payment.
     *
     * @param $form_id
     * @param Lib\UserBookingData $userData
     * @param string $page_url
     */
    public static function paymentPage( $form_id, Lib\UserBookingData $userData, $page_url )
    {
        $api = self::_getApi();
        $url = array(
            'return' => add_query_arg( array( 'bookly_action' => 'payson-response', 'bookly_fid' => $form_id ), $page_url ),
            'cancel' => add_query_arg( array( 'bookly_action' => 'payson-cancel', 'bookly_fid' => $form_id ), $page_url ),
            'ipn'    => add_query_arg( array( 'bookly_action' => 'payson-ipn' ), $page_url )
        );
        $payson_email = get_option( 'bookly_pmt_payson_api_receiver_email' );
        list( $total, $deposit ) = $userData->cart->getInfo();
        $receiver  = new \Receiver( $payson_email, $deposit );
        $receivers = array( $receiver );
        $client    = array(
            'email'      => $userData->get( 'email' ),
            'first_name' => $userData->get( 'name' ),
            'last_name'  => ''
        );
        $sender   = new \Sender( $client['email'], $client['first_name'], $client['last_name'] );

        $pay_data = new \PayData( $url['return'], $url['cancel'], $url['ipn'], $userData->cart->getItemsTitle( 128 ), $sender, $receivers );
        $products = array ( new \OrderItem( $userData->cart->getItemsTitle( 128 ), $deposit, 1, 0, Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_service' ) ) );
        $pay_data->setOrderItems( $products );

        // $constraints = array( FundingConstraint::BANK, FundingConstraint::CREDITCARD, FundingConstraint::INVOICE, FundingConstraint::SMS ); // all available
        $funding     = (array) get_option( 'bookly_pmt_payson_funding' );
        $reflector   = new \ReflectionClass( 'FundingConstraint' );
        $enum        = $reflector->getConstants();
        $constraints = array();
        foreach ( $funding as $type ) {
            if ( array_key_exists( $type, $enum ) ) {
                $constraints[] = $enum[ $type ];
            }
        }
        if ( empty( $constraints ) ) {
            $constraints = array( \FundingConstraint::CREDITCARD );
        }
        $pay_data->setFundingConstraints( $constraints );
        $pay_data->setFeesPayer( get_option( 'bookly_pmt_payson_fees_payer' ) );
        $pay_data->setGuaranteeOffered( 'NO' );  // Disable PaysonGuarantee™
        $pay_data->setCurrencyCode( get_option( 'bookly_pmt_currency' ) );

        $coupon  = $userData->getCoupon();
        $payment = new Lib\Entities\Payment();
        $payment->set( 'type', Lib\Entities\Payment::TYPE_PAYSON )
            ->set( 'status',   Lib\Entities\Payment::STATUS_PENDING )
            ->set( 'created',  current_time( 'mysql' ) )
            ->set( 'total',    $total )
            ->set( 'paid',     $deposit )
            ->save();
        $ca_list = $userData->save( $payment->get( 'id' ) );
        $pay_data->setCustom( $payment->get( 'id' ) );
        try {
            $pay_response = $api->pay( $pay_data );
            if ( $pay_response->getResponseEnvelope()->wasSuccessful() ) {
                if ( $coupon ) {
                    $coupon->claim();
                    $coupon->save();
                }
                $payment->setDetails( $ca_list, $coupon )->save();
                header( 'Location: ' . $api->getForwardPayUrl( $pay_response ) );
                exit;
            } else {
                $payment->delete();
                self::_deleteAppointments( $ca_list );
                /** @var \PaysonApiError[] $errors */
                $errors = $pay_response->getResponseEnvelope()->getErrors();
                self::_redirectTo( $userData, 'error', $errors[0]->getMessage() );
            }
        } catch ( \Exception $e ) {
            $payment->delete();
            self::_deleteAppointments( $ca_list );
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYSON, 'error', $e->getMessage() );
            @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
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

    /**
     * Handles IPN messages
     */
    public static function ipn()
    {
        $api      = self::_getApi();
        $received = file_get_contents( 'php://input' );
        $response = $api->validate( $received );

        if ( $response->isVerified() ) {
            Lib\Payment\Payson::handlePayment( $response->getPaymentDetails(), true, null );
        }
        wp_send_json_success();
    }

    /**
     * Response when payment form completed
     *
     * @return \PaymentDetailsResponse or redirect
     */
    public static function response()
    {
        $api   = self::_getApi();
        $token = stripslashes( @$_GET['TOKEN'] );
        $details_response = $api->paymentDetails( new \PaymentDetailsData( $token ) );

        if ( $details_response->getResponseEnvelope()->wasSuccessful() ) {
            Lib\Payment\Payson::handlePayment( $details_response->getPaymentDetails(), false, new Lib\UserBookingData( stripslashes( @$_GET['bookly_fid'] ) ) );
        } else {
            return $details_response;
        }
    }

    /**
     * Handle cancel request
     */
    public static function cancel()
    {
        $api   = self::_getApi();
        $token = stripslashes( @$_GET['TOKEN'] );
        $payment_details = $api->paymentDetails( new \PaymentDetailsData( $token ) )->getPaymentDetails();
        if ( $payment_details->getStatus() == 'ABORTED' ) {
            $payment_id = $payment_details->getCustom();
            /** @var Lib\Entities\CustomerAppointment[] $customer_appointments */
            $customer_appointments = Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $payment_id )->find();
            self::_deleteAppointments( $customer_appointments );
            // Delete Payment record
            Lib\Entities\Payment::query()->delete()->where( 'type', Lib\Entities\Payment::TYPE_PAYSON )
                ->where( 'id', $payment_id )->execute();
        }
    }

    /**
     * Init Api
     *
     * @return \PaysonApi
     */
    private static function _getApi()
    {
        include_once Lib\Plugin::getDirectory() . '/lib/payment/payson/paysonapi.php';
        $agent       = get_option( 'bookly_pmt_payson_api_agent_id' );
        $api_key     = get_option( 'bookly_pmt_payson_api_key' );
        $credentials = new \PaysonCredentials( $agent, $api_key );

        return new \PaysonApi( $credentials, ( get_option( 'bookly_pmt_payson_sandbox' ) == 1 ) );
    }

}