<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking admin
 */
class WC_Bookings_Admin {
	private static $_this;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$_this = $this;

		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'admin_init', array( $this, 'init_tabs' ) );
		add_action( 'admin_init', array( $this, 'include_post_type_handlers' ) );
		add_action( 'admin_init', array( $this, 'include_meta_box_handlers' ) );
		add_action( 'admin_init', array( $this, 'redirect_new_add_booking_url' ) );
		add_filter( 'product_type_options', array( $this, 'product_type_options' ) );
		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'booking_data' ) );
		add_filter( 'product_type_options', array( $this, 'booking_product_type_options' ) );
		add_action( 'load-options-general.php', array( $this, 'reset_ics_exporter_timezone_cache' ) );
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'booking_display' ), 10, 3 );
		add_action( 'woocommerce_debug_tools', array( $this, 'bookings_debug_tools' ) );

		// Saving data.
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ), 20 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'set_props' ), 20 );

		include( 'class-wc-bookings-menus.php' );
	}

	public function init() {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			add_action( 'woocommerce_duplicate_product', array( $this, 'woocommerce_duplicate_product_pre_wc30' ), 10, 2 );
		} else {
			add_action( 'woocommerce_product_duplicate', array( $this, 'woocommerce_duplicate_product' ), 10, 2 );
		}
	}

	/**
	 * Bookings debug tools in WooCommerce > Status > Tools.
	 *
	 * @param array $tools
	 */
	public function bookings_debug_tools( $tools ) {
		$bookings_tools = array(
			'clean_person_types' => array(
				'name'     => __( 'Clean unused Person Types from DB', 'woocommerce-bookings' ),
				'button'   => __( 'Clean Person Types', 'woocommerce-bookings' ),
				'desc'     => __( 'This tool will clean the person types that are not used by any booking or a product.', 'woocommerce-bookings' ),
				'callback' => array( 'WC_Bookings_Tools', 'clean_person_types' ),
			),
		);

		return array_merge( $tools, $bookings_tools );
	}

	/**
	 * Save Booking data for the product in 2.6.x.
	 *
	 * @param int $post_id
	 */
	public function save_product_data( $post_id ) {
		if ( version_compare( WC_VERSION, '3.0', '>=' ) || 'booking' !== sanitize_title( stripslashes( $_POST['product-type'] ) ) ) {
			return;
		}
		$product = new WC_Product_Booking( $post_id );
		$this->set_props( $product );
		$product->save();
	}

	/**
	 * Get posted availability fields and format.
	 *
	 * @return array
	 */
	private function get_posted_availability() {
		$availability = array();
		$row_size     = isset( $_POST['wc_booking_availability_type'] ) ? sizeof( $_POST['wc_booking_availability_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST['wc_booking_availability_type'][ $i ] );
			$availability[ $i ]['bookable'] = wc_clean( $_POST['wc_booking_availability_bookable'][ $i ] );
			$availability[ $i ]['priority'] = intval( $_POST['wc_booking_availability_priority'][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_date'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_date'][ $i ] );
				break;
				case 'months' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_month'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_month'][ $i ] );
				break;
				case 'weeks' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_week'][ $i ] );
				break;
				case 'days' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_day_of_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_day_of_week'][ $i ] );
				break;
				case 'time' :
				case 'time:1' :
				case 'time:2' :
				case 'time:3' :
				case 'time:4' :
				case 'time:5' :
				case 'time:6' :
				case 'time:7' :
					$availability[ $i ]['from'] = wc_booking_sanitize_time( $_POST['wc_booking_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_booking_sanitize_time( $_POST['wc_booking_availability_to_time'][ $i ] );
				break;
				case 'time:range' :
					$availability[ $i ]['from'] = wc_booking_sanitize_time( $_POST['wc_booking_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_booking_sanitize_time( $_POST['wc_booking_availability_to_time'][ $i ] );

					$availability[ $i ]['from_date'] = wc_clean( $_POST['wc_booking_availability_from_date'][ $i ] );
					$availability[ $i ]['to_date']   = wc_clean( $_POST['wc_booking_availability_to_date'][ $i ] );
				break;
			}
		}
		return $availability;
	}

	/**
	 * Get posted pricing fields and format.
	 *
	 * @return array
	 */
	private function get_posted_pricing() {
		$pricing = array();
		$row_size     = isset( $_POST['wc_booking_pricing_type'] ) ? sizeof( $_POST['wc_booking_pricing_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$pricing[ $i ]['type']          = wc_clean( $_POST['wc_booking_pricing_type'][ $i ] );
			$pricing[ $i ]['cost']          = wc_clean( $_POST['wc_booking_pricing_cost'][ $i ] );
			$pricing[ $i ]['modifier']      = wc_clean( $_POST['wc_booking_pricing_cost_modifier'][ $i ] );
			$pricing[ $i ]['base_cost']     = wc_clean( $_POST['wc_booking_pricing_base_cost'][ $i ] );
			$pricing[ $i ]['base_modifier'] = wc_clean( $_POST['wc_booking_pricing_base_cost_modifier'][ $i ] );

			switch ( $pricing[ $i ]['type'] ) {
				case 'custom' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_booking_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_booking_pricing_to_date'][ $i ] );
				break;
				case 'months' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_booking_pricing_from_month'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_booking_pricing_to_month'][ $i ] );
				break;
				case 'weeks' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_booking_pricing_from_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_booking_pricing_to_week'][ $i ] );
				break;
				case 'days' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_booking_pricing_from_day_of_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_booking_pricing_to_day_of_week'][ $i ] );
				break;
				case 'time' :
				case 'time:1' :
				case 'time:2' :
				case 'time:3' :
				case 'time:4' :
				case 'time:5' :
				case 'time:6' :
				case 'time:7' :
					$pricing[ $i ]['from'] = wc_booking_sanitize_time( $_POST['wc_booking_pricing_from_time'][ $i ] );
					$pricing[ $i ]['to']   = wc_booking_sanitize_time( $_POST['wc_booking_pricing_to_time'][ $i ] );
				break;
				case 'time:range' :
					$pricing[ $i ]['from'] = wc_booking_sanitize_time( $_POST['wc_booking_pricing_from_time'][ $i ] );
					$pricing[ $i ]['to']   = wc_booking_sanitize_time( $_POST['wc_booking_pricing_to_time'][ $i ] );

					$pricing[ $i ]['from_date'] = wc_clean( $_POST['wc_booking_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to_date']   = wc_clean( $_POST['wc_booking_pricing_to_date'][ $i ] );
				break;
				default :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_booking_pricing_from'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_booking_pricing_to'][ $i ] );
				break;
			}
		}
		return $pricing;
	}

	/**
	 * Get posted person types.
	 *
	 * @return array
	 */
	private function get_posted_person_types( $product ) {
		$person_types = array();

		if ( isset( $_POST['person_id'] ) && isset( $_POST['_wc_booking_has_persons'] ) ) {
			$person_ids         = $_POST['person_id'];
			$person_menu_order  = $_POST['person_menu_order'];
			$person_name        = $_POST['person_name'];
			$person_cost        = $_POST['person_cost'];
			$person_block_cost  = $_POST['person_block_cost'];
			$person_description = $_POST['person_description'];
			$person_min         = $_POST['person_min'];
			$person_max         = $_POST['person_max'];
			$max_loop           = max( array_keys( $_POST['person_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i ++ ) {
				if ( ! isset( $person_ids[ $i ] ) ) {
					continue;
				}
				$person_id   = absint( $person_ids[ $i ] );
				$person_type = new WC_Product_Booking_Person_Type( $person_id );
				$person_type->set_props( array(
					'name'        => wc_clean( stripslashes( $person_name[ $i ] ) ),
					'description' => wc_clean( stripslashes( $person_description[ $i ] ) ),
					'sort_order'  => absint( $person_menu_order[ $i ] ),
					'cost'        => wc_clean( $person_cost[ $i ] ),
					'block_cost'  => wc_clean( $person_block_cost[ $i ] ),
					'min'         => wc_clean( $person_min[ $i ] ),
					'max'         => wc_clean( $person_max[ $i ] ),
					'parent_id'   => $product->get_id(),
				) );
				$person_types[] = $person_type;
			}
		}
		return $person_types;
	}

	/**
	 * Get posted resources. Resources are global, but booking products store information about the relationship.
	 *
	 * @return array
	 */
	private function get_posted_resources( $product ) {
		$resources = array();

		if ( isset( $_POST['resource_id'] ) && isset( $_POST['_wc_booking_has_resources'] ) ) {
			$resource_ids         = $_POST['resource_id'];
			$resource_menu_order  = $_POST['resource_menu_order'];
			$resource_base_cost   = $_POST['resource_cost'];
			$resource_block_cost  = $_POST['resource_block_cost'];
			$max_loop             = max( array_keys( $_POST['resource_id'] ) );
			$resource_base_costs  = array();
			$resource_block_costs = array();

			foreach ( $resource_menu_order as $key => $value ) {
				$resources[ absint( $resource_ids[ $key ] ) ] = array(
					'base_cost'  => wc_clean( $resource_base_cost[ $key ] ),
					'block_cost' => wc_clean( $resource_block_cost[ $key ] ),
				);
			}
		}

		return $resources;
	}

	/**
	 * Set data in 3.0.x
	 *
	 * @param WC_Product $product
	 */
	public function set_props( $product ) {
		// Only set props if the product is a bookable product.
		if ( ! is_a( $product, 'WC_Product_Booking' ) ) {
			return;
		}

		$resources = $this->get_posted_resources( $product );
		$product->set_props( array(
			'apply_adjacent_buffer'      => isset( $_POST['_wc_booking_apply_adjacent_buffer'] ),
			'availability'               => $this->get_posted_availability(),
			'base_cost'                  => wc_clean( $_POST['_wc_booking_base_cost'] ),
			'buffer_period'              => wc_clean( $_POST['_wc_booking_buffer_period'] ),
			'calendar_display_mode'      => wc_clean( $_POST['_wc_booking_calendar_display_mode'] ),
			'cancel_limit_unit'          => wc_clean( $_POST['_wc_booking_cancel_limit_unit'] ),
			'cancel_limit'               => wc_clean( $_POST['_wc_booking_cancel_limit'] ),
			'check_start_block_only'     => 'start' === $_POST['_wc_booking_check_availability_against'],
			'cost'                       => wc_clean( $_POST['_wc_booking_cost'] ),
			'default_date_availability'  => wc_clean( $_POST['_wc_booking_default_date_availability'] ),
			'display_cost'               => wc_clean( $_POST['_wc_display_cost'] ),
			'duration_type'              => wc_clean( $_POST['_wc_booking_duration_type'] ),
			'duration_unit'              => wc_clean( $_POST['_wc_booking_duration_unit'] ),
			'duration'                   => wc_clean( $_POST['_wc_booking_duration'] ),
			'enable_range_picker'        => isset( $_POST['_wc_booking_enable_range_picker'] ),
			'first_block_time'           => wc_clean( $_POST['_wc_booking_first_block_time'] ),
			'has_person_cost_multiplier' => isset( $_POST['_wc_booking_person_cost_multiplier'] ),
			'has_person_qty_multiplier'  => isset( $_POST['_wc_booking_person_qty_multiplier'] ),
			'has_person_types'           => isset( $_POST['_wc_booking_has_person_types'] ),
			'has_persons'                => isset( $_POST['_wc_booking_has_persons'] ),
			'has_resources'              => isset( $_POST['_wc_booking_has_resources'] ),
			'max_date_unit'              => wc_clean( $_POST['_wc_booking_max_date_unit'] ),
			'max_date_value'             => wc_clean( $_POST['_wc_booking_max_date'] ),
			'max_duration'               => wc_clean( $_POST['_wc_booking_max_duration'] ),
			'max_persons'                => wc_clean( $_POST['_wc_booking_max_persons_group'] ),
			'min_date_unit'              => wc_clean( $_POST['_wc_booking_min_date_unit'] ),
			'min_date_value'             => wc_clean( $_POST['_wc_booking_min_date'] ),
			'min_duration'               => wc_clean( $_POST['_wc_booking_min_duration'] ),
			'min_persons'                => wc_clean( $_POST['_wc_booking_min_persons_group'] ),
			'person_types'               => $this->get_posted_person_types( $product ),
			'pricing'                    => $this->get_posted_pricing(),
			'qty'                        => wc_clean( $_POST['_wc_booking_qty'] ),
			'requires_confirmation'      => isset( $_POST['_wc_booking_requires_confirmation'] ),
			'resource_label'              => wc_clean( $_POST['_wc_booking_resource_label'] ),
			'resource_base_costs'        => wp_list_pluck( $resources, 'base_cost' ),
			'resource_block_costs'       => wp_list_pluck( $resources, 'block_cost' ),
			'resource_ids'               => array_keys( $resources ),
			'resources_assignment'       => wc_clean( $_POST['_wc_booking_resources_assignment'] ),
			'user_can_cancel'            => isset( $_POST['_wc_booking_user_can_cancel'] ),
		) );
	}

	/**
	 * Init product edit tabs.
	 */
	public function init_tabs() {
		if ( version_compare( WC_VERSION, '2.6', '<' ) ) {
			add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tab' ), 5 );
			add_action( 'woocommerce_product_write_panels', array( $this, 'booking_panels' ) );
		} else {
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'register_tab' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'booking_panels' ) );
		}
	}

	/**
	 * Add tabs to WC 2.6+
	 *
	 * @param  array $tabs
	 * @return array
	 */
	public function register_tab( $tabs ) {
		$tabs['bookings_resources'] = array(
			'label'  => __( 'Resources', 'woocommerce-bookings' ),
			'target' => 'bookings_resources',
			'class'  => array(
				'show_if_booking'
			),
		);
		$tabs['bookings_availability'] = array(
			'label'  => __( 'Availability', 'woocommerce-bookings' ),
			'target' => 'bookings_availability',
			'class'  => array(
				'show_if_booking'
			),
		);
		$tabs['bookings_pricing'] = array(
			'label'  => __( 'Costs', 'woocommerce-bookings' ),
			'target' => 'bookings_pricing',
			'class'  => array(
				'show_if_booking'
			),
		);
		$tabs['bookings_persons'] = array(
			'label'  => __( 'Persons', 'woocommerce-bookings' ),
			'target' => 'bookings_persons',
			'class'  => array(
				'show_if_booking'
			),
		);
		return $tabs;
	}

	/**
	 * Public access to instance object
	 *
	 * @return object
	 */
	public static function get_instance() {
		return self::$_this;
	}

	/**
	 * Duplicate a post.
	 *
	 * @param  int     $new_post_id Duplicated product ID.
	 * @param  WP_Post $post        Original product post.
	 */
	public function woocommerce_duplicate_product_pre_wc_30( $new_post_id, $post ) {
		$product = wc_get_product( $post->ID );

		if ( $product->is_type( 'booking' ) ) {
			global $wpdb;
			// Duplicate relationships
			$relationships = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d;", $post->ID ), ARRAY_A );
			foreach ( $relationships as $relationship ) {
				$relationship['product_id'] = $new_post_id;
				unset( $relationship['ID'] );
				$wpdb->insert( "{$wpdb->prefix}wc_booking_relationships", $relationship );
			}

			// Clone and re-save person types.
			foreach ( $product->get_person_types() as $person_type ) {
				$dupe_person_type = clone $person_type;
				$dupe_person_type->set_id( 0 );
				$dupe_person_type->set_parent_id( $new_post_id );
				$dupe_person_type->save();
			}
		}
	}

	/**
	 * Duplicate a post.
	 *
	 * @param  WC_Product $new_product Duplicated product.
	 * @param  WC_Product $product     Original product.
	 */
	public function woocommerce_duplicate_product( $new_product, $product ) {
		if ( $product->is_type( 'booking' ) ) {
			// Clone and re-save person types.
			foreach ( $product->get_person_types() as $person_type ) {
				$dupe_person_type = clone $person_type;
				$dupe_person_type->set_id( 0 );
				$dupe_person_type->set_parent_id( $new_product->get_id() );
				$dupe_person_type->save();
			}
		}
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		$messages['wc_booking'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Booking updated.', 'woocommerce-bookings' ),
			2  => __( 'Custom field updated.', 'woocommerce-bookings' ),
			3  => __( 'Custom field deleted.', 'woocommerce-bookings' ),
			4  => __( 'Booking updated.', 'woocommerce-bookings' ),
			5  => '',
			6  => __( 'Booking updated.', 'woocommerce-bookings' ),
			7  => __( 'Booking saved.', 'woocommerce-bookings' ),
			8  => __( 'Booking submitted.', 'woocommerce-bookings' ),
			9  => '',
			10 => '',
		);
		return $messages;
	}

	/**
	 * Show booking data if a line item is linked to a booking ID.
	 */
	public function booking_display( $item_id, $item, $product ) {
		$booking_ids = WC_Booking_Data_Store::get_booking_ids_from_order_item_id( $item_id );

		if ( $booking_ids ) {
			foreach ( $booking_ids as $booking_id ) {
				$booking = new WC_Booking( $booking_id );
				?>
				<div class="wc-booking-summary">
					<strong class="wc-booking-summary-number">
						<?php printf( __( 'Booking #%s', 'woocommerce-bookings' ), esc_html( $booking->get_id() ) ); ?>
						<span class="status-<?php echo esc_attr( $booking->get_status() ); ?>">
							<?php echo esc_html( wc_bookings_get_status_label( $booking->get_status() ) ) ?>
						</span>
					</strong>
					<?php wc_bookings_get_summary_list( $booking ); ?>
					<div class="wc-booking-summary-actions">
						<?php if ( in_array( $booking->get_status(), array( 'pending-confirmation' ) ) ) : ?>
							<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=wc-booking-confirm&booking_id=' . $booking_id ), 'wc-booking-confirm' ); ?>"><?php _e( 'Confirm booking', 'woocommerce-bookings' ); ?></a>
						<?php endif; ?>

						<?php if ( $booking_id ) : ?>
							<a href="<?php echo admin_url( 'post.php?post=' . absint( $booking_id ) . '&action=edit' ); ?>"><?php _e( 'View booking &rarr;', 'woocommerce-bookings' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * Include CPT handlers
	 */
	public function include_post_type_handlers() {
		include( 'class-wc-bookings-cpt.php' );
		include( 'class-wc-bookable-resource-cpt.php' );
	}

	/**
	 * Include meta box handlers
	 */
	public function include_meta_box_handlers() {
		include( 'class-wc-bookings-meta-boxes.php' );
	}

	/**
	 * Redirect the default add booking url to the custom one
	 */
	public function redirect_new_add_booking_url() {
		global $pagenow;

		if ( 'post-new.php' == $pagenow && isset( $_GET['post_type'] ) && 'wc_booking' == $_GET['post_type'] ) {
			wp_redirect( admin_url( 'edit.php?post_type=wc_booking&page=create_booking' ), '301' );
		}
	}

	/**
	 * Get booking products.
	 *
	 * @return array
	 */
	public static function get_booking_products() {
		$ids               = WC_Data_Store::load( 'product-booking' )->get_bookable_product_ids();
		$bookable_products = array();

		foreach ( $ids as $id ) {
			$bookable_products[] = new WC_Product_Booking( $id );
		}
		return $bookable_products;
	}

	/**
	 * Get booking product resources.
	 *
	 * @return array
	 */
	public static function get_booking_resources() {
		$ids       = WC_Data_Store::load( 'product-booking-resource' )->get_bookable_product_resource_ids();
		$resources = array();

		foreach ( $ids as $id ) {
			$resources[] = new WC_Product_Booking_Resource( $id );
		}
		return $resources;
	}

	/**
	 * Tweak product type options
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' show_if_booking';
		return $options;
	}

	/**
	 * Add the booking product type
	 */
	public function product_type_selector( $types ) {
		$types['booking'] = __( 'Bookable product', 'woocommerce-bookings' );
		return $types;
	}

	/**
	 * Show the booking tab
	 */
	public function add_tab() {
		include( 'views/html-booking-tab.php' );
	}

	/**
	 * Show the booking data view
	 */
	public function booking_data() {
		global $post, $bookable_product;

		if ( empty( $bookable_product ) || $bookable_product->get_id() !== $post->ID ) {
			$bookable_product = new WC_Product_Booking( $post->ID );
		}

		include( 'views/html-booking-data.php' );
	}

	/**
	 * Show the booking panels views
	 */
	public function booking_panels() {
		global $post, $bookable_product;

		if ( empty( $bookable_product ) || $bookable_product->get_id() !== $post->ID ) {
			$bookable_product = new WC_Product_Booking( $post->ID );
		}

		wp_enqueue_script( 'wc_bookings_writepanel_js' );

		include( 'views/html-booking-resources.php' );
		include( 'views/html-booking-availability.php' );
		include( 'views/html-booking-pricing.php' );
		include( 'views/html-booking-persons.php' );
	}

	/**
	 * Add admin styles
	 */
	public function styles_and_scripts() {
		global $post, $wp_scripts;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wc_bookings_admin_styles', WC_BOOKINGS_PLUGIN_URL . '/assets/css/admin.css', null, WC_BOOKINGS_VERSION );
		wp_register_script( 'wc_bookings_writepanel_js', WC_BOOKINGS_PLUGIN_URL . '/assets/js/writepanel' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_BOOKINGS_VERSION, true );
		wp_register_script( 'wc_bookings_settings_js', WC_BOOKINGS_PLUGIN_URL . '/assets/js/settings' . $suffix . '.js', array( 'jquery' ), WC_BOOKINGS_VERSION, true );

		$params = array(
			'i18n_remove_person'     => esc_js( __( 'Are you sure you want to remove this person type?', 'woocommerce-bookings' ) ),
			'nonce_unlink_person'    => wp_create_nonce( 'unlink-bookable-person' ),
			'nonce_add_person'       => wp_create_nonce( 'add-bookable-person' ),
			'i18n_remove_resource'   => esc_js( __( 'Are you sure you want to remove this resource?', 'woocommerce-bookings' ) ),
			'nonce_delete_resource'  => wp_create_nonce( 'delete-bookable-resource' ),
			'nonce_add_resource'     => wp_create_nonce( 'add-bookable-resource' ),
			'i18n_minutes'           => esc_js( __( 'minutes', 'woocommerce-bookings' ) ),
			'i18n_hours'             => esc_js( __( 'hours', 'woocommerce-bookings' ) ),
			'i18n_days'              => esc_js( __( 'days', 'woocommerce-bookings' ) ),
			'i18n_new_resource_name' => esc_js( __( 'Enter a name for the new resource', 'woocommerce-bookings' ) ),
			'post'                   => isset( $post->ID ) ? $post->ID : '',
			'plugin_url'             => WC()->plugin_url(),
			'ajax_url'               => admin_url( 'admin-ajax.php' ),
			'calendar_image'         => WC()->plugin_url() . '/assets/images/calendar.png',
		);

		wp_localize_script( 'wc_bookings_writepanel_js', 'wc_bookings_writepanel_js_params', $params );
	}

	/**
	 * Add extra product type options
	 * @param  array $options
	 * @return array
	 */
	public function booking_product_type_options( $options ) {
		return array_merge( $options, array(
			'wc_booking_has_persons' => array(
				'id'            => '_wc_booking_has_persons',
				'wrapper_class' => 'show_if_booking',
				'label'         => __( 'Has persons', 'woocommerce-bookings' ),
				'description'   => __( 'Enable this if this bookable product can be booked by a customer defined number of persons.', 'woocommerce-bookings' ),
				'default'       => 'no',
			),
			'wc_booking_has_resources' => array(
				'id'            => '_wc_booking_has_resources',
				'wrapper_class' => 'show_if_booking',
				'label'         => __( 'Has resources', 'woocommerce-bookings' ),
				'description'   => __( 'Enable this if this bookable product has multiple bookable resources, for example room types or instructors.', 'woocommerce-bookings' ),
				'default'       => 'no',
			),
		) );
	}

	/**
	 * Reset the ics exporter timezone string cache.
	 *
	 * @return void
	 */
	public function reset_ics_exporter_timezone_cache() {
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
			wp_cache_delete( 'wc_bookings_timezone_string' );
		}
	}
}

new WC_Bookings_Admin();
