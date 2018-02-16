jQuery(document).ready(function($) {

	if ( ! window.console ) {
		window.console = {
			log : function(str) {
				// alert(str);
			}
		};
	}

	var xhr = [];

	$('.wc-bookings-booking-form')
		.on('change', 'input, select', function( e ) {

			var name  = $(this).attr('name');

			var $fieldset = $(this).closest('fieldset');
			var $picker   = $fieldset.find( '.picker:eq(0)' );
			if ( $picker.data( 'is_range_picker_enabled' ) ) {
				if ( 'wc_bookings_field_duration' !== name ) {
					return;
				}
			}

			var index = $('.wc-bookings-booking-form').index(this);
			$form = $(this).closest('form');
			var isEmptyCalendarSelection =  ! $form.find( "[name='wc_bookings_field_start_date_day']" ).val() &&
										! $form.find( '#wc_bookings_field_start_date' ).val();

			// Do not update if triggered by Product Addons and no date is selected.
			if ( jQuery(e.target).hasClass('addon') && isEmptyCalendarSelection ) {
				return;
			}

			var required_fields = $form.find('input.required_for_calculation');
			var filled          = true;
			$.each( required_fields, function( index, field ) {
				var value = $(field).val();
				if ( ! value ) {
					filled = false;
				}
			});
			if ( ! filled ) {
				$form.find('.wc-bookings-booking-cost').hide();
				return;
			}

			$form.find('.wc-bookings-booking-cost').block({message: null, overlayCSS: {background: '#fff', backgroundSize: '16px 16px', opacity: 0.6}}).show();
			xhr[index] = $.ajax({
				type: 		'POST',
				url: 		booking_form_params.ajax_url,
				data: 		{
					action: 'wc_bookings_calculate_costs',
					form:   $form.serialize()
				},
				success: 	function( code ) {
					if ( code.charAt(0) !== '{' ) {
						console.log( code );
						code = '{' + code.split(/\{(.+)?/)[1];
					}

					result = $.parseJSON( code );

					if ( result.result == 'ERROR' ) {
						$form.find('.wc-bookings-booking-cost').html( result.html );
						$form.find('.wc-bookings-booking-cost').unblock();
						$form.find('.single_add_to_cart_button').addClass('disabled');
					} else if ( result.result == 'SUCCESS' ) {
						$form.find('.wc-bookings-booking-cost').html( result.html );
						$form.find('.wc-bookings-booking-cost').unblock();
						$form.find('.single_add_to_cart_button').removeClass('disabled');
					} else {
						$form.find('.wc-bookings-booking-cost').hide();
						$form.find('.single_add_to_cart_button').addClass('disabled');
						console.log( code );
					}

					$( document.body ).trigger( 'wc_booking_form_changed' );
				},
				error: function() {
					$form.find('.wc-bookings-booking-cost').hide();
					$form.find('.single_add_to_cart_button').addClass('disabled');
				},
				dataType: 	"html"
			});
		})
		.each(function(){
			var button = $(this).closest('form').find('.single_add_to_cart_button');

			button.addClass('disabled');
		});

	$( '.single_add_to_cart_button' ).on( 'click', function( event ) {
		if ( $(this).hasClass('disabled') ) {
			alert( booking_form_params.i18n_choose_options );
			event.preventDefault();
			return false;
		}
	})

	$('.wc-bookings-booking-form, .wc-bookings-booking-form-button').show().removeAttr( 'disabled' );

});
