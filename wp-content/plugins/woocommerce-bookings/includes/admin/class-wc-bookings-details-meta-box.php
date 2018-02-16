<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Bookings_Details_Meta_Box.
 */
class WC_Bookings_Details_Meta_Box {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Meta box title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Meta box context.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Meta box priority.
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * Meta box post types.
	 * @var array
	 */
	public $post_types;

	/**
	 * Are meta boxes saved?
	 *
	 * @var boolean
	 */
	private static $saved_meta_box = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'woocommerce-booking-data';
		$this->title      = __( 'Booking Details', 'woocommerce-bookings' );
		$this->context    = 'normal';
		$this->priority   = 'high';
		$this->post_types = array( 'wc_booking' );
		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 2 );
	}

	/**
	 * Check data and output warnings.
	 */
	private function sanity_check_notices( $booking, $product ) {
		if ( $booking->get_start() && $booking->get_start() > strtotime( '+ 2 year', current_time( 'timestamp' ) ) ) {
			echo '<div class="updated highlight"><p>' . __( 'This booking is scheduled over 2 years into the future. Please ensure this is correct.', 'woocommerce-bookings' ) . '</p></div>';
		}

		if ( $product && is_callable( array( $product, 'get_max_date' ) ) ) {
			$max      = $product->get_max_date();
			$max_date = strtotime( "+{$max['value']} {$max['unit']}", current_time( 'timestamp' ) );
			if ( $booking->get_start() > $max_date || $booking->get_end() > $max_date ) {
				echo '<div class="updated highlight"><p>' . sprintf( __( 'This booking is scheduled over the products allowed max booking date (%s). Please ensure this is correct.', 'woocommerce-bookings' ), date_i18n( wc_date_format(), $max_date ) ) . '</p></div>';
			}
		}

		if ( $booking->get_start() && $booking->get_end() && $booking->get_start() > $booking->get_end() ) {
			echo '<div class="error"><p>' . __( 'This booking has an end date set before the start date.', 'woocommerce-bookings' ) . '</p></div>';
		}

		if ( $booking->get_product_id() && ! wc_get_product( $booking->get_product_id() ) ) {
			echo '<div class="error"><p>' . __( 'It appears the booking product associated with this booking has been removed.', 'woocommerce-bookings' ) . '</p></div>';
			return;
		}

		if ( $product && is_callable( array( $product, 'is_skeleton' ) ) && $product->is_skeleton() ) {
			echo '<div class="error"><p>' . sprintf( __( 'This booking is missing a required add-on (product type: %s). Some information is shown below but might be incomplete. Please install the missing add-on through the plugins screen.', 'woocommerce-bookings' ), $product_check->get_type() ) . '</p></div>';
		}
	}

	/**
	 * Meta box content.
	 *
	 * @version 1.10.2
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_inner( $post ) {
		global $booking;

		wp_nonce_field( 'wc_bookings_details_meta_box', 'wc_bookings_details_meta_box_nonce' );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		if ( ! is_a( $booking, 'WC_Booking' ) || $booking->get_id() !== $post->ID ) {
			$booking = new WC_Booking( $post->ID );
		}
		$order             = $booking->get_order();
		$product_id        = $booking->get_product_id( 'edit' );
		$resource_id       = $booking->get_resource_id( 'edit' );
		$customer_id       = $booking->get_customer_id( 'edit' );
		$product           = $booking->get_product( $product_id );
		$customer          = $booking->get_customer();
		$statuses          = array_unique( array_merge( get_wc_booking_statuses( null, true ), get_wc_booking_statuses( 'user', true ), get_wc_booking_statuses( 'cancel', true ) ) );
		$bookable_products = array( '' => __( 'N/A', 'woocommerce-bookings' ) );

		foreach ( WC_Bookings_Admin::get_booking_products() as $bookable_product ) {
			$bookable_products[ $bookable_product->get_id() ] = $bookable_product->get_name();

			$resources = $bookable_product->get_resources();

			foreach ( $resources as $resource ) {
				$bookable_products[ $bookable_product->get_id() . '=>' . $resource->get_id() ] = '&nbsp;&nbsp;&nbsp;' . $resource->get_name();
			}
		}

		$this->sanity_check_notices( $booking, $product );
		?>
		<style type="text/css">
			#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
		</style>
		<div class="panel-wrap woocommerce">
			<div id="booking_data" class="panel">
				<h2><?php printf( __( 'Booking #%s details', 'woocommerce-bookings' ), esc_html( $post->ID ) ) ?></h2>
				<p class="booking_number"><?php
					if ( $order ) {
						printf( ' ' . __( 'Linked to order %s.', 'woocommerce-bookings' ), '<a href="' . admin_url( 'post.php?post=' . absint( ( is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id ) ) . '&action=edit' ) . '">#' . esc_html( $order->get_order_number() ) . '</a>' );
					}

					if ( $product && is_callable( array( $product, 'is_bookings_addon' ) ) && $product->is_bookings_addon() ) {
						printf( ' ' . __( 'Booking type: %s', 'woocommerce-bookings' ), $product->bookings_addon_title() );
					}
				?></p>

				<div class="booking_data_column_container">
					<div class="booking_data_column">
						<h4><?php _e( 'General details', 'woocommerce-bookings' ); ?></h4>

						<p class="form-field form-field-wide">
							<label for="_booking_order_id"><?php _e( 'Order ID:', 'woocommerce-bookings' ); ?></label>
							<?php if ( version_compare( WC_VERSION, '3.0', '<' ) ) : ?>
								<input type="hidden" name="_booking_order_id" id="_booking_order_id" value="<?php echo esc_attr( $booking->get_order_id() ); ?>" data-selected="<?php echo esc_attr( $order ? $order->get_order_number() : '' ); ?>" data-placeholder="<?php _e( 'N/A', 'woocommerce-bookings' ); ?>" data-allow_clear="true" />
							<?php else : ?>
								<select name="_booking_order_id" id="_booking_order_id" data-placeholder="<?php _e( 'N/A', 'woocommerce-bookings' ); ?>" data-allow_clear="true">
									<?php if ( $booking->get_order_id() && $order ) : ?>
										<option selected="selected" value="<?php echo esc_attr( $booking->get_order_id() ); ?>"><?php echo esc_html( $order->get_order_number() . ' &ndash; ' . date_i18n( wc_date_format(), strtotime( is_callable( array( $order, 'get_date_created' ) ) ? $order->get_date_created() : $order->post_date ) ) ); ?></option>
									<?php endif; ?>
								</select>
							<?php endif; ?>
						</p>

						<p class="form-field form-field-wide"><label for="booking_date"><?php _e( 'Date created:', 'woocommerce-bookings' ); ?></label>
							<input type="text" class="date-picker-field" name="booking_date" id="booking_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', $booking->get_date_created() ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" /> @ <input type="number" class="hour" placeholder="<?php _e( 'h', 'woocommerce-bookings' ); ?>" name="booking_date_hour" id="booking_date_hour" maxlength="2" size="2" value="<?php echo date_i18n( 'H', $booking->get_date_created() ); ?>" pattern="\-?\d+(\.\d{0,})?" />:<input type="number" class="minute" placeholder="<?php _e( 'm', 'woocommerce-bookings' ); ?>" name="booking_date_minute" id="booking_date_minute" maxlength="2" size="2" value="<?php echo date_i18n( 'i', $booking->get_date_created() ); ?>" pattern="\-?\d+(\.\d{0,})?" />
						</p>

						<p class="form-field form-field-wide">
							<label for="_booking_status"><?php _e( 'Booking status:', 'woocommerce-bookings' ); ?></label>
							<select id="_booking_status" name="_booking_status" class="wc-enhanced-select"><?php
								foreach ( $statuses as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $booking->get_status(), false ) . '>' . esc_html__( $value, 'woocommerce-bookings' ) . '</option>';
								}
							?></select>
							<input type="hidden" name="post_status" value="<?php echo esc_attr( $booking->get_status() ); ?>">
						</p>

						<p class="form-field form-field-wide">
							<label for="_booking_customer_id"><?php _e( 'Customer:', 'woocommerce-bookings' ); ?></label>
							<?php
								$name = ! empty( $customer->name ) ? ' &ndash; ' . $customer->name : '';
								$guest_placeholder = __( 'Guest', 'woocommerce-bookings' );
								if ( 'Guest' === $name ) {
									$guest_placeholder = sprintf( _x( 'Guest (%s)', 'woocommerce-bookings', 'Admin booking guest placeholder' ), $name );
								}

								if ( $booking->get_customer_id() ) {
									$user            = get_user_by( 'id', $booking->get_customer_id() );
									$customer_string = sprintf(
										esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce-bookings' ),
										trim( $user->first_name . ' ' . $user->last_name ),
										$customer->user_id,
										$customer->email
									);
								} else {
									$customer_string = '';
								}
							?>
							<?php if ( version_compare( WC_VERSION, '3.0', '<' ) ) : ?>
								<input type="hidden" name="_booking_customer_id" id="_booking_customer_id" class="wc-customer-search" value="<?php echo esc_attr( $booking->get_customer_id() ); ?>" data-selected="<?php echo esc_attr( $customer_string ); ?>" data-placeholder="<?php echo esc_attr( $guest_placeholder ); ?>" data-allow_clear="true" />
							<?php else : ?>
								<select name="_booking_customer_id" id="_booking_customer_id" class="wc-customer-search" data-placeholder="<?php echo esc_attr( $guest_placeholder ); ?>" data-allow_clear="true">
									<?php if ( $booking->get_customer_id() ) : ?>
										<option selected="selected" value="<?php echo esc_attr( $booking->get_customer_id() ); ?>"><?php echo esc_attr( $customer_string ); ?></option>
									<?php endif; ?>
								</select>
							<?php endif; ?>
						</p>

						<?php do_action( 'woocommerce_admin_booking_data_after_booking_details', $post->ID ); ?>

					</div>
					<div class="booking_data_column">
						<h4><?php _e( 'Booking specification', 'woocommerce-bookings' ); ?></h4>

						<?php
							woocommerce_wp_select( array(
								'id'            => 'product_or_resource_id',
								'class'         => 'wc-enhanced-select',
								'wrapper_class' => 'form-field form-field-wide',
								'label'         => __( 'Booked product:', 'woocommerce-bookings' ),
								'options'       => $bookable_products,
								'value'         => $resource_id ? $product_id . '=>' . $resource_id : $product_id,
							) );

							woocommerce_wp_text_input( array(
								'id'            => '_booking_parent_id',
								'label'         => __( 'Parent booking ID:', 'woocommerce-bookings' ),
								'wrapper_class' => 'form-field form-field-wide',
								'placeholder'   => 'N/A',
								'class'         => '',
								'value'         => $booking->get_parent_id() ? $booking->get_parent_id() : '',
							) );

							$person_counts = $booking->get_person_counts();

							echo '<br class="clear" />';
							echo '<h4>' . esc_html__( 'Person(s)', 'woocommerce-bookings' ) . '</h4>';

							$person_types = $product->get_person_types();

							if ( count( $person_counts ) > 0 || count( $person_types ) > 0 ) {
								$needs_update = false;

								foreach ( $person_counts as $person_id => $person_count ) {
									$person_type = null;

									try {
										$person_type = new WC_Product_Booking_Person_Type( $person_id );
									} catch ( Exception $e ) {
										// This person type was deleted from the database.
										unset( $person_counts[ $person_id ] );
										$needs_update = true;
									}

									if ( $person_type ) {
										woocommerce_wp_text_input( array(
											'id'            => '_booking_person_' . $person_id,
											'label'         => $person_type->get_name(),
											'type'          => 'number',
											'placeholder'   => '0',
											'value'         => $person_count,
											'wrapper_class' => 'booking-person',
										) );
									}
								}

								if ( $needs_update ) {
									$booking->set_person_counts( $person_counts );
									$booking->save();
								}

								$product_booking_diff = array_diff( array_keys( $person_types ), array_keys( $person_counts ) );

								foreach ( $product_booking_diff as $id ) {
									$person_type = $person_types[ $id ];
									woocommerce_wp_text_input( array(
										'id'            => '_booking_person_' . $person_type->get_id(),
										'label'         => $person_type->get_name(),
										'type'          => 'number',
										'placeholder'   => '0',
										'value'         => '0',
										'wrapper_class' => 'booking-person',
									) );
								}
							} else {
								$person_counts = $booking->get_person_counts();
								$person_type   = new WC_Product_Booking_Person_Type( 0 );

								woocommerce_wp_text_input( array(
									'id'            => '_booking_person_0',
									'label'         => $person_type->get_name(),
									'type'          => 'number',
									'placeholder'   => '0',
									'value'         => ! empty( $person_counts[0] ) ? $person_counts[0] : 0,
									'wrapper_class' => 'booking-person',
								) );
							}
						?>
					</div>
					<div class="booking_data_column">
						<h4><?php _e( 'Booking date &amp; time', 'woocommerce-bookings' ); ?></h4>
						<?php
							woocommerce_wp_text_input( array(
								'id'          => 'booking_start_date',
								'label'       => __( 'Start date:', 'woocommerce-bookings' ),
								'placeholder' => 'yyyy-mm-dd',
								'value'       => date( 'Y-m-d', $booking->get_start( 'edit' ) ),
								'class'       => 'date-picker-field',
							) );

							woocommerce_wp_text_input( array(
								'id'          => 'booking_end_date',
								'label'       => __( 'End date:', 'woocommerce-bookings' ),
								'placeholder' => 'yyyy-mm-dd',
								'value'       => date( 'Y-m-d', $booking->get_end( 'edit' ) ),
								'class'       => 'date-picker-field',
							) );

							woocommerce_wp_checkbox( array(
								'id'          => '_booking_all_day',
								'label'       => __( 'All day booking:', 'woocommerce-bookings' ),
								'description' => __( 'Check this box if the booking is for all day.', 'woocommerce-bookings' ),
								'value'       => $booking->get_all_day( 'edit' ) ? 'yes' : 'no',
							) );

							woocommerce_wp_text_input( array(
								'id'          => 'booking_start_time',
								'label'       => __( 'Start time:', 'woocommerce-bookings' ),
								'placeholder' => 'hh:mm',
								'value'       => date( 'H:i', $booking->get_start( 'edit' ) ),
							) );

							woocommerce_wp_text_input( array(
								'id'          => 'booking_end_time',
								'label'       => __( 'End time:', 'woocommerce-bookings' ),
								'placeholder' => 'hh:mm',
								'value'       => date( 'H:i', $booking->get_end( 'edit' ) ),
							) );
						?>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>

		<?php
			wc_enqueue_js( "
				$( '#_booking_all_day' ).change( function () {
					if ( $( this ).is( ':checked' ) ) {
						$( '#booking_start_time, #booking_end_time' ).closest( 'p' ).hide();
					} else {
						$( '#booking_start_time, #booking_end_time' ).closest( 'p' ).show();
					}
				}).change();

				$( '.date-picker-field' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1,
					showButtonPanel: true,
				});
			" );

			// Select2 handling
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				wc_enqueue_js( "
					$( '#_booking_order_id' ).filter( ':not(.enhanced)' ).each( function() {
						var select2_args = {
							allowClear:  true,
							placeholder: $( this ).data( 'placeholder' ),
							minimumInputLength: 1,
							escapeMarkup: function( m ) {
								return m;
							},
							ajax: {
								url:         '" . admin_url( 'admin-ajax.php' ) . "',
								dataType:    'json',
								quietMillis: 250,
								data: function( term, page ) {
									return {
										term:     term,
										action:   'wc_bookings_json_search_order',
										security: '" . wp_create_nonce( 'search-booking-order' ) . "'
									};
								},
								results: function( data, page ) {
									var terms = [];
									if ( data ) {
										$.each( data, function( id, text ) {
											terms.push( { id: id, text: text } );
										});
									}
									return { results: terms };
								},
								cache: true
							},
							multiple: false
						};
						select2_args.initSelection = function( element, callback ) {
							var data = {id: element.val(), text: element.attr( 'data-selected' )};
							return callback( data );
						};
						$( this ).select2( select2_args ).addClass( 'enhanced' );
					});
				" );
			} else {
				wc_enqueue_js( "
					$( '#_booking_order_id' ).filter( ':not(.enhanced)' ).each( function() {
						var select2_args = {
							allowClear:  true,
							placeholder: $( this ).data( 'placeholder' ),
							minimumInputLength: 1,
							escapeMarkup: function( m ) {
								return m;
							},
							ajax: {
								url:         '" . admin_url( 'admin-ajax.php' ) . "',
								dataType:    'json',
								quietMillis: 250,
								data: function( params ) {
									return {
										term:     params.term,
										action:   'wc_bookings_json_search_order',
										security: '" . wp_create_nonce( 'search-booking-order' ) . "'
									};
								},
								processResults: function( data ) {
									var terms = [];
									if ( data ) {
										$.each( data, function( id, text ) {
											terms.push({
												id: id,
												text: text
											});
										});
									}
									return {
										results: terms
									};
								},
								cache: true
							},
							multiple: false
						};
						$( this ).select2( select2_args ).addClass( 'enhanced' );
					});
				" );
			}
	}

	/**
	 * Returns an array of labels (statuses wrapped in gettext)
	 * @param  array  $statuses
	 * @deprecated since 1.9.13. $this->get_wc_booking_statuses now also comes with globalised strings.
	 * @return array
	 */
	public function get_labels_for_statuses( $statuses = array() ) {
		$labels = array();
		foreach ( $statuses as $status ) {
			$labels[ $status ] = __( $status, 'woocommerce-bookings' );
		}
		return $labels;
	}

	/**
	 * Save handler.
	 *
	 * @version 1.10.2
	 *
	 * @param  int     $post_id Post ID.
	 * @param  WP_Post $post    Post object.
	 */
	public function meta_box_save( $post_id, $post ) {
		if ( ! isset( $_POST['wc_bookings_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_bookings_details_meta_box_nonce'], 'wc_bookings_details_meta_box' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || intval( $_POST['post_ID'] ) !== $post_id ) {
			return $post_id;
		}

		if ( ! in_array( $post->post_type, $this->post_types ) ) {
			return $post_id;
		}

		if ( self::$saved_meta_box ) {
			return $post_id;
		}

		// We need this save event to run once to avoid potential endless loops. This would have been perfect:
		// remove_action( current_filter(), __METHOD__ );
		// But cannot be used due to https://github.com/woocommerce/woocommerce/issues/6485
		// When that is patched in core we can use the above. For now:
		self::$saved_meta_box = true;

		// Get booking object.
		$booking    = new WC_Booking( $post_id );
		$product_id = wc_clean( $_POST['product_or_resource_id'] );
		$start_date = explode( '-', wc_clean( $_POST['booking_start_date'] ) );
		$end_date   = explode( '-', wc_clean( $_POST['booking_end_date'] ) );
		$start_time = explode( ':', wc_clean( $_POST['booking_start_time'] ) );
		$end_time   = explode( ':', wc_clean( $_POST['booking_end_time'] ) );
		$start      = mktime( $start_time[0], $start_time[1], 0, $start_date[1], $start_date[2], $start_date[0] );
		$end        = mktime( $end_time[0], $end_time[1], 0, $end_date[1], $end_date[2], $end_date[0] );

		if ( strstr( $product_id, '=>' ) ) {
			list( $product_id, $resource_id ) = explode( '=>', $product_id );
		} else {
			$resource_id = 0;
		}

		$person_counts     = $booking->get_person_counts( 'edit' );
		$product           = wc_get_product( $product_id );
		$booking_types_ids = array_keys( $booking->get_person_counts( 'edit' ) );
		$product_types_ids = array_keys( $product->get_person_types() );
		$booking_persons   = array();

		foreach ( array_unique( array_merge( $booking_types_ids, $product_types_ids ) ) as $person_id ) {
			$booking_persons[ $person_id ] = absint( $_POST[ '_booking_person_' . $person_id ] );
		}

		$booking->set_props( array(
			'all_day'       => isset( $_POST['_booking_all_day'] ),
			'customer_id'   => absint( $_POST['_booking_customer_id'] ),
			'date_created'  => empty( $_POST['booking_date'] ) ? current_time( 'timestamp' ) : strtotime( $_POST['booking_date'] . ' ' . (int) $_POST['booking_date_hour'] . ':' . (int) $_POST['booking_date_minute'] . ':00' ),
			'end'           => $end,
			'order_id'      => isset( $_POST['_booking_order_id'] ) ? absint( $_POST['_booking_order_id'] ) : '',
			'parent_id'     => absint( $_POST['_booking_parent_id'] ),
			'person_counts' => $booking_persons,
			'product_id'    => absint( $product_id ),
			'resource_id'   => absint( $resource_id ),
			'start'         => $start,
			'status'        => wc_clean( $_POST['_booking_status'] ),
		) );

		do_action( 'woocommerce_admin_process_booking_object', $booking );

		$booking->save();

		do_action( 'woocommerce_booking_process_meta', $post_id );
	}
}

return new WC_Bookings_Details_Meta_Box();
