<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="bookings_availability" class="panel woocommerce_options_panel">
	<div class="options_group"><?php
		$min_date      = $bookable_product->get_min_date_value( 'edit' );
		$min_date_unit = $bookable_product->get_min_date_unit( 'edit' );
		$max_date      = $bookable_product->get_max_date_value( 'edit' );
		$max_date_unit = $bookable_product->get_max_date_unit( 'edit' );

		woocommerce_wp_text_input( array(
			'id'                => '_wc_booking_qty',
			'label'             => __( 'Max bookings per block', 'woocommerce-bookings' ),
			'description'       => __( 'The maximum bookings allowed for each block. Can be overridden at resource level.', 'woocommerce-bookings' ),
			'value'             => $bookable_product->get_qty( 'edit' ),
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array(
				'min'           => '',
				'step' 	        => '1',
			),
		) );

		?>
		<p class="form-field">
			<label for="_wc_booking_min_date"><?php _e( 'Minimum block bookable', 'woocommerce-bookings' ); ?></label>
			<input type="number" name="_wc_booking_min_date" id="_wc_booking_min_date" value="<?php echo $min_date; ?>" step="1" min="0" style="margin-right: 7px; width: 4em;">
			<select name="_wc_booking_min_date_unit" id="_wc_booking_min_date_unit" class="short" style="margin-right: 7px;">
				<option value="month" <?php selected( $min_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-bookings' ); ?></option>
				<option value="week" <?php selected( $min_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-bookings' ); ?></option>
				<option value="day" <?php selected( $min_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-bookings' ); ?></option>
				<option value="hour" <?php selected( $min_date_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-bookings' ); ?></option>
			</select> <?php _e( 'into the future', 'woocommerce-bookings' ); ?>
		</p>
		<p class="form-field">
			<label for="_wc_booking_max_date"><?php _e( 'Maximum block bookable', 'woocommerce-bookings' ); ?></label>
			<input type="number" name="_wc_booking_max_date" id="_wc_booking_max_date" value="<?php echo $max_date; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
			<select name="_wc_booking_max_date_unit" id="_wc_booking_max_date_unit" class="short" style="margin-right: 7px;">
				<option value="month" <?php selected( $max_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-bookings' ); ?></option>
				<option value="week" <?php selected( $max_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-bookings' ); ?></option>
				<option value="day" <?php selected( $max_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-bookings' ); ?></option>
				<option value="hour" <?php selected( $max_date_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-bookings' ); ?></option>
			</select> <?php _e( 'into the future', 'woocommerce-bookings' ); ?>
		</p>
		<p class="form-field _wc_booking_buffer_period">
			<label for="_wc_booking_buffer_period"><?php _e( 'Require a buffer period of', 'woocommerce-bookings' ); ?></label>
			<input type="number" name="_wc_booking_buffer_period" id="_wc_booking_buffer_period" value="<?php echo esc_attr( $bookable_product->get_buffer_period( 'edit' ) ); ?>" step="1" min="0" style="margin-right: 7px; width: 4em;">
			<span class='_wc_booking_buffer_period_unit'></span>
			<?php _e( 'between bookings', 'woocommerce-bookings' ); ?>
		</p>
		<?php

		woocommerce_wp_checkbox(
			array(
				'id'          => '_wc_booking_apply_adjacent_buffer',
				'value'       => $bookable_product->get_apply_adjacent_buffer( 'edit' ) ? 'yes' : 'no',
				'label'       => __( 'Adjacent Buffering?', 'woocommerce-bookings' ),
				'description' => __( 'By default buffer period applies forward into the future of a booking. Enabling this option will apply adjacently (before and after Bookings).', 'woocommerce-bookings' ),
			)
		);

		woocommerce_wp_select(
			array(
				'id'                => '_wc_booking_default_date_availability',
				'label'             => __( 'All dates are...', 'woocommerce-bookings' ),
				'description'       => '',
				'value'             => $bookable_product->get_default_date_availability( 'edit' ),
				'options'           => array(
					'available'     => __( 'available by default', 'woocommerce-bookings' ),
					'non-available' => __( 'not-available by default', 'woocommerce-bookings' ),
				),
				'description'       => __( 'This option affects how you use the rules below.', 'woocommerce-bookings' )
			)
		);

		woocommerce_wp_select(
			array(
				'id'          => '_wc_booking_check_availability_against',
				'label'       => __( 'Check rules against...', 'woocommerce-bookings' ),
				'description' => '',
				'value'       => $bookable_product->get_check_start_block_only( 'edit' ) ? 'start' : '',
				'options'     => array(
					''        => __( 'All blocks being booked', 'woocommerce-bookings' ),
					'start'   => __( 'The starting block only', 'woocommerce-bookings' ),
				),
				'description' => __( 'This option affects how bookings are checked for availability.', 'woocommerce-bookings' )
			)
		);
		?>
		<p class="form-field _wc_booking_first_block_time_field">
			<label for="_wc_booking_first_block_time"><?php _e( 'First block starts at...', 'woocommerce-bookings' ); ?></label>
			<input type="time" name="_wc_booking_first_block_time" id="_wc_booking_first_block_time" value="<?php echo $bookable_product->get_first_block_time( 'edit' ); ?>" placeholder="HH:MM" />
		</p>
	</div>
	<div class="options_group">
		<div class="table_grid">
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort" width="1%">&nbsp;</th>
						<th><?php esc_html_e( 'Range type', 'woocommerce-bookings' ); ?></th>
						<th><?php esc_html_e( 'Range', 'woocommerce-bookings' ); ?></th>
						<th></th>
						<th></th>
						<th><?php esc_html_e( 'Bookable', 'woocommerce-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'If not bookable, users won\'t be able to choose this block for their booking.', 'woocommerce-bookings' ); ?>">[?]</a></th>
						<th><?php esc_html_e( 'Priority', 'woocommerce-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php echo esc_attr( get_wc_booking_priority_explanation() ); ?>">[?]</a></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="6">
							<a href="#" class="button add_row" data-row="<?php
								ob_start();
								include( 'html-booking-availability-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php _e( 'Add Range', 'woocommerce-bookings' ); ?></a>
							<span class="description"><?php echo esc_html( get_wc_booking_rules_explanation() ); ?></span>
						</th>
					</tr>
				</tfoot>
				<tbody id="availability_rows">
					<?php
						$values = $bookable_product->get_availability( 'edit' );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $availability ) {
								include( 'html-booking-availability-fields.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
