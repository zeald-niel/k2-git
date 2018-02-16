<?php
namespace Bookly\Frontend\Modules\Stripe;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Stripe
 */
class Controller extends Lib\Base\Controller
{
    /** @var array Zero-decimal currencies */
    private $zero_decimal = array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF', );

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous' );
    }

    public function executeStripe()
    {
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $failed_cart_key = $userData->cart->getFailedKey();
            if ( $failed_cart_key === null ) {
                include_once Lib\Plugin::getDirectory() . '/lib/payment/Stripe/init.php';
                \Stripe\Stripe::setApiKey( get_option( 'bookly_pmt_stripe_secret_key' ) );
                \Stripe\Stripe::setApiVersion( '2015-08-19' );

                list( $total, $deposit ) = $userData->cart->getInfo();
                try {
                    if ( in_array( get_option( 'bookly_pmt_currency' ), $this->zero_decimal ) ) {
                        // Zero-decimal currency
                        $stripe_amount = $deposit;
                    } else {
                        $stripe_amount = $deposit * 100; // amount in cents
                    }
                    $charge = \Stripe\Charge::create( array(
                        'amount'      => (int) $stripe_amount,
                        'currency'    => get_option( 'bookly_pmt_currency' ),
                        'source'      => $this->getParameter( 'card' ), // contain token or card data
                        'description' => 'Charge for ' . $userData->get( 'email' )
                    ) );

                    if ( $charge->paid ) {
                        $coupon = $userData->getCoupon();
                        if ( $coupon ) {
                            $coupon->claim();
                            $coupon->save();
                        }
                        $payment = new Lib\Entities\Payment();
                        $payment->set( 'type', Lib\Entities\Payment::TYPE_STRIPE )
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
                        $response = array( 'success' => false, 'error_code' => 7, 'error' => __( 'Error', 'bookly' ) );
                    }
                } catch ( \Exception $e ) {
                    $response = array( 'success' => false, 'error_code' => 7, 'error' => $e->getMessage() );
                }
            } else {
                $response = array(
                    'success'         => false,
                    'error_code'      => 3,
                    'failed_cart_key' => $failed_cart_key,
                    'error'           => Lib\Config::showStepCart()
                        ? __( 'The highlighted time is not available anymore. Please, choose another time slot.', 'bookly' )
                        : __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' )
                );
            }
        } else {
            $response = array( 'success' => false, 'error_code' => 1, 'error' => __( 'Session error.', 'bookly' ) );
        }

        // Output JSON response.
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