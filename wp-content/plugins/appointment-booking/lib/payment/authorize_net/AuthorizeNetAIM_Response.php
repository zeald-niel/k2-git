<?php
namespace Bookly\Lib\Payment\AuthorizeNet;

/**
 * Class AuthorizeNetAIM_Response
 *
 * @package Bookly\Lib\Payment\AuthorizeNet
 */
class AuthorizeNetAIM_Response
{
    const APPROVED = 1;
    const DECLINED = 2;
    const ERROR    = 3;
    const HELD     = 4;
    private $_response_array = array();

    /**
     * Constructor. Parses the AuthorizeNet response string.
     *
     * @param string $response      The response from the AuthNet server.
     * @param string $delimiter     The delimiter used (default is ",")
     * @param string $encap_char    The encap_char used (default is "|")
     */
    public function __construct( $response, $delimiter, $encap_char )
    {
        if ( $response ) {

            // Split Array
            $this->response = $response;
            if ( $encap_char ) {
                $this->_response_array = explode( $encap_char . $delimiter . $encap_char, substr( $response, 1, - 1 ) );
            } else {
                $this->_response_array = explode( $delimiter, $response );
            }

            // If AuthorizeNet doesn't return a delimited response.
            if ( count( $this->_response_array ) < 10 ) {
                $this->approved      = false;
                $this->error         = true;
                $this->error_message = 'Unrecognized response from AuthorizeNet: ' . $response;

                return;
            }
            // Set all fields
            $this->response_code         = $this->_response_array[0];
            $this->response_subcode      = $this->_response_array[1];
            $this->response_reason_code  = $this->_response_array[2];
            $this->response_reason_text  = $this->_response_array[3];
            $this->authorization_code    = $this->_response_array[4];
            $this->avs_response          = $this->_response_array[5];
            $this->transaction_id        = $this->_response_array[6];
            $this->invoice_number        = $this->_response_array[7];
            $this->description           = $this->_response_array[8];
            $this->amount                = $this->_response_array[9];
            $this->method                = $this->_response_array[10];
            $this->transaction_type      = $this->_response_array[11];
            $this->customer_id           = $this->_response_array[12];
            $this->first_name            = $this->_response_array[13];
            $this->last_name             = $this->_response_array[14];
            $this->company               = $this->_response_array[15];
            $this->address               = $this->_response_array[16];
            $this->city                  = $this->_response_array[17];
            $this->state                 = $this->_response_array[18];
            $this->zip_code              = $this->_response_array[19];
            $this->country               = $this->_response_array[20];
            $this->phone                 = $this->_response_array[21];
            $this->fax                   = $this->_response_array[22];
            $this->email_address         = $this->_response_array[23];
            $this->ship_to_first_name    = $this->_response_array[24];
            $this->ship_to_last_name     = $this->_response_array[25];
            $this->ship_to_company       = $this->_response_array[26];
            $this->ship_to_address       = $this->_response_array[27];
            $this->ship_to_city          = $this->_response_array[28];
            $this->ship_to_state         = $this->_response_array[29];
            $this->ship_to_zip_code      = $this->_response_array[30];
            $this->ship_to_country       = $this->_response_array[31];
            $this->tax                   = $this->_response_array[32];
            $this->duty                  = $this->_response_array[33];
            $this->freight               = $this->_response_array[34];
            $this->tax_exempt            = $this->_response_array[35];
            $this->purchase_order_number = $this->_response_array[36];
            $this->md5_hash              = $this->_response_array[37];
            $this->card_code_response    = $this->_response_array[38];
            $this->cavv_response         = $this->_response_array[39];
            $this->account_number        = $this->_response_array[50];
            $this->card_type             = $this->_response_array[51];
            $this->split_tender_id       = $this->_response_array[52];
            $this->requested_amount      = $this->_response_array[53];
            $this->balance_on_card       = $this->_response_array[54];

            $this->approved = ( $this->response_code == self::APPROVED );
            $this->declined = ( $this->response_code == self::DECLINED );
            $this->error    = ( $this->response_code == self::ERROR );
            $this->held     = ( $this->response_code == self::HELD );

            if ( $this->error ) {
                $this->error_message = 'AuthorizeNet Error:
                Code: ' . $this->response_code . '
                Subcode: ' . $this->response_subcode . '
                Reason Code: ' . $this->response_reason_code . '
                Reason Text: ' . $this->response_reason_text;
            }
        } else {
            $this->approved      = false;
            $this->error         = true;
            $this->error_message = 'Error connecting to AuthorizeNet';
        }
    }

}