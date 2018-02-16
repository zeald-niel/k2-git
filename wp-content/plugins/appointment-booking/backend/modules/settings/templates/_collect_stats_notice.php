<?php
/**
 * Template to show notice about "we'r starting to collect statistics about usage"
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="bookly-tbs" class="wrap">
    <div id="bookly-collect-stats-notice" class="alert alert-info bookly-tbs-body bookly-flexbox">
        <div class="bookly-flex-row">
            <div class="bookly-flex-cell" style="width:39px"><i class="alert-icon"></i></div>
            <div class="bookly-flex-cell">
                <button type="button" class="close" data-dismiss="alert"></button>
                <?php _e( 'In order to improve your Bookly experience, we have started collecting anonymous plugin usage stats. If you do not want to provide this information, please disable this option in Bookly settings.', 'bookly' ); ?>
            </div>
        </div>
    </div>
</div>
