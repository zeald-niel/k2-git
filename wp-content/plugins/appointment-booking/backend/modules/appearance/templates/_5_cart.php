<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * @var Bookly\Backend\Modules\Appearance\Lib\Helper $editable
 */
?>
<div class="bookly-form">
    <?php include '_progress_tracker.php' ?>

    <div class="bookly-box">
        <?php $editable::renderText( 'bookly_l10n_info_cart_step', $this->render( '_codes', array( 'step' => 5 ), false ) ) ?>
    </div>

    <div class="bookly-box">
        <div class="bookly-btn bookly-add-item bookly-inline-block">
            <?php $editable::renderString( array( 'bookly_l10n_button_book_more', ) ) ?>
        </div>
    </div>

    <div class="bookly-cart-step">
        <div class="bookly-cart bookly-box">
            <table>
                <thead class="bookly-desktop-version">
                    <tr>
                        <th class="bookly-js-option bookly_l10n_label_service"><?php echo esc_html( get_option( 'bookly_l10n_label_service' ) ) ?></th>
                        <th><?php _e( 'Date', 'bookly' ) ?></th>
                        <th><?php _e( 'Time', 'bookly' ) ?></th>
                        <th class="bookly-js-option bookly_l10n_label_employee"><?php echo esc_html( get_option( 'bookly_l10n_label_employee' ) ) ?></th>
                        <th><?php _e( 'Price', 'bookly' ) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="bookly-desktop-version">
                    <tr>
                        <td>Crown and Bridge</td>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatDate( '+2 days' ) ?></td>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatTime( 28800 ) ?></td>
                        <td>Nick Knight</td>
                        <td><?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?></td>
                        <td>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Edit', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-edit"></i></button>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Remove', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-drop"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>Teeth Whitening</td>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatDate( '+3 days' ) ?></td>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatTime( 57600 ) ?></td>
                        <td>Wayne Turner</td>
                        <td><?php echo \Bookly\Lib\Utils\Common::formatPrice( 400 ) ?></td>
                        <td>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Edit', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-edit"></i></button>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Remove', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-drop"></i></button>
                        </td>
                    </tr>
                </tbody>
                <tbody class="bookly-mobile-version">
                    <tr>
                        <th class="bookly-js-option bookly_l10n_label_service"><?php echo esc_html( get_option( 'bookly_l10n_label_service' ) ) ?></th>
                        <td>Crown and Bridge</td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Date', 'bookly' ) ?></th>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatDate( '+2 days' ) ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Time', 'bookly' ) ?></th>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatTime( 28800 ) ?></td>
                    </tr>
                    <tr>
                        <th class="bookly-js-option bookly_l10n_label_employee"><?php echo esc_html( get_option( 'bookly_l10n_label_employee' ) ) ?></th>
                        <td>Nick Knight</td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Price', 'bookly' ) ?></th>
                        <td><?php echo \Bookly\Lib\Utils\Common::formatPrice( 350 ) ?></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Edit', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-edit"></i></button>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Remove', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-drop"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <th class="bookly-js-option bookly_l10n_label_service"><?php echo esc_html( get_option( 'bookly_l10n_label_service' ) ) ?></th>
                        <td>Teeth Whitening</td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Date', 'bookly' ) ?></th>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatDate( '+3 days' ) ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Time', 'bookly' ) ?></th>
                        <td><?php echo \Bookly\Lib\Utils\DateTime::formatTime( 57600 ) ?></td>
                    </tr>
                    <tr>
                        <th class="bookly-js-option bookly_l10n_label_employee"><?php echo esc_html( get_option( 'bookly_l10n_label_employee' ) ) ?></th>
                        <td>Wayne Turner</td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Price', 'bookly' ) ?></th>
                        <td><?php echo \Bookly\Lib\Utils\Common::formatPrice( 400 ) ?></td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Edit', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-edit"></i></button>
                            <button class="bookly-round" title="<?php esc_attr_e( 'Remove', 'bookly' ) ?>"><i class="bookly-icon-sm bookly-icon-drop"></i></button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bookly-desktop-version">
                    <tr>
                        <td colspan="4"><strong><?php _e( 'Total', 'bookly' ) ?>:</strong></td>
                        <td><strong class="bookly-js-total-price"><?php echo \Bookly\Lib\Utils\Common::formatPrice( 750 ) ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
                <tfoot class="bookly-mobile-version">
                    <tr>
                        <th><strong><?php _e( 'Total', 'bookly' ) ?>:</strong></th>
                        <td><strong class="bookly-js-total-price"><?php echo \Bookly\Lib\Utils\Common::formatPrice( 750 ) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php \Bookly\Lib\Proxy\RecurringAppointments::renderAppearanceEditableInfoMessage() ?>

    <div class="bookly-box bookly-nav-steps">
        <div class="bookly-back-step bookly-js-back-step bookly-btn">
            <?php $editable::renderString( array( 'bookly_l10n_button_back' ) ) ?>
        </div>
        <div class="bookly-next-step bookly-js-next-step bookly-btn">
            <?php $editable::renderString( array( 'bookly_l10n_step_cart_button_next' ) ) ?>
        </div>
    </div>
</div>
