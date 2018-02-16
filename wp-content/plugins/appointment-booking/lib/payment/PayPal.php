<?php
namespace Bookly\Lib\Payment;

use Bookly\Lib;

/**
 * Class PayPal
 * @package Bookly\Lib\Payment
 */
class PayPal
{
    const TYPE_EXPRESS_CHECKOUT = 'ec';
    const TYPE_PAYMENTS_STANDARD = 'ps';

    const URL_POSTBACK_IPN_LIVE = 'https://www.paypal.com/cgi-bin/webscr';
    const URL_POSTBACK_IPN_SANDBOX = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    // Array for cleaning PayPal request
    static public $remove_parameters = array( 'bookly_action', 'bookly_fid', 'error_msg', 'token', 'PayerID',  'type' );

    /**
     * The array of products for checkout
     *
     * @var array
     */
    protected $products = array();

    /**
     * Send the Express Checkout NVP request
     *
     * @param $form_id
     * @throws \Exception
     */
    public function sendECRequest( $form_id )
    {
        $current_url = Lib\Utils\Common::getCurrentPageURL();

        // create the data to send on PayPal
        $data = array(
            'SOLUTIONTYPE' => 'Sole',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'  => get_option( 'bookly_pmt_currency' ),
            'NOSHIPPING' => 1,
            'RETURNURL'  => add_query_arg( array( 'bookly_action' => 'paypal-ec-return', 'bookly_fid' => $form_id ), $current_url ),
            'CANCELURL'  => add_query_arg( array( 'bookly_action' => 'paypal-ec-cancel', 'bookly_fid' => $form_id ), $current_url )
        );
        $total = 0;
        foreach ( $this->products as $index => $product ) {
            $data[ 'L_PAYMENTREQUEST_0_NAME' . $index ] = $product->name;
            $data[ 'L_PAYMENTREQUEST_0_AMT' . $index ]  = $product->price;
            $data[ 'L_PAYMENTREQUEST_0_QTY' . $index ]  = $product->qty;

            $total += ( $product->qty * $product->price );
        }
        $data['PAYMENTREQUEST_0_AMT']     = $total;
        $data['PAYMENTREQUEST_0_ITEMAMT'] = $total;

        // send the request to PayPal
        $response = $this->sendNvpRequest( 'SetExpressCheckout', $data );

        // Respond according to message we receive from PayPal
        $ack = strtoupper( $response['ACK'] );
        if ( $ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING' ) {
            // Redirect to PayPal.
            $paypal_url = sprintf(
                'https://www%s.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=%s',
                get_option( 'bookly_pmt_paypal_sandbox' ) ? '.sandbox' : '',
                urlencode( $response['TOKEN'] )
            );
            header( 'Location: ' . $paypal_url );
        } else {
            header( 'Location: ' . wp_sanitize_redirect(
                add_query_arg( array(
                    'bookly_action' => 'paypal-ec-error',
                    'bookly_fid' => $form_id,
                    'error_msg'  => urlencode( $response['L_LONGMESSAGE0'] )
                ), $current_url )
            ) );
        }

        exit;
    }

    /**
     * Send the NVP Request to the PayPal
     *
     * @param       $method
     * @param array $data
     * @return array
     */
    public function sendNvpRequest( $method, array $data )
    {
        $url  = 'https://api-3t' . ( get_option( 'bookly_pmt_paypal_sandbox' ) ? '.sandbox' : '' ) . '.paypal.com/nvp';

        $curl = new Lib\Curl\Curl();
        $curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $curl->options['CURLOPT_SSL_VERIFYHOST'] = false;

        $data['METHOD']    = $method;
        $data['VERSION']   = '76.0';
        $data['USER']      = get_option( 'bookly_pmt_paypal_api_username' );
        $data['PWD']       = get_option( 'bookly_pmt_paypal_api_password' );
        $data['SIGNATURE'] = get_option( 'bookly_pmt_paypal_api_signature' );

        $httpResponse = $curl->post( $url, $data );
        if ( ! $httpResponse ) {
            exit( $curl->error() );
        }

        // Extract the response details.
        parse_str( $httpResponse, $PayPalResponse );

        if ( ! array_key_exists( 'ACK', $PayPalResponse ) ) {
            exit( 'Invalid HTTP Response for POST request to ' . $url );
        }

        return $PayPalResponse;
    }

    /**
     * Outputs HTML form for PayPal Express Checkout.
     *
     * @param string $form_id
     */
    public static function renderECForm( $form_id )
    {
        $replacement = array(
            '%form_id%' => $form_id,
            '%gateway%' => Lib\Entities\Payment::TYPE_PAYPAL,
            '%back%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ),
            '%next%'    => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ),
        );

        $form = '<form method="post" class="bookly-%gateway%-form">
                <input type="hidden" name="bookly_action" value="paypal-ec-init"/>
                <input type="hidden" name="bookly_fid" value="%form_id%"/>
                <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" style="margin-right: 10px;" data-spinner-size="40"><span class="ladda-label">%back%</span></button>
                <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40"><span class="ladda-label">%next%</span></button>
             </form>';

        echo strtr( $form, $replacement );
    }

    /**
     * Add the Product for payment
     *
     * @param \stdClass $product
     */
    public function addProduct( \stdClass $product )
    {
        $this->products[] = $product;
    }

    /**
     * Verify IPN request
     * @return bool
     */
    public static function verifyIPN()
    {
        $paypalUrl = get_option( 'bookly_pmt_paypal_sandbox' ) ?
            self::URL_POSTBACK_IPN_SANDBOX :
            self::URL_POSTBACK_IPN_LIVE;

        $raw_post_data  = file_get_contents( 'php://input' );
        $raw_post_array = explode( '&', $raw_post_data );
        $postData       = array();
        foreach ( $raw_post_array as $keyval ) {
            $keyval = explode( '=', $keyval );
            if ( count( $keyval ) == 2 ) {
                $postData[ $keyval[0] ] = urldecode( $keyval[1] );
            }
        }

        $req = 'cmd=_notify-validate';
        foreach ( $postData as $key => $value ) {
            if (
                ( function_exists( 'get_magic_quotes_gpc' ) === true )
                && ( get_magic_quotes_gpc() === 1 )
            ) {
                $value = urlencode( stripslashes( $value ) );
            } else {
                $value = urlencode( $value );
            }
            $req .= "&$key=$value";
        }

        $response = wp_safe_remote_post(
            $paypalUrl,
            array(
                'sslcertificates' => __DIR__ . '/PayPal/cert/cacert.pem',
                'body'            => $req,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return strcmp( $response['body'], 'VERIFIED' ) === 0;
    }
}