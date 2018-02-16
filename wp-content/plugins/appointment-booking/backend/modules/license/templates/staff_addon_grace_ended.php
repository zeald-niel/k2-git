<!-- PCR-4-2 -->
<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div>
    <p><?php _e( 'The following add-ons have been disabled:', 'bookly' ) ?></p>
    <ul>
        <?php foreach ( $states['add-ons']['expired'] as $plugin ) :
            printf( '<li>%s</li>', $plugin::getTitle() );
        endforeach ?>
    </ul>
    <p><?php _e( 'To enable these add-ons, please contact your website administrator in order to verify the license.', 'bookly' ) ?></p>
</div>
<div class="btn-group-vertical align-left" role="group">
    <button type="button" class="btn btn-link" data-trigger="close"><span class="text-warning"><i class="glyphicon glyphicon glyphicon-time"></i> <?php _e( 'Proceed to Bookly without license verification', 'bookly' ) ?></span></button>
</div>