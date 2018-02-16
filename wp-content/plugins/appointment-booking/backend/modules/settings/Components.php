<?php
namespace Bookly\Backend\Modules\Settings;

use Bookly\Lib;

/**
 * Class Components
 * @package Bookly\Backend\Modules\Support
 */
class Components extends Lib\Base\Components
{
    /**
     * Render collect stats notice and marks it as showed for every user
     */
    public function renderCollectStatsNotice()
    {
        if ( Lib\Utils\Common::isCurrentUserAdmin() &&
            get_option( 'bookly_gen_collect_stats' ) == '1' &&
            ! get_user_meta( get_current_user_id(), Lib\Plugin::getPrefix() . 'dismiss_collect_stats_notice', true )
        ) {
            $this->enqueueStyles( array(
                'frontend' => array( 'css/ladda.min.css', ),
            ) );
            $this->enqueueScripts( array(
                'module'  => array( 'js/collect-stats-notice.js' => array( 'jquery' ), ),
            ) );
            $this->render( '_collect_stats_notice' );
        }
    }
}