<?php
namespace Bookly\Lib;

abstract class API
{
    const API_URL = 'http://api.booking-wp-plugin.com/1.0';

    /**
     * Verify envato.com Purchase Code
     *
     * @param $purchase_code
     * @param Base\Plugin $plugin_class
     * @return array
     */
    public static function verifyPurchaseCode( $purchase_code, $plugin_class )
    {
        $options   = array(
            'timeout' => 10, //seconds
            'headers' => array(
                'Accept' => 'application/json'
            ),
        );
        $arguments = array(
            'api'           => '1.0',
            'action'        => 'verify-purchase-code',
            'plugin'        => $plugin_class::getSlug(),
            'purchase_code' => $purchase_code,
            'site_url'      => site_url(),
        );
        $url = add_query_arg( $arguments, 'http://booking-wp-plugin.com/' );
        try {
            $response = wp_remote_get( $url, $options );
            if ( $response instanceof \WP_Error ) {

            } elseif ( isset( $response['body'] ) ) {
                $json = json_decode( $response['body'], true );
                if ( isset( $json['success'] ) ) {
                    if ( (bool) $json['success'] ) {
                        return array(
                            'valid' => true,
                        );
                    } else {
                        return array(
                            'valid' => false,
                            'error' => sprintf(
                                __( '%s is not a valid purchase code for %s.', 'bookly' ),
                                $purchase_code,
                                $plugin_class::getTitle()
                            )
                        );
                    }
                }
            }
        } catch ( \Exception $e ) {

        }

        return array(
            'valid' => false,
            'error' => __( 'Purchase code verification is temporarily unavailable. Please try again later.', 'bookly' )
        );
    }

    /**
     * Register subscriber.
     *
     * @param string $email
     * @return bool
     */
    public static function registerSubscriber( $email )
    {
        try {
            $url  = self::API_URL . '/subscribers';
            $curl = new Curl\Curl();
            $curl->options['CURLOPT_HEADER']  = 0;
            $curl->options['CURLOPT_TIMEOUT'] = 25;
            $data = array( 'email' => $email, 'site_url' => site_url() );
            $response = (array) json_decode( $curl->post( $url, $data ), true );
            if ( isset ( $response['success'] ) && $response['success'] ) {
                return true;
            }
        } catch ( \Exception $e ) {

        }

        return false;
    }

    /**
     * Send Net Promoter Score.
     *
     * @param integer $rate
     * @param string  $msg
     * @param string  $email
     * @return bool
     */
    public static function sendNps( $rate, $msg, $email )
    {
        try {
            $url = self::API_URL . '/nps';
            $curl = new Curl\Curl();
            $curl->options['CURLOPT_HEADER']  = 0;
            $curl->options['CURLOPT_TIMEOUT'] = 25;
            $data = array( 'rate' => $rate, 'msg' => $msg, 'email' => $email, 'site_url' => site_url() );
            $response = (array) json_decode( $curl->post( $url, $data ), true );
            if ( isset ( $response['success'] ) && $response['success'] ) {
                return true;
            }
        } catch ( \Exception $e ) {

        }

        return false;
    }
}