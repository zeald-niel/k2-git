jQuery(function ($) {

    var Details = function($container, options) {
        var obj  = this;
        jQuery.extend(obj.options, options);

        if (Object.keys(obj.options.get_details).length === 0) {
            // backend united edit & details in one request.
            initDetails($container);
        } else {
            // get details content.
            $container.html('<div class="bookly-loading"></div>');
            $.ajax({
                url         : ajaxurl,
                data        : obj.options.get_details,
                dataType    : 'json',
                xhrFields   : { withCredentials: true },
                crossDomain : 'withCredentials' in new XMLHttpRequest(),
                success     : function (response) {
                    $container.html(response.data.html);
                    initDetails($container);
                }
            });
        }

        function initDetails($container) {
            var $staff_full_name = $('#bookly-full-name', $container),
                $staff_wp_user = $('#bookly-wp-user', $container),
                $staff_email   = $('#bookly-email', $container),
                $staff_phone   = $('#bookly-phone', $container);

            if (obj.options.intlTelInput.enabled) {
                $staff_phone.intlTelInput({
                    preferredCountries: [obj.options.intlTelInput.country],
                    defaultCountry: obj.options.intlTelInput.country,
                    geoIpLookup: function (callback) {
                        $.ajax({
                            url        : ajaxurl,
                            data       : {action: 'bookly_ip_info'},
                            dataType   : 'json',
                            xhrFields  : {withCredentials: true},
                            crossDomain: 'withCredentials' in new XMLHttpRequest(),
                            success    : function (response) {
                                var countryCode = (response && response.country) ? resp.country : '';
                                callback(countryCode);
                            }
                        });
                    },
                    utilsScript: obj.options.intlTelInput.utils
                });
            }

            $staff_wp_user.on('change', function () {
                if (this.value) {
                    $staff_full_name.val($staff_wp_user.find(':selected').text());
                    $staff_email.val($staff_wp_user.find(':selected').data('email'));
                }
            });

            $('input.bookly-js-all-locations, input.bookly-location', $container).on('change', function () {
                var $panel = $(this).parents('.locations-row');
                if ($(this).hasClass('bookly-js-all-locations')) {
                    $panel.find('.bookly-location').prop('checked', $(this).prop('checked'));
                } else {
                    $panel.find('.bookly-js-all-locations').prop('checked', $panel.find('.bookly-location:not(:checked)').length == 0);
                }
                updateLocationsButton($panel);
            });

            function updateLocationsButton($panel) {
                var locations_checked = $panel.find('.bookly-location:checked').length;
                if (locations_checked == 0) {
                    $panel.find('.bookly-locations-count').text(obj.options.l10n.selector.nothing_selected);
                } else if (locations_checked == 1) {
                    $panel.find('.bookly-locations-count').text($panel.find('.bookly-location:checked').data('location_name'));
                } else {
                    if (locations_checked == $panel.find('.bookly-location').length) {
                        $panel.find('.bookly-locations-count').text(obj.options.l10n.selector.all_selected);
                    } else {
                        $panel.find('.bookly-locations-count').text(locations_checked + '/' + $panel.find('.bookly-location').length);
                    }
                }
            }

            updateLocationsButton($('.locations-row'));
        }

    };

    Details.prototype.options = {
        intlTelInput: {},
        get_details : {
            action: 'bookly_get_staff_details',
            id    : -1,
            csrf_token: ''
        },
        l10n        : {}
    };

    window.BooklyStaffDetails = Details;
});