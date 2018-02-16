<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div>
    <p><?php printf( __( 'Cannot find your purchase code? See this <a href="%s" target="_blank">page</a>.', 'bookly' ), 'https://help.market.envato.com/hc/en-us/articles/202822600-Where-can-I-find-my-Purchase-Code' ) ?></p>
    <?php /** @var \Bookly\Lib\Base\Plugin $plugin */
    foreach ( apply_filters( 'bookly_plugins', array() ) as $plugin ) :
        if ( $plugin::getPurchaseCode() == '' ) :
            printf(
                '<div class="form-group %4$s has-feedback">
                    <label for="%2$s">%1$s:</label>
                    <input id="%2$s" class="purchase-code form-control bookly-margin-bottom-xs" type="text" value="%3$s" />
                    <span class="alert-icon form-control-feedback" aria-hidden="true"></span>
                    </div>',
                $plugin::getTitle() . ' ' . __( 'Purchase Code', 'bookly' ),
                $plugin::getRootNamespace(),
                $plugin::getPurchaseCode(),
                $plugin::getPurchaseCode() == '' ? 'has-warning' : 'has-success'
            );
        endif;
    endforeach ?>
</div>
<div class="btn-group-vertical align-left bookly-verified" role="group" style="display: none">
    <a href="" class="btn btn-link" data-trigger="close"><span class="text-warning"><i class="glyphicon glyphicon glyphicon-time"></i> <?php _e( 'Proceed to Bookly without activating Add-ons', 'bookly' ) ?></span></a>
</div>