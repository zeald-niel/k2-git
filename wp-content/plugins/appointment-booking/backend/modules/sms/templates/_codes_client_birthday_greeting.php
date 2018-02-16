<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$codes = array(
    array( 'code' => 'client_email',    'description' => __( 'email of client', 'bookly' ) ),
    array( 'code' => 'client_name',     'description' => __( 'name of client', 'bookly' ) ),
    array( 'code' => 'client_phone',    'description' => __( 'phone of client', 'bookly' ) ),
    array( 'code' => 'company_name',    'description' => __( 'name of your company', 'bookly' ) ),
    array( 'code' => 'company_logo',    'description' => __( 'your company logo', 'bookly' ) ),
    array( 'code' => 'company_address', 'description' => __( 'address of your company', 'bookly' ) ),
    array( 'code' => 'company_phone',   'description' => __( 'your company phone', 'bookly' ) ),
    array( 'code' => 'company_website', 'description' => __( 'this web-site address', 'bookly' ) ),
);
\Bookly\Lib\Utils\Common::codes( $codes );