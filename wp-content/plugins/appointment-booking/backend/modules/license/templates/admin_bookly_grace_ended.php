<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div>
    <p><?php _e( 'Access to your bookings has been disabled.', 'bookly' ) ?></p>
    <p><?php _e( 'To enable access to your bookings, please verify your license by providing a valid purchase code.', 'bookly' ) ?></p>
</div>
<div class="btn-group-vertical align-left" role="group">
    <button type="button" class="btn btn-link" data-trigger="request_code"><span class="text-success"><i class="glyphicon glyphicon-star"></i> <?php _e( 'I have already made the purchase', 'bookly' ) ?></span></button>
    <a type="button" class="btn btn-link" href="https://codecanyon.net/user/ladela/portfolio" target="_blank"><i class="glyphicon glyphicon-thumbs-up"></i> <?php _e( 'I want to make a purchase now', 'bookly' ) ?></a>
</div>