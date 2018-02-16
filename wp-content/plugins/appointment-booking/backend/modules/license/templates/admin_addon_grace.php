<!-- PCR-3-1 -->
<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div>
    <p><?php _e( 'Thank you for choosing Bookly as your booking solution.<br>The following add-ons require license verification:', 'bookly' ) ?></p>
    <ul>
        <?php foreach ( $states['add-ons']['in_grace'] as $plugin ) :
            printf( '<li>%s</li>', $plugin::getTitle() );
        endforeach ?>
    </ul>
    <p><?php _e( 'Please verify your license by providing a valid purchase code.', 'bookly' ) ?></p>
    <p><?php echo strtr( __( 'If you do not provide a valid purchase code within {days}, these add-ons will be disabled.', 'bookly' ), $days_text ) ?></p>
</div>
<div class="btn-group-vertical align-left" role="group">
    <button type="button" class="btn btn-link" data-trigger="request_code"><span class="text-success"><i class="glyphicon glyphicon-star"></i> <?php _e( 'I have already made the purchase', 'bookly' ) ?></span></button>
    <a type="button" class="btn btn-link" href="https://codecanyon.net/user/ladela/portfolio" target="_blank"><i class="glyphicon glyphicon-thumbs-up"></i> <?php _e( 'I want to make a purchase now', 'bookly' ) ?></a>
    <button type="button" class="btn btn-link" data-trigger="close"><span class="text-warning"><i class="glyphicon glyphicon glyphicon-time"></i> <?php _e( 'I will provide license info later', 'bookly' ) ?></span></button>
</div>