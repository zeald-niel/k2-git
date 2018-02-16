<?php
$datepicker_format = Tribe__Date_Utils::datepicker_formats( tribe_get_option( 'datepickerFormat' ) );

if ( ! isset( $post_id ) ) {
	$post_id = get_the_ID();
}

if ( ! isset( $ticket_id ) ) {
	$provider = null;
	$ticket_id = null;
	$ticket = null;
} else {
	$provider = tribe_tickets_get_ticket_provider( $ticket_id );
	$ticket = $provider->get_ticket( $post_id, $ticket_id );

	if ( $ticket->start_date ) {
		$start_date = Tribe__Date_Utils::date_only( $ticket->start_date, false, $datepicker_format );
	} else {
		$start_date = null;
	}

	if ( $ticket->end_date ) {
		$end_date = Tribe__Date_Utils::date_only( $ticket->end_date, false, $datepicker_format );
	} else {
		$end_date = null;
	}
}

$timepicker_step = 30;
if ( class_exists( 'Tribe__Events__Main' ) ) {
	$timepicker_step = (int) tribe( 'tec.admin.event-meta-box' )->get_timepicker_step( 'start' );
}

$timepicker_round = '00:00:00';

$start_date_errors = array(
	'is-required' => __( 'Start sale date cannot be empty.', 'event-tickets' ),
	'is-less-or-equal-to' => __( 'Start sale date cannot be greater than End Sale date', 'event-tickets' ),
);
?>
<button class="accordion-header tribe_advanced_meta">
	<?php esc_html_e( 'Advanced', 'event-tickets' ); ?>
</button>
<section id="ticket_form_advanced" class="advanced accordion-content" data-datepicker_format="<?php echo esc_attr( tribe_get_option( 'datepickerFormat' ) ); ?>">
	<h4 class="accordion-label screen_reader_text"><?php esc_html_e( 'Advanced Settings', 'event-tickets' ); ?></h4>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_description"><?php esc_html_e( 'Description:', 'event-tickets' ); ?></label>
		<textarea
			rows="5"
			cols="40"
			name="ticket_description"
			class="ticket_field ticket_form_right"
			id="ticket_description"
		><?php echo esc_textarea( $ticket ? $ticket->description : null ) ?></textarea>
		<div class="input_block">
			<label class="tribe_soft_note">
				<input
					type="checkbox"
					id="tribe_tickets_show_description"
					name="ticket_show_description"
					value="1"
					class="ticket_field ticket_form_left"
					<?php checked( true, $ticket ? $ticket->show_description : true ); ?>
				>
				<?php esc_html_e( 'Show description on front end ticket form.', 'event-tickets' ); ?>
			</label>
		</div>
	</div>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_start_date"><?php esc_html_e( 'Start sale:', 'event-tickets' ); ?></label>
		<div class="ticket_form_right">
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-start_date ticket_field"
				name="ticket_start_date"
				id="ticket_start_date"
				value="<?php echo esc_attr( $ticket ? $start_date : null ); ?>"
				data-validation-type="datepicker"
				data-validation-is-less-or-equal-to="#ticket_end_date"
				data-validation-error="<?php echo esc_attr( json_encode( $start_date_errors ) ) ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ) ?></span>
			<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-start_time ticket_field"
				name="ticket_start_time"
				id="ticket_start_time"
				<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : '' ?>
				data-step="<?php echo esc_attr( $timepicker_step ); ?>"
				data-round="<?php echo esc_attr( $timepicker_round ); ?>"
				value="<?php echo esc_attr( $ticket ? $ticket->start_time : null ); ?>"
				aria-label="<?php esc_html_e( 'Ticket start date', 'event-tickets' ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ) ?></span>
			<span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'If you do not set a start sale date, tickets will be available immediately.', 'event-tickets' ); ?>"></span>
		</div>
	</div>
	<div class="input_block">
		<label class="ticket_form_label ticket_form_left" for="ticket_end_date"><?php esc_html_e( 'End sale:', 'event-tickets' ); ?></label>
		<div class="ticket_form_right">
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-end_date ticket_field"
				name="ticket_end_date"
				id="ticket_end_date"
				value="<?php echo esc_attr( $ticket ? $end_date : null ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ) ?></span>
			<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-end_time ticket_field"
				name="ticket_end_time"
				id="ticket_end_time"
				<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : '' ?>
				data-step="<?php echo esc_attr( $timepicker_step ); ?>"
				data-round="<?php echo esc_attr( $timepicker_round ); ?>"
				value="<?php echo esc_attr( $ticket ? $ticket->end_time : null ); ?>"
				aria-label="<?php esc_html_e( 'Ticket end date', 'event-tickets' ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ) ?></span>
			<span
				class="dashicons dashicons-editor-help"
				<?php if ( 'tribe_event' === get_post_type( $post_id ) ) : ?>
					title="<?php esc_attr_e( 'If you do not set an end sale date, tickets will be available until the event begins.', 'event-tickets' ); ?>"
				<?php else : ?>
					title="<?php esc_attr_e( 'If you do not set an end sale date, tickets sales will never end.', 'event-tickets' ); ?>"
				<?php endif; ?>
			></span>
		</div>
	</div>
	<div id="advanced_fields">
		<?php
		/**
		 * Allows for the insertion of additional content into the ticket edit form - advanced section
		 *
		 * @since 4.6
		 *
		 * @param int      $post_id  Post ID
		 * @param int|null $ticket_id  Ticket ID
		 */
		do_action( 'tribe_events_tickets_metabox_edit_advanced', $post_id, $ticket_id );
		?>
	</div>
</section><!-- #ticket_form_advanced -->
