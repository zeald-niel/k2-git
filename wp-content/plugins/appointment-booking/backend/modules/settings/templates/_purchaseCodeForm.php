<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'purchase_code' ) ) ?>" id="purchase_code">
    <div class="form-group">
        <h4><?php _e( 'Instructions', 'bookly' ) ?></h4>
        <p><?php _e( 'Upon providing the purchase code you will have access to free updates of Bookly. Updates may contain functionality improvements and important security fixes. For more information on where to find your purchase code see this <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-can-I-find-my-Purchase-Code-" target="_blank">page</a>.', 'bookly' ) ?></p>
    </div>
    <?php do_action( 'bookly_render_purchase_code' ) ?>

    <div class="panel-footer">
        <?php \Bookly\Lib\Utils\Common::submitButton() ?>
        <?php \Bookly\Lib\Utils\Common::resetButton() ?>
    </div>
</form>