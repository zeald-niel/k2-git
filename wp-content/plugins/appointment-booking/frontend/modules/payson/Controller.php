<?php
namespace Bookly\Frontend\Modules\Payson;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Payson
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
            Lib\Payment\Payson::paymentPage( $form_id, $userData, $this->getParameter( 'response_url' ) );
        }
    }

    /**
     * Redirect with token from Payment Form to Bookly page
     */
    public function response()
    {
        $details_response = Lib\Payment\Payson::response();
        if ( $details_response instanceof \PaymentDetailsResponse ) {
            // We have some errors from Payson
            $errors   = $details_response->getResponseEnvelope()->getErrors();
            $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
            $userData->load();
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYSON, 'error', $errors[0]->getMessage() );
            @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
    }

    public function cancel()
    {
        Lib\Payment\Payson::cancel();
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYSON, 'cancelled' );
        @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    public function error()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYSON, 'error', $this->getParameter( 'error_msg' ) );
        @wp_redirect( remove_query_arg( Lib\Payment\Payson::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

}