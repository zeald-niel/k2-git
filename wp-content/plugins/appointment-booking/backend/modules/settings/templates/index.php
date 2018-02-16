<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="bookly-tbs" class="wrap">
    <div class="bookly-tbs-body">
        <div class="page-header text-right clearfix">
            <div class="bookly-page-title">
                <?php _e( 'Settings', 'bookly' ) ?>
            </div>
            <?php \Bookly\Backend\Modules\Support\Components::getInstance()->renderButtons( '' ) ?>
        </div>
        <div class="row">
            <div id="bookly-sidebar" class="col-sm-4">
                <ul class="bookly-nav" role="tablist">
                    <li class="bookly-nav-item" data-target="#bookly_settings_general" data-toggle="tab">
                        <?php _e( 'General', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_company" data-toggle="tab">
                        <?php _e( 'Company', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_customers" data-toggle="tab">
                        <?php _e( 'Customers', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_google_calendar" data-toggle="tab">
                        <?php _e( 'Google Calendar', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_woo_commerce" data-toggle="tab">
                        WooCommerce
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_cart" data-toggle="tab">
                        <?php _e( 'Cart', 'bookly' ) ?>
                    </li>
                    <?php \Bookly\Lib\Proxy\Shared::renderSettingsMenu() ?>
                    <li class="bookly-nav-item" data-target="#bookly_settings_payments" data-toggle="tab">
                        <?php _e( 'Payments', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_business_hours" data-toggle="tab">
                        <?php _e( 'Business Hours', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_holidays" data-toggle="tab">
                        <?php _e( 'Holidays', 'bookly' ) ?>
                    </li>
                    <li class="bookly-nav-item" data-target="#bookly_settings_purchase_code" data-toggle="tab">
                        <?php _e( 'Purchase Code', 'bookly' ) ?>
                    </li>
                </ul>
            </div>

            <div id="bookly_settings_controls" class="col-sm-8">
                <div class="panel panel-default bookly-main">
                    <div class="panel-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="bookly_settings_general">
                                <?php include '_generalForm.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_company">
                                <?php include '_companyForm.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_customers">
                                <?php include '_customers.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_google_calendar">
                                <?php include '_googleCalendarForm.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_woo_commerce">
                                <?php include '_woocommerce.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_cart">
                                <?php include '_cartForm.php' ?>
                            </div>
                            <?php \Bookly\Lib\Proxy\Shared::renderSettingsForm() ?>
                            <div class="tab-pane" id="bookly_settings_payments">
                                <?php include '_paymentsForm.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_business_hours">
                                <?php include '_hoursForm.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_holidays">
                                <?php include '_holidaysForm.php' ?>
                            </div>
                            <div class="tab-pane" id="bookly_settings_purchase_code">
                                <?php include '_purchaseCodeForm.php' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>