<?php
namespace Bookly\Frontend\Modules\AuthorizeNet;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\AuthorizeNet
 */
class Controller extends Lib\Base\Controller
{

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    /**
     * Do AIM payment.
     */
    public function executeAuthorizeNetAIM()
    {
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $failed_cart_key = $userData->cart->getFailedKey();
            if ( $failed_cart_key === null ) {
                list( $total, $deposit ) = $userData->cart->getInfo();
                $card  = $this->getParameter( 'card' );
                $full_name  = $userData->get( 'name' );
                $first_name = strtok( $full_name, ' ' );
                $last_name  = strtok( '' );
                // Authorize.Net AIM Payment.
                $authorize = new Lib\Payment\AuthorizeNet( get_option( 'bookly_pmt_authorize_net_api_login_id' ), get_option( 'bookly_pmt_authorize_net_transaction_key' ), (bool) get_option( 'bookly_pmt_authorize_net_sandbox' ) );
                $authorize->setField( 'amount',     $deposit );
                $authorize->setField( 'card_num',   $card['number'] );
                $authorize->setField( 'card_code',  $card['cvc'] );
                $authorize->setField( 'exp_date',   $card['exp_month'] . '/' . $card['exp_year'] );
                $authorize->setField( 'email',      $userData->get( 'email' ) );
                $authorize->setField( 'phone',      $userData->get( 'phone' ) );
                $authorize->setField( 'first_name', $first_name );
                if ( $last_name ) {
                    $authorize->setField( 'last_name', $last_name );
                }

                $aim_response = $authorize->authorizeAndCapture();
                if ( $aim_response->approved ) {
                    $coupon = $userData->getCoupon();
                    if ( $coupon ) {
                        $coupon->claim();
                        $coupon->save();
                    }
                    $payment = new Lib\Entities\Payment();
                    $payment->set( 'type', Lib\Entities\Payment::TYPE_AUTHORIZENET )
                        ->set( 'status',   Lib\Entities\Payment::STATUS_COMPLETED )
                        ->set( 'total',    $total )
                        ->set( 'paid',     $deposit )
                        ->set( 'created',  current_time( 'mysql' ) )
                        ->save();
                    $ca_list = $userData->save( $payment->get( 'id' ) );
                    Lib\NotificationSender::sendFromCart( $ca_list );
                    $payment->setDetails( $ca_list, $coupon )->save();
                    $response = array( 'success' => true );
                } else {
                    $response = array( 'success' => false, 'error_code' => 7, 'error' => $aim_response->response_reason_text );
                }
            } else {
                $response = array(
                    'success'         => false,
                    'error_code'      => 3,
                    'failed_cart_key' => $failed_cart_key,
                    'error'           => get_option( 'bookly_cart_enabled' )
                        ? __( 'The highlighted time is not available anymore. Please, choose another time slot.', 'bookly' )
                        : __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' )
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        wp_send_json( $response );
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
