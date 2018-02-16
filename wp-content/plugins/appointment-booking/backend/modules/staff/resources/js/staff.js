jQuery(function($) {
    var $staff_list       = $('#bookly-staff-list'),
        $new_form         = $('#bookly-new-staff'),
        $wp_user_select   = $('#bookly-new-staff-wpuser'),
        $name_input       = $('#bookly-new-staff-fullname'),
        $staff_count      = $('#bookly-staff-count'),
        $edit_form        = $('#bookly-container-edit-staff'),
        options           = {
            get_details: {},
            intlTelInput: BooklyL10n.intlTelInput,
            l10n: BooklyL10n
        };

    function saveNewForm() {
        var data = {
            action     : 'bookly_create_staff',
            wp_user_id : $wp_user_select.val(),
            full_name  : $name_input.val(),
            csrf_token : BooklyL10n.csrf_token
        };

        if (validateForm($new_form)) {
            $.post(ajaxurl, data, function (response) {
                if (response.success) {
                    $staff_list.append(response.data.html);
                    $staff_count.text($staff_list.find('[data-staff-id]').length);
                    $staff_list.find('[data-staff-id]:last').trigger('click');
                }
            });
            $('#bookly-newstaff-member').popover('hide');
            if ($wp_user_select.val()) {
                $wp_user_select.find('option:selected').remove();
                $wp_user_select.val('');
            }
            $name_input.val('');
        }
    }

    // Save new staff on enter press
    $name_input.on('keypress', function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) {
            saveNewForm();
        }
    });

    // Close new staff form on esc
    $new_form.on('keypress', function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 27) {
            $('#bookly-newstaff-member').popover('hide');
        }
    });

    $staff_list.on('click', '.bookly-js-handle', function (e) {
        e.stopPropagation();
    });

    $edit_form
        .on('click', '.bookly-pretty-indicator', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var frame = wp.media({
                library: {type: 'image'},
                multiple: false
            });
            frame.on('select', function () {
                var selection = frame.state().get('selection').toJSON(),
                    img_src;
                if (selection.length) {
                    if (selection[0].sizes['thumbnail'] !== undefined) {
                        img_src = selection[0].sizes['thumbnail'].url;
                    } else {
                        img_src = selection[0].url;
                    }
                    $edit_form.find('[name=attachment_id]').val(selection[0].id);
                    $('#bookly-js-staff-avatar').find('.bookly-js-image').css({'background-image': 'url(' + img_src + ')', 'background-size': 'cover'});
                    $('.bookly-thumb-delete').show();
                    $(this).hide();
                }
            });

            frame.open();
        });

    /**
     * Load staff profile on click on staff in the list.
     */
    $staff_list.on('click', 'li', function() {
        var $this = $(this);
        // Mark selected element as active
        $staff_list.find('.active').removeClass('active');
        $this.addClass('active');

        var staff_id = $this.data('staff-id'),
            active_tab_id = $('.nav .active a').attr('id');
        $edit_form.html('<div class="bookly-loading"></div>');
        $.get(ajaxurl, {action: 'bookly_edit_staff', id: staff_id, csrf_token: BooklyL10n.csrf_token}, function (response) {
            $edit_form.html(response.data.html.edit);
            booklyAlert(response.data.alert);
            var $details_container   = $('#bookly-details-container', $edit_form),
                $loading_indicator   = $('.bookly-loading', $edit_form),
                $services_container  = $('#bookly-services-container', $edit_form),
                $schedule_container  = $('#bookly-schedule-container', $edit_form),
                $holidays_container  = $('#bookly-holidays-container', $edit_form)
            ;
            $details_container.html(response.data.html.details);

            new BooklyStaffDetails($details_container, options);

            // Delete staff member.
            $('#bookly-staff-delete', $edit_form).on('click', function (e) {
                e.preventDefault();
                if (confirm(BooklyL10n.are_you_sure)) {
                    $edit_form.html('<div class="bookly-loading"></div>');
                    $.post(ajaxurl, {action: 'bookly_delete_staff', id: staff_id, csrf_token: BooklyL10n.csrf_token}, function (response) {
                        $edit_form.html('');
                        $wp_user_select.children(':not(:first)').remove();
                        $.each(response.data.wp_users, function (index, wp_user) {
                            var $option = $('<option>')
                                .data('email', wp_user.user_email)
                                .val(wp_user.ID)
                                .text(wp_user.display_name);
                            $wp_user_select.append($option);
                        });
                        $('#bookly-staff-' + staff_id).remove();
                        $staff_count.text($staff_list.children().length);
                        $staff_list.children(':first').click();
                    });
                }
            });

            // Delete staff avatar
            $('.bookly-thumb-delete', $edit_form).on('click', function () {
                var $thumb = $(this).parents('.bookly-js-image');
                $.post(ajaxurl, {action: 'bookly_delete_staff_avatar', id: staff_id, csrf_token: BooklyL10n.csrf_token}, function (response) {
                    if (response.success) {
                        $thumb.attr('style', '');
                        $edit_form.find('[name=attachment_id]').val('');
                    }
                });
            });

            // Save staff member details.
            $('#bookly-details-save', $edit_form).on('click',function(e){
                e.preventDefault();
                var $form = $(this).closest('form'),
                    data  = $form.serializeArray(),
                    ladda = Ladda.create(this),
                    $staff_phone = $('#bookly-phone',$form),
                    phone;
                try {
                    phone = BooklyL10n.intlTelInput.enabled ? $staff_phone.intlTelInput('getNumber') : $staff_phone.val();
                    if (phone == '') {
                        phone = $staff_phone.val();
                    }
                } catch (error) {  // In case when intlTelInput can't return phone number.
                    phone = $staff_phone.val();
                }
                data.push({name: 'action', value: 'bookly_update_staff'});
                data.push({name: 'phone',  value: phone});
                ladda.start();
                $.post(ajaxurl, data, function (response) {
                    if (response.success) {
                        booklyAlert({success : [BooklyL10n.saved]});
                        // Update staff name throughout the page.
                        $('.bookly-js-staff-name-' + staff_id).text($form.find('#bookly-full-name').val());
                        // Update wp users in new staff form.
                        $wp_user_select.children(':not(:first)').remove();
                        $.each(response.data.wp_users, function (index, wp_user) {
                            var $option = $('<option>')
                                .data('email', wp_user.user_email)
                                .val(wp_user.ID)
                                .text(wp_user.display_name);
                            $wp_user_select.append($option);
                        });
                    } else {
                        booklyAlert({error : [response.data.error]});
                    }
                    ladda.stop();
                });
            });

            // Open details tab
            $('#bookly-details-tab', $edit_form).on('click', function () {
                $('.tab-pane > div').hide();
                $details_container.show();
            });

            // Open services tab
            $('#bookly-services-tab', $edit_form).on('click', function () {
                $('.tab-pane > div').hide();

                new BooklyStaffServices($services_container, {
                    get_staff_services: {
                        action    : 'bookly_get_staff_services',
                        staff_id  : staff_id,
                        csrf_token: BooklyL10n.csrf_token
                    },
                    l10n: BooklyL10n
                });

                $services_container.show();
            });

            // Open special days tab
            $('#bookly-special-days-tab',$edit_form).on('click', function () {
                $(document.body).trigger( 'special_days.tab_show', [ staff_id, $loading_indicator ] );
            });

            // Open schedule tab
            $('#bookly-schedule-tab', $edit_form).on('click', function () {
                $('.tab-pane > div').hide();
                $schedule_container.show();

                // Loads schedule list
                if (!$schedule_container.children().length) {
                    $loading_indicator.show();
                    $.post(ajaxurl, {action: 'bookly_staff_schedule', id: staff_id, csrf_token: BooklyL10n.csrf_token}, function (response) {
                        // fill in the container
                        $schedule_container.html(response.data.html);




                        // init 'add break' functionality
                        $('.bookly-js-toggle-popover:not(.break-interval)', $schedule_container ).popover({
                            html: true,
                            placement: 'bottom',
                            template: '<div class="popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
                            trigger: 'manual',
                            content: function () {
                                return $($(this).data('popover-content')).html()
                            }
                        }).on('click', function () {
                            $(this).popover('toggle');

                            var $popover      = $(this).next('.popover'),
                                working_start = $popover.closest('.row').find('.working-schedule-start').val(),
                                $break_start  = $popover.find('.break-start'),
                                $break_end    = $popover.find('.break-end'),
                                working_start_time  = working_start.split(':'),
                                working_start_hours = parseInt(working_start_time[0], 10),
                                break_start_hours   = working_start_hours + 1;
                            if (break_start_hours < 10) {
                                break_start_hours = '0' + break_start_hours;
                            }
                            var break_end_hours = working_start_hours + 2;
                            if (break_end_hours < 10) {
                                break_end_hours = '0' + break_end_hours;
                            }
                            var break_end_hours_str   = break_end_hours +   ':' + working_start_time[1] + ':' + working_start_time[2],
                                break_start_hours_str = break_start_hours + ':' + working_start_time[1] + ':' + working_start_time[2];

                            $break_start.val(break_start_hours_str);
                            $break_end.val(break_end_hours_str);

                            hideInaccessibleBreaks($break_start, $break_end);

                            $popover.find('.bookly-popover-close').on('click', function () {
                                $popover.popover('hide');
                            });
                        });

                        $schedule_container
                            // Save Schedule
                            .on('click', '#bookly-schedule-save', function (e) {
                                e.preventDefault();
                                var ladda = Ladda.create(this);
                                ladda.start();
                                var data = {};
                                $('select.working-schedule-start, select.working-schedule-end, input:hidden', $schedule_container).each(function () {
                                    data[this.name] = this.value;
                                });
                                $.post(ajaxurl, $.param(data), function () {
                                    ladda.stop();
                                    booklyAlert({success: [BooklyL10n.saved]});
                                });
                            })
                            // Resets initial schedule values
                            .on('click', '#bookly-schedule-reset', function (e) {
                                e.preventDefault();
                                var ladda = Ladda.create(this);
                                ladda.start();

                                $('.working-schedule-start', $schedule_container).each(function () {
                                    $(this).val($(this).data('default_value'));
                                    $(this).trigger('change');
                                });

                                $('.working-schedule-end', $schedule_container).each(function () {
                                    $(this).val($(this).data('default_value'));
                                });

                                // reset breaks
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {action: 'bookly_reset_breaks', breaks: $(this).data('default-breaks'), csrf_token: BooklyL10n.csrf_token},
                                    dataType: 'json',
                                    success: function (response) {
                                        for (var k in response) {
                                            var $content = $(response[k]);
                                            $('[data-staff_schedule_item_id=' + k + '] .breaks', $schedule_container).html($content);
                                            $content.find('.bookly-intervals-wrapper .delete-break').on('click', function () {
                                                deleteBreak.call(this);
                                            });
                                        }
                                    },
                                    complete: function () {
                                        ladda.stop();
                                    }
                                });
                            })

                            .on('click', '.break-interval', function () {
                                var $button = $(this);
                                $('.popover').popover('hide');
                                var break_id = $button.closest('.bookly-intervals-wrapper').data('break_id');
                                $(this).popover({
                                    html: true,
                                    placement: 'bottom',
                                    template: '<div class="popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
                                    content: function () {
                                        return $('.bookly-js-content-break-' + break_id).html();
                                    },
                                    trigger: 'manual'
                                });

                                $(this).popover('toggle');

                                var $popover = $(this).next('.popover'),
                                    $break_start = $popover.find('.break-start'),
                                    $break_end = $popover.find('.break-end');

                                if ($button.hasClass('break-interval')) {
                                    var interval = $button.html().trim().split(' - ');
                                    rangeTools.setVal($break_start, interval[0]);
                                    rangeTools.setVal($break_end, interval[1]);
                                }

                                hideInaccessibleBreaks($break_start, $break_end, true);

                                $popover.find('.bookly-popover-close').on('click', function () {
                                    $popover.popover('hide');
                                });
                            })

                            .on('click', '.bookly-js-save-break', function (e) {
                                var $table = $(this).closest('.bookly-js-schedule-form'),
                                    $row = $table.parents('.staff-schedule-item-row').first(),
                                    $data = {
                                        action: 'bookly_staff_schedule_handle_break',
                                        staff_schedule_item_id: $row.data('staff_schedule_item_id'),
                                        start_time: $table.find('.break-start > option:selected').val(),
                                        end_time: $table.find('.break-end > option:selected').val(),
                                        working_end: $row.find('.working-schedule-end > option:selected').val(),
                                        working_start: $row.find('.working-schedule-start > option:selected').val(),
                                        csrf_token: BooklyL10n.csrf_token
                                    },
                                    $break_interval_wrapper = $table.parents('.bookly-intervals-wrapper').first(),
                                    ladda = Ladda.create(e.currentTarget);
                                ladda.start();

                                if ($break_interval_wrapper.data('break_id')) {
                                    $data['break_id'] = $break_interval_wrapper.data('break_id');
                                }

                                $.post(ajaxurl, $data, function (response) {
                                        if (response.success) {
                                            if (response['item_content']) {
                                                var $new_break_interval_item = $(response['item_content']);
                                                $new_break_interval_item
                                                    .hide()
                                                    .appendTo($row.find('.breaks-list-content'))
                                                    .fadeIn('slow');
                                                $new_break_interval_item.find('.delete-break').on('click', function () {
                                                    deleteBreak.call(this);
                                                });
                                            } else if (response.data.interval) {
                                                $break_interval_wrapper
                                                    .find('.break-interval')
                                                    .text(response.data.interval);
                                            }
                                            $('.popover').popover('hide');
                                        } else {
                                            booklyAlert({error: [response.data.message]});
                                        }
                                    },
                                    'json'
                                ).always(function () {
                                    ladda.stop()
                                });

                                return false;
                            })

                            .on('click', '.bookly-intervals-wrapper .delete-break', function () {
                                deleteBreak.call(this);
                            })

                            .on('change', '.break-start', function () {
                                var $start = $(this);
                                var $end = $start.parents('.bookly-flexbox').find('.break-end');
                                hideInaccessibleBreaks($start, $end);
                            })

                            .on('change', '.working-schedule-start', function () {
                                var $this = $(this),
                                    $end_select = $this.closest('.bookly-flexbox').find('.working-schedule-end'),
                                    start_time = $this.val();

                                // Hide end time options to keep them within 24 hours after start time.
                                var parts = start_time.split(':');
                                parts[0] = parseInt(parts[0]) + 24;
                                var end_time = parts.join(':');
                                var frag = document.createDocumentFragment();
                                var old_value = $end_select.val();
                                var new_value = null;
                                $('option', $end_select).each(function () {
                                    if (this.value <= start_time || this.value > end_time) {
                                        var span = document.createElement('span');
                                        span.style.display = 'none';
                                        span.appendChild(this.cloneNode(true));
                                        frag.appendChild(span);
                                    } else {
                                        frag.appendChild(this.cloneNode(true));
                                        if (new_value === null || old_value == this.value) {
                                            new_value = this.value;
                                        }
                                    }
                                });
                                $end_select.empty().append(frag).val(new_value);

                                // when the working day is disabled (working start time is set to 'OFF')
                                // hide all the elements inside the row
                                if (!$this.val()) {
                                    $this.closest('.row').find('.bookly-hide-on-off').hide();
                                } else {
                                    $this.closest('.row').find('.bookly-hide-on-off').show();
                                }
                            });
                        $('.working-schedule-start', $schedule_container).trigger('change');
                        $('.break-start',$schedule_container).trigger('change');

                        $loading_indicator.hide();
                    });
                }
            });

            // Open holiday tab
            $('#bookly-holidays-tab').on('click', function () {
                $('.tab-pane > div').hide();
                $holidays_container.show();

                if (!$holidays_container.children().length) {
                    $loading_indicator.show();
                    $holidays_container.load(ajaxurl, {action: 'bookly_staff_holidays', id: staff_id, csrf_token : BooklyL10n.csrf_token}, function(){ $loading_indicator.hide(); });
                }
            });


            function hideInaccessibleBreaks( $start, $end, force_keep_values ) {
                var $row           = $start.closest('.row'),
                    $working_start = $row.find('.working-schedule-start'),
                    $working_end   = $row.find('.working-schedule-end'),
                    frag1          = document.createDocumentFragment(),
                    frag2          = document.createDocumentFragment(),
                    old_value      = $start.val(),
                    new_value      = null;

                $('option', $start).each(function () {
                    if ((this.value < $working_start.val() || this.value >= $working_end.val()) && (!force_keep_values || this.value != old_value)) {
                        var span = document.createElement('span');
                        span.style.display = 'none';
                        span.appendChild(this.cloneNode(true));
                        frag1.appendChild(span);
                    } else {
                        frag1.appendChild(this.cloneNode(true));
                        if (new_value === null || old_value == this.value) {
                            new_value = this.value;
                        }
                    }
                });
                $start.empty().append(frag1).val(new_value);

                // Hide end time options with value less than in the start time.
                old_value = $end.val();
                new_value = null;
                $('option', $end).each(function () {
                    if ((this.value <= $start.val() || this.value > $working_end.val()) && (!force_keep_values || this.value != old_value)) {
                        var span = document.createElement('span');
                        span.style.display = 'none';
                        span.appendChild(this.cloneNode(true));
                        frag2.appendChild(span);
                    } else {
                        frag2.appendChild(this.cloneNode(true));
                        if (new_value === null || old_value == this.value) {
                            new_value = this.value;
                        }
                    }
                });
                $end.empty().append(frag2).val(new_value);
            }

            function deleteBreak() {
                var $break_interval_wrapper = $(this).closest('.bookly-intervals-wrapper');
                if (confirm(BooklyL10n.are_you_sure)) {
                    var ladda = Ladda.create(this);
                    ladda.start();
                    $.post(ajaxurl, {action: 'bookly_delete_staff_schedule_break', id: $break_interval_wrapper.data('break_id'), csrf_token: BooklyL10n.csrf_token}, function (response) {
                        if (response.success) {
                            $break_interval_wrapper.remove();
                        }
                    }).always(function () {
                        ladda.stop()
                    });
                }
            }

            $('#' + active_tab_id).click();
        });
    }).find('li.active').click();

    $wp_user_select.on('change', function () {
        if (this.value) {
            $name_input.val($(this).find(':selected').text());
        }
    });

    $staff_list.sortable({
        axis   : 'y',
        handle : '.bookly-js-handle',
        update : function( event, ui ) {
            var data = [];
            $staff_list.children('li').each(function() {
                var $this = $(this);
                var position = $this.data('staff-id');
                data.push(position);
            });
            $.ajax({
                type : 'POST',
                url  : ajaxurl,
                data : {action: 'bookly_update_staff_position', position: data, csrf_token: BooklyL10n.csrf_token}
            });
        }
    });

    $('#bookly-newstaff-member').popover({
        html: true,
        placement: 'bottom',
        template: '<div class="popover" style="width: calc(100% - 20px)" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>',
        content: $new_form.show().detach(),
        trigger: 'manual'
    }).on('click', function () {
        var $button = $(this);
        $button.popover('toggle');
        var $popover = $button.next('.popover');
        $popover.find('.bookly-js-save-form').on('click', function () {
            saveNewForm();
        });
        $popover.find('.bookly-popover-close').on('click', function () {
            $popover.popover('hide');
        });
    }).on('shown.bs.popover', function () {
        var $button = $(this);
        $button.next('.popover').find($name_input).focus();
    }).on('hidden.bs.popover', function (e) {
        //clear input
        $name_input.val('');
        $(e.target).data("bs.popover").inState.click = false;
    });
});