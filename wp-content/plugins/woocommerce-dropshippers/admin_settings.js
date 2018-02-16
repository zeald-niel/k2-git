/**
* File: admin_settings.js
* Author: ArticNet LLC.
**/

var woocommerce_dropshippers_uploader;

jQuery(document).ready(function($){
	$('#woocommerce_dropshippers_company_logo_button').click(function(e) {
		e.preventDefault();
		//If the uploader object has already been created, reopen the dialog
		if (woocommerce_dropshippers_uploader) {
			woocommerce_dropshippers_uploader.open();
			return;
		}
		//Extend the wp.media object
		woocommerce_dropshippers_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: false
		});
		//When a file is selected, grab the URL and set it as the text field's value
		woocommerce_dropshippers_uploader.on('select', function() {
			attachment = woocommerce_dropshippers_uploader.state().get('selection').first().toJSON();
			$('#woocommerce_dropshippers_company_logo').hide();
			$('#woocommerce_dropshippers_company_logo').after('<img id="woocommerce-dropshippers-spinning-img" src="'+ woocommerce_dropshippers_throbber_url +'">');
			$('#woocommerce_dropshippers_company_logo_button').attr('disabled', true);
			jQuery(document).ready(function($) {
				var data = {
					'action': 'woocommerce_dropshippers_get_attachment_path',
					'att_id': attachment.id
				};
				jQuery.post(ajaxurl, data, function(response) {
					$('#woocommerce_dropshippers_company_logo').val(response);
					$('#woocommerce_dropshippers_company_logo').show();
					$('#woocommerce-dropshippers-spinning-img').remove();
					$('#woocommerce_dropshippers_company_logo_button').attr('disabled', false);
				});
			});

		});
		woocommerce_dropshippers_uploader.open();
	});
});