<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    /**
     * @var Bookly\Backend\Modules\Appearance\Lib\Helper $editable
     * @var WP_Locale $wp_locale
     */
    global $wp_locale;
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>

    <div class="bookly-service-step">
        <div class="bookly-box">
            <span class="bookly-bold bookly-desc">
                <?php $editable::renderText( 'bookly_l10n_info_service_step' ) ?>
            </span>
        </div>
        <div class="bookly-mobile-step-1 bookly-js-mobile-step-1 bookly-box">
            <div class="bookly-js-chain-item bookly-table bookly-box">
                <?php if ( \Bookly\Lib\Config::locationsEnabled() ) : ?>
                    <div class="bookly-form-group">
                        <?php \Bookly\Lib\Proxy\Locations::renderAppearance() ?>
                    </div>
                <?php endif ?>
                <div class="bookly-form-group">
                    <?php $editable::renderLabel( array( 'bookly_l10n_label_category', 'bookly_l10n_option_category', ) ) ?>
                    <div>
                        <select class="bookly-select-mobile bookly-js-select-category">
                            <option value="" class="bookly-js-option bookly_l10n_option_category"><?php echo esc_html( get_option( 'bookly_l10n_option_category' ) ) ?></option>
                            <option value="1">Cosmetic Dentistry</option>
                            <option value="2">Invisalign</option>
                            <option value="3">Orthodontics</option>
                            <option value="4">Dentures</option>
                        </select>
                    </div>
                </div>
                <div class="bookly-form-group">
                    <?php $editable::renderLabel( array(
                        'bookly_l10n_label_service',
                        'bookly_l10n_option_service',
                        'bookly_l10n_required_service',
                    ) ) ?>
                    <div>
                        <select class="bookly-select-mobile bookly-js-select-service">
                            <option class="bookly-js-option bookly_l10n_option_service"><?php echo esc_html( get_option( 'bookly_l10n_option_service' ) ) ?></option>
                            <option>Crown and Bridge</option>
                            <option>Teeth Whitening</option>
                            <option>Veneers</option>
                            <option>Invisalign (invisable braces)</option>
                            <option>Orthodontics (braces)</option>
                            <option>Wisdom tooth Removal</option>
                            <option>Root Canal Treatment</option>
                            <option>Dentures</option>
                        </select>
                    </div>
                </div>
                <div class="bookly-form-group">
                    <?php $editable::renderLabel( array(
                        'bookly_l10n_label_employee',
                        'bookly_l10n_option_employee',
                        'bookly_l10n_required_employee',
                    ) ) ?>
                    <div>
                        <select class="bookly-select-mobile bookly-js-select-employee">
                            <option value="0" class="bookly-js-option bookly_l10n_option_employee"><?php echo esc_html( get_option( 'bookly_l10n_option_employee' ) ) ?></option>
                            <option value="1" class="employee-name-price">Nick Knight (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?>)</option>
                            <option value="-1" class="employee-name">Nick Knight</option>
                            <option value="2" class="employee-name-price">Jane Howard (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 375 ) ?>)</option>
                            <option value="-2" class="employee-name">Jane Howard</option>
                            <option value="3" class="employee-name-price">Ashley Stamp (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 300 ) ?>)</option>
                            <option value="-3" class="employee-name">Ashley Stamp</option>
                            <option value="4" class="employee-name-price">Bradley Tannen (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 400 ) ?>)</option>
                            <option value="-4" class="employee-name">Bradley Tannen</option>
                            <option value="5" class="employee-name-price">Wayne Turner (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?>)</option>
                            <option value="-5" class="employee-name">Wayne Turner</option>
                            <option value="6" class="employee-name-price">Emily Taylor (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?>)</option>
                            <option value="-6" class="employee-name">Emily Taylor</option>
                            <option value="7" class="employee-name-price">Hugh Canberg (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 380 ) ?>)</option>
                            <option value="-7" class="employee-name">Hugh Canberg</option>
                            <option value="8" class="employee-name-price">Jim Gonzalez (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 390 ) ?>)</option>
                            <option value="-8" class="employee-name">Jim Gonzalez</option>
                            <option value="9" class="employee-name-price">Nancy Stinson (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 360 ) ?>)</option>
                            <option value="-9" class="employee-name">Nancy Stinson</option>
                            <option value="10" class="employee-name-price">Marry Murphy (<?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?>)</option>
                            <option value="-10" class="employee-name">Marry Murphy</option>
                        </select>
                    </div>
                </div>
                <div class="bookly-form-group">
                    <?php $editable::renderLabel( array( 'bookly_l10n_label_number_of_persons', ) ) ?>
                    <div>
                        <select class="bookly-select-mobile bookly-js-select-number-of-persons">
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                        </select>
                    </div>
                </div>
                <?php if ( \Bookly\Lib\Config::multiplyAppointmentsEnabled() ) : ?>
                    <div class="bookly-form-group">
                        <?php \Bookly\Lib\Proxy\MultiplyAppointments::renderAppearance() ?>
                    </div>
                <?php endif ?>
                <?php if ( \Bookly\Lib\Config::chainAppointmentsEnabled() ) : ?>
                    <div class="bookly-form-group">
                        <label></label>
                        <div>
                            <button class="bookly-round" ><i class="bookly-icon-sm bookly-icon-plus"></i></button>
                        </div>
                    </div>
                <?php endif ?>
            </div>
            <div class="bookly-right bookly-mobile-next-step bookly-js-mobile-next-step bookly-btn bookly-none">
                <?php $editable::renderString( array( 'bookly_l10n_step_service_mobile_button_next' ) ) ?>
            </div>
        </div>
        <div class="bookly-mobile-step-2 bookly-js-mobile-step-2">
            <div class="bookly-box">
                <div class="bookly-left">
                    <div class="bookly-available-date bookly-js-available-date bookly-left">
                        <div class="bookly-form-group">
                            <?php $editable::renderLabel( array( 'bookly_l10n_label_select_date', ) ) ?>
                            <div>
                               <input class="bookly-date-from bookly-js-date-from" style="background-color: #fff;" type="text" data-value="<?php echo date( 'Y-m-d' ) ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="bookly-week-days bookly-js-week-days bookly-table bookly-left">
                        <?php foreach ( $wp_locale->weekday_abbrev as $weekday_abbrev ) : ?>
                            <div>
                                <div class="bookly-font-bold"><?php echo $weekday_abbrev ?></div>
                                <label class="active">
                                    <input class="bookly-js-week-day" value="1" checked="checked" type="checkbox">
                                </label>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="bookly-time-range bookly-js-time-range bookly-left">
                    <div class="bookly-form-group bookly-time-from bookly-left">
                        <?php $editable::renderLabel( array( 'bookly_l10n_label_start_from', ) ) ?>
                        <div>
                            <select class="bookly-js-select-time-from">
                                <?php for ( $i = 28800; $i <= 64800; $i += 3600 ) : ?>
                                    <option><?php echo \Bookly\Lib\Utils\DateTime::formatTime( $i ) ?></option>
                                <?php endfor ?>
                            </select>
                        </div>
                    </div>
                    <div class="bookly-form-group bookly-time-to bookly-left">
                        <?php $editable::renderLabel( array( 'bookly_l10n_label_finish_by', ) ) ?>
                        <div>
                            <select class="bookly-js-select-time-to">
                                <?php for ( $i = 28800; $i <= 64800; $i += 3600 ) : ?>
                                    <option<?php selected( $i == 64800 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::formatTime( $i ) ?></option>
                                <?php endfor ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bookly-box bookly-nav-steps">
                <div class="bookly-right bookly-mobile-prev-step bookly-js-mobile-prev-step bookly-btn bookly-none">
                    <?php $editable::renderString( array( 'bookly_l10n_button_back' ) ) ?>
                </div>
                <div class="bookly-next-step bookly-js-next-step bookly-btn">
                    <?php $editable::renderString( array( 'bookly_l10n_step_service_button_next' ) ) ?>
                </div>
                <button class="bookly-go-to-cart bookly-js-go-to-cart bookly-round bookly-round-md ladda-button"><span><img src="<?php echo plugins_url( 'appointment-booking/frontend/resources/images/cart.png' ) ?>" /></span></button>
            </div>
        </div>
    </div>
</div>
<div style="display: none">
    <?php foreach ( array( 'bookly_l10n_required_service', 'bookly_l10n_required_name', 'bookly_l10n_required_phone', 'bookly_l10n_required_email', 'bookly_l10n_required_employee', 'bookly_l10n_required_location' ) as $validator ) : ?>
        <div class="bookly-js-option <?php echo $validator ?>"><?php echo get_option( $validator ) ?></div>
    <?php endforeach ?>
</div>
<style id="bookly-pickadate-style"></style>
