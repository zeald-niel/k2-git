<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Admin
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Admin class
 *
 * @since 1.0.0
 */
class WC_Memberships_Admin {


	/** @var \SV_WP_Admin_Message_Handler instance */
	public $message_handler; // this is passed from \WC_Memberships and can't be protected

	/** @var \WC_Memberships_Admin_Import_Export_Handler instance */
	protected $import_export;

	/** @var \WC_Memberships_Admin_User_Memberships instance */
	protected $user_memberships;

	/** @var \WC_Memberships_Admin_Membership_Plans instance */
	protected $membership_plans;

	/** @var \WC_Memberships_Admin_Users instance */
	protected $users;

	/** @var array Array of valid post types for content restriction rules */
	private $valid_post_types_for_content_restriction;

	/** @var array Array of valid taxonomies for rule types */
	private $valid_rule_type_taxonomies = array();

	/** @var \WC_Memberships_Meta_Box[] Object container of meta box classes instances */
	protected $meta_boxes;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// init settings page
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );

		// init import/export age
		add_action( 'admin_menu', array( $this, 'add_import_export_admin_page' ) );

		// init content in Memberships admin pages
		add_action( 'current_screen', array( $this, 'init' ) );

		// set current tab for Memberships admin pages
		add_filter( 'wc_memberships_admin_current_tab', array( $this, 'set_current_tab' ) );

		// render Memberships admin tabs for pages with custom post types
		add_action( 'all_admin_notices', array( $this, 'render_tabs' ), 5 );

		// enqueue admin scripts & styles
		add_action( 'admin_enqueue_scripts', array( $this,  'enqueue_scripts_and_styles' ) );
		// load admin scripts & styles
		add_filter( 'woocommerce_screen_ids', array( $this, 'load_wc_scripts' ) );

		// list user memberships on individual "edit order" screen
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'render_order_data' ) );

		// display admin messages
		add_action( 'admin_notices', array( $this, 'show_admin_messages' ) );

		// remove "New User Membership" item from Admin bar
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 9999 );

		// conditionally remove duplicate submenu link
		add_action( 'admin_menu', array( $this, 'remove_submenu_link' ) );

		// duplicate memberships settings for products
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
			add_action( 'woocommerce_product_duplicate', array( $this, 'duplicate_product_memberships_data' ), 10, 2 );
		} else {
			add_action( 'woocommerce_duplicate_product', array( $this, 'duplicate_product_memberships_data' ), 10, 2 );
		}

		// process import / export submission form
		add_action( 'admin_post_wc_memberships_csv_import_user_memberships', array( $this, 'process_import_export_form' ) );
		add_action( 'admin_post_wc_memberships_csv_export_user_memberships', array( $this, 'process_import_export_form' ) );
	}


	/**
	 * Get the Message Handler instance
	 *
	 * @since 1.6.0
	 * @return \SV_WP_Admin_Message_Handler
	 */
	public function get_message_handler() {
		// note: this property is public since it needs to be passed from the main class
		return $this->message_handler;
	}


	/**
	 * Get the Users instance.
	 *
	 * @since 1.7.4
	 * @return \WC_Memberships_Admin_Users
	 */
	public function get_users_instance() {
		return $this->users;
	}


	/**
	 * Get the User Memberships instance
	 *
	 * @since 1.6.0
	 * @return \WC_Memberships_Admin_User_Memberships
	 */
	public function get_user_memberships_instance() {
		return $this->user_memberships;
	}


	/**
	 * Get the User Memberships instance
	 *
	 * @since 1.6.0
	 * @return \WC_Memberships_Admin_Membership_Plans
	 */
	public function get_membership_plans_instance() {
		return $this->membership_plans;
	}


	/**
	 * Get the Import / Export Handler instance
	 *
	 * @since 1.6.0
	 * @return \WC_Memberships_Admin_Import_Export_Handler
	 */
	public function get_import_export_handler_instance() {
		return $this->import_export;
	}


	/**
	 * Get Memberships admin screen IDs.
	 *
	 * @since 1.0.0
	 * @return string[] List (array) with admin screen IDs where Memberships does something.
	 */
	public function get_screen_ids() {
		return array(
			// User screens:
			'users',
			'user-edit',
			'profile',
			// User Membership screens:
			'wc_user_membership',
			'edit-wc_user_membership',
			// Membership Plan screens:
			'wc_membership_plan',
			'edit-wc_membership_plan',
			// User Memberships Import/Export screens:
			'wc_memberships_import_export',
			'admin_page_wc_memberships_import_export',
			'admin_page_wc-memberships-settings',
		);
	}


	/**
	 * Get admin page tabs
	 *
	 * @since 1.7.0
	 * @return array
	 */
	private function get_tabs() {
		return array(
			'members'       => array(
				'title' => __( 'Members', 'woocommerce-memberships' ),
				'url'   => admin_url( 'edit.php?post_type=wc_user_membership' ),
			),
			'memberships'   => array(
				'title' => __( 'Membership Plans', 'woocommerce-memberships' ),
				'url'   => admin_url( 'edit.php?post_type=wc_membership_plan' ),
			),
			'import-export' => array(
				'title' => __( 'Import / Export', 'woocommerce-memberships' ),
				'url'   => admin_url( 'admin.php?page=wc_memberships_import_export' ),
			),
		);
	}


	/**
	 * Add Memberships settings page to WooCommerce settings
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_page( $settings ) {

		$settings[] = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-settings.php', 'WC_Settings_Memberships' );

		return $settings;
	}


	/**
	 * Add Import / Export page for Memberships admin page
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function add_import_export_admin_page() {

		/**
		 * Set minimum capability to use Import / Export features
		 *
		 * @since 1.6.0
		 * @param string $capability Defaults to Shop Managers with 'manage_woocommerce'
		 */
		$capability = apply_filters( 'woocommerce_memberships_can_import_export', 'manage_woocommerce' );

		add_submenu_page(
			'',
			__( 'Import / Export', 'woocommerce-memberships' ),
			__( 'Import / Export', 'woocommerce-memberships' ),
			$capability,
			'wc_memberships_import_export',
			array( $this, 'render_import_export_admin_page' )
		);
	}


	/**
	 * Render Import / Export admin page
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function render_import_export_admin_page() {

		/**
		 * Output the Import / Export admin page
		 *
		 * @since 1.6.0
		 */
		do_action( 'wc_memberships_render_import_export_page' );
	}


	/**
	 * Check if the current screen is the
	 *
	 * @since 1.6.0
	 * @param null|\WP_Screen $screen Optional, defaults to current screen global
	 * @return bool
	 */
	private function is_import_export_admin_page( $screen = null ) {

		$current_screen = null !== $screen ? $screen : get_current_screen();

		return $current_screen instanceof WP_Screen && 'admin_page_wc_memberships_import_export' === $current_screen->id;
	}


	/**
	 * Initialize the main admin screen
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$screen = get_current_screen();

		switch ( $screen->id ) {

			case 'wc_membership_plan' :
			case 'edit-wc_membership_plan' :
				$this->membership_plans = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-admin-membership-plans.php',  'WC_Memberships_Admin_Membership_Plans');
			break;

			case 'wc_user_membership' :
			case 'edit-wc_user_membership' :
				$this->user_memberships = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-admin-user-memberships.php',  'WC_Memberships_Admin_User_Memberships' );
				// the import / export handler runs bulk export on User Memberships screen
				$this->import_export    = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-import-export-handler.php', 'WC_Memberships_Admin_Import_Export_Handler' );
			break;

			case 'admin_page_wc_memberships_import_export' :
				$this->import_export    = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-import-export-handler.php', 'WC_Memberships_Admin_Import_Export_Handler' );
			break;

			case 'users' :
			case 'user-edit' :
			case 'profile' :
				$this->users            = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-admin-users.php', 'WC_Memberships_Admin_Users' );
			break;
		}

		$this->load_meta_boxes();
	}


	/**
	 * Load meta boxes
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function load_meta_boxes() {
		global $pagenow;

		// bail out if not on a new post / edit post screen
		if ( 'post-new.php' !== $pagenow && 'post.php' !== $pagenow ) {
			return;
		}

		// load meta boxes abstract class
		require_once( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/abstract-wc-memberships-meta-box.php' );

		$screen = get_current_screen();

		$this->meta_boxes = new stdClass();
		$meta_box_classes = array();

		// load restriction meta boxes on post screen only
		$meta_box_classes[] = 'WC_Memberships_Meta_Box_Post_Memberships_Data';

		// product-specific meta boxes
		if ( 'product' === $screen->id ) {
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_Product_Memberships_Data';
		}

		// load user membership meta boxes on user membership screen only
		if ( 'wc_membership_plan' === $screen->id ) {
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_Membership_Plan_Data';
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_Membership_Plan_Email_Content_Merge_Tags';
		}

		// load user membership meta boxes on user membership screen only
		if ( 'wc_user_membership' === $screen->id ) {
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_User_Membership_Data';
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_User_Membership_Notes';
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_User_Membership_Member_Details';
			$meta_box_classes[] = 'WC_Memberships_Meta_Box_User_Membership_Recent_Activity';
		}

		// load and instantiate
		foreach ( $meta_box_classes as $class ) {

			$file_name = 'class-'. strtolower( str_replace( '_', '-', $class ) ) . '.php';
			$file_path = wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/' . $file_name;

			if ( is_readable( $file_path ) ) {

				require_once( $file_path );

				if ( class_exists( $class ) ) {

					$instance_name = strtolower( str_replace( 'WC_Memberships_Meta_Box_', '', $class ) );
					$this->meta_boxes->$instance_name = new $class();
				}
			}
		}
	}


	/**
	 * Get the admin meta boxes
	 *
	 * @since 1.0.0
	 * @return \WC_Memberships_Meta_Box[] Object of \WC_Memberships_Meta_Box objects
	 */
	public function get_meta_boxes() {
		return $this->meta_boxes;
	}


	/**
	 * Get the admin meta box IDs.
	 *
	 * @since 1.0.0
	 * @return string[] Array of meta box IDs.
	 */
	public function get_meta_box_ids() {

		$ids = array();

		foreach ( $this->get_meta_boxes() as $meta_box ) {
			$ids[] = $meta_box->get_id();
		}

		return $ids;
	}


	/**
	 * Check if we are on a Memberships admin screen
	 *
	 * It will return true also on post types edit screens for restrictable content types
	 *
	 * @since 1.6.0
	 * @param string $hook_suffix Optional, defaults to pagenow
	 * @return bool
	 */
	public function is_memberships_admin_screen( $hook_suffix = '' ) {
		global $typenow, $pagenow;

		$hook_suffix = empty( $hook_suffix ) ? $pagenow : $hook_suffix;

		$restrictable_post_types   = array_keys( $this->get_valid_post_types_for_content_restriction() );
		$restrictable_post_types[] = 'product';

		// return true for any of the following conditions -- ie., we are on:
		return
			// restrictable post type screens
			( ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) && in_array( $typenow, $restrictable_post_types, true ) )
			// user memberships or membership plans screens
			|| in_array( $typenow, array( 'wc_user_membership', 'wc_membership_plan' ), true )
			// import / export page
			|| $this->is_import_export_admin_page()
			// settings page
			|| wc_memberships()->is_plugin_settings();
	}


	/**
	 * Enqueue admin scripts & styles
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current URL filename, ie edit.php, post.php, etc
	 */
	public function enqueue_scripts_and_styles( $hook_suffix ) {

		// Only load scripts on appropriate screens.
		if ( $this->is_memberships_admin_screen( $hook_suffix ) ) {

			$screen = get_current_screen();

			// Load the WP Pointers script on some screens.
			if ( $screen && ( 'edit-wc_user_membership' === $screen->id || 'wc_user_membership' === $screen->id ) ) {
				wp_enqueue_style( 'wp-pointer' );
				wp_enqueue_script( 'wp-pointer' );
			}

			$this->enqueue_styles();
			$this->enqueue_scripts();
		}
	}


	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.8.0
	 */
	private function enqueue_styles() {

		wp_enqueue_style( 'wc-memberships-admin', wc_memberships()->get_plugin_url() . '/assets/css/admin/wc-memberships-admin.min.css', array(), WC_Memberships::VERSION );
	}


	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.8.0
	 */
	private function enqueue_scripts() {

		$screen = get_current_screen();
		$path   = wc_memberships()->get_plugin_url() . '/assets/js/admin/';
		$ver    = WC_Memberships::VERSION;
		$deps   = array( 'jquery' );

		// Base scripts.
		wp_register_script( 'wc-memberships-enhanced-select',   $path . 'wc-memberships-enhanced-select.min.js',   array_merge( $deps, array( 'select2' ) ), $ver );
		wp_register_script( 'wc-memberships-rules',             $path . 'wc-memberships-rules.min.js',             array_merge( $deps, array( 'wc-memberships-enhanced-select' ) ), $ver );
		wp_register_script( 'wc-memberships-customers-pointer', $path . 'wc-memberships-customers-pointer.min.js', array_merge( $deps, array( 'wc-memberships-enhanced-select' ) ), $ver );
		wp_enqueue_script(  'wc-memberships-admin',             $path . 'wc-memberships-admin.min.js',             array_merge( $deps, array( 'wc-memberships-enhanced-select', 'wc-memberships-rules' ) ), $ver );

		// Load additional scripts selectively according to current Memberships admin page.
		if ( $screen && in_array( $screen->id, array( 'wc_membership_plan', 'edit-wc_membership_plan' ), false ) ) {
			wp_enqueue_script( 'wc-memberships-membership-plans', $path . 'wc-memberships-plans.min.js', array_merge( $deps, array( 'wc-memberships-admin', 'jquery-ui-datepicker' ) ), $ver );
		} elseif ( $screen && in_array( $screen->id, array( 'wc_user_membership', 'edit-wc_user_membership' ), false ) ) {
			wp_enqueue_script( 'wc-memberships-user-memberships', $path . 'wc-memberships-user-memberships.min.js', array_merge( $deps, array( 'wc-memberships-admin', 'jquery-ui-datepicker', 'wc-memberships-customers-pointer' ) ), $ver );
		} elseif ( $this->is_import_export_admin_page( $screen ) ) {
			wp_enqueue_script( 'wc-memberships-import-export', $path . 'wc-memberships-import-export.min.js', array_merge( $deps, array( 'jquery-ui-datepicker' ) ), $ver );
		} elseif ( wc_memberships()->is_plugin_settings() ) {
			wp_enqueue_script( 'wc-memberships-settings', $path . 'wc-memberships-settings.min.js', array_merge( $deps, array( 'wc-memberships-admin' ) ), $ver );
		}

		// Localize the main admin script to add variable properties and localization strings.
		wp_localize_script( 'wc-memberships-admin', 'wc_memberships_admin', array(

			// add any config/state properties here, for example:
			// 'is_user_logged_in' => is_user_logged_in()

			'ajax_url'                                  => admin_url( 'admin-ajax.php' ),
			'select2_version'                           => SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? '4.0.3' : '3.5.3',
			'search_products_nonce'                     => wp_create_nonce( 'search-products' ),
			'search_posts_nonce'                        => wp_create_nonce( 'search-posts' ),
			'search_terms_nonce'                        => wp_create_nonce( 'search-terms' ),
			'wc_plugin_url'                             => WC()->plugin_url(),
			'calendar_image'                            => WC()->plugin_url() . '/assets/images/calendar.png',
			'user_membership_url'                       => admin_url( 'edit.php?post_type=wc_user_membership' ),
			'new_user_membership_url'                   => admin_url( 'post-new.php?post_type=wc_user_membership' ),
			'get_membership_date_nonce'                 => wp_create_nonce( 'get-membership-date' ),
			'search_customers_nonce'                    => wp_create_nonce( 'search-customers' ),
			'add_user_membership_note_nonce'            => wp_create_nonce( 'add-user-membership-note' ),
			'delete_user_membership_note_nonce'         => wp_create_nonce( 'delete-user-membership-note' ),
			'transfer_user_membership_nonce'            => wp_create_nonce( 'transfer-user-membership' ),
			'delete_user_membership_subscription_nonce' => wp_create_nonce( 'delete-user-membership-with-subscription' ),
			'restrictable_post_types'                   => array_merge( array_keys( $this->get_valid_post_types_for_content_restriction() ), array( 'product') ),

			'i18n' => array(

				// add i18n strings here, for example:
				// 'log_in' => __( 'Log In', 'woocommerce-memberships' )

				'select_user'                => __( 'Select user', 'woocommerce-memberships' ),
				'add_member'                 => __( 'Add Member', 'woocommerce-memberships' ),
				'delete_membership_confirm'  => __( 'Are you sure that you want to permanently delete this membership?', 'woocommerce-memberships' ),
				'delete_memberships_confirm' => __( 'Are you sure that you want to permanently delete these memberships?', 'woocommerce-memberships' ),
				'transfer_membership'        => __( 'Transfer Membership', 'woocommerce-memberships' ),
				'cancel'                     => __( 'Cancel', 'woocommerce-memberships' ),
				'search_for_user'            => __( 'Search for a user&hellip;', 'woocommerce-memberships' ),
				/* translators: Placeholders: %1$s - opening <a> HTML tag, %2$s closing </a> HTML tag */
				'search_or_create_user'      => sprintf( __( 'Search for an existing user, or %1$sadd a new user%2$s to give them a membership.', 'woocommerce-memberships'), '<a href="' . esc_url( admin_url( 'user-new.php' ) ) . '">', '</a>' ),
			),
		) );
	}


	/**
	 * Add settings/export screen ID to the list of pages for WC to load its JS on
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_scripts( $screen_ids ) {
		return array_merge( $screen_ids, $this->get_screen_ids() );
	}


	/**
	 * Remove the duplicate submenu link for Memberships custom post type that
	 * is not being viewed. It's easier to add both submenu links via register_post_type()
	 * and conditionally remove them here than it is try to add them both
	 * correctly.
	 *
	 * @internal
	 *
	 * @since 1.2.0
	 */
	public function remove_submenu_link() {
		global $pagenow, $typenow;

		$submenu_slug = 'edit.php?post_type=wc_membership_plan';

		// remove user membership submenu page when viewing or editing membership plans
		if ( ( 'edit.php' === $pagenow && 'wc_membership_plan' === $typenow )
		     || ( 'post.php' === $pagenow && isset( $_GET['post'] ) && 'wc_membership_plan' === get_post_type( $_GET['post'] ) ) ) {

			$submenu_slug = 'edit.php?post_type=wc_user_membership';
		}

		remove_submenu_page( 'woocommerce', $submenu_slug );
	}


	/**
	 * Set the current tab
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param string $current_tab Current tab slug
	 * @return string
	 */
	public function set_current_tab( $current_tab ) {
		global $typenow;

		if ( 'wc_membership_plan' === $typenow ) {
			$current_tab = 'memberships';
		} elseif ( 'wc_user_membership' === $typenow ) {
			$current_tab = 'members';
		} elseif ( $this->is_import_export_admin_page() ) {
			$current_tab = 'import-export';
		}

		return $current_tab;
	}


	/**
	 * Render tabs on our custom post types pages.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function render_tabs() {
		global $typenow;

		// handle tabs on the relevant WooCommerce pages
		if (    $this->is_import_export_admin_page()
		     || ( is_string( $typenow ) && in_array( $typenow, array( 'wc_user_membership', 'wc_membership_plan' ), true ) ) ) :

			?>
			<div class="wrap woocommerce">
				<?php
				/**
				 * Filter the current Memberships Admin tab.
				 *
				 * @since 1.0.0
				 * @param string $current_tab
				 */
				$current_tab = apply_filters( 'wc_memberships_admin_current_tab', '' );
				?>
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php $tabs = $this->get_tabs(); ?>
					<?php foreach ( $tabs as $tab_id => $tab ) : ?>
						<?php $class = ( $tab_id === $current_tab ) ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' ); ?>
						<?php printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $tab['url'] ), implode( ' ', array_map( 'sanitize_html_class', $class ) ), esc_html( $tab['title'] ) ); ?>
					<?php endforeach; ?>
				</h2>
			</div>
			<?php

		// warn users against the usage of 'woocommerce_my_account' deprecated shortcode attributes as these could conflict with Memberships and trigger a server error in the Members Area
		elseif ( 'page' === $typenow  ) :
			global $post;

			if ( $post && ( (int) $post->ID === (int) wc_get_page_id( 'myaccount' ) || has_shortcode( $post->post_content, 'woocommerce_my_account' ) ) ) :

				preg_match_all('/' . get_shortcode_regex() .'/s', $post->post_content, $matches );

				if ( isset( $matches[2], $matches[3] ) && ( is_array( $matches[2] ) && is_array( $matches[3] ) ) ) {

					$position = null;

					foreach ( $matches[2] as $key => $found_shortcode ) {
						if ( 'woocommerce_my_account' === $found_shortcode ) {
							$position = $key;
							break;
						}
					}

					if ( null !== $position && ! empty( $matches[3][ $position ] ) ) {

						$has_atts = trim( $matches[3][ $position ] );

						if ( ! empty( $has_atts ) ) {

							?>
							<div class="notice notice-warning">
								<p><?php
									/* translators: Placeholders: %1$s - the 'woocommerce_my_account' shortcode, %2$s - the 'order_count' shortcode attribute */
									printf( __( 'It looks like you might be using the %1$s shortcode with deprecated attributes, such as %2$s. These attributes have been deprecated since WooCommerce 2.6 and may no longer have any effect on the shortcode output. Furthermore, they might cause a server error when visiting the Members Area while WooCommerce Memberships is active.', 'woocommerce-memberships' ), '<code>woocommerce_my_account</code>', '<code>order_count</code>' );
									?></p>
							</div>
							<?php
						}
					}
				}

			endif;

		endif;
	}


	/**
	 * Get valid post types for content restriction rules
	 *
	 * @since 1.0.0
	 * @return array Associative array of post type names and labels
	 */
	public function get_valid_post_types_for_content_restriction() {

		if ( ! isset( $this->valid_post_types_for_content_restriction ) ) {

			/**
			 * Exclude (blacklist) post types from content restriction content type options
			 *
			 * @since 1.0.0
			 * @param array $post_types List of post types to exclude
			 */
			$excluded_post_types = apply_filters( 'wc_memberships_content_restriction_excluded_post_types', array(
				'attachment',
				'wc_product_tab',
				'wooframework',
			) );

			$this->valid_post_types_for_content_restriction = array();

			foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {

				// skip products - they have their own restriction rules
				if ( in_array( $post_type->name, array( 'product', 'product_variation' ), true ) ) {
					continue;
				}

				// skip excluded custom post types
				if ( ! empty( $excluded_post_types ) && in_array( $post_type->name, $excluded_post_types, true ) ) {
					continue;
				}

				$this->valid_post_types_for_content_restriction[ $post_type->name ] = $post_type;
			}
		}

		return $this->valid_post_types_for_content_restriction;
	}


	/**
	 * Get valid taxonomies for a rule type
	 *
	 * @since 1.0.0
	 * @param string $rule_type Rule type. One of 'content_restriction', 'product_restriction' or 'purchasing_discount'
	 * @return array Associative array of taxonomy names and labels
	 */
	public function get_valid_taxonomies_for_rule_type( $rule_type ) {

		if ( ! isset( $this->valid_rule_type_taxonomies[ $rule_type ] ) ) {

			$excluded_taxonomies = array( 'product_shipping_class' );

			switch ( $rule_type ) {

				case 'content_restriction':
					$excluded_taxonomies = array_merge( $excluded_taxonomies, array( 'post_format', 'product_cat' ) );
				break;

				case 'product_restriction':
				case 'purchasing_discount':
					$excluded_taxonomies = array_merge( $excluded_taxonomies, array( 'product_tag' ) );
				break;

			}

			/**
			 * Exclude taxonomies from a rule type
			 *
			 * This filter allows excluding taxonomies from content & product restriction and
			 * purchasing discount rules.
			 *
			 * @since 1.0.0
			 * @param array $taxonomies List of taxonomies to exclude
			 */
			$excluded_taxonomies = apply_filters( "wc_memberships_{$rule_type}_excluded_taxonomies", $excluded_taxonomies );

			$this->valid_rule_type_taxonomies[ $rule_type ] = array();

			// $wp_taxonomy global used as some post types (product add-ons) attach
			// themselves to certain product-related taxonomies (like product_cat)
			// and get_taxonomies() provides no way to do an in_array() on the object
			// types. they either must match exactly or the taxonomy isn't returned
			foreach ( $GLOBALS['wp_taxonomies'] as $taxonomy ) {

				// skip non-public or excluded taxonomies
				if ( ! $taxonomy->public || ( ! empty( $excluded_taxonomies ) && in_array( $taxonomy->name, $excluded_taxonomies ) ) ) {
					continue;
				}

				if ( 'content_restriction' === $rule_type ) {

					// skip product-only taxonomies, they are listed in product restriction rules
					if ( count( $taxonomy->object_type ) === 1 && in_array( 'product', $taxonomy->object_type, true ) ) {
						continue;
					}
				}

				if ( in_array( $rule_type, array( 'product_restriction', 'purchasing_discount' ), true ) ) {

					// skip taxonomies not registered for products
					if ( ! in_array( 'product', (array) $taxonomy->object_type, true ) ) {
						continue;
					}

					// skip product attributes
					if ( strpos( $taxonomy->name, 'pa_' ) === 0 ) {
						continue;
					}
				}

				$this->valid_rule_type_taxonomies[ $rule_type ][ $taxonomy->name ] = $taxonomy;
			}
		}

		return $this->valid_rule_type_taxonomies[ $rule_type ];
	}


	/**
	 * Get valid taxonomies for content restriction rules
	 *
	 * @since 1.0.0
	 * @return array Associative array of taxonomy names and labels
	 */
	public function get_valid_taxonomies_for_content_restriction() {
		return $this->get_valid_taxonomies_for_rule_type( 'content_restriction' );
	}


	/**
	 * Get valid taxonomies for product restriction rules
	 *
	 * @since 1.0.0
	 * @return array Associative array of taxonomy names and labels
	 */
	public function get_valid_taxonomies_for_product_restriction() {
		return $this->get_valid_taxonomies_for_rule_type( 'product_restriction' );
	}


	/**
	 * Get valid taxonomies for purchasing discount rules
	 *
	 * @since 1.0.0
	 * @return array Associative array of taxonomy names and labels
	 */
	public function get_valid_taxonomies_for_purchasing_discounts() {
		return $this->get_valid_taxonomies_for_rule_type( 'purchasing_discount' );
	}


	/**
	 * Adds Memberships to "Edit Order" screen
	 *
	 * @internal
	 *
	 * @since 1.3.8
	 * @param \WC_Order|\WC_Order_Refund $order the WooCommerce order
	 */
	public function render_order_data( $order ) {

		$customer_user = $order instanceof WC_Order || $order instanceof WC_Order_Refund ? get_user_by( 'id', $order->get_user_id() ) : null;

		if ( empty( $customer_user ) ) {
			return;
		}

		?>
		<p class="form-field form-field-wide wc-customer-memberships">
			<label for="customer_memberships"><?php esc_html_e( 'Active Memberships:', 'woocommerce-memberships' ); ?></label>
			<?php

			$user_id = $order->get_user_id();

			// Get all active memberships
			$memberships = wc_memberships()->get_user_memberships_instance()->get_user_memberships( $user_id );

			// count the memberships displayed
			$count = 0;

			if ( ! empty( $memberships ) ) {

				foreach ( $memberships as $membership ) {

					$plan = $membership->get_plan();

					if ( $plan && wc_memberships_is_user_active_member( $user_id, $plan ) ) {

						edit_post_link( esc_html( $plan->name ), '', '<br />', $membership->id );
						$count++;
					}
				}
			}

            if ( empty( $memberships ) || ! $count ) {
				esc_html_e( 'none', 'woocommerce-memberships' );
			}

			?>
		</p>
		<?php
	}


	/**
	 * Display admin messages
	 *
	 * @since 1.0.0
	 */
	public function show_admin_messages() {
		$this->message_handler->show_messages();
	}


	/**
	 * Update rules for each provided rule type
	 *
	 * This method should be used by individual meta boxes that are updating rules
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param array $rule_types Array of rule types to update
	 * @param string $target Optional. Indicates the context we are updating rules in. One of 'plan' or 'post'
	 */
	public function update_rules( $post_id, $rule_types, $target = 'plan' ) {

		$rules = get_option( 'wc_memberships_rules', array() );

		foreach ( $rule_types as $rule_type ) {

			$rule_type_post_key = '_' . $rule_type . '_rules';

			if ( ! isset( $_POST[ $rule_type_post_key ] ) ) {
				continue;
			}

			// Save rule type
			$posted_rules = $_POST[ $rule_type_post_key ];

			// Remove template rule
			if ( isset( $posted_rules['__INDEX__'] ) ) {
				unset( $posted_rules['__INDEX__'] );
			}

			// Stop processing rule type if no rules left
			if ( empty( $posted_rules ) ) {
				continue;
			}

			// Pre-process rules before saving
			foreach ( $posted_rules as $key => $rule ) {

				// If not updating rules for a plan, but rather a single post,
				// do not process or update inherited rules or rules that apply to multiple objects
				if ( 'post' === $target && isset( $rule['object_ids'] ) && is_array( $rule['object_ids'] ) && isset( $rule['object_ids'][0] ) && $rule['object_ids'][0] != $post_id ) {
					unset( $posted_rules[ $key ] );
					continue;
				}

				// Make sure each rule has an ID
				if ( ! isset( $rule['id'] ) || ! $rule['id'] ) {
					$rule['id'] = uniqid( 'rule_', false );
				}

				// Make sure each rule has the rule type set
				$rule['rule_type'] = $rule_type;

				// If updating rules for a single plan, set the plan ID
				// and content type fields on the rule
				if ( 'plan' === $target ) {

					// Make sure each rule has correct membership plan ID
					$rule['membership_plan_id'] = $post_id;

					// Normalize content type: break content_type_key into parts
					$content_type_parts        = explode( '|', isset( $rule['content_type_key'] ) ? $rule['content_type_key'] : '' );
					$rule['content_type']      = isset( $content_type_parts[0] )                  ? $content_type_parts[0]    : '';
					$rule['content_type_name'] = isset( $content_type_parts[1] )                  ? $content_type_parts[1]    : '';

					if ( isset( $rule['content_type_key'] ) ) {
						unset( $rule['content_type_key'] );
					}

					// Normalize object IDs
					if ( isset( $rule['object_ids'] ) && $rule['object_ids'] && ! is_array( $rule['object_ids'] ) ) {
						$rule['object_ids'] = explode( ',', $rule['object_ids'] );
					}
				}

				// If updating rules for a single post, rather than a plan,
				// set the object ID and content type explicitly to match
				// the current post
				else {

					// Ensure that the correct object ID is set
					if ( ! isset( $rule['object_ids'] ) || empty( $rule['object_ids'] ) ) {
						$rule['object_ids'] = array( $post_id );
					}

					// Ensure correct content type & name is set
					$rule['content_type']      = 'post_type';
					$rule['content_type_name'] = get_post_type( $post_id );
				}

				// Content restriction & product restriction:
				if ( in_array( $rule_type, array( 'content_restriction', 'product_restriction' ), true ) ) {

					// Make sure access_schedule_exclude_trial is set, even if it's a no
					if ( ! isset( $rule['access_schedule_exclude_trial'] ) ) {
						$rule['access_schedule_exclude_trial'] = 'no';
					}

					// If no access schedule is set, set it to immediate by default
					if ( ! isset( $rule['access_schedule'] ) ) {
						$rule['access_schedule'] = 'immediate';
					}

					// Normalize access schedule
					if ( 'specific' === $rule['access_schedule'] ) {

						if ( ! $rule['access_schedule_amount'] ) {
							$rule['access_schedule'] = 'immediate';
						} else {
							// Create textual (human-readable) representation of the access schedule
							$rule['access_schedule'] = sprintf( '%d %s', $rule['access_schedule_amount'], $rule['access_schedule_period'] );
						}
					}

					unset( $rule['access_schedule_amount'], $rule['access_schedule_period'] );
				}

				// Purchasing discounts:
				else if ( 'purchasing_discount' === $rule_type  ) {

					// Make sure active is set, even if it's a no
					$rule['active'] = isset( $rule['active'] ) && $rule['active'] ? 'yes' : 'no';
				}

				// Update rule properties
				$posted_rules[ $key ] = $rule;

			} // end pre-processing rules


			// Process posted rules
			foreach ( $posted_rules as $key => $posted ) {

				$existing_rule_key = $this->array_search_key_value( $rules, 'id', $posted['id'] );

				// This is an existing rule
				if ( is_numeric( $existing_rule_key ) ) {

					$rule = new WC_Memberships_Membership_Plan_Rule( $rules[ $existing_rule_key ] );

					// Check capabilities
					if ( $rule->content_type_exists() && ! $rule->current_user_can_edit() ) {
						continue;
					}

					// Check if current context allows editing
					if ( ! $rule->current_context_allows_editing() ) {
						continue;
					}

					if ( isset( $posted['remove'] ) && $posted['remove'] ) {
						unset( $rules[ $existing_rule_key ] );
						continue;
					}

					// Remove unnecessary keys
					unset( $posted['remove'] );

					// Update existing rule
					$rules[ $existing_rule_key ] = $posted;

				}

				// This is a new rule, so simply append it to existing rules
				else {

					// Remove unnecessary keys
					unset( $posted['remove'] );

					// TODO perhaps refactor `switch` below with `if/else` because `continue` used within `switch` behaves like `break` and this might cause confusion or unintended result - since this switch is nested inside foreach an if/else would probably be more suitable {FN 2016-5-1}

					// Check capabilities
					switch ( $posted['content_type'] ) {

						case 'post_type':
							$post_type = get_post_type_object( $posted['content_type_name'] );

							// Skip if user has no capabilities to edit the associated post type
							if ( ! ( current_user_can( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_others_posts ) ) ) {
								continue;
							}

						break;

						case 'taxonomy':
							$taxonomy = get_taxonomy( $posted['content_type_name'] );

							// Skip if user has no capabilities to edit the associated taxonomy
							if ( ! ( current_user_can( $taxonomy->cap->manage_terms ) && current_user_can( $taxonomy->cap->edit_terms ) ) ) {
								continue;
							}

						break;
					}

					$rules[] = $posted;
				}
			}
		}

		update_option( 'wc_memberships_rules', ! empty( $rules ) ? array_values( $rules ) : array() );
	}


	/**
	 * Search an array of arrays by key-value
	 *
	 * If a match is found in the array more than once,
	 * only the first matching key is returned
	 *
	 * @since 1.6.0
	 * @param array $array Array of arrays
	 * @param string $key The key to search for
	 * @param string $value The value to search for
	 * @return array|false|null Found results, or false if none found, null if no array supplied
	 */
	private function array_search_key_value( $array, $key, $value ) {

		if ( ! is_array( $array ) ) {
			return null;
		}

		if ( empty( $array ) ) {
			return false;
		}

		$found_key = false;

		foreach ( $array as $element_key => $element ) {

			if ( isset( $element[ $key ] ) && $value == $element[ $key ] ) {

				$found_key = $element_key;
				break;
			}
		}

		return $found_key;
	}


	/**
	 * Update a custom message for a post
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param array $message_types
	 */
	public function update_custom_message( $post_id, $message_types ) {

		foreach ( $message_types as $message_type ) {

			$message    = '';
			$use_custom = 'no';

			if ( isset( $_POST["_wc_memberships_{$message_type}_message"] ) ) {
				$message    = wp_unslash( sanitize_post_field( 'post_content', $_POST["_wc_memberships_{$message_type}_message"], 0, 'db' ) );
			}
			if ( isset( $_POST["_wc_memberships_use_custom_{$message_type}_message"] ) && 'yes' === $_POST["_wc_memberships_use_custom_{$message_type}_message"] ) {
				$use_custom = 'yes';
			}

			wc_memberships_set_content_meta( $post_id, "_wc_memberships_use_custom_{$message_type}_message", $use_custom );
			wc_memberships_set_content_meta( $post_id, "_wc_memberships_{$message_type}_message", $message );
		}
	}


	/**
	 * Remove New User Membership menu option from Admin Bar
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @param \WP_Admin_Bar $admin_bar WP_Admin_Bar instance, passed by reference
	 */
	public function admin_bar_menu( $admin_bar ) {
		$admin_bar->remove_menu( 'new-wc_user_membership' );
	}


	/**
	 * Duplicate memberships data for a product
	 *
	 * TODO update phpdoc and method when WC 3.0 is the minimal requirement {FN 2017-01-13}
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 * @param int|\WC_Product $new_product New product (was product id in WC versions earlier than 2.7).
	 * @param \WP_Post|\WC_Product $old_product Old product (was old post object in WC versions earlier than 2.7).
	 */
	public function duplicate_product_memberships_data( $new_product, $old_product ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
			$new_product_id        = $new_product->get_id();
			$old_product_id        = $old_product->get_id();
			$old_product_post_type = get_post_type( $old_product );
		} else {
			$new_product_id        = $new_product;
			$new_product           = wc_get_product( $new_product_id );
			$old_product_id        = $old_product->ID;
			$old_product_post_type = $old_product->post_type;
		}

		// Get product restriction rules.
		$product_restriction_rules = wc_memberships()->get_rules_instance()->get_rules( array(
			'rule_type'         => 'product_restriction',
			'object_id'         => $old_product_id,
			'content_type'      => 'post_type',
			'content_type_name' => $old_product_post_type,
			'exclude_inherited' => true,
			'plan_status'       => 'any',
		) );

		// Get purchasing discount rules.
		$purchasing_discount_rules = wc_memberships()->get_rules_instance()->get_rules( array(
			'rule_type'         => 'purchasing_discount',
			'object_id'         => $old_product_id,
			'content_type'      => 'post_type',
			'content_type_name' => $old_product_post_type,
			'exclude_inherited' => true,
			'plan_status'       => 'any',
		) );

		$product_rules = array_merge( $product_restriction_rules, $purchasing_discount_rules );

		// Duplicate rules.
		if ( ! empty( $product_rules ) ) {

			$all_rules = get_option( 'wc_memberships_rules' );

			foreach ( $product_rules as $rule ) {

				$new_rule               = $rule->get_raw_data();
				$new_rule['object_ids'] = array( $new_product_id );
				$all_rules[]            = $new_rule;
			}

			update_option( 'wc_memberships_rules', $all_rules );
		}

		// Duplicate custom messages.
		foreach ( array( 'product_viewing_restricted', 'product_purchasing_restricted' ) as $message_type ) {

			if ( $use_custom = wc_memberships_get_content_meta( $old_product, "_wc_memberships_use_custom_{$message_type}_message", true ) ) {
				wc_memberships_set_content_meta( $new_product, "_wc_memberships_use_custom_{$message_type}_message", $use_custom );
			}

			if ( $message = wc_memberships_get_content_meta( $old_product, "_wc_memberships_{$message_type}_message", true ) ) {
				wc_memberships_set_content_meta( $new_product, "_wc_memberships_{$message_type}_message", $message );
			}
		}

		$plans = wc_memberships_get_membership_plans();

		if ( ! empty( $plans ) ) {

			// duplicate 'grants access to'
			foreach ( $plans as $plan ) {

				if ( $plan->has_product( $old_product_id ) ) {
					// add new product id to product ids
					$plan->set_product_ids( $new_product_id, true );
				}
			}
		}

		// Duplicate other settings.
		wc_memberships_set_content_meta( $new_product, '_wc_memberships_force_public', wc_memberships_get_content_meta( $old_product, '_wc_memberships_force_public', true ) );
		wc_memberships_set_content_meta( $new_product, '_wc_memberships_exclude_discounts', wc_memberships_get_content_meta( $old_product, '_wc_memberships_exclude_discounts', true ) );
	}


	/**
	 * Process import / export page input form
	 *
	 * @since 1.6.0
	 */
	public function process_import_export_form() {

		// get action and bail out if can't be found
		if ( isset( $_POST['action'], $_POST['_wp_http_referer'] ) && is_string( $_POST['action'] ) && SV_WC_Helper::str_starts_with( $_POST['action'], 'wc_memberships_' ) ) {
			$action = str_replace( 'wc_memberships_', '', $_POST['action'] );
		} else {
			return;
		}

		$handler  = $this->import_export = wc_memberships()->load_class( '/includes/admin/class-wc-memberships-import-export-handler.php', 'WC_Memberships_Admin_Import_Export_Handler' );
		$sections = $handler->get_admin_page_sections();

		// set the action and do a sanity check
		if ( $action && isset( $sections[ $action ] ) ) {
			$handler->set_action( $action );
		} else {
			return;
		}

		// security check
		if ( ! current_user_can( 'manage_woocommerce' ) || ! check_admin_referer( 'wc_memberships_' . $action, '_wpnonce' ) ) {
			wp_die( __( 'You are not allowed to perform this action.', 'woocommerce-memberships' ) );
		}

		/**
		 * Upon Memberships importing or exporting
		 *
		 * @since 1.6.0
		 * @param array $posted_data form submission
		 * @param \WC_Memberships_Admin_Import_Export_Handler $handler Current instance of handler class
		 */
		do_action( "wc_memberships_import_export_process_{$action}", $_POST, $handler );

		$handler->process_form();

		// redirect back to import / export screen
		wp_safe_redirect( $_POST['_wp_http_referer'] );
		exit;
	}


	/**
	 * Backwards compatibility handler for deprecated properties
	 *
	 * TODO by version 2.0.0 these backward compatibility calls could be removed {FN 2016-04-27}
	 *
	 * @since 1.6.0
	 * @param string $property Property called
	 * @return null|void|mixed
	 */
	public function __get( $property ) {

		$class = 'wc_memberships()->get_admin_instance()';

		$deprecated_since_1_6_0 = '1.6.0';

		switch ( $property ) {

			/** @deprecated since 1.6.0 */
			case 'tabs' :
				_deprecated_function( "{$class}->{$property}", $deprecated_since_1_6_0 );
				return $this->get_tabs();

			default :
				// you're probably doing it wrong
				trigger_error( 'Call to undefined property ' . __CLASS__ . '::' . $property, E_USER_ERROR );
				return null;

		}
	}


	/**
	 * Backwards compatibility handler for deprecated methods
	 *
	 * TODO by version 2.0.0 these backward compatibility calls could be removed {FN 2016-04-27}
	 *
	 * @since 1.6.0
	 * @param string $method Method called
	 * @param void|string|array|mixed $args Optional argument(s)
	 * @return null|void|mixed
	 */
	public function __call( $method, $args ) {

		switch ( $method ) {

			/** @deprecated since 1.6.0 */
			case 'render_tabs_for_cpt' :
				_deprecated_function( "WC_Memberships_Admin::{$method}()", '1.6.0', 'wc_memberships()->get_admin_instance()->render_tabs()' );
				$this->render_tabs();
				return null;

			/** @deprecated since 1.7.4 */
			case 'add_user_columns' :
				_deprecated_function( "WC_Memberships_Admin::{$method}()", '1.7.4', 'wc_memberships()->get_admin_instance()->get_users_instance()->add_user_columns()' );
				return $this->get_users_instance()->add_user_columns( $args );

			/** @deprecated since 1.7.4 */
			case 'user_column_values' :
				_deprecated_function( "WC_Memberships_Admin::{$method}()", '1.7.4', 'wc_memberships()->get_admin_instance()->get_users_instance()->user_column_values()' );
				$output      = isset( $args[0] ) ? $args[0] : $args;
				$column_name = isset( $args[1] ) ? $args[1] : '';
				$user_id     = isset( $args[2] ) ? $args[2] : 0;
				return $this->get_users_instance()->user_column_values( $output, $column_name, $user_id );

			/** @deprecated since 1.7.4 */
			case 'show_user_memberships' :
				_deprecated_function( "WC_Memberships_Admin::{$method}()", '1.7.4', 'wc_memberships()->get_admin_instance()->get_users_instance()->show_user_memberships()' );
				return $this->get_users_instance()->show_user_memberships( $args );

			default :
				// you're probably doing it wrong
				trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $method, E_USER_ERROR );
				return null;
		}
	}


}
