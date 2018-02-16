<!-- PCR-1-2 -->
<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div>
    <p><?php _e( 'Thank you for choosing Bookly as your booking solution.', 'bookly' ) ?></p>
    <p><?php _e( 'Please contact your website administrator in order to verify the license.', 'bookly' ) ?></p>
    <p><?php echo strtr( __( 'If you do not verify the license within {days}, access to your bookings will be disabled.', 'bookly' ), $days_text ) ?></p>
</div>
<div class="btn-group-vertical align-left" role="group">
    <button type="button" class="btn btn-link" data-trigger="close"><span class="text-warning"><i class="glyphicon glyphicon glyphicon-time"></i> <?php _e( 'Proceed to Bookly without license verification', 'bookly' ) ?></span></button>
</div>