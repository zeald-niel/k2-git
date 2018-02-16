<?php
$provider      = $ticket->provider_class;
$provider_obj  = call_user_func( array( $provider, 'get_instance' ) );
$inventory     = $ticket->inventory();
$available     = $ticket->available();
$capacity      = $ticket->capacity();
$stock         = $ticket->stock();
$needs_warning = false;
$mode          = $ticket->global_stock_mode();
$event         = $ticket->get_event();

// If we don't have an event we should't even continue
if ( ! $event ) {
	return;
}

if (
	'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $ticket->provider_class
	&& -1 !== $capacity
) {
	$product = wc_get_product( $ticket->ID );
	$shared_stock = new Tribe__Tickets__Global_Stock( $event->ID );
	$needs_warning = (int) $inventory !== (int) $stock;

	// We remove the warning flag when shared stock is used
	if ( $shared_stock->is_enabled() && (int) $stock >= (int) $shared_stock->get_stock_level() ) {
		$needs_warning = false;
	}
}

?>
<tr class="<?php echo esc_attr( $provider ); ?> is-expanded" data-ticket-order-id="order_<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-type-id="<?php echo esc_attr( $ticket->ID ); ?>">
	<td class="column-primary ticket_name <?php echo esc_attr( $provider ); ?>" data-label="<?php esc_html_e( 'Ticket Type:', 'event-tickets' ); ?>">
		<span class="dashicons dashicons-screenoptions tribe-handle"></span>
		<input
			type="hidden"
			class="tribe-ticket-field-order"
			name="tribe-tickets[list][<?php echo esc_attr( $ticket->ID ); ?>][order]"
			value="<?php echo esc_attr( $ticket->menu_order ); ?>"
			<?php echo 'Tribe__Tickets__RSVP' === $ticket->provider_class ? 'disabled' : ''; ?>
		>

		<?php
		/**
		 * Fires before the ticket name is printed in the tickets table.
		 *
		 * @since 4.6.2
		 *
		 * @param Tribe__Tickets__Ticket_Object $ticket       The current ticket object.
		 * @param Tribe__Tickets__Tickets       $provider_obj The current ticket provider object.
		 */
		do_action( 'event_tickets_ticket_list_before_ticket_name', $ticket, $provider_obj ); ?>

		<?php echo esc_html( $ticket->name ); ?>

		<?php
		/**
		 * Fires after the ticket name is printed in the tickets table.
		 *
		 * @since 4.6.2
		 *
		 * @param Tribe__Tickets__Ticket_Object $ticket       The current ticket object.
		 * @param Tribe__Tickets__Tickets       $provider_obj The current ticket provider object.
		 */
		do_action( 'event_tickets_ticket_list_after_ticket_name', $ticket, $provider_obj ); ?>
	</td>

	<?php
	/**
	 * Allows for the insertion of additional content into the main ticket admin panel after the tickets listing
	 *
	 * @since 4.6
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 * @param obj ecommerce provider object
	 */
	do_action( 'tribe_events_tickets_ticket_table_add_tbody_column', $ticket, $provider_obj );
	?>

	<td class="ticket_capacity">
		<span class='tribe-mobile-only'><?php esc_html_e( 'Capacity:', 'event-tickets' ); ?></span>
		<?php tribe_tickets_get_readable_amount( $capacity, $mode, true ); ?>
	</td>

	<td class="ticket_available">
		<span class='tribe-mobile-only'><?php esc_html_e( 'Available:', 'event-tickets' ); ?></span>
		<?php if ( $needs_warning ) : ?>
			<span class="dashicons dashicons-warning required" title="<?php esc_attr_e( 'The number of Complete ticket sales does not match the number of attendees. Please check the Attendees list and adjust ticket stock in WooCommerce as needed.', 'event-tickets' ) ?>"></span>
		<?php endif; ?>

		<?php tribe_tickets_get_readable_amount( $available, $mode, true ); ?>
	</td>

	<td class="ticket_edit">
		<?php
		printf(
			"<button data-provider='%s' data-ticket-id='%s' title='%s' class='ticket_edit_button'><span class='ticket_edit_text'>%s</span></a>",
			esc_attr( $ticket->provider_class ),
			esc_attr( $ticket->ID ),
			esc_attr( sprintf( __( '( Ticket ID: %d )', 'tribe-tickets' ), $ticket->ID ) ),
			esc_html( $ticket->name )
		);
		?>
	</td>
</tr>
