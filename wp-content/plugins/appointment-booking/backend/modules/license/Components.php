<?php
namespace Bookly\Backend\Modules\License;

use Bookly\Lib;

/**
 * Class Components
 * @package Bookly\Backend\Modules\Calendar
 */
class Components extends Lib\Base\Components
{

    private function enqueueAssets()
    {
        $this->enqueueStyles( array(
            'backend' => array( 'bootstrap/css/bootstrap-theme.min.css', ),
        ) );

        $this->enqueueScripts( array(
            'module'  => array( 'js/license.js' => array( 'jquery' ), ),
            'backend' => array(
                'js/alert.js' => array( 'jquery' ),
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
            ),
        ) );
    }

    public function renderLicenseRequired()
    {
        $states = Lib\Config::getPluginVerificationStates();
        $prefix = Lib\Utils\Common::isCurrentUserAdmin() ? 'admin' : 'staff';
        $prefix .= '_' . ( $states['bookly'] != 'verified' ? 'bookly' : 'addon' );
        if ( Lib\Config::isBooklyExpired() || ! empty ( $states['add-ons']['expired'] ) ) {
            $this->enqueueAssets();
            $this->render( 'board', array( 'board_body' => $this->render( $prefix . '_grace_ended', compact( 'states' ), false ) ) );
        } elseif ( $states['grace_remaining_days'] ) {
            // Some plugin in grace period
            $this->enqueueAssets();
            $days_text = array( '{days}' => sprintf( _n( '%d day', '%d days', $states['grace_remaining_days'], 'bookly' ), $states['grace_remaining_days'] ) );
            $this->render( 'board', array( 'board_body' => $this->render( $prefix . '_grace', compact( 'states', 'days_text' ), false ) ) );
        }
    }

}