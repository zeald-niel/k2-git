<?php
namespace Bookly\Frontend\Modules\PayuLatam;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\PayuLatam
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function checkout()
    {
        $transaction_state = $this->getParameter( 'transactionState' );
        if ( false === Lib\Payment\PayuLatam::processPayment( $transaction_state, $this->getParameter( 'referenceCode' ), $this->getParameter( 'signature' ) ) ) {
            switch ( $transaction_state ) {
                case 6:
                    $message = __( 'Transaction rejected', 'bookly' );
                    break;
                case 104:
                    $message = __( 'Error', 'bookly' );
                    break;
                case 7:
                    $message = __( 'Pending payment', 'bookly' );
                    break;
                default:
                    $message = $this->getParameter( 'message' ) . ' ' . __( 'Invalid token provided', 'bookly' );
                    break;
            }
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                    'bookly_action' => 'payu_latam-error',
                    'bookly_fid' => $this->getParameter( 'bookly_fid' ),
                    'error_msg'  => urlencode( $message ),
                ), Lib\Utils\Common::getCurrentPageURL()
                ) ) );
            exit;
        } else {
            // Clean GET parameters from PayU Latam.
            $userData = new Lib\UserBookingData( stripslashes( $this->getParameter( 'bookly_fid' ) ) );
            $userData->load();
            $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYULATAM, 'success' );
            @wp_redirect( remove_query_arg( Lib\Payment\PayuLatam::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
    }

    public function error()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_PAYULATAM, 'error', $this->getParameter( 'error_msg' ) );
        @wp_redirect( remove_query_arg( Lib\Payment\PayuLatam::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

    /**
     * New CSRF tokens
     */
    public function executePayuLatamRefreshTokens()
    {
        $replacement = get_option( 'bookly_pmt_payu_latam' ) ? Lib\Payment\PayuLatam::replaceData( $this->getParameter( 'form_id' ) ) : false;

        empty ( $replacement ) ? wp_send_json_error() : wp_send_json_success( array ( 'signature' => $replacement['%signature%'], 'referenceCode' => $replacement['%referenceCode%'] ) );
    }

    /**
     * Override parent method to register 'wp_ajax_nopriv_' actions too.
     *
     * @param bool $with_nopriv
     */
    protected function registerWpAjaxActions( $with_nopriv = false )
    {
        parent::registerWpAjaxActions( true );
    }
}