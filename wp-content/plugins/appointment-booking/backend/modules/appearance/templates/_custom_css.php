<?php
/**
 * Template to work with custom css.
 * Template includes button to show custom css form + form to edit it
 *
 * @var string $custom_css custom css text
 */
?>
<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="form-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#bookly-custom-css-dialog">
        <?php _e( 'Edit custom CSS', 'bookly' ); ?>
    </button>
</div>

<div id="bookly-custom-css-dialog" class="modal fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <?php _e( 'Edit custom CSS', 'bookly' ) ?>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="bookly-custom-css" class="control-label"><?php _e( 'Set up your custom CSS styles', 'bookly' ) ?></label>
                    <textarea id="bookly-custom-css" class="form-control" rows="10"><?php echo $custom_css ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div id="bookly-custom-css-error"></div>
                <?php \Bookly\Lib\Utils\Common::customButton( 'bookly-custom-css-save', 'btn-success btn-lg', __( 'Save', 'bookly' ) ) ?>
                <?php \Bookly\Lib\Utils\Common::customButton( 'bookly-custom-css-cancel', 'btn-default btn-lg', __( 'Cancel', 'bookly' ) ) ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var saved_css = <?php echo json_encode( $custom_css ); ?>;
</script>
