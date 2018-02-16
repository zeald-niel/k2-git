jQuery(function($) {

    var
        $coupons_list       = $('#bookly-coupons-list'),
        $check_all_button   = $('#bookly-check-all'),
        $coupon_modal       = $('#bookly-coupon-modal'),
        $coupon_new_title   = $('#bookly-new-coupon-title'),
        $coupon_edit_title  = $('#bookly-edit-coupon-title'),
        $coupon_code        = $('#bookly-coupon-code'),
        $coupon_discount    = $('#bookly-coupon-discount'),
        $coupon_deduction   = $('#bookly-coupon-deduction'),
        $coupon_usage_limit = $('#bookly-coupon-usage-limit'),
        $save_button        = $('#bookly-coupon-save'),
        $add_button         = $('#bookly-add'),
        $delete_button      = $('#bookly-delete'),
        $service_counter    = $('#bookly-entity-counter'),
        $service_list       = $('.bookly-entity-selector'),
        $service_check_all  = $('#bookly-check-all-entities'),
        row
        ;

    /**
     * Init DataTables.
     */
    var dt = $coupons_list.DataTable({
        order: [[ 0, "asc" ]],
        paging: false,
        info: false,
        searching: false,
        processing: true,
        responsive: true,
        ajax: {
            url: ajaxurl,
            data: { action: 'bookly_get_coupons' }
        },
        columns: [
            { data: "code" },
            { data: "discount" },
            { data: "deduction" },
            {
                data: 'service_ids',
                render: function (data, type, row, meta) {
                    if (data.length == 0) {
                        return BooklyL10n.selector.nothing_selected;
                    } else if (data.length == 1) {
                        return BooklyL10n.selector.collection[data[0]].title;
                    } else {
                        if (data.length == Object.keys(BooklyL10n.selector.collection).length) {
                            return BooklyL10n.selector.all_selected;
                        } else {
                            return data.length + '/' + Object.keys(BooklyL10n.selector.collection).length;
                        }
                    }
                }
            },
            { data: "usage_limit" },
            { data: "used" },
            {
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#bookly-coupon-modal"><i class="glyphicon glyphicon-edit"></i> ' + BooklyL10n.edit + '</button>';
                }
            },
            {
                responsivePriority: 1,
                orderable: false,
                searchable: false,
                render: function ( data, type, row, meta ) {
                    return '<input type="checkbox" value="' + row.id + '">';
                }
            }
        ],
        language: {
            zeroRecords: BooklyL10n.zeroRecords,
            processing:  BooklyL10n.processing
        }
    });

    /**
     * Select all coupons.
     */
    $check_all_button.on('change', function () {
        $coupons_list.find('tbody input:checkbox').prop('checked', this.checked);
    });

    /**
     * On coupon select.
     */
    $coupons_list.on('change', 'tbody input:checkbox', function () {
        $check_all_button.prop('checked', $coupons_list.find('tbody input:not(:checked)').length == 0);
    });

    /**
     * Edit coupon.
     */
    $coupons_list.on('click', 'button', function () {
        row = dt.row($(this).closest('td'));
    });

    /**
     * New coupon.
     */
    $add_button.on('click', function () {
        row = null;
    });

    /**
     * On show modal.
     */
    $coupon_modal.on('show.bs.modal', function () {
        var data = {};
        if (row) {
            data = row.data();
            $coupon_code.val(data.code);
            $coupon_discount.val(data.discount);
            $coupon_deduction.val(data.deduction);
            $coupon_usage_limit.val(data.usage_limit);
            $coupon_edit_title.show();
            $coupon_new_title.hide();
        } else {
            $coupon_code.val('');
            $coupon_discount.val('0');
            $coupon_deduction.val('0');
            $coupon_usage_limit.val('1');
            $coupon_edit_title.hide();
            $coupon_new_title.show();
        }
        $service_list.find('li:gt(0)').remove();
        $.each(BooklyL10n.selector.collection, function (id, service) {
            $service_list.append('<li><a class="checkbox" href="javascript:void(0)"><label><input type="checkbox" name="service_ids[]" class="bookly-js-check-entity" value="' + service.id + '"' + ($.inArray(service.id, data.service_ids) != -1 ? ' checked' : '') + ' />' + service.title + '</label></a></li>');
        });
        if (!row) {
            $service_check_all.prop('checked', true);
            $service_check_all.trigger('change');
        }
        var event = jQuery.Event('change');
        var $checkboxes = $coupon_modal.find('input:checkbox');
        if ($checkboxes.length > 1) {
            event.target = $checkboxes[1];
        } else {
            event.target = $checkboxes[0];
        }
        $coupon_modal.trigger(event);
    });

    /**
     * On select staff.
     */
    $coupon_modal.on('change', 'input:checkbox', function() {
        if (this.id == 'bookly-check-all-entities') {
            $service_list.find('.bookly-js-check-entity').prop('checked', this.checked);
        } else {
            $service_check_all.prop('checked', $service_list.find('.bookly-js-check-entity:not(:checked)').length == 0);
        }

        var $checked = $service_list.find('.bookly-js-check-entity:checked');
        if ($checked.length == 0) {
            $service_counter.text(BooklyL10n.selector.nothing_selected);
        } else if ($checked.length == 1) {
            $service_counter.text(BooklyL10n.selector.collection[$checked.val()].title);
        } else {
            if ($checked.length == Object.keys(BooklyL10n.selector.collection).length) {
                $service_counter.text(BooklyL10n.selector.all_selected);
            } else {
                $service_counter.text($checked.length + '/' + Object.keys(BooklyL10n.selector.collection).length);
            }
        }
    });

    /**
     * Save coupon.
     */
    $save_button.on('click', function (e) {
        e.preventDefault();
        var $form = $(this).parents('form');
        var data = $form.serializeArray();
        data.push({name: 'action', value: 'bookly_save_coupon'});
        if (row){
            data.push({name: 'id', value: row.data().id});
        }
        var ladda = Ladda.create(this, {timeout: 2000});
        ladda.start();
        $.ajax({
            url  : ajaxurl,
            type : 'POST',
            data : data,
            dataType : 'json',
            success  : function(response) {
                if (response.success) {
                    if (row) {
                        row.data(response.data).draw();
                    } else {
                        dt.row.add(response.data).draw();
                    }
                    $coupon_modal.modal('hide');
                } else {
                    alert(response.data.message);
                }
                ladda.stop();
            }
        });
    });

    /**
     * Delete coupons.
     */
    $delete_button.on('click', function () {
        if (confirm(BooklyL10n.are_you_sure)) {
            var ladda = Ladda.create(this);
            ladda.start();

            var data = [];
            var $checkboxes = $coupons_list.find('tbody input:checked');
            $checkboxes.each(function () {
                data.push(this.value);
            });

            $.ajax({
                url  : ajaxurl,
                type : 'POST',
                data : {
                    action : 'bookly_delete_coupons',
                    data   : data
                },
                dataType : 'json',
                success  : function(response) {
                    if (response.success) {
                        dt.rows($checkboxes.closest('td')).remove().draw();
                    } else {
                        alert(response.data.message);
                    }
                    ladda.stop();
                }
            });
        }
    });
});
