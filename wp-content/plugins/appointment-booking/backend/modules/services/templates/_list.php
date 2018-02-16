<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $time_interval = get_option( 'bookly_gen_time_slot_length' );
?>
<?php if ( ! empty( $service_collection ) ) : ?>
    <div class="panel-group" id="services_list" role="tablist" aria-multiselectable="true">
        <?php foreach ( $service_collection as $service ) : ?>
            <?php $service_id   = $service['id'];
            $assigned_staff_ids = $service['staff_ids'] ? explode( ',', $service['staff_ids'] ) : array();
            $all_staff_selected = count( $assigned_staff_ids ) == count( $staff_collection );
            ?>
            <div class="panel panel-default bookly-js-collapse" data-service-id="<?php echo $service_id ?>">
                <div class="panel-heading" role="tab" id="s_<?php echo $service_id ?>">
                    <div class="row">
                        <div class="col-sm-8 col-xs-10">
                            <div class="bookly-flexbox">
                                <div class="bookly-flex-cell bookly-vertical-middle" style="width: 1%">
                                    <i class="bookly-js-handle bookly-icon bookly-icon-draghandle bookly-margin-right-sm bookly-cursor-move"
                                       title="<?php esc_attr_e( 'Reorder', 'bookly' ) ?>"></i>
                                </div>
                                <div class="bookly-flex-cell bookly-vertical-middle" style="width: 1%">
                                    <span class="bookly-service-color bookly-margin-right-sm bookly-js-service-color"
                                          style="background-color: <?php echo esc_attr( $service['color'] ) ?>">&nbsp;</span>
                                </div>
                                <div class="bookly-flex-cell bookly-vertical-middle">
                                    <a role="button" class="panel-title collapsed bookly-js-service-title" data-toggle="collapse"
                                       data-parent="#services_list" href="#service_<?php echo $service_id ?>"
                                       aria-expanded="false" aria-controls="service_<?php echo $service_id ?>">
                                        <?php echo esc_html( $service['title'] ) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-xs-2">
                            <div class="bookly-flexbox">
                                <div class="bookly-flex-cell bookly-vertical-middle hidden-xs" style="width: 60%">
                                <span class="bookly-js-service-duration">
                                    <?php echo( $service['type'] == \Bookly\Lib\Entities\Service::TYPE_SIMPLE ? \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['duration'] ) : sprintf( _n( '%d service', '%d services', count( json_decode( $service['sub_services'], true ) ), 'bookly' ), count( json_decode( $service['sub_services'], true ) ) ) ) ?>
                                </span>
                                </div>
                                <div class="bookly-flex-cell bookly-vertical-middle hidden-xs" style="width: 30%">
                                <span class="bookly-js-service-price">
                                    <?php echo \Bookly\Lib\Utils\Common::formatPrice( $service['price'] ) ?>
                                </span>
                                </div>
                                <div class="bookly-flex-cell bookly-vertical-middle text-right" style="width: 10%">
                                    <div class="checkbox bookly-margin-remove">
                                        <label><input type="checkbox" class="service-checker" value="<?php echo $service_id ?>"/></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="service_<?php echo $service_id ?>" class="panel-collapse collapse" role="tabpanel" style="height: 0">
                    <div class="panel-body">
                        <form method="post">
                            <?php \Bookly\Lib\Proxy\Shared::renderServiceFormHead( $service ) ?>
                            <div class="row">
                                <div class="col-md-9 col-sm-6">
                                    <div class="form-group">
                                        <label for="title_<?php echo $service_id ?>"><?php _e( 'Title', 'bookly' ) ?></label>
                                        <input name="title" value="<?php echo esc_attr( $service['title'] ) ?>" id="title_<?php echo $service_id ?>" class="form-control" type="text">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label><?php _e( 'Color', 'bookly' ) ?></label>
                                        <div class="bookly-color-picker-wrapper">
                                            <input name="color" value="<?php echo esc_attr( $service['color'] ) ?>" class="bookly-js-color-picker" data-last-color="<?php echo esc_attr( $service['color'] ) ?>" type="text">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="visibility_<?php echo $service_id ?>"><?php _e( 'Visibility', 'bookly' ) ?></label>
                                        <p class="help-block"><?php _e( 'To make service invisible to your customers set the visibility to "Private".', 'bookly' ) ?></p>
                                        <select name="visibility" class="form-control" id="visibility_<?php echo $service_id ?>">
                                            <option value="public" <?php selected( $service['visibility'], 'public' ) ?>><?php _e( 'Public', 'bookly' ) ?></option>
                                            <option value="private" <?php selected( $service['visibility'], 'private' ) ?>><?php _e( 'Private', 'bookly' ) ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="price_<?php echo $service_id ?>"><?php _e( 'Price', 'bookly' ) ?></label>
                                        <input id="price_<?php echo $service_id ?>" class="form-control bookly-question" type="number" min="0" step="1" name="price" value="<?php echo esc_attr( $service['price'] ) ?>">
                                    </div>
                                </div>
                                <div class="col-sm-4 bookly-js-service-simple">
                                    <div class="form-group">
                                        <label for="capacity_<?php echo $service_id ?>"><?php _e( 'Capacity', 'bookly' ) ?></label>
                                        <p class="help-block"><?php _e( 'The maximum number of customers allowed to book the service for the certain time period.', 'bookly' ) ?></p>
                                        <input id="capacity_<?php echo $service_id ?>" class="form-control bookly-question" type="number" min="1" step="1" name="capacity" value="<?php echo esc_attr( $service['capacity'] ) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="bookly-js-service-simple">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="duration_<?php echo $service_id ?>">
                                                <?php _e( 'Duration', 'bookly' ) ?>
                                            </label>
                                            <select id="duration_<?php echo $service_id ?>" class="form-control" name="duration">
                                                <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $service['duration'] / 60 > $j - $time_interval && $service['duration'] / 60 < $j ) : ?><option value="<?php echo esc_attr( $service['duration'] ) ?>" selected><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['duration'] ) ?></option><?php endif ?><option value="<?php echo $j * 60 ?>" <?php selected( $service['duration'], $j * 60 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                                                <?php for ( $j = 86400; $j <= 604800; $j += 86400 ) : ?><option value="<?php echo $j ?>" <?php selected( $service['duration'], $j ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j ) ?></option><?php endfor ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <label for="padding_left_<?php echo $service_id ?>">
                                                <?php _e( 'Padding time (before and after)', 'bookly' ) ?>
                                            </label>
                                            <p class="help-block"><?php _e( 'Set padding time before and/or after an appointment. For example, if you require 15 minutes to prepare for the next appointment then you should set "padding before" to 15 min. If there is an appointment from 8:00 to 9:00 then the next available time slot will be 9:15 rather than 9:00.', 'bookly' ) ?></p>
                                            <div class="row">
                                                <div class="col-xs-6">
                                                    <select id="padding_left_<?php echo $service_id ?>" class="form-control" name="padding_left">
                                                        <option value="0"><?php _e( 'OFF', 'bookly' ) ?></option>
                                                        <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $service['padding_left'] > 0 && $service['padding_left'] / 60 > $j - $time_interval && $service['padding_left'] / 60 < $j ) : ?><option value="<?php echo esc_attr( $service['padding_left'] ) ?>" selected><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['padding_left'] ) ?></option><?php endif ?><option value="<?php echo $j * 60 ?>" <?php selected( $service['padding_left'], $j * 60 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                                                    </select>
                                                </div>
                                                <div class="col-xs-6">
                                                    <select id="padding_right_<?php echo $service_id ?>" class="form-control" name="padding_right">
                                                        <option value="0"><?php _e( 'OFF', 'bookly' ) ?></option>
                                                        <?php for ( $j = $time_interval; $j <= 720; $j += $time_interval ) : ?><?php if ( $service['padding_right'] > 0 && $service['padding_right'] / 60 > $j - $time_interval && $service['padding_right'] / 60 < $j ) : ?><option value="<?php echo esc_attr( $service['padding_right'] ) ?>" selected><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $service['padding_right'] ) ?></option><?php endif ?><option value="<?php echo $j * 60 ?>" <?php selected( $service['padding_right'], $j * 60 ) ?>><?php echo \Bookly\Lib\Utils\DateTime::secondsToInterval( $j * 60 ) ?></option><?php endfor ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="category_<?php echo $service_id ?>"><?php _e( 'Category', 'bookly' ) ?></label>
                                        <select id="category_<?php echo $service_id ?>" class="form-control" name="category_id"><option value="0"><?php _e( 'Uncategorized', 'bookly' ) ?></option>
                                            <?php foreach ( $category_collection as $category ) : ?>
                                                <option value="<?php echo $category['id'] ?>" <?php selected( $category['id'], $service['category_id'] ) ?>><?php echo esc_html( $category['name'] ) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 bookly-js-service-simple">
                                    <div class="form-group">
                                        <label><?php _e( 'Providers', 'bookly' ) ?></label><br>
                                        <div class="btn-group">
                                            <button class="btn btn-default btn-block dropdown-toggle bookly-flexbox" data-toggle="dropdown">
                                                <div class="bookly-flex-cell">
                                                    <i class="dashicons dashicons-admin-users bookly-margin-right-md"></i>
                                                </div>
                                                <div class="bookly-flex-cell text-left" style="width: 100%">
                                                    <span class=bookly-entity-counter><?php echo $service['total_staff'] ?></span>
                                                </div>
                                                <div class="bookly-flex-cell"><div class="bookly-margin-left-md"><span class="caret"></span></div></div>
                                            </button>
                                            <ul class="dropdown-menu bookly-entity-selector">
                                                <li>
                                                    <a class="checkbox" href="javascript:void(0)">
                                                        <label>
                                                            <input type="checkbox" id="service_<?php echo $service_id ?>_all_bookly-js-check-entity" class="bookly-check-all-entities" <?php checked( $all_staff_selected ) ?>>
                                                            <?php _e( 'All staff', 'bookly' ) ?>
                                                        </label>
                                                    </a>
                                                </li>
                                                <?php foreach ( $staff_collection as $i => $staff ) : ?>
                                                    <li>
                                                        <a class="checkbox" href="javascript:void(0)">
                                                            <label>
                                                                <input type="checkbox" name="staff_ids[]" class="bookly-js-check-entity" value="<?php echo $staff['id'] ?>" <?php checked( in_array( $staff['id'], $assigned_staff_ids ) ) ?> data-staff_name="<?php echo esc_attr( $staff['full_name'] ) ?>">
                                                                <?php echo esc_html( $staff['full_name'] ) ?>
                                                            </label>
                                                        </a>
                                                    </li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="info_<?php echo $service_id ?>">
                                    <?php _e( 'Info', 'bookly' ) ?>
                                </label>
                                <p class="help-block">
                                    <?php printf( __( 'This text can be inserted into notifications with %s code.', 'bookly' ), '{service_info}' ) ?>
                                </p>
                                <textarea class="form-control" id="info_<?php echo $service_id ?>" name="info" rows="3" type="text"><?php echo esc_textarea( $service['info'] ) ?></textarea>
                            </div>

                            <?php \Bookly\Lib\Proxy\CompoundServices::renderSubServices( $service, $service_collection,  $service['sub_services'] ) ?>
                            <?php \Bookly\Lib\Proxy\Shared::renderServiceForm( $service ) ?>

                            <div class="panel-footer">
                                <input type="hidden" name="action" value="bookly_update_service">
                                <input type="hidden" name="id" value="<?php echo esc_html( $service_id ) ?>">
                                <input type="hidden" name="update_staff" value="0">
                                <span class="bookly-js-services-error text-danger"></span>
                                <?php \Bookly\Lib\Utils\Common::submitButton( null, 'ajax-service-send' ) ?>
                                <?php \Bookly\Lib\Utils\Common::resetButton( null, 'js-reset' ) ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>
<div style="display: none">
    <?php \Bookly\Lib\Proxy\Shared::renderAfterServiceList( $service_collection ) ?>
</div>