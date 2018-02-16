<?php
/**
 * Admin new booking email
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( wc_booking_order_requires_confirmation( $booking->get_order() ) && $booking->get_status() == 'pending-confirmation' ) {
	$opening_paragraph = __( 'A booking has been made by %s and is awaiting your approval. The details of this booking are as follows:', 'woocommerce-bookings' );
} else {
	$opening_paragraph = __( 'A new booking has been made by %s. The details of this booking are as follows:', 'woocommerce-bookings' );
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading );

$order = $booking->get_order();

if ( $order ) {
	if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		$first_name = $order->billing_first_name;
		$last_name = $order->billing_last_name;
	} else {
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
	}
}

?>

<?php if ( ! empty( $first_name ) && ! empty( $last_name ) ) : ?>
	<p><?php printf( $opening_paragraph, $first_name . ' ' . $last_name ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Booked Product', 'woocommerce-bookings' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->get_product()->get_title(); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking ID', 'woocommerce-bookings' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->get_id(); ?></td>
		</tr>
		<?php if ( $booking->has_resources() && ( $resource = $booking->get_resource() ) ) : ?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking Type', 'woocommerce-bookings' ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo $resource->post_title; ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking Start Date', 'woocommerce-bookings' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->get_start_date(); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Booking End Date', 'woocommerce-bookings' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $booking->get_end_date(); ?></td>
		</tr>
		<?php if ( $booking->has_persons() ) : ?>
			<?php
				foreach ( $booking->get_persons() as $id => $qty ) :
					if ( 0 === $qty ) {
						continue;
					}

					$person_type = ( 0 < $id ) ? get_the_title( $id ) : __( 'Person(s)', 'woocommerce-bookings' );
			?>
				<tr>
					<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php echo $person_type; ?></th>
					<td style="text-align:left; border: 1px solid #eee;"><?php echo $qty; ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( wc_booking_order_requires_confirmation( $booking->get_order() ) && $booking->get_status() == 'pending-confirmation' ) : ?>
<p><?php _e( 'This booking is awaiting your approval. Please check it and inform the customer if the date is available or not.', 'woocommerce-bookings' ); ?></p>
<?php endif; ?>

<p><?php echo make_clickable( sprintf( __( 'You can view and edit this booking in the dashboard here: %s', 'woocommerce-bookings' ), admin_url( 'post.php?post=' . $booking->get_id() . '&action=edit' ) ) ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
