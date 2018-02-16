<?php
namespace Bookly\Backend\Modules\License;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Debug
 */
class Controller extends Lib\Base\Controller
{
    public function index()
    {
        // license required page
    }

    /**
     * Render form for verification purchase codes.
     */
    public function executeVerifyPurchaseCodeForm()
    {
        $template = 'verification';
        wp_send_json_success( array( 'html' => $this->render( 'board', compact( 'template' ), false ) ) );
    }

    /**
     * Purchase code verification.
     */
    public function executeVerifyPurchaseCode()
    {
        $purchase_code = $this->getParameter( 'purchase_code' );
        /** @var Lib\Base\Plugin $plugin_class */
        $plugin_class  = $this->getParameter( 'plugin' ) . '\Lib\Plugin';
        $result = Lib\API::verifyPurchaseCode( $purchase_code, $plugin_class );
        $response = array( 'success' => $result['valid'] );
        if ( $result['valid'] ) {
            $plugin_class::updatePurchaseCode( $purchase_code );
        } else {
            $response['data']['message'] = $result['error'];
        }

        wp_send_json( $response );
    }

    /**
     * One hour no show message License Required.
     */
    public function executeGraceHideAdminNotice()
    {
        update_option( 'bookly_grace_hide_admin_notice_time', time() + HOUR_IN_SECONDS );
        wp_send_json_success();
    }

    /**
     * Render window with message license verification succeeded.
     */
    public function executeVerificationSucceeded()
    {
        $template = 'verification_succeeded';
        wp_send_json_success( array( 'html' => $this->render( 'board', compact( 'template' ), false ) ) );
    }
}