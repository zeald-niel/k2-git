<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="bookly-export-customers-dialog" class="modal fade" tabindex=-1 role="dialog">
    <div class="modal-dialog">
        <form action="<?php echo admin_url( 'admin-ajax.php?action=bookly_export_customers' ) ?>" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <div class="modal-title h2"><?php _e( 'Export to CSV', 'bookly' ) ?></div>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="export_customers_delimiter"><?php _e( 'Delimiter', 'bookly' ) ?></label>
                        <select name="export_customers_delimiter" id="export_customers_delimiter" class="form-control">
                            <option value=","><?php _e( 'Comma (,)', 'bookly' ) ?></option>
                            <option value=";"><?php _e( 'Semicolon (;)', 'bookly' ) ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="checkbox"><label><input checked name="exp[name]" type="checkbox"> <?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_name' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[wp_user]" type="checkbox"><?php _e( 'User', 'bookly' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[phone]" type="checkbox"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_phone' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[email]" type="checkbox"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_email' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[notes]" type="checkbox"><?php _e( 'Notes', 'bookly' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[last_appointment]" type="checkbox"><?php _e( 'Last appointment', 'bookly' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[total_appointments]" type="checkbox"><?php _e( 'Total appointments', 'bookly' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[payments]" type="checkbox"><?php _e( 'Payments', 'bookly' ) ?></label></div>
                        <div class="checkbox"><label><input checked name="exp[birthday]" type="checkbox"><?php _e( 'Date of birth', 'bookly' ) ?></label></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php \Bookly\Lib\Utils\Common::submitButton( null, '', __( 'Export to CSV', 'bookly' ) ) ?>
                </div>
            </div>
        </form>
    </div>
</div>