<?php
/**
 * Booster for WooCommerce Invoice
 *
 * @version 2.5.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Invoice' ) ) :

class WCJ_Invoice {

	public $order_id;
	public $invoice_type;

	/**
	 * Constructor.
	 */
	function __construct( $order_id, $invoice_type ) {
		$this->order_id     = $order_id;
		$this->invoice_type = $invoice_type;
	}

	/**
	 * is_created.
	 */
	function is_created() {
		return ( '' != get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_date', true ) ) ? true : false;
	}

	/**
	 * delete.
	 *
	 * @version 2.3.0
	 */
	function delete() {
		update_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_number_id', 0 );
		//update_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_number', 0 );
		update_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_date', '' );

		if ( 'yes' === get_option( 'wcj_invoicing_' . $this->invoice_type . '_sequential_enabled' ) ) {
			$option_name = 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter';
			$the_invoice_counter = get_option( $option_name, 1 );
			update_option( $option_name, ( $the_invoice_counter - 1 ) );
		}
	}

	/**
	 * create.
	 *
	 * @version 2.5.9
	 * @todo    use mysql transaction enabled (as in "wcj_order_number_use_mysql_transaction_enabled")
	 */
	function create( $date = '' ) {
		$order_id = $this->order_id;
		$invoice_type = $this->invoice_type;
		if ( 'yes' === apply_filters( 'booster_get_option', 'no', get_option( 'wcj_invoicing_' . $invoice_type . '_skip_zero_total', 'no' ) ) ) {
			$_order = wc_get_order( $order_id );
			if ( 0 == $_order->get_total() ) {
				return;
			}
		}
		//if ( '' == get_post_meta( $order_id, '_wcj_invoicing_' . $invoice_type . '_number', true ) ) {
			if ( 'yes' === get_option( 'wcj_invoicing_' . $invoice_type . '_sequential_enabled' ) ) {
				$the_invoice_number = get_option( 'wcj_invoicing_' . $invoice_type . '_numbering_counter', 1 );
				update_option( 'wcj_invoicing_' . $invoice_type . '_numbering_counter', ( $the_invoice_number + 1 ) );
			} else {
				$the_invoice_number = $order_id;
			}
			$the_date = ( '' == $date ) ? current_time( 'timestamp' ) : $date;
			update_post_meta( $order_id, '_wcj_invoicing_' . $invoice_type . '_number_id', $the_invoice_number );
//			update_post_meta( $order_id, '_wcj_invoicing_' . $invoice_type . '_number', $this->get_invoice_full_number( $the_invoice_number ) );
			update_post_meta( $order_id, '_wcj_invoicing_' . $invoice_type . '_date', $the_date );
		//}
	}

	/**
	 * get_file_name.
	 */
	function get_file_name() {
		$the_file_name = do_shortcode( get_option( 'wcj_invoicing_' . $this->invoice_type . '_file_name', 'invoice-' . $this->order_id ) . '.pdf' );
		if ( '' == $the_file_name ) $the_file_name = 'invoice';
		$the_file_name = sanitize_file_name( $the_file_name );
		return apply_filters( 'wcj_get_' . $this->invoice_type . '_file_name', $the_file_name, $this->order_id );
	}

	/**
	 * get_invoice_date.
	 */
	function get_invoice_date() {
		$the_date = get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_date', true );
		return apply_filters( 'wcj_get_' . $this->invoice_type . '_date', $the_date, $this->order_id );
	}

	/**
	 * get_invoice_number.
	 */
	function get_invoice_number() {
		//$the_number = get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_number', true );
		$the_number_id = get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_number_id', true );
		//$the_number = $this->get_invoice_full_number( $the_number_id );
		$the_prefix = get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_prefix' );
		$the_suffix = get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_suffix' );
		$the_number = do_shortcode( sprintf( '%s%0' . get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter_width', 0 ). 'd%s',
			$the_prefix,
			$the_number_id,
			$the_suffix ) );
		return apply_filters( 'wcj_get_' . $this->invoice_type . '_number', $the_number, $this->order_id );
	}

	/**
	 * get_invoice_full_number.
	 *
	private function get_invoice_full_number( $the_number ) {
		$the_prefix = get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_prefix' );
		$the_suffix = get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_suffix' );
		return do_shortcode( sprintf( '%s%0' . get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter_width', 0 ). 'd%s',
			$the_prefix,
			$the_number,
			$the_suffix ) );
	}
	/**/
}

endif;
