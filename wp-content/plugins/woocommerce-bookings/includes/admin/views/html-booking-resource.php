<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce_booking_resource wc-metabox closed">
	<h3>
		<button type="button" class="remove_booking_resource button" rel="<?php echo esc_attr( absint( $resource->get_id() ) ); ?>"><?php _e( 'Remove', 'woocommerce-bookings' ); ?></button>

		<a href="<?php echo admin_url( 'post.php?post=' . absint( $resource->get_id() ) . '&action=edit' ); ?>" target="_blank" class="edit_resource"><?php _e( 'Edit resource', 'woocommerce-bookings' ); ?> &rarr;</a>

		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce-bookings' ); ?>"></div>

		<strong>#<?php echo esc_html( $resource->get_id() ); ?> &mdash; <span class="resource_name"><?php echo esc_html( $resource->get_name() ); ?></span></strong>

		<input type="hidden" name="resource_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $resource->get_id() ); ?>" />
		<input type="hidden" name="resource_title[<?php echo $loop; ?>]" value="<?php echo esc_attr( '#' . $resource->get_id(). ' - ' . $resource->get_name() ); ?>" />
		<input type="hidden" class="resource_menu_order" name="resource_menu_order[<?php echo $loop; ?>]" value="<?php echo $loop; ?>" />
	</h3>
	<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
		<tbody>
			<tr>
				<td>
					<label><?php _e( 'Base Cost', 'woocommerce-bookings' ); ?>:</label>
					<input type="number" class="" name="resource_cost[<?php echo $loop; ?>]" value="<?php if ( ! empty( $resource_base_cost ) ) echo esc_attr( $resource_base_cost ); ?>" placeholder="0.00" step="0.01" />
					<?php do_action( 'woocommerce_bookings_after_resource_cost', $resource->get_id(), $post->ID ); ?>
				</td>
				<td>
					<label><?php _e( 'Block Cost', 'woocommerce-bookings' ); ?>:</label>
					<input type="number" class="" name="resource_block_cost[<?php echo $loop; ?>]" value="<?php if ( ! empty( $resource_block_cost ) ) echo esc_attr( $resource_block_cost ); ?>" placeholder="0.00" step="0.01" />
					<?php do_action( 'woocommerce_bookings_after_resource_block_cost', $resource->get_id(), $post->ID ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
