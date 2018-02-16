<?php
namespace Bookly\Backend\Modules\Settings\Forms;

use Bookly\Lib;

/**
 * Class Payments
 * @package Bookly\Backend\Modules\Settings
 */
class Payments extends Lib\Base\Form
{
    public function __construct()
    {
    }

    public function bind( array $_post, array $files = array() )
    {
        $fields = Lib\Proxy\Shared::preparePaymentOptions( array(
            'bookly_pmt_currency',
            'bookly_pmt_coupons',
            'bookly_pmt_local',
            'bookly_pmt_paypal',
            'bookly_pmt_paypal_api_username',
            'bookly_pmt_paypal_api_password',
            'bookly_pmt_paypal_api_signature',
            'bookly_pmt_paypal_sandbox',
            'bookly_pmt_authorize_net',
            'bookly_pmt_authorize_net_api_login_id',
            'bookly_pmt_authorize_net_transaction_key',
            'bookly_pmt_authorize_net_sandbox',
            'bookly_pmt_stripe',
            'bookly_pmt_stripe_secret_key',
            'bookly_pmt_stripe_publishable_key',
            'bookly_pmt_2checkout',
            'bookly_pmt_2checkout_sandbox',
            'bookly_pmt_2checkout_api_seller_id',
            'bookly_pmt_2checkout_api_secret_word',
            'bookly_pmt_payu_latam',
            'bookly_pmt_payu_latam_sandbox',
            'bookly_pmt_payu_latam_api_account_id',
            'bookly_pmt_payu_latam_api_key',
            'bookly_pmt_payu_latam_api_merchant_id',
            'bookly_pmt_payson',
            'bookly_pmt_payson_sandbox',
            'bookly_pmt_payson_fees_payer',
            'bookly_pmt_payson_api_agent_id',
            'bookly_pmt_payson_api_key',
            'bookly_pmt_payson_api_receiver_email',
            'bookly_pmt_payson_funding',
            'bookly_pmt_mollie',
            'bookly_pmt_mollie_api_key',
        ) );

        $_post = Lib\Proxy\Shared::preparePaymentOptionsData( $_post );

        $this->setFields( $fields );
        parent::bind( $_post, $files );
    }

    public function save()
    {
        if ( empty( $this->data['bookly_pmt_payson_funding'] ) ) {
            $this->data['bookly_pmt_payson_funding'] = array( 'CREDITCARD' );
        }
        foreach ( $this->data as $field => $value ) {
            update_option( $field, $value );
        }
    }

}