<?php
$is_admin = tribe_is_truthy( tribe_get_request_var( 'is_admin', is_admin() ) );

if ( ! isset( $post_id ) ) {
	$post_id = get_the_ID();
}

if ( ! isset( $ticket_id ) ) {
	$provider = null;
	$provider_class = null;
	$ticket_id = null;
	$ticket = null;

	if ( ! $is_admin ) {
		$provider_class = 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main';
	}
} else {
	$provider = tribe_tickets_get_ticket_provider( $ticket_id );
	$provider_class = get_class( $provider );
	$ticket = $provider->get_ticket( $post_id, $ticket_id );
}

$modules = Tribe__Tickets__Tickets::modules();
?>

<div id="tribe_panel_edit" class="ticket_panel panel_edit tribe-validation" aria-hidden="true">
	<?php
	/**
	 * Allows for the insertion of additional elements into the main ticket edit panel
	 *
	 * @since 4.6
	 *
	 * @param int Post ID
	 * @param int Ticket ID
	 */
	do_action( 'tribe_events_tickets_pre_edit', $post_id, $ticket_id );
	?>

	<div id="ticket_form" class="ticket_form tribe_sectionheader">
		<div id="ticket_form_table" class="eventtable ticket_form">
			<div
				class="tribe-dependent"
				data-depends="#Tribe__Tickets__RSVP_radio"
				data-condition-is-not-checked
			>
				<h4
					id="ticket_title_add"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-empty
				>
					<?php esc_html_e( 'Add new ticket', 'event-tickets' ); ?>
				</h4>
				<h4
					id="ticket_title_edit"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-not-empty
				>
					<?php esc_html_e( 'Edit ticket', 'event-tickets' ); ?>
				</h4>
			</div>
			<div
				class="tribe-dependent"
				data-depends="#Tribe__Tickets__RSVP_radio"
				data-condition-is-checked
			>
				<h4
					id="rsvp_title_add"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-empty
				>
					<?php esc_html_e( 'Add new RSVP', 'event-tickets' ); ?>
				</h4>
				<h4
					id="rsvp_title_edit"
					class="ticket_form_title tribe-dependent"
					data-depends="#ticket_id"
					data-condition-is-not-empty
				>
					<?php esc_html_e( 'Edit RSVP', 'event-tickets' ); ?>
				</h4>
			</div>
			<section id="ticket_form_main" class="main">
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left" for="ticket_name"><?php esc_html_e( 'Type:', 'event-tickets' ); ?></label>
					<input
						type='text'
						id='ticket_name'
						name='ticket_name'
						class="ticket_field ticket_form_right"
						size='25'
						value="<?php echo esc_attr( $ticket ? $ticket->name : null ); ?>"
						data-validation-is-required
						data-validation-error="<?php esc_attr_e( 'Ticket Type is a required field.', 'event-tickets' ); ?>"
					/>
					<span class="tribe_soft_note ticket_form_right"><?php esc_html_e( 'Ticket type name shows on the front end and emailed tickets', 'event-tickets' ); ?></span>
				</div>
				<fieldset id="tribe_ticket_provider_wrapper" class="input_block" aria-hidden="true" >
					<legend class="ticket_form_label"><?php esc_html_e( 'Sell using:', 'event-tickets' ); ?></legend>
					<?php foreach ( $modules as $class => $module ) : ?>
						<input
							type="radio"
							name="ticket_provider"
							id="<?php echo esc_attr( $class . '_radio' ); ?>"
							value="<?php echo esc_attr( $class ); ?>"
							class="ticket_field ticket_provider"
							tabindex="-1"
							<?php checked( true, $provider_class ? $provider_class === $class : false ); ?>
						>
						<span>
							<?php
							/**
							 * Allows for the editing of the module name before output
							 *
							 * @since 4.6
							 *
							 * @param string $module the module name
							 */
							echo esc_html( apply_filters( 'tribe_events_tickets_module_name', $module ) );
							?>
						</span>
					<?php endforeach; ?>
				</fieldset>
				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form - main section
				 *
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_main', $post_id, $ticket_id ); ?>
			</section>

			<div class="accordion">
				<?php tribe( 'tickets.admin.views' )->template( 'editor/fieldset/advanced', array( 'post_id' => $post_id, 'ticket_id' => $ticket_id ) ); ?>

				<?php tribe( 'tickets.admin.views' )->template( 'editor/fieldset/history', array( 'post_id' => $post_id, 'ticket_id' => $ticket_id ) ); ?>

				<?php
				/**
				 * Allows for the insertion of additional content sections into the ticket edit form accordion
				 *
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_metabox_edit_accordion_content', $post_id, $ticket_id );
				?>
			</div>

			<?php
			/**
			 * Allows for the insertion of additional elements into the main ticket edit panel below the accordion section
			 *
			 * @since 4.6
			 *
			 * @param int Post ID
			 * @param int Ticket ID
			 */
			do_action( 'tribe_events_tickets_post_accordion', $post_id, $ticket_id );
			?>
			<div class="ticket_bottom">
				<input
					type="hidden"
					name="ticket_id"
					id="ticket_id"
					class="ticket_field"
					value="<?php echo esc_attr( $ticket_id ); ?>"
				/>
				<input
					type="button"
					id="ticket_form_save"
					class="button-primary tribe-dependent tribe-validation-submit"
					name="ticket_form_save"
					value="<?php esc_attr_e( 'Save ticket', 'event-tickets' ); ?>"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-not-checked
				/>
				<input
					type="button"
					id="rsvp_form_save"
					class="button-primary tribe-dependent tribe-validation-submit"
					name="ticket_form_save"
					value="<?php esc_attr_e( 'Save RSVP', 'event-tickets' ); ?>"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-checked
				/>
				<input
					type="button"
					id="ticket_form_cancel"
					class="button-secondary"
					name="ticket_form_cancel"
					value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>"
				/>

				<?php
				/**
				 * Allows for the insertion of additional content into the ticket edit form bottom (buttons) section
				 *
				 * @since 4.6
				 *
				 * @param int Post ID
				 * @param int Ticket ID
				 */
				do_action( 'tribe_events_tickets_bottom', $post_id, $ticket_id );
				?>

				<div id="ticket_bottom_right">
					<?php
					/**
					 * Allows for the insertion of additional content into the ticket edit form bottom (links on right) section
					 *
					 * @since 4.6
					 *
					 * @param int Post ID
					 * @param int Ticket ID
					 */
					do_action( 'tribe_events_tickets_bottom_right', $post_id, $ticket_id );
					?>
				</div>
			</div>
		</div>
	</div>
</div>
