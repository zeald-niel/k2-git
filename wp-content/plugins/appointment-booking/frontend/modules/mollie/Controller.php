<?php
namespace Bookly\Frontend\Modules\Mollie;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Mollie
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function checkout()
    {
        $form_id  = $this->getParameter( 'bookly_fid' );
        $userData = new Lib\UserBookingData( $form_id );
        if ( $userData->load() ) {
            Lib\Payment\Mollie::paymentPage( $form_id, $userData, $this->getParameter( 'response_url' ) );
        }
    }

    /**
     * Redirect from Payment Form to Bookly page
     */
    public function response()
    {
        $form_id  = $this->getParameter( 'bookly_fid' );
        $userData = new Lib\UserBookingData( $form_id );
        $userData->load();
        if ( $payment = Lib\Session::getFormVar( $form_id, 'payment' ) ) {
            if ( $payment['status'] == 'pending' ) {
                $mollie_payment = Lib\Payment\Mollie::getPayment( $payment['data'] );
                if ( $mollie_payment->isOpen() || $mollie_payment->isPaid() ) {
                    // Payment processing
                    $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'processing' );
                    @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                } else {
                    // Customer cancel payment
                    /** @var Lib\Entities\CustomerAppointment $ca */
                    foreach ( Lib\Entities\CustomerAppointment::query()->where( 'payment_id', $mollie_payment->metadata->payment_id )->find() as $ca ) {
                        $ca->deleteCascade();
                    }
                    Lib\Entities\Payment::query()->delete()->where( 'type', Lib\Entities\Payment::TYPE_MOLLIE )
                        ->where( 'id', $mollie_payment->metadata->payment_id )
                        ->execute();
                    $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_MOLLIE, 'cancelled' );

                    @wp_redirect( remove_query_arg( Lib\Payment\Mollie::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
                }
            }
        }
        exit;
    }

}