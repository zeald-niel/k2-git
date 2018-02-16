<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Lib\Entities\CustomerAppointment;
?>
<div ng-app="appointmentDialog" ng-controller="appointmentDialogCtrl">
    <div id=bookly-appointment-dialog class="modal fade" tabindex=-1 role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form ng-submit=processForm()>
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="modal-title h2"><?php _e( 'New appointment', 'bookly' ) ?></div>
                    </div>
                    <div ng-show=loading class="modal-body">
                        <div class="bookly-loading"></div>
                    </div>
                    <div ng-hide="loading || form.screen != 'main'" class="modal-body">
                        <div class=form-group>
                            <label for="bookly-provider"><?php _e( 'Provider', 'bookly' ) ?></label>
                            <select id="bookly-provider" class="field form-control" ng-model="form.staff" ng-options="s.full_name for s in dataSource.data.staff" ng-change="onStaffChange()"></select>
                        </div>

                        <div class=form-group>
                            <label for="bookly-service"><?php _e( 'Service', 'bookly' ) ?></label>
                            <select id="bookly-service" class="field form-control" ng-model="form.service"
                                    ng-options="s.title for s in form.staff.services" ng-change="onServiceChange()">
                                <option value=""><?php _e( '-- Select a service --', 'bookly' ) ?></option>
                            </select>
                            <p class="text-danger" my-slide-up="errors.service_required">
                                <?php _e( 'Please select a service', 'bookly' ) ?>
                            </p>
                        </div>

                        <div class=form-group>
                            <div class="row">
                                <div class="col-sm-4">
                                    <label for="bookly-date"><?php _e( 'Date', 'bookly' ) ?></label>
                                    <input id="bookly-date" class="form-control" type=text
                                           ng-model=form.date ui-date="dateOptions" autocomplete="off"
                                           ng-change=onDateChange()>
                                </div>
                                <div class="col-sm-8">
                                    <div ng-hide="form.service.duration >= 86400">
                                        <label for="bookly-period"><?php _e( 'Period', 'bookly' ) ?></label>
                                        <div class="bookly-flexbox">
                                            <div class="bookly-flex-cell">
                                                <select id="bookly-period" class="form-control" ng-model=form.start_time
                                                        ng-options="t.title for t in dataSource.data.start_time"
                                                        ng-change=onStartTimeChange()></select>
                                            </div>
                                            <div class="bookly-flex-cell" style="width: 4%">
                                                <div class="bookly-margin-horizontal-md"><?php _e( 'to', 'bookly' ) ?></div>
                                            </div>
                                            <div class="bookly-flex-cell" style="width: 48%">
                                                <select class="form-control" ng-model=form.end_time
                                                        ng-options="t.title for t in dataSource.getDataForEndTime()"
                                                        ng-change=onEndTimeChange()></select>
                                            </div>
                                        </div>
                                        <p class="text-success" my-slide-up=errors.date_interval_warning id=date_interval_warning_msg>
                                            <?php _e( 'Selected period doesn\'t match service duration', 'bookly' ) ?>
                                        </p>
                                        <p class="text-success" my-slide-up="errors.time_interval" ng-bind="errors.time_interval"></p>
                                    </div>
                                </div>
                                <div class="text-danger col-sm-12" my-slide-up=errors.date_interval_not_available id=date_interval_not_available_msg>
                                    <?php _e( 'The selected period is occupied by another appointment', 'bookly' ) ?>
                                </div>
                            </div>
                        </div>
                        <?php \Bookly\Lib\Proxy\RecurringAppointments::renderRecurringSubForm() ?>
                        <div class=form-group>
                            <label for="bookly-chosen"><?php _e( 'Customers', 'bookly' ) ?></label>
                            <span ng-show="form.service" title="<?php esc_attr_e( 'Selected / maximum', 'bookly' ) ?>">
                                ({{dataSource.getTotalNumberOfPersons()}}/{{form.service.capacity}})
                            </span>
                            <ul class="bookly-flexbox">
                                <li ng-repeat="customer in form.customers" class="bookly-flex-row">
                                    <a ng-click="editCustomerDetails(customer)" title="<?php esc_attr_e( 'Edit booking details', 'bookly' ) ?>" class="bookly-flex-cell bookly-padding-bottom-sm" href>{{customer.name}}</a>
                                    <span class="bookly-flex-cell text-right text-nowrap bookly-padding-bottom-sm">
                                        <?php \Bookly\Lib\Proxy\Shared::renderAppointmentDialogCustomerList() ?>
                                        <span class="dropdown">
                                            <button type="button" class="btn btn-sm btn-default bookly-margin-left-xs" data-toggle="dropdown" popover="<?php esc_attr_e( 'Status', 'bookly' ) ?>: {{statusToString(customer.status)}}">
                                                <span ng-class="{'dashicons': true, 'dashicons-clock': customer.status == 'pending', 'dashicons-yes': customer.status == 'approved', 'dashicons-no': customer.status == 'cancelled', 'dashicons-dismiss': customer.status == 'rejected'}"></span>
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a href ng-click="customer.status = 'pending'">
                                                        <span class="dashicons dashicons-clock"></span>
                                                        <?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_PENDING ) ) ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href ng-click="customer.status = 'approved'">
                                                        <span class="dashicons dashicons-yes"></span>
                                                        <?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_APPROVED ) ) ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href ng-click="customer.status = 'cancelled'">
                                                        <span class="dashicons dashicons-no"></span>
                                                        <?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_CANCELLED ) ) ?>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href ng-click="customer.status = 'rejected'">
                                                        <span class="dashicons dashicons-dismiss"></span>
                                                        <?php echo esc_html( CustomerAppointment::statusToString( CustomerAppointment::STATUS_REJECTED ) ) ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-default bookly-margin-left-xs" data-toggle="modal" href="#bookly-payment-details-modal" data-payment_id="{{customer.payment_id}}" ng-show="customer.payment_id" popover="<?php esc_attr_e( 'Payment', 'bookly' ) ?>: {{customer.payment_title}}">
                                            <span ng-class="{'bookly-js-toggle-popover dashicons': true, 'dashicons-thumbs-up': customer.payment_type == 'full', 'dashicons-warning': customer.payment_type == 'partial'}"></span>
                                        </button>
                                        <span class="btn btn-sm btn-default disabled bookly-margin-left-xs" style="opacity:1;cursor:default;"><i class="glyphicon glyphicon-user"></i>&times;{{customer.number_of_persons}}</span>
                                        <a ng-click="removeCustomer(customer)" class="dashicons dashicons-trash text-danger bookly-vertical-middle" href="#"
                                           popover="<?php esc_attr_e( 'Remove customer', 'bookly' ) ?>"></a>
                                    </span>
                                </li>
                            </ul>

                            <div ng-show="!form.service || dataSource.getTotalNumberOfNotCancelledPersons() < form.service.capacity">
                                <div class="form-group">
                                    <div class="input-group">
                                        <select id="bookly-chosen" multiple data-placeholder="<?php esc_attr_e( '-- Search customers --', 'bookly' ) ?>"
                                                class="field chzn-select form-control" chosen="dataSource.data.customers"
                                                ng-model="form.customers" ng-options="c.name for c in dataSource.data.customers">
                                        </select>
                                        <span class="input-group-btn">
                                            <a href="#bookly-customer-dialog" class="btn btn-success" data-toggle="modal">
                                                <i class="glyphicon glyphicon-plus"></i>
                                                <?php _e( 'New customer', 'bookly' ) ?>
                                            </a>
                                        </span>
                                    </div>
                                    <p class="text-danger" my-slide-up="errors.customers_required">
                                        <?php _e( 'Please select a customer', 'bookly' ) ?>
                                    </p>
                                </div>
                            </div>
                            <p class="text-danger" my-slide-up="errors.overflow_capacity" ng-bind="errors.overflow_capacity"></p>
                        </div>

                        <div class=form-group>
                            <label for="bookly-notification"><?php _e( 'Send notifications', 'bookly' ) ?></label>
                            <p class="help-block"><?php _e( 'If email or SMS notifications are enabled and you want customers or staff member to be notified about this appointment after saving, select appropriate option before clicking Save. With "If status changed" the notifications are sent to those customers whose status has just been changed. With "To all customers" the notifications are sent to everyone in the list.', 'bookly' ) ?></p>
                            <select class="form-control" style="margin-top: 0" ng-model=form.notification id="bookly-notification" ng-init="form.notification = '<?php echo get_user_meta( get_current_user_id(), 'bookly_appointment_form_send_notifications', true ) ?>' || 'no'" >
                                <option value="no"><?php _e( 'Don\'t send', 'bookly' ) ?></option>
                                <option value="changed_status"><?php _e( 'If status changed', 'bookly' ) ?></option>
                                <option value="all"><?php _e( 'To all customers', 'bookly' ) ?></option>
                            </select>
                        </div>

                        <div class=form-group>
                            <label for="bookly-internal-note"><?php _e( 'Internal note', 'bookly' ) ?></label>
                            <textarea class="form-control" ng-model=form.internal_note id="bookly-internal-note"></textarea>
                        </div>
                    </div>
                    <?php \Bookly\Lib\Proxy\RecurringAppointments::renderSchedule() ?>
                    <div class="modal-footer">
                        <div ng-hide=loading>
                            <?php \Bookly\Lib\Proxy\Shared::renderAppointmentDialogFooter() ?>
                            <?php \Bookly\Lib\Utils\Common::customButton( 'bookly-save', 'btn-lg btn-success', null, array( 'ng-hide' => 'form.repeat.enabled && form.screen == \'main\'', 'ng-disabled' => 'form.repeat.enabled && schIsScheduleEmpty()' ), 'submit' ) ?>
                            <?php \Bookly\Lib\Utils\Common::customButton( null, 'btn-lg btn-default', __( 'Cancel', 'bookly' ), array( 'ng-click' => 'closeDialog()', 'data-dismiss' => 'modal' ) ) ?>
                        </div>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <div customer-dialog=createCustomer(customer)></div>
    <div payment-details-dialog="completePayment(payment_id, payment_title)"></div>

    <?php $this->render( '_customer_details_dialog', compact( 'custom_fields' ) ) ?>
    <?php \Bookly\Backend\Modules\Customers\Components::getInstance()->renderCustomerDialog() ?>
    <?php \Bookly\Backend\Modules\Payments\Components::getInstance()->renderPaymentDetailsDialog() ?>
</div>
