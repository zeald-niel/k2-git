<?php
 /**
  * Plugin Name: WooCommerce Bookings
  * Plugin URI: https://woocommerce.com/products/woocommerce-bookings/
  * Description: Setup bookable products such as for reservations, services and hires.
  * Version: 1.10.5
  * Author: Automattic
  * Author URI: https://woocommerce.com
  * Text Domain: woocommerce-bookings
  * Domain Path: /languages
  *
  * Copyright: © 2009-2013 Automattic.
  * License: GNU General Public License v3.0
  * License URI: http://www.gnu.org/licenses/gpl-3.0.html
  *
  * Woo: 390890:911c438934af094c2b38d5560b9f50f3
  */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '911c438934af094c2b38d5560b9f50f3', '390890' );

if ( ! is_woocommerce_active() ) {
	return;
}

/**
 * WC Bookings class
 */
class WC_Bookings {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_BOOKINGS_VERSION', '1.10.5' );
		define( 'WC_BOOKINGS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		define( 'WC_BOOKINGS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_BOOKINGS_MAIN_FILE', __FILE__ );
		define( 'WC_BOOKINGS_ABSPATH', dirname( __FILE__ ) . '/' );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'init_post_types' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'booking_form_styles' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );

		if ( is_admin() ) {
			$this->admin_includes();
		}

		// Install
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		if ( get_option( 'wc_bookings_version' ) !== WC_BOOKINGS_VERSION ) {
			add_action( 'shutdown', array( $this, 'delayed_install' ) );
		}

		// Load payment gateway name.
		add_filter( 'woocommerce_payment_gateways', array( $this, 'include_gateway' ) );

		// Load integration.
		add_filter( 'woocommerce_integrations', array( $this, 'include_integration' ) );

		// Init Bookings settings.
		add_filter( 'woocommerce_general_settings', array( $this, 'init_bookings_settings' ) );

		$this->init_cache_clearing();
	}

	/**
	 * Installer
	 */
	public function install() {
		add_action( 'shutdown', array( $this, 'delayed_install' ) );

		// Register the rewrite endpoint before permalinks are flushed
		add_rewrite_endpoint( apply_filters( 'woocommerce_bookings_account_endpoint', 'bookings' ), EP_PAGES );

		// Flush Permalinks
		flush_rewrite_rules();
	}

	/**
	 * Installer (delayed)
	 */
	public function delayed_install() {
		global $wpdb, $wp_roles;

		$wpdb->hide_errors();

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( "
CREATE TABLE {$wpdb->prefix}wc_booking_relationships (
ID bigint(20) unsigned NOT NULL auto_increment,
product_id bigint(20) unsigned NOT NULL,
resource_id bigint(20) unsigned NOT NULL,
sort_order bigint(20) unsigned NOT NULL default 0,
PRIMARY KEY  (ID),
KEY product_id (product_id),
KEY resource_id (resource_id)
) $collate;
		" );

		// Product type
		if ( ! get_term_by( 'slug', sanitize_title( 'booking' ), 'product_type' ) ) {
			wp_insert_term( 'booking', 'product_type' );
		}

		// Capabilities
		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'shop_manager', 'manage_bookings' );
			$wp_roles->add_cap( 'administrator', 'manage_bookings' );
		}

		// Data updates
		if ( version_compare( get_option( 'wc_bookings_version', WC_BOOKINGS_VERSION ), '1.3', '<' ) ) {
			$bookings = $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key IN ( '_booking_start', '_booking_end' );" );
			foreach ( $bookings as $booking ) {
				if ( ctype_digit( $booking->meta_value ) && $booking->meta_value <= 2147483647 ) {
					$new_date = date( 'YmdHis', $booking->meta_value );
					update_post_meta( $booking->post_id, $booking->meta_key, $new_date );
				}
			}
		}

		if ( version_compare( get_option( 'wc_bookings_version', WC_BOOKINGS_VERSION ), '1.4', '<' ) ) {
			$resources = $wpdb->get_results( "SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'bookable_resource' AND post_parent > 0;" );
			foreach ( $resources as $resource ) {
				$wpdb->insert(
					$wpdb->prefix . 'wc_booking_relationships',
					array(
						'product_id'  => $resource->post_parent,
						'resource_id' => $resource->ID,
						'sort_order'  => 1,
					)
				);
				if ( $wpdb->insert_id ) {
					$wpdb->update(
						$wpdb->posts,
						array(
							'post_parent' => 0,
						),
						array(
							'ID' => $resource->ID,
						)
					);
					$cost         = get_post_meta( $resource->ID, 'cost', true );
					$parent_costs = get_post_meta( $resource->post_parent, '_resource_base_costs', true );
					if ( ! $parent_costs ) {
						$parent_costs = array();
					}
					$parent_costs[ $resource->ID ] = $cost;
					update_post_meta( $resource->post_parent, '_resource_base_costs', $parent_costs );
				}
			}
		}

		if ( version_compare( get_option( 'wc_bookings_version', WC_BOOKINGS_VERSION ), '1.5', '<' ) ) {
			$wpdb->query( "
				UPDATE {$wpdb->posts} as posts
				SET posts.post_status = 'pending-confirmation'
				WHERE posts.post_type = 'wc_booking'
				AND posts.post_status = 'pending';
				"
			);
		}

		if ( version_compare( get_option( 'wc_bookings_version', WC_BOOKINGS_VERSION ), '1.10.3', '<' ) ) {
			$this->includes();

			$booking_products = WC_Product_Booking_Data_Store_CPT::get_bookable_product_ids();

			// Update all bookings to match the proper price
			foreach ( $booking_products as $product_id ) {
				$price = get_post_meta( $product_id, '_price', true );

				if ( ! empty( $price ) ) {
					continue;
				}

				$new_price = wc_booking_calculated_base_cost( new WC_Product_Booking( $product_id ) );

				update_post_meta( $product_id, '_price', $new_price );
			}
		}

		// Update version
		update_option( 'wc_bookings_version', WC_BOOKINGS_VERSION );
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-bookings' );
		$dir    = trailingslashit( WP_LANG_DIR );

		load_textdomain( 'woocommerce-bookings', $dir . 'woocommerce-bookings/woocommerce-bookings-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-bookings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Load Classes.
	 */
	public function includes() {
		/**
		 * Load 3.0.x classes and backwards compatibility code when using older versions of WooCommerce.
		 *
		 * @since 1.10.0
		 * @todo  remove this when 2.6.x support is dropped.
		 */
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			if ( ! class_exists( 'WC_Data_Store' ) ) {
				include_once( WC_BOOKINGS_ABSPATH . 'includes/compatibility/class-wc-data-store.php' );
			}
			if ( ! class_exists( 'WC_Data_Exception' ) ) {
				include_once( WC_BOOKINGS_ABSPATH . 'includes/compatibility/class-wc-data-exception.php' );
			}
			if ( ! class_exists( 'WC_Data_Store_WP' ) ) {
				include_once( WC_BOOKINGS_ABSPATH . 'includes/compatibility/class-wc-data-store-wp.php' );
			}
			if ( ! class_exists( 'WC_Product_Data_Store_CPT' ) ) {
				include_once( WC_BOOKINGS_ABSPATH . 'includes/compatibility/class-wc-product-data-store-cpt.php' );
			}
		}

		if ( ! class_exists( 'WC_Bookings_Data' ) ) {
			include_once( WC_BOOKINGS_ABSPATH . 'includes/compatibility/abstract-wc-bookings-data.php' ); // Bookings version of WC_Data.
		}

		// Objects.
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-objects/class-wc-product-booking.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-objects/class-wc-product-booking-resource.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-objects/class-wc-product-booking-person-type.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-objects/class-wc-booking.php' );

		// Stores.
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-stores/class-wc-booking-data-store.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-stores/class-wc-product-booking-data-store-cpt.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-stores/class-wc-product-booking-resource-data-store-cpt.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/data-stores/class-wc-product-booking-person-type-data-store-cpt.php' );

		include_once( WC_BOOKINGS_ABSPATH . 'includes/wc-bookings-functions.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-form-handler.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-order-manager.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-bookings-controller.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-cron-manager.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-bookings-ics-exporter.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/gateways/class-wc-bookings-gateway.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/integrations/class-wc-bookings-google-calendar-integration.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/booking-form/class-wc-booking-form.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-coupon.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-product-class-loader.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-product-booking-rule-manager.php' );

		if ( class_exists( 'WC_Product_Addons' ) ) {
			include_once( WC_BOOKINGS_ABSPATH . 'includes/integrations/class-wc-bookings-addons.php' );
		}
	}

	/**
	 * Init self
	 */
	public function init() {
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-email-manager.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-cart-manager.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/class-wc-booking-checkout-manager.php' );
	}

	/**
	 * Include admin
	 */
	public function admin_includes() {
		include_once( WC_BOOKINGS_ABSPATH . 'includes/admin/class-wc-bookings-tools.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/admin/class-wc-bookings-admin.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/admin/class-wc-bookings-ajax.php' );
		include_once( WC_BOOKINGS_ABSPATH . 'includes/admin/class-wc-bookings-addons.php' );
	}

	/**
	 * Init post types
	 */
	public function init_post_types() {
		register_post_type( 'bookable_person',
			apply_filters( 'woocommerce_register_post_type_bookable_person',
				array(
					'label'        => __( 'Person Type', 'woocommerce-bookings' ),
					'public'       => false,
					'hierarchical' => false,
					'supports'     => false,
				)
			)
		);

		register_post_type( 'bookable_resource',
			apply_filters( 'woocommerce_register_post_type_bookable_resource',
				array(
					'label'  => __( 'Resources', 'woocommerce-bookings' ),
					'labels' => array(
							'name'               => __( 'Bookable resources', 'woocommerce-bookings' ),
							'singular_name'      => __( 'Bookable resource', 'woocommerce-bookings' ),
							'add_new'            => __( 'Add Resource', 'woocommerce-bookings' ),
							'add_new_item'       => __( 'Add New Resource', 'woocommerce-bookings' ),
							'edit'               => __( 'Edit', 'woocommerce-bookings' ),
							'edit_item'          => __( 'Edit Resource', 'woocommerce-bookings' ),
							'new_item'           => __( 'New Resource', 'woocommerce-bookings' ),
							'view'               => __( 'View Resource', 'woocommerce-bookings' ),
							'view_item'          => __( 'View Resource', 'woocommerce-bookings' ),
							'search_items'       => __( 'Search Resource', 'woocommerce-bookings' ),
							'not_found'          => __( 'No Resource found', 'woocommerce-bookings' ),
							'not_found_in_trash' => __( 'No Resource found in trash', 'woocommerce-bookings' ),
							'parent'             => __( 'Parent Resources', 'woocommerce-bookings' ),
							'menu_name'          => _x( 'Resources', 'Admin menu name', 'woocommerce-bookings' ),
							'all_items'          => __( 'Resources', 'woocommerce-bookings' ),
						),
					'description' 			=> __( 'Bookable resources are bookable within a bookings product.', 'woocommerce-bookings' ),
					'public' 				=> false,
					'show_ui' 				=> true,
					'capability_type' 		=> 'product',
					'map_meta_cap'			=> true,
					'publicly_queryable' 	=> false,
					'exclude_from_search' 	=> true,
					'show_in_menu' 			=> true,
					'hierarchical' 			=> false,
					'show_in_nav_menus' 	=> false,
					'rewrite' 				=> false,
					'query_var' 			=> false,
					'supports' 				=> array( 'title' ),
					'has_archive' 			=> false,
					'show_in_menu' 			=> 'edit.php?post_type=wc_booking',
				)
			)
		);

		register_post_type( 'wc_booking',
			apply_filters( 'woocommerce_register_post_type_wc_booking',
				array(
					'label'                      => __( 'Booking', 'woocommerce-bookings' ),
					'labels'                     => array(
							'name'               => __( 'Bookings', 'woocommerce-bookings' ),
							'singular_name'      => __( 'Booking', 'woocommerce-bookings' ),
							'add_new'            => __( 'Add Booking', 'woocommerce-bookings' ),
							'add_new_item'       => __( 'Add New Booking', 'woocommerce-bookings' ),
							'edit'               => __( 'Edit', 'woocommerce-bookings' ),
							'edit_item'          => __( 'Edit Booking', 'woocommerce-bookings' ),
							'new_item'           => __( 'New Booking', 'woocommerce-bookings' ),
							'view'               => __( 'View Booking', 'woocommerce-bookings' ),
							'view_item'          => __( 'View Booking', 'woocommerce-bookings' ),
							'search_items'       => __( 'Search Bookings', 'woocommerce-bookings' ),
							'not_found'          => __( 'No Bookings found', 'woocommerce-bookings' ),
							'not_found_in_trash' => __( 'No Bookings found in trash', 'woocommerce-bookings' ),
							'parent'             => __( 'Parent Bookings', 'woocommerce-bookings' ),
							'menu_name'          => _x( 'Bookings', 'Admin menu name', 'woocommerce-bookings' ),
							'all_items'          => __( 'All Bookings', 'woocommerce-bookings' ),
						),
					'description'                => __( 'This is where bookings are stored.', 'woocommerce-bookings' ),
					'public'                     => false,
					'show_ui'                    => true,
					'capability_type'            => 'product',
					'map_meta_cap'               => true,
					'publicly_queryable'         => false,
					'exclude_from_search'        => true,
					'show_in_menu'               => true,
					'hierarchical'               => false,
					'show_in_nav_menus'          => false,
					'rewrite'                    => false,
					'query_var'                  => false,
					'supports'                   => array( '' ),
					'has_archive'                => false,
					'menu_icon'                  => 'dashicons-calendar-alt',
				)
			)
		);

		/**
		 * Post status
		 */
		register_post_status( 'complete', array(
			'label'                     => '<span class="status-complete tips" data-tip="' . _x( 'Complete', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'Complete', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Complete <span class="count">(%s)</span>', 'Complete <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'paid', array(
			'label'                     => '<span class="status-paid tips" data-tip="' . _x( 'Paid &amp; Confirmed', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'Paid &amp; Confirmed', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Paid &amp; Confirmed <span class="count">(%s)</span>', 'Paid &amp; Confirmed <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'confirmed', array(
			'label'                     => '<span class="status-confirmed tips" data-tip="' . _x( 'Confirmed', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'Confirmed', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'unpaid', array(
			'label'                     => '<span class="status-unpaid tips" data-tip="' . _x( 'Un-paid', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'Un-paid', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Un-paid <span class="count">(%s)</span>', 'Un-paid <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'pending-confirmation', array(
			'label'                     => '<span class="status-pending tips" data-tip="' . _x( 'Pending Confirmation', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'Pending Confirmation', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Pending Confirmation <span class="count">(%s)</span>', 'Pending Confirmation <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'cancelled', array(
			'label'                     => '<span class="status-cancelled tips" data-tip="' . _x( 'Cancelled', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'Cancelled', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'in-cart', array(
			'label'                     => '<span class="status-incart tips" data-tip="' . _x( 'In Cart', 'woocommerce-bookings', 'woocommerce-bookings' ) . '">' . _x( 'In Cart', 'woocommerce-bookings', 'woocommerce-bookings' ) . '</span>',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'In Cart <span class="count">(%s)</span>', 'In Cart <span class="count">(%s)</span>', 'woocommerce-bookings' ),
		) );
		register_post_status( 'was-in-cart', array(
			'label'                     => false,
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
			'label_count'               => false,
		) );

		if ( class_exists( 'WC_Deposits' ) && is_admin()
			 && isset( $_GET['post_type'] ) && 'wc_booking' === $_GET['post_type'] ) {
			register_post_status( 'wc-partial-payment', array(
				'label'                     => '<span class="status-partial-payment tips" data-tip="' . _x( 'Partially Paid', 'woocommerce-deposits', 'woocommerce-deposits' ) . '">' . _x( 'Partially Paid', 'woocommerce-deposits', 'woocommerce-deposits' ) . '</span>',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Partially Paid <span class="count">(%s)</span>', 'Partially Paid <span class="count">(%s)</span>', 'woocommerce-deposits' ),
			) );
		}
	}

	public function init_cache_clearing() {
		add_action( 'woocommerce_booking_cancelled', array( $this, 'clear_cache' ) );
		add_action( 'before_delete_post', array( $this, 'clear_cache' ) );
		add_action( 'wp_trash_post', array( $this, 'clear_cache' ) );
		add_action( 'untrash_post', array( $this, 'clear_cache' ) );
		add_action( 'save_post', array( $this, 'clear_cache_on_save_post' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'clear_cache' ) );
		add_action( 'woocommerce_pre_payment_complete', array( $this, 'clear_cache' ) );

		// scheduled events
		add_action( 'delete_booking_transients', array( $this, 'clear_cache' ) );
		add_action( 'delete_booking_dr_transients', array( $this, 'clear_cache' ) );
		add_action( 'delete_booking_ress_transients', array( $this, 'clear_cache' ) );
		add_action( 'delete_booking_res_transients', array( $this, 'clear_cache' ) );
	}

	public function clear_cache() {
		$this->delete_booking_transients();
		$this->delete_booking_dr_transients();
		$this->delete_booking_ress_transients();
		$this->delete_booking_res_transients();
	}

	/**
	 * Clears the transients when booking is edited
	 *
	 * @param int $post_id
	 * @return int $post_id
	 */
	public function clear_cache_on_save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		$post = get_post( $post_id );

		if ( 'wc_booking' !== $post->post_type && 'product' !== $post->post_type ) {
			return $post_id;
		}

		$this->clear_cache();
	}

	/**
	 * Delete Booking Related Transients
	 */
	public function delete_booking_transients() {
		global $wpdb;
		$limit = 1000;

		$affected_timeouts   = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_timeout_book_fo_%', $limit ) );
		$affected_transients = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_book_fo_%', $limit ) );

		// If affected rows is equal to limit, there are more rows to delete. Delete in 10 secs.
		if ( $affected_transients === $limit ) {
			wp_schedule_single_event( time() + 10, 'delete_booking_transients', array( time() ) );
		}
	}

	/**
	 * Delete Booking Date Range Related Transients
	 */
	public function delete_booking_dr_transients() {
		global $wpdb;
		$limit = 1000;

		$affected_timeouts   = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_timeout_book_dr_%', $limit ) );
		$affected_transients = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_book_dr_%', $limit ) );

		// If affected rows is equal to limit, there are more rows to delete. Delete in 10 secs.
		if ( $affected_transients === $limit ) {
			wp_schedule_single_event( time() + 10, 'delete_booking_dr_transients', array( time() ) );
		}
	}

	/**
	 * Delete Booking Product Resources Related Transients
	 */
	public function delete_booking_ress_transients() {
		global $wpdb;
		$limit = 1000;

		$affected_timeouts   = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_timeout_book_ress_%', $limit ) );
		$affected_transients = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_book_ress_%', $limit ) );

		// If affected rows is equal to limit, there are more rows to delete. Delete in 10 secs.
		if ( $affected_transients === $limit ) {
			wp_schedule_single_event( time() + 10, 'delete_booking_ress_transients', array( time() ) );
		}
	}

	/**
	 * Delete Booking Product Resource Related Transients
	 */
	public function delete_booking_res_transients() {
		global $wpdb;
		$limit = 1000;

		$affected_timeouts   = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_timeout_book_res_%', $limit ) );
		$affected_transients = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT %d;", '_transient_book_res_%', $limit ) );

		// If affected rows is equal to limit, there are more rows to delete. Delete in 10 secs.
		if ( $affected_transients === $limit ) {
			wp_schedule_single_event( time() + 10, 'delete_booking_res_transients', array( time() ) );
		}
	}

	/**
	 * Frontend booking form scripts
	 */
	public function booking_form_styles() {
		global $wp_scripts;

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.min.css' );
		wp_enqueue_style( 'wc-bookings-styles', WC_BOOKINGS_PLUGIN_URL . '/assets/css/frontend.css', null, WC_BOOKINGS_VERSION );
	}

	/**
	 * Add a custom payment gateway
	 * This gateway works with booking that requires confirmation
	 */
	public function include_gateway( $gateways ) {
		$gateways[] = 'WC_Bookings_Gateway';

		return $gateways;
	}

	/**
	 * Add integrations
	 * This add the Google Calendar integration
	 */
	public function include_integration( $integrations ) {
		$integrations[] = 'WC_Bookings_Google_Calendar_Integration';

		return $integrations;
	}

	/**
	 * Add Bookings settings to the WC Settings screen.
	 *
	 * @param  array $settings
	 * @return array
	 */
	public function init_bookings_settings( $settings ) {
		// Pop the separator.
		$last_element = array_pop( $settings );

		$bookings_settings = array(
			array(
				'title'   => __( 'Enable Bookings Timezone calculation', 'woocommerce' ),
				'desc'    => __( 'Schedule Bookings events, such as reminder emails and auto-completions of bookings, using your site’s configured timezone.', 'woocommerce' ),
				'id'      => 'woocommerce_bookings_tz_calculation',
				'default' => 'no',
				'type'    => 'checkbox',
			),
		);

		$settings = array_merge( $settings, $bookings_settings );
		$settings[] = $last_element;

		return $settings;
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @access	public
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( plugin_basename( WC_BOOKINGS_MAIN_FILE ) == $file ) {
			$row_meta = array(
				'docs'		=> '<a href="' . esc_url( apply_filters( 'woocommerce_bookings_docs_url', 'http://docs.woothemes.com/documentation/plugins/woocommerce/woocommerce-extensions/bookings/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-bookings' ) ) . '">' . __( 'Docs', 'woocommerce-bookings' ) . '</a>',
				'support'	=> '<a href="' . esc_url( apply_filters( 'woocommerce_bookings_support_url', 'http://support.woothemes.com/' ) ) . '" title="' . esc_attr( __( 'Visit Premium Customer Support Forum', 'woocommerce-bookings' ) ) . '">' . __( 'Premium Support', 'woocommerce-bookings' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	/**
	 * Register data stores for bookings.
	 *
	 * @param  array  $data_stores
	 * @return array
	 */
	public function register_data_stores( $data_stores = array() ) {
		$data_stores['booking']                     = 'WC_Booking_Data_Store';
		$data_stores['product-booking']             = 'WC_Product_Booking_Data_Store_CPT';
		$data_stores['product-booking-resource']    = 'WC_Product_Booking_Resource_Data_Store_CPT';
		$data_stores['product-booking-person-type'] = 'WC_Product_Booking_Person_Type_Data_Store_CPT';
		return $data_stores;
	}
}

$GLOBALS['wc_bookings'] = new WC_Bookings();
