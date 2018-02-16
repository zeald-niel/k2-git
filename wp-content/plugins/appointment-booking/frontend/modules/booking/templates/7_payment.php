<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    echo $progress_tracker;
?>
<?php if ( get_option( 'bookly_pmt_coupons' ) ) : ?>
    <div class="bookly-box bookly-info-text-coupon"><?php echo $info_text_coupon ?></div>
    <div class="bookly-box bookly-list">
        <?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_coupon' ) ?>
        <?php if ( $coupon_code ) : ?>
            <?php echo esc_attr( $coupon_code ) . ' ✓' ?>
        <?php else : ?>
            <input class="bookly-user-coupon" name="bookly-coupon" type="text" value="<?php echo esc_attr( $coupon_code ) ?>" />
            <button class="bookly-btn ladda-button btn-apply-coupon" data-style="zoom-in" data-spinner-size="40">
                <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_apply' ) ?></span><span class="spinner"></span>
            </button>
        <?php endif ?>
        <div class="bookly-label-error bookly-js-coupon-error"></div>
    </div>
<?php endif ?>

<div class="bookly-payment-nav">
    <div class="bookly-box"><?php echo $info_text ?></div>
    <?php if ( $pay_local ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="local"/>
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_locally' ) ?></span>
            </label>
        </div>
    <?php endif ?>

    <?php if ( $pay_paypal ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="paypal"/>
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_paypal' ) ?></span>
                <img src="<?php echo plugins_url( 'frontend/resources/images/paypal.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="PayPal" />
            </label>
            <?php if ( $payment['gateway'] == Bookly\Lib\Entities\Payment::TYPE_PAYPAL && $payment['status'] == 'error' ) : ?>
                <div class="bookly-label-error"><?php echo $payment['data'] ?></div>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php if ( $pay_authorize_net ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="card" data-form="authorize-net" />
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_ccard' ) ?></span>
                <img src="<?php echo $url_cards_image ?>" alt="cards" />
            </label>
            <form class="bookly-authorize-net" style="display: none; margin-top: 15px;">
                <?php include '_card_payment.php' ?>
            </form>
        </div>
    <?php endif ?>

    <?php if ( $pay_stripe ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="card" data-form="stripe" />
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_ccard' ) ?></span>
                <img src="<?php echo $url_cards_image ?>" alt="cards" />
            </label>
            <?php if ( get_option( 'bookly_pmt_stripe_publishable_key' ) != '' ) : ?>
                <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
            <?php endif ?>
            <form class="bookly-stripe" style="display: none; margin-top: 15px;">
                <input type="hidden" id="publishable_key" value="<?php echo get_option( 'bookly_pmt_stripe_publishable_key' ) ?>">
                <?php include '_card_payment.php' ?>
            </form>
        </div>
    <?php endif ?>

    <?php if ( $pay_2checkout ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="2checkout"/>
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_ccard' ) ?></span>
                <img src="<?php echo $url_cards_image ?>" alt="cards" />
            </label>
        </div>
    <?php endif ?>

    <?php if ( $pay_payu_latam ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="payu_latam"/>
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_ccard' ) ?></span>
                <img src="<?php echo $url_cards_image ?>" alt="cards" />
            </label>
            <?php if ( $payment['gateway'] == Bookly\Lib\Entities\Payment::TYPE_PAYULATAM && $payment['status'] == 'error' ) : ?>
                <div class="bookly-label-error" style="padding-top: 5px;">* <?php echo $payment['data'] ?></div>
            <?php endif ?>
        </div>
    <?php endif ?>
    <div class="bookly-box bookly-list" style="display: none">
        <input type="radio" class="bookly-js-coupon-free" name="payment-method-<?php echo $form_id ?>" value="coupon" />
    </div>

    <?php if ( $pay_payson ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="payson"/>
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_ccard' ) ?></span>
                <img src="<?php echo $url_cards_image ?>" alt="cards" />
            </label>
            <?php if ( $payment['gateway'] == Bookly\Lib\Entities\Payment::TYPE_PAYSON && $payment['status'] == 'error' ) : ?>
                <div class="bookly-label-error" style="padding-top: 5px;">* <?php echo $payment['data'] ?></div>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php if ( $pay_mollie ) : ?>
        <div class="bookly-box bookly-list">
            <label>
                <input type="radio" class="bookly-payment" name="payment-method-<?php echo $form_id ?>" value="mollie"/>
                <span><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_mollie' ) ?></span>
                <img src="<?php echo plugins_url( 'frontend/resources/images/mollie.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" alt="Mollie" />
            </label>
            <?php if ( $payment['gateway'] == Bookly\Lib\Entities\Payment::TYPE_MOLLIE && $payment['status'] == 'error' ) : ?>
                <div class="bookly-label-error" style="padding-top: 5px;">* <?php echo $payment['data'] ?></div>
            <?php endif ?>
        </div>
    <?php endif ?>
    <?php do_action( 'bookly_render_payment_gateway_selector', $form_id, $payment ) ?>
</div>

<?php $this->render( '_info_block', compact( 'info_message' ) ) ?>

<?php if ( $pay_local ) : ?>
    <div class="bookly-gateway-buttons pay-local bookly-box bookly-nav-steps">
        <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in"  data-spinner-size="40">
            <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
        </button>
        <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ) ?></span>
        </button>
    </div>
<?php endif ?>

<?php if ( $pay_paypal ) : ?>
    <div class="bookly-gateway-buttons pay-paypal bookly-box bookly-nav-steps" style="display:none">
        <?php if ( $pay_paypal === Bookly\Lib\Payment\PayPal::TYPE_EXPRESS_CHECKOUT ) :
            Bookly\Lib\Payment\PayPal::renderECForm( $form_id );
        elseif ( $pay_paypal === Bookly\Lib\Payment\PayPal::TYPE_PAYMENTS_STANDARD ) :
            \Bookly\Lib\Proxy\PaypalPaymentsStandard::renderPaymentForm( $form_id, $page_url );
        endif ?>
    </div>
<?php endif ?>

<?php if ( $pay_2checkout ) : ?>
    <div class="bookly-gateway-buttons pay-2checkout bookly-box bookly-nav-steps" style="display:none">
        <?php Bookly\Lib\Payment\TwoCheckout::renderForm( $form_id, $page_url ) ?>
    </div>
<?php endif ?>

<?php if ( $pay_payu_latam ) : ?>
    <div class="bookly-gateway-buttons pay-payu_latam bookly-box bookly-nav-steps" style="display:none">
        <?php Bookly\Lib\Payment\PayuLatam::renderForm( $form_id, $page_url ) ?>
    </div>
<?php endif ?>

<?php if ( $pay_authorize_net || $pay_stripe ) : ?>
    <div class="bookly-gateway-buttons pay-card bookly-box bookly-nav-steps" style="display:none">
        <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
        </button>
        <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
            <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ) ?></span>
        </button>
    </div>
<?php endif ?>

<?php if ( $pay_payson ) : ?>
    <div class="bookly-gateway-buttons pay-payson bookly-box bookly-nav-steps" style="display:none">
        <?php Bookly\Lib\Payment\Payson::renderForm( $form_id, $page_url ) ?>
    </div>
<?php endif ?>

<?php if ( $pay_mollie ) : ?>
    <div class="bookly-gateway-buttons pay-mollie bookly-box bookly-nav-steps" style="display:none">
        <?php Bookly\Lib\Payment\Mollie::renderForm( $form_id, $page_url ) ?>
    </div>
<?php endif ?>

<?php do_action( 'bookly_render_payment_gateway', $form_id, $page_url ) ?>

<div class="bookly-gateway-buttons pay-coupon bookly-box bookly-nav-steps" style="display: none">
    <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
    </button>
    <button class="bookly-next-step bookly-js-next-step bookly-js-coupon-payment bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_payment_button_next' ) ?></span>
    </button>
</div>
