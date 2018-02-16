jQuery(window).load(function () {
    jQuery('#subscribe_thickbox').trigger('click');
    jQuery("#TB_closeWindowButton").click(function () {
        jQuery.post(ajaxurl,
                {
                    'action': 'close_tab'
                });
    });

    // deactivation popup code
    var ual_plugin_admin = jQuery('.documentation_ual_plugin').closest('div').find('.deactivate').find('a');
    ual_plugin_admin.click(function (event) {
        event.preventDefault();
        jQuery('#deactivation_thickbox_ual').trigger('click');
        jQuery('#TB_window').removeClass('thickbox-loading');
        change_thickbox_size_ual();
    });
    checkOtherDeactivate();
    jQuery('.sol_deactivation_reasons').click(function () {
        checkOtherDeactivate();
    });
    jQuery('#sbtDeactivationFormCloseual').click(function (event) {
        event.preventDefault();
        jQuery("#TB_closeWindowButton").trigger('click');
    })
    function checkOtherDeactivate() {
        var selected_option_de = jQuery('input[name=sol_deactivation_reasons_ual]:checked', '#frmDeactivationual').val();
        if (selected_option_de == '7') {
            jQuery('.sol_deactivation_reason_other_ual').val('');
            jQuery('.sol_deactivation_reason_other_ual').show();
        }
        else {
            jQuery('.sol_deactivation_reason_other_ual').val('');
            jQuery('.sol_deactivation_reason_other_ual').hide();
        }
    }
    jQuery('#sbtDeactivationFormual').click(function (event) {
        event.preventDefault();
        var selected_option_de = jQuery('input[name=sol_deactivation_reasons_ual]:checked', '#frmDeactivationual').val();
        var selected_option_de_id = jQuery('input[name=sol_deactivation_reasons_ual]:checked', '#frmDeactivationual').attr("id");
        var selected_option_de_text = jQuery("label[for='" + selected_option_de_id + "']").text();
        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                'action': 'ual_sbtDeactivationform',
                'deactivation_option': selected_option_de,
                'deactivation_option_text': selected_option_de_text,
                'deactivation_option_other': jQuery('.sol_deactivation_reason_other_ual').val()
            },
            complete: function () {
                window.location.href = ual_plugin_admin.attr('href');
            }
        });
    });
    function change_thickbox_size_ual() {
        jQuery(document).find('#TB_window').width('700').height('400').css('margin-left', -700 / 2);
        jQuery(document).find('#TB_ajaxContent').width('640');
        var doc_height = jQuery(window).height();
        var doc_space = doc_height - 400;
        if (doc_space > 0) {
            jQuery(document).find('#TB_window').css('margin-top', doc_space / 2);
        }
    }

});
jQuery(document).ready(function () {
    jQuery('script').each(function () {
        var src = jQuery(this).attr('src');
        if (typeof src !== typeof undefined && src !== false) {
            if (src.search('bootstrap.js') !== -1 || src.search('bootstrap.min.js') !== -1) {
                if (jQuery.fn.button.noConflict) {
                    var bootstrapButton = jQuery.fn.button.noConflict();
                    jQuery.fn.bootstrapBtn = bootstrapButton;
                }
            }
        }
    });

    if (jQuery('form.sol-form input[name="emailEnable"]:checked').val() == 0) {
        jQuery('form.sol-form .ui-button.ui-corner-right').addClass('active');
        jQuery('form.sol-form .ui-button.ui-corner-left').removeClass('active');
    } else {
        jQuery('form.sol-form .ui-button.ui-corner-left').addClass('active');
        jQuery('form.sol-form .ui-button.ui-corner-right').removeClass('active');
    }

    jQuery('form.sol-form input[name="emailEnable"]').click(function () {
        if (jQuery('form.sol-form input[name="emailEnable"]:checked').val() == 0) {
            jQuery('form.sol-form .ui-button.ui-corner-right').addClass('active');
            jQuery('form.sol-form .ui-button.ui-corner-left').removeClass('active');
        } else {
            jQuery('form.sol-form .ui-button.ui-corner-left').addClass('active');
            jQuery('form.sol-form .ui-button.ui-corner-right').removeClass('active');
        }
    });

    //settings tab script
    if (window.localStorage.getItem("lasttab") == null ||
            (window.localStorage.getItem("lasttab") != 'ualGeneralSettings' &&
                    window.localStorage.getItem("lasttab") != 'ualUserSettings' &&
                    window.localStorage.getItem("lasttab") != 'ualEmailSettings')) {
        jQuery('.ualParentTabs .nav-tab-wrapper a.nav-tab').removeClass('nav-tab-active');
        jQuery('.ualParentTabs .nav-tab-wrapper a.nav-tab.ualUserSettings').addClass('nav-tab-active');
        jQuery('.ualpContentDiv').hide();
        jQuery('#ualUserSettings.ualpContentDiv').show();
        jQuery('#ualUserSettings.ualpContentDiv').css('display', 'block');
    } else {
        jQuery('.ualParentTabs .nav-tab-wrapper a').removeClass('nav-tab-active');
        jQuery('.' + window.localStorage.getItem("lasttab")).addClass('nav-tab-active');
        jQuery('.ualpContentDiv').hide();
        jQuery('#' + window.localStorage.getItem("lasttab")).css('display', 'block');
        jQuery('.ualpContentDiv#' + window.localStorage.getItem("lasttab")).show();
    }
    jQuery('.ualParentTabs .nav-tab-wrapper a').not(".ual-pro-feature").click(function (e) {
        e.preventDefault();
        jQuery('.ualpAdminNotice.is-dismissible').hide();
        var this_tab = jQuery(this);
        var data_href = jQuery(this).attr('data-href');
        jQuery('.ualpContentDiv').hide();
        jQuery('#' + data_href).show();
        jQuery('.nav-tab-wrapper a.nav-tab').removeClass('nav-tab-active');
        this_tab.addClass('nav-tab-active');
        if (window.localStorage) {
            window.localStorage.setItem("lasttab", data_href);
        }
    });

    // Enable email notification start
    if (jQuery('.sol-email-table input[name="emailEnable"]:checked').val() == 0) {
        jQuery('.sol-email-table .fromEmailTr,.sol-email-table .toEmailTr,.sol-email-table .messageTr').hide();
    } else {
        jQuery('.sol-email-table .fromEmailTr,.sol-email-table .toEmailTr,.sol-email-table .messageTr').show();
    }
    jQuery('.sol-email-table input[name="emailEnable"]').click(function() {
        if (jQuery('.sol-email-table input[name="emailEnable"]:checked').val() == 0) {
            jQuery('.sol-email-table .fromEmailTr,.sol-email-table .toEmailTr,.sol-email-table .messageTr').hide();
        } else {
            jQuery('.sol-email-table .fromEmailTr,.sol-email-table .toEmailTr,.sol-email-table .messageTr').show();
        }
    });
    // Enable email notification end
    
    jQuery('.ual-pro-feature').on('click', function (e) {
        e.preventDefault();
        jQuery("#ual-advertisement-popup").dialog({
            resizable: false,
            draggable: false,
            modal: true,
            height: "auto",
            width: 'auto',
            maxWidth: '100%',
            dialogClass: 'ual-advertisement-ui-dialog',
            buttons: [
                {
                    text: 'x',
                    "class": 'ual-btn ual-btn-gray',
                    click: function () {
                        jQuery(this).dialog("close");
                    }
                }
            ],
            open: function (event, ui) {
                jQuery(this).parent().children('.ui-dialog-titlebar').hide();
                jQuery('.ui-widget-overlay').bind('click', function() {
                    jQuery("#ual-advertisement-popup").dialog('close');
                });
            },
            hide: {
                effect: "fadeOut",
                duration: 500
            },
            close: function (event, ui) {
                jQuery("#ual-advertisement-popup").dialog('close');
            },
        });
    });
});