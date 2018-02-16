<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var Bookly\Backend\Modules\Appearance\Lib\Helper $editable
 */
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>
    <div class="bookly-box bookly-js-payment-single-app">
        <?php $editable::renderText( 'bookly_l10n_info_coupon_single_app', $this->render( '_codes', array( 'step' => 7 ), false ) ) ?>
    </div>
    <div class="bookly-box bookly-js-payment-several-apps" style="display:none">
        <?php $editable::renderText( 'bookly_l10n_info_coupon_several_apps', $this->render( '_codes', array( 'step' => 7, 'extra_codes' => 1), false ) ) ?>
    </div>

    <div class="bookly-box bookly-list">
        <?php $editable::renderString( array( 'bookly_l10n_label_coupon', ) ) ?>
        <div class="bookly-inline-block">
            <input class="bookly-user-coupon" type="text" />
            <div class="bookly-btn btn-apply-coupon">
                <?php $editable::renderString( array( 'bookly_l10n_button_apply', ) ) ?>
            </div>
        </div>
    </div>
    <div class="bookly-payment-nav">
        <div class="bookly-box bookly-js-payment-single-app">
            <?php $editable::renderText( 'bookly_l10n_info_payment_step_single_app', $this->render( '_codes', array( 'step' => 7 ), false ), 'right' ) ?>
        </div>
        <div class="bookly-box bookly-js-payment-several-apps" style="display:none">
            <?php $editable::renderText( 'bookly_l10n_info_payment_step_several_apps', $this->render( '_codes', array( 'step' => 7, 'extra_codes' => 1 ), false ), 'right' ) ?>
        </div>

        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" name="payment" checked="checked" />
                <?php $editable::renderString( array( 'bookly_l10n_label_pay_locally', ) ) ?>
            </label>
        </div>

        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" name="payment" />
                <?php $editable::renderString( array( 'bookly_l10n_label_pay_paypal', ) ) ?>
                <img src="<?php echo plugins_url( 'frontend/resources/images/paypal.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="paypal" />
            </label>
        </div>

        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" name="payment" id="bookly-card-payment" />
                <?php $editable::renderString( array( 'bookly_l10n_label_pay_ccard', ) ) ?>
                <img src="<?php echo plugins_url( 'frontend/resources/images/cards.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="cards" />
            </label>
            <form class="bookly-card-form bookly-clear-bottom" style="margin-top:15px;display: none;">
                <?php include '_card_payment.php' ?>
            </form>
        </div>

        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" name="payment" />
                <?php $editable::renderString( array( 'bookly_l10n_label_pay_mollie', ) ) ?>
                <img src="<?php echo plugins_url( 'frontend/resources/images/mollie.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="mollie" />
            </label>
        </div>

        <?php do_action( 'bookly_render_appearance_payment_gateway_selector' ) ?>
    </div>

    <?php \Bookly\Lib\Proxy\RecurringAppointments::renderAppearanceEditableInfoMessage() ?>

    <div class="bookly-box bookly-nav-steps">
        <div class="bookly-back-step bookly-js-back-step bookly-btn">
            <?php $editable::renderString( array( 'bookly_l10n_button_back' ) ) ?>
        </div>
        <div class="bookly-next-step bookly-js-next-step bookly-btn">
            <?php $editable::renderString( array( 'bookly_l10n_step_payment_button_next' ) ) ?>
        </div>
    </div>
</div>