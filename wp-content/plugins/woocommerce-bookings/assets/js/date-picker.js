/* globals: jQuery, wc_bookings_booking_form, booking_form_params */

// globally accessible for tests
wc_bookings_date_picker = {};

jQuery( function( $ ) {
	var wc_bookings_timeout      = 0,
		wc_bookings_date_picker_object  = {
		init: function() {
			$( 'body' ).on( 'change', '#wc_bookings_field_duration, #wc_bookings_field_resource', this.date_picker_init );
			$( 'body' ).on( 'click', '.wc-bookings-date-picker legend small.wc-bookings-date-picker-choose-date', this.toggle_calendar );
			$( 'body' ).on( 'input', '.booking_date_year, .booking_date_month, .booking_date_day', this.input_date_trigger );
			$( 'body' ).on( 'keypress', '.booking_date_year, .booking_date_month, .booking_date_day', this.input_date_keypress );
			$( 'body' ).on( 'keypress', '.booking_to_date_year, .booking_to_date_month, .booking_to_date_day', this.input_date_keypress );
			$( 'body' ).on( 'change', '.booking_to_date_year, .booking_to_date_month, .booking_to_date_day', this.input_date_trigger );
			$( '.wc-bookings-date-picker legend small.wc-bookings-date-picker-choose-date' ).show();
			$( '.wc-bookings-date-picker' ).each( function() {
				var form     = $( this ).closest( 'form' ),
					picker   = form.find( '.picker' ),
					fieldset = $( this ).closest( 'fieldset' );

				wc_bookings_date_picker.date_picker_init( picker );

				if ( picker.data( 'display' ) == 'always_visible' ) {
					$( '.wc-bookings-date-picker-date-fields', fieldset ).hide();
					$( '.wc-bookings-date-picker-choose-date', fieldset ).hide();
				} else {
					picker.hide();
				}

				if ( picker.data( 'is_range_picker_enabled' ) ) {
					form.find( 'p.wc_bookings_field_duration' ).hide();
					form.find( '.wc_bookings_field_start_date legend span.label' ).text( 'always_visible' !== picker.data( 'display' ) ? booking_form_params.i18n_dates : booking_form_params.i18n_start_date );
				}
			} );
		},
		calc_duration: function( picker ) {
			var form     = picker.closest('form'),
				fieldSet = picker.closest('fieldset'),
				unit     = picker.data( 'duration-unit' );

			setTimeout( function() {
				var days    = 1,
					e_year  = parseInt( fieldSet.find( 'input.booking_to_date_year' ).val(), 10 ),
					e_month = parseInt( fieldSet.find( 'input.booking_to_date_month' ).val(), 10 ),
					e_day   = parseInt( fieldSet.find( 'input.booking_to_date_day' ).val(), 10 ),
					s_year  = parseInt( fieldSet.find( 'input.booking_date_year' ).val(), 10 ),
					s_month = parseInt( fieldSet.find( 'input.booking_date_month' ).val(), 10 ),
					s_day   = parseInt( fieldSet.find( 'input.booking_date_day' ).val(), 10 );

				if ( e_year && e_month >= 0 && e_day && s_year && s_month >= 0 && s_day ) {
					var s_date = new Date( Date.UTC( s_year, s_month - 1, s_day ) ),
						e_date = new Date( Date.UTC( e_year, e_month - 1, e_day ) );

					days = Math.floor( ( e_date.getTime() - s_date.getTime() ) / ( 1000*60*60*24 ) );
					if ( 'day' === unit ) {
						days = days + 1;
					}
				}

				form.find( '#wc_bookings_field_duration' ).val( days ).change();
			} );

		},
		toggle_calendar: function() {
			$picker = $( this ).closest( 'fieldset' ).find( '.picker:eq(0)' );
			wc_bookings_date_picker.date_picker_init( $picker );
			$picker.slideToggle();
		},
		input_date_keypress: function() {
			var $fieldset = $(this).closest( 'fieldset' ),
				$picker   = $fieldset.find( '.picker:eq(0)' );

			if ( $picker.data( 'is_range_picker_enabled' ) ) {
				clearTimeout( wc_bookings_timeout );

				wc_bookings_timeout = setTimeout( wc_bookings_date_picker.calc_duration( $picker ), 800 );
			}
		},
		input_date_trigger: function() {
			var $fieldset = $(this).closest('fieldset'),
				$picker   = $fieldset.find( '.picker:eq(0)' ),
				$form     = $(this).closest('form'),
				year      = parseInt( $fieldset.find( 'input.booking_date_year' ).val(), 10 ),
				month     = parseInt( $fieldset.find( 'input.booking_date_month' ).val(), 10 ),
				day       = parseInt( $fieldset.find( 'input.booking_date_day' ).val(), 10 );

			if ( year && month && day ) {
				var date = new Date( year, month - 1, day );
				$picker.datepicker( "setDate", date );

				if ( $picker.data( 'is_range_picker_enabled' ) ) {
					var to_year      = parseInt( $fieldset.find( 'input.booking_to_date_year' ).val(), 10 ),
						to_month     = parseInt( $fieldset.find( 'input.booking_to_date_month' ).val(), 10 ),
						to_day       = parseInt( $fieldset.find( 'input.booking_to_date_day' ).val(), 10 );

					var to_date = new Date( to_year, to_month - 1, to_day );

					if ( ! to_date || to_date < date ) {
						$fieldset.find( 'input.booking_to_date_year' ).val( '' ).addClass( 'error' );
						$fieldset.find( 'input.booking_to_date_month' ).val( '' ).addClass( 'error' );
						$fieldset.find( 'input.booking_to_date_day' ).val( '' ).addClass( 'error' );
					} else {
						$fieldset.find( 'input' ).removeClass( 'error' );
					}
				}
				$fieldset.triggerHandler( 'date-selected', date );
			}
		},
		select_date_trigger: function( date ) {
			var fieldset          = $( this ).closest('fieldset'),
				picker            = fieldset.find( '.picker:eq(0)' ),
				form              = $( this ).closest( 'form' ),
				parsed_date       = date.split( '-' ),
				start_or_end_date = picker.data( 'start_or_end_date' );

			if ( ! picker.data( 'is_range_picker_enabled' ) || ! start_or_end_date ) {
				start_or_end_date = 'start';
			}

			// End date selected
			if ( start_or_end_date === 'end' ) {

				// Set min date to default
				picker.data( 'min_date', picker.data( 'o_min_date' ) );

				// Set fields
				fieldset.find( 'input.booking_to_date_year' ).val( parsed_date[0] );
				fieldset.find( 'input.booking_to_date_month' ).val( parsed_date[1] );
				fieldset.find( 'input.booking_to_date_day' ).val( parsed_date[2] ).change();

				// Calc duration
				if ( picker.data( 'is_range_picker_enabled' ) ) {
					wc_bookings_date_picker.calc_duration( picker );
				}

				// Next click will be start date
				picker.data( 'start_or_end_date', 'start' );

				if ( picker.data( 'is_range_picker_enabled' ) ) {
					form.find( '.wc_bookings_field_start_date legend span.label' ).text( 'always_visible' !== picker.data( 'display' ) ? booking_form_params.i18n_dates : booking_form_params.i18n_start_date );
				}

				if ( 'always_visible' !== picker.data( 'display' ) ) {
					$( this ).hide();
				}

			// Start date selected
			} else {
				// Set min date to today
				if ( picker.data( 'is_range_picker_enabled' ) ) {
					picker.data( 'o_min_date', picker.data( 'min_date' ) );
					picker.data( 'min_date', date );
				}

				// Set fields
				fieldset.find( 'input.booking_to_date_year' ).val( '' );
				fieldset.find( 'input.booking_to_date_month' ).val( '' );
				fieldset.find( 'input.booking_to_date_day' ).val( '' );

				fieldset.find( 'input.booking_date_year' ).val( parsed_date[0] );
				fieldset.find( 'input.booking_date_month' ).val( parsed_date[1] );
				fieldset.find( 'input.booking_date_day' ).val( parsed_date[2] ).change();

				// Calc duration
				if ( picker.data( 'is_range_picker_enabled' ) ) {
					wc_bookings_date_picker.calc_duration( picker );
				}

				// Next click will be end date
				picker.data( 'start_or_end_date', 'end' );

				if ( picker.data( 'is_range_picker_enabled' ) ) {
					form.find( '.wc_bookings_field_start_date legend span.label' ).text( booking_form_params.i18n_end_date );
				}

				if ( 'always_visible' !== picker.data( 'display' ) && ! picker.data( 'is_range_picker_enabled' ) ) {
					$( this ).hide();
				}
			}

			fieldset.triggerHandler( 'date-selected', date, start_or_end_date );
		},
		date_picker_init: function( element ) {
			var $picker;
			if ( $( element ).is( '.picker' ) ) {
				$picker = $( element );
			} else {
				$picker = $( this ).closest('form').find( '.picker' );
			}

			$picker.empty().removeClass('hasDatepicker').datepicker({
				dateFormat: $.datepicker.ISO_8601,
				showWeek: false,
				showOn: false,
				beforeShowDay: wc_bookings_date_picker.is_bookable,
				onSelect: wc_bookings_date_picker.select_date_trigger,
				minDate: $picker.data( 'min_date' ),
				maxDate: $picker.data( 'max_date' ),
				defaultDate: $picker.data( 'default_date'),
				numberOfMonths: 1,
				showButtonPanel: false,
				showOtherMonths: true,
				selectOtherMonths: true,
				closeText: wc_bookings_booking_form.closeText,
				currentText: wc_bookings_booking_form.currentText,
				prevText: wc_bookings_booking_form.prevText,
				nextText: wc_bookings_booking_form.nextText,
				monthNames: wc_bookings_booking_form.monthNames,
				monthNamesShort: wc_bookings_booking_form.monthNamesShort,
				dayNames: wc_bookings_booking_form.dayNames,
				dayNamesShort: wc_bookings_booking_form.dayNamesShort,
				dayNamesMin: wc_bookings_booking_form.dayNamesMin,
				firstDay: wc_bookings_booking_form.firstDay,
				gotoCurrent: true
			});

			$( '.ui-datepicker-current-day' ).removeClass( 'ui-datepicker-current-day' );

			var form  = $picker.closest( 'form' ),
				year  = parseInt( form.find( 'input.booking_date_year' ).val(), 10 ),
				month = parseInt( form.find( 'input.booking_date_month' ).val(), 10 ),
				day   = parseInt( form.find( 'input.booking_date_day' ).val(), 10 );

			if ( year && month && day ) {
				var date = new Date( year, month - 1, day );
				$picker.datepicker( "setDate", date );
			}
		},
		get_input_date: function( fieldset, where ) {
			var year  = fieldset.find( 'input.booking_' + where + 'date_year' ),
				month = fieldset.find( 'input.booking_' + where + 'date_month' ),
				day   = fieldset.find( 'input.booking_' + where + 'date_day' );

			if ( 0 !== year.val().length && 0 !== month.val().length && 0 !== day.val().length ) {
				return year.val() + '-' + month.val() + '-' + day.val();
			} else {
				return '';
			}
		},
		is_bookable: function( date ) {
			var $form                      = $( this ).closest('form'),
				$picker                    = $form.find( '.picker:eq(0)' ),
				availability               = $( this ).data( 'availability' ),
				default_availability       = $( this ).data( 'default-availability' ),
				fully_booked_days          = $( this ).data( 'fully-booked-days' ),
				buffer_days                = $( this ).data( 'buffer-days' ),
				partially_booked_days      = $( this ).data( 'partially-booked-days' ),
				check_availability_against = wc_bookings_booking_form.check_availability_against,
				css_classes                = '',
				title                      = '',
				resource_id                = 0,
				resources_assignment       = wc_bookings_booking_form.resources_assignment;

			// Get selected resource
			if ( $form.find('select#wc_bookings_field_resource').val() > 0 ) {
				resource_id = $form.find('select#wc_bookings_field_resource').val();
			}

			// Get days needed for block - this affects availability
			var duration = wc_bookings_booking_form.booking_duration,
				the_date = new Date( date ),
				year     = the_date.getFullYear(),
				month    = the_date.getMonth() + 1,
				day      = the_date.getDate(),
				ymdIndex = year + '-' + month + '-' + day;

			// Fully booked?
			if ( fully_booked_days[ ymdIndex ] ) {
				if ( fully_booked_days[ ymdIndex ][0] || fully_booked_days[ ymdIndex ][ resource_id ] ) {
					return [ false, 'fully_booked', booking_form_params.i18n_date_fully_booked ];
				} else if ( 'automatic' === resources_assignment ) {
					css_classes = css_classes + 'partial_booked ';
				}
			}

			// Buffer days?
			if ( buffer_days && buffer_days && buffer_days[ ymdIndex ] ) {
				return [ false, 'not_bookable', booking_form_params.i18n_date_unavailable ];
			}

			if ( '' + year + month + day < wc_bookings_booking_form.current_time ) {
				return [ false, 'not_bookable', booking_form_params.i18n_date_unavailable ];
			}

			// Apply partially booked CSS class.
			if ( partially_booked_days && partially_booked_days[ ymdIndex ] ) {
				if ( 'automatic' === resources_assignment || partially_booked_days[ ymdIndex ][0] || partially_booked_days[ ymdIndex ][ resource_id ] ) {
					css_classes = css_classes + 'partial_booked ';
				}
			}

			var number_of_days = wc_bookings_date_picker.get_number_of_days( duration, $form, $picker, wc_bookings_booking_form );
			var block_args = {
				start_date          : date,
				number_of_days      : number_of_days,
				fully_booked_days   : fully_booked_days,
				availability        : availability,
				default_availability: default_availability,
				resource_id         : resource_id,
				resources_assignment: resources_assignment
			};

			var bookable = wc_bookings_date_picker.is_blocks_bookable( block_args );

			if ( ! bookable ) {
				return [ bookable, fully_booked_days[ ymdIndex ] ? 'fully_booked' : 'not_bookable', booking_form_params.i18n_date_unavailable ];
			} else {

				if ( css_classes.indexOf( 'partial_booked' ) > -1 ) {
					title = booking_form_params.i18n_date_partially_booked;
				} else {
					title = booking_form_params.i18n_date_available;
				}

				if ( $picker.data( 'is_range_picker_enabled' ) ) {
					var fieldset     = $(this).closest( 'fieldset' ),
						start_date   = $.datepicker.parseDate( $.datepicker.ISO_8601, wc_bookings_date_picker.get_input_date( fieldset, '' ) ),
						end_date     = $.datepicker.parseDate( $.datepicker.ISO_8601, wc_bookings_date_picker.get_input_date( fieldset, 'to_' ) );

					return [ bookable, start_date && ( ( date.getTime() === start_date.getTime() ) || ( end_date && date >= start_date && date <= end_date ) ) ? css_classes + 'bookable-range' : css_classes + 'bookable', title ];
				} else {
					return [ bookable, css_classes + 'bookable', title ];
				}
			}
		},

		get_number_of_days: function( defaultNumberOfDays, $form, $picker, wc_bookings_booking_form ){
			var number_of_days = defaultNumberOfDays;
			var wcbf = wc_bookings_booking_form;
			if ( $form.find('#wc_bookings_field_duration').length > 0
				&& wcbf.duration_unit != 'minute'
				&& wcbf.duration_unit != 'hour'
				&& ! $picker.data( 'is_range_picker_enabled' ) )
			{
				var user_duration = $form.find('#wc_bookings_field_duration').val();
				number_of_days   = number_of_days * user_duration;
			}

			if ( number_of_days < 1 || wcbf.check_availability_against === 'start' ) {
					number_of_days = 1;
				}
				return number_of_days;
		},

		is_blocks_bookable: function( args ) {
			var bookable = args.default_availability;

			// Loop all the days we need to check for this block.
			for ( var i = 0; i < args.number_of_days; i++ ) {
				var the_date     = new Date( args.start_date );
				the_date.setDate( the_date.getDate() + i );

				var year        = the_date.getFullYear(),
					month       = the_date.getMonth() + 1,
					day         = the_date.getDate(),
					day_of_week = the_date.getDay(),
					week        = $.datepicker.iso8601Week( the_date );

				// Sunday is 0, Monday is 1, and so on.
				if ( day_of_week === 0 ) {
					day_of_week = 7;
				}

				// Is resource available in current date?
				// Note: resource_id = 0 is product's availability rules.
				// Each resource rules also contains product's rules.
				var resource_args = {
					date: the_date,
					default_availability: args.default_availability
				};
				var resource_rules = args.availability[ args.resource_id ];
				bookable = wc_bookings_date_picker.is_resource_available_on_date( resource_args, resource_rules );

				// In case of automatic assignment we want to make sure at least
				// one resource is available.
				if ( 'automatic' === args.resources_assignment ) {
					var automatic_resource_args = $.extend(
						{
							availability: args.availability,
							fully_booked_days: args.fully_booked_days
						},
						resource_args
					);

					bookable = wc_bookings_date_picker.has_available_resource( automatic_resource_args );
				}

				// Fully booked in entire block?
				var ymdIndex = year + '-' + month + '-' + day;
				if ( args.fully_booked_days[ ymdIndex ] ) {
					if ( args.fully_booked_days[ ymdIndex ][0] || args.fully_booked_days[ ymdIndex ][ args.resource_id ] ) {
						bookable = false;
					}
				}

				if ( ! bookable ) {
					break;
				}
			}

			return bookable;

		},

		/**
		 * Goes through all the rules and applies then to them to see if booking is available
		 * for the given date.
		 *
		 * Rules are recursively applied. Rules later array will override rules earlier in the array if
		 * applicable to the block being checked.
		 *
		 * @param args
		 * @param rules array of rules in order from lowest override power to highest.
		 *
		 * @returns boolean
		 */
		is_resource_available_on_date: function( args, rules ) {

			if ( 'object'!== typeof args || 'object' !== typeof rules ) {
				return false;
			}

			var defaultAvailability = args.default_availability,
				year         = args.date.getFullYear(),
				month        = args.date.getMonth() + 1, // months start at 0
				day          = args.date.getDate(),
				day_of_week  = args.date.getDay();

			var	firstOfJanuary = new Date( year, 0, 1 );
			var week =  Math.ceil( ( ( (args.date - firstOfJanuary ) / 86400000) + firstOfJanuary.getDay() + 1 ) / 7 );

			// Sunday is 0, Monday is 1, and so on.
			if ( day_of_week === 0 ) {
				day_of_week = 7;
			}

			// `args.fully_booked_days` and `args.resource_id` only available
			// when checking 'automatic' resource assignment.
			if ( args.fully_booked_days && args.fully_booked_days[ year + '-' + month + '-' + day ] && args.fully_booked_days[ year + '-' + month + '-' + day ][ args.resource_id ] ) {
				return false;
			}

			var minutesAvailableForDay    = [];
			var minutesForADay = _.range( 1, 1440 ,1 );
			// Ensure that the minutes are set when the all slots are available by default.
			if ( defaultAvailability ){
				minutesAvailableForDay = minutesForADay;
			}

			$.each( rules, function( index, rule ) {
				var type  = rule['type'];
				var range = rule['range'];
				try {
					switch ( type ) {
						case 'months':
							if ( typeof range[ month ] != 'undefined' ) {

								if ( range[ month ] ) {
									minutesAvailableForDay = minutesForADay;
								} else{
									minutesAvailableForDay = [];
								}
								return true; // go to the next rule
							}
							break;
						case 'weeks':
							if ( typeof range[ week ] != 'undefined' ) {
								if( range[ week ] ){
									minutesAvailableForDay = minutesForADay;
								} else{
									minutesAvailableForDay = [];
								}
								return true; // go to the next rule
							}
							break;
						case 'days':
							if ( typeof range[ day_of_week ] != 'undefined' ) {
								if( range[ day_of_week ] ){
									minutesAvailableForDay = minutesForADay;
								} else{
									minutesAvailableForDay = [];
								}
								return true; // go to the next rule
							}
							break;
						case 'custom':
							if ( typeof range[ year ][ month ][ day ] != 'undefined' ) {
								if( range[ year ][ month ][ day ]){
									minutesAvailableForDay = minutesForADay;
								} else{
									minutesAvailableForDay = [];
								}
								return true; // go to the next rule
							}
							break;
						case 'time':
						case 'time:1':
						case 'time:2':
						case 'time:3':
						case 'time:4':
						case 'time:5':
						case 'time:6':
						case 'time:7':
							if ( day_of_week === range.day || 0 === range.day ) {

								var fromHour = parseInt( range.from.split(':')[0] );
								var fromMinute = parseInt( range.from.split(':')[1] );
								var toHour = parseInt( range.to.split(':')[0] );
								var toMinute = parseInt( range.to.split(':')[1] );

								// each minute in the day gets a number from 1 to 1440
								var fromMinuteNumber = fromMinute + ( fromHour * 60 );
								var toMinuteNumber = toMinute + ( toHour * 60 );
								var minutesAvailableForTime = _.range(fromMinuteNumber, toMinuteNumber, 1);

								if ( range.rule ) {
									minutesAvailableForDay = _.union(minutesAvailableForDay, minutesAvailableForTime);
								} else {
									minutesAvailableForDay = _.difference(minutesAvailableForDay, minutesAvailableForTime);
								}

								return true;
							}
							break;
						case 'time:range':
							range = range[year][month][day];
							var fromHour = parseInt( range.from.split(':')[0] );
							var fromMinute = parseInt( range.from.split(':')[1] );
							var toHour = parseInt( range.to.split(':')[0] );
							var toMinute = parseInt( range.to.split(':')[1] );

							// each minute in the day gets a number from 1 to 1440
							var fromMinuteNumber = fromMinute + ( fromHour * 60 );
							var toMinuteNumber = toMinute + ( toHour * 60 );
							var minutesAvailableForTime = _.range(fromMinuteNumber, toMinuteNumber, 1);

							if ( range.rule ) {
								minutesAvailableForDay = _.union(minutesAvailableForDay, minutesAvailableForTime);
							} else {
								minutesAvailableForDay = _.difference(minutesAvailableForDay, minutesAvailableForTime);
							}
							break;
					}
				} catch( err ) {
					return true; // go to the next rule
				}
			});

			return ! _.isEmpty( minutesAvailableForDay );

		},
		get_week_number: function( date ){
			var January1 = new Date( date.getFullYear(), 0, 1 );
			var week     = Math.ceil( ( ( ( date - January1 ) / 86400000) + January1.getDay() + 1 ) / 7 );
			return week;
		},
		has_available_resource: function( args ) {
			for ( var resource_id in args.availability ) {
				resource_id = parseInt( resource_id, 10 );

				// Skip resource_id '0' that has been performed before.
				if ( 0 === resource_id ) {
					continue;
				}

				var resource_rules = args.availability[ resource_id ];
				args.resource_id = resource_id;
				if ( wc_bookings_date_picker.is_resource_available_on_date( args, resource_rules ) ) {
					return true;
				}
			}

			return false;
		}
	};

	// export globally
	wc_bookings_date_picker = wc_bookings_date_picker_object;
	wc_bookings_date_picker.init();
});
