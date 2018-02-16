/**
 * plugin admin area javascript
 */
(function($){$(function () {
	
	if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page

	$('.existing_umeta_keys').change(function(){

		var parent_fieldset = $(this).parents('fieldset').first();
		var key = $(this).find('option:selected').val();
		
		if ("" != $(this).val()) {

			parent_fieldset.find('input[name^=custom_name]:visible').each(function(){
				if ("" == $(this).val()) $(this).parents('tr').first().remove();
			});
			parent_fieldset.find('a.add-new-custom').click();
			parent_fieldset.find('input[name^=custom_name]:visible').last().val($(this).val());

			$(this).prop('selectedIndex',0);	

			parent_fieldset.addClass('loading');		

			var request = {
				action:'get_umeta_values',		
				security: wp_all_import_security,		
				key: key
		    };
		    
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: request,
				success: function(response) {
					parent_fieldset.find('input[name^=custom_name]:visible:last').after(response.html);
					parent_fieldset.removeClass('loading');			
				},
				dataType: "json"
			});
					
		}

	});

	$('.existing_umeta_values').live('change', function(){
		var parent_fieldset = $(this).parents('.form-field:first');
		if ($(this).val() != ""){
			parent_fieldset.find('textarea').val($(this).val());
			$(this).prop('selectedIndex', 0);
		}
	});		

});})(jQuery);
