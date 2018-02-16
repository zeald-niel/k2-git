jQuery(function ($) {
    $('.board-backdrop').on('click', '[data-trigger]', function () {
        switch ($(this).data('trigger')) {
            case 'close':
                $.get(ajaxurl, {action: 'bookly_grace_hide_admin_notice'});
                $(this).closest('.board-backdrop').remove();
                break;
            case 'request_code':
                var $body = $(this).closest('.bookly-board-body');
                $.post(ajaxurl, {action: 'bookly_verify_purchase_code_form'}, function (response) {
                    $body.html(response.data.html);
                    var $proceed_link = $body.find('.bookly-verified');
                    if ($body.find('#Bookly').length == 0) {
                        $proceed_link.show();
                    }
                    $('.purchase-code').on('keyup', function (e) {
                        var $input = $(this);
                        if ($input.val().length == 36 && e.which > 47 /* key 0 */) {
                            var $group = $input.closest('.has-feedback');
                            $group.removeClass('has-warning has-error').addClass('has-ajax');
                            $.post(ajaxurl, {action: 'bookly_verify_purchase_code', plugin: $input.attr('id'), purchase_code: $input.val()}, function (response) {
                                $group.removeClass('has-ajax');
                                if (response.success) {
                                    $group.addClass('has-success');
                                    $all_valid = true;
                                    $('.has-feedback').each(function (index, elem) {
                                        if (!$(elem).hasClass('has-success')) {
                                            $all_valid = false;
                                        }
                                    });
                                    if ($all_valid) {
                                        $.post(ajaxurl, {action: 'bookly_verification_succeeded'}, function (response) {
                                            $body.closest('.board-backdrop').html(response.data.html);
                                        });
                                    }
                                    if ($input.attr('id') == 'Bookly') {
                                        $proceed_link.show();
                                    }
                                } else {
                                    if (response.data.message) {
                                        booklyAlert({error: [response.data.message]});
                                    }
                                    $group.addClass('has-error');
                                }
                            });
                        }
                    });
                });
                break;
        }
    });

});
