<?php
namespace Bookly\Frontend\Modules\TwoCheckout;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\TwoCheckout
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function approved()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        if ( ( $redirect_url = $this->getParameter( 'x_receipt_link_url', false ) ) === false ) {
            // Clean GET parameters from 2Checkout.
            $redirect_url = remove_query_arg( Lib\Payment\TwoCheckout::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() );
        }
        if ( $userData->load() ) {
            list( $total, $deposit ) = $userData->cart->getInfo();
            $amount = number_format( $deposit, 2, '.', '' );
            $compare_key = strtoupper( md5( get_option( 'bookly_pmt_2checkout_api_secret_word' ) . get_option( 'bookly_pmt_2checkout_api_seller_id' ) . $this->getParameter( 'order_number' ) . $amount ) );
            if ( $compare_key != $this->getParameter( 'key' ) ) {
                header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                        'bookly_action' => '2checkout-error',
                        'bookly_fid' => $this->getParameter( 'bookly_fid' ),
                        'error_msg'  => urlencode( __( 'Invalid token provided', 'bookly' ) ),
                    ), Lib\Utils\Common::getCurrentPageURL()
                ) ) );
                exit;
            } else {
                $coupon = $userData->getCoupon();
                if ( $coupon ) {
                    $coupon->claim();
                    $coupon->save();
                }
                $payment = new Lib\Entities\Payment();
                $payment->set( 'type', Lib\Entities\Payment::TYPE_2CHECKOUT )
                    ->set( 'status',   Lib\Entities\Payment::STATUS_COMPLETED )
                    ->set( 'total',    $total )
                    ->set( 'paid',     $deposit )
                    ->set( 'created',  current_time( 'mysql' ) )
                    ->save();
                $ca_list = $userData->save( $payment->get( 'id' ) );
                Lib\NotificationSender::sendFromCart( $ca_list );
                $payment->setDetails( $ca_list, $coupon )->save();

                $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_2CHECKOUT, 'success' );

                @wp_redirect( $redirect_url );
                exit;
            }
        } else {
            header( 'Location: ' . wp_sanitize_redirect( add_query_arg( array(
                    'bookly_action' => '2checkout-error',
                    'bookly_fid' => $this->getParameter( 'bookly_fid' ),
                    'error_msg'  => urlencode( __( 'Invalid session', 'bookly' ) ),
                ), $redirect_url
            ) ) );
            exit;
        }
    }

    public function error()
    {
        $userData = new Lib\UserBookingData( $this->getParameter( 'bookly_fid' ) );
        $userData->load();
        $userData->setPaymentStatus( Lib\Entities\Payment::TYPE_2CHECKOUT, 'error', $this->getParameter( 'error_msg' ) );
        @wp_redirect( remove_query_arg( Lib\Payment\TwoCheckout::$remove_parameters, Lib\Utils\Common::getCurrentPageURL() ) );
        exit;
    }

}