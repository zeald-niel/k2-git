<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var Bookly\Backend\Modules\Appearance\Lib\Helper $editable
 */
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>

    <div class="bookly-box">
        <?php $editable::renderText( 'bookly_l10n_info_details_step', $this->render( '_codes', array( 'step' => 6 ), false ) ) ?>
    </div>
    <div class="bookly-box">
        <?php $editable::renderText( 'bookly_l10n_info_details_step_guest', $this->render( '_codes', array( 'step' => 6, 'extra_codes' => 1 ), false ), 'bottom', __( 'Visible to non-logged in customers only', 'bookly' ) ) ?>
    </div>
    <div class="bookly-details-step">
        <div class="bookly-box bookly-table">
            <div class="bookly-form-group">
                <?php $editable::renderLabel( array( 'bookly_l10n_label_name', 'bookly_l10n_required_name', ) ) ?>
                <div>
                    <input type="text" value="" maxlength="60" />
                </div>
            </div>
            <div class="bookly-form-group">
                <?php $editable::renderLabel( array( 'bookly_l10n_label_phone', 'bookly_l10n_required_phone', ) ) ?>
                <div>
                    <input type="text" class="<?php if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) : ?>bookly-user-phone<?php endif ?>" value="" />
                </div>
            </div>
            <div class="bookly-form-group">
                <?php $editable::renderLabel( array( 'bookly_l10n_label_email', 'bookly_l10n_required_email', ) ) ?>
                <div>
                    <input maxlength="40" type="text" value="" />
                </div>
            </div>
        </div>
    </div>

    <?php \Bookly\Lib\Proxy\RecurringAppointments::renderAppearanceEditableInfoMessage() ?>

    <div class="bookly-box bookly-nav-steps">
        <div class="bookly-back-step bookly-js-back-step bookly-btn">
            <?php $editable::renderString( array( 'bookly_l10n_button_back' ) ) ?>
        </div>
        <div class="bookly-next-step bookly-js-next-step bookly-btn">
            <?php $editable::renderString( array( 'bookly_l10n_step_details_button_next' ) ) ?>
        </div>
    </div>
</div>
