<?php
$attendees_url   = tribe( 'tickets.attendees' )->get_report_link( get_post( $post_id ) );
$total_tickets   = tribe( 'tickets.handler' )->get_total_event_capacity( $post_id );
$container_class = 'tribe_sectionheader ticket_list_container';
$container_class .= ( empty( $total_tickets ) ) ? ' tribe_no_capacity' : '' ;
?>
<div
	id="tribe_panel_base"
	class="ticket_panel panel_base"
	aria-hidden="false"
	data-save-prompt="<?php echo esc_attr( __( 'You have unsaved changes to your tickets. Discard those changes?', 'event-tickets' ) ); ?>"
>
	<div class="<?php echo esc_attr( $container_class ); ?>">
		<?php if ( ! empty( $tickets ) ) : ?>
			<div class="ticket_table_intro">
				<?php
				/**
				 * Allows for the insertion of total capacity element into the main ticket admin panel "header"
				 *
				 * @since 4.6
				 *
				 * @param int $post_id the id of the post
				 */
				do_action( 'tribe_events_tickets_capacity', $post_id );

				/**
				 * Allows for the insertion of additional elements (buttons/links) into the main ticket admin panel "header"
				 *
				 * @since 4.6
				 *
				 * @param int $post_id the id of the post
				 */
				do_action( 'tribe_events_tickets_post_capacity', $post_id );
				?>
				<a
					class="button-secondary"
					href="<?php echo esc_url( $attendees_url ); ?>"
				>
					<?php esc_html_e( 'View Attendees', 'event-tickets' ); ?>
				</a>
			</div>
			<?php tribe( 'tickets.admin.views' )->template( 'editor/list-table', array( 'tickets' => $tickets ) ); ?>
		<?php endif; ?>
	</div>
	<div>
		<?php
		/**
		 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
		 *
		 * @since 4.6
		 *
		 * @param int $post_id the id of the post
		 */
		do_action( 'tribe_events_tickets_new_ticket_buttons', $post_id );
		?>
		<button
			id="rsvp_form_toggle"
			class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
			aria-label="<?php esc_attr_e( 'Add a new RSVP', 'event-tickets' ); ?>"
		>
			<?php esc_html_e( 'New RSVP', 'event-tickets' ); ?>
		</button>


		<button id="settings_form_toggle" class="button-secondary tribe-button-icon tribe-button-icon-settings">
			<?php esc_html_e( 'Settings', 'event-tickets' ); ?>
		</button>

		<?php
		/**
		 * Allows for the insertion of warnings before the settings button
		 *
		 * @since 4.6
		 *
		 * @param int Post ID
		 */
		do_action( 'tribe_events_tickets_new_ticket_warnings', $post_id );
		?>

	</div>
	<?php
	/**
	 * Allows for the insertion of content at the end of the new ticket admin panel
	 *
	 * @since 4.6
	 *
	 * @param int Post ID
	 */
	do_action( 'tribe_events_tickets_after_new_ticket_panel', $post_id );
	?>

</div>
