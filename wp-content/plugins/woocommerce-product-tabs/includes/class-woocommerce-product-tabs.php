<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://nilambar.net
 * @since      1.0.0
 *
 * @package    Woocommerce_Product_Tabs
 * @subpackage Woocommerce_Product_Tabs/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommerce_Product_Tabs
 * @subpackage Woocommerce_Product_Tabs/includes
 * @author     Nilambar Sharma <nilambar@outlook.com>
 */
class Woocommerce_Product_Tabs {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woocommerce_Product_Tabs_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'woocommerce-product-tabs';
		$this->version = '2.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woocommerce_Product_Tabs_Loader. Orchestrates the hooks of the plugin.
	 * - Woocommerce_Product_Tabs_i18n. Defines internationalization functionality.
	 * - Woocommerce_Product_Tabs_Admin. Defines all hooks for the dashboard.
	 * - Woocommerce_Product_Tabs_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-product-tabs-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocommerce-product-tabs-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woocmmerce-product-tabs-product-category-walker.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woocommerce-product-tabs-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woocommerce-product-tabs-public.php';

		$this->loader = new Woocommerce_Product_Tabs_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woocommerce_Product_Tabs_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Woocommerce_Product_Tabs_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woocommerce_Product_Tabs_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		$this->loader->add_action( 'edit_form_after_editor', $plugin_admin, 'content_after_editor' );

		// Metabox in Product page
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_product_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_box_content' );

		// Metabox in Tab
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_tab_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_tab_meta_box_content' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_tab_meta_box_conditions_content' );

		// Columns in Tab Listing
		$this->loader->add_filter( 'manage_woo_product_tab_posts_columns', $plugin_admin, 'add_columns_in_tab_listing' );
		$this->loader->add_filter( 'manage_edit-woo_product_tab_sortable_columns', $plugin_admin, 'sortable_tab_columns' );
		$this->loader->add_action( 'manage_woo_product_tab_posts_custom_column', $plugin_admin, 'custom_columns_in_tab_listing', 10, 2  );

		// Bulk Messages
		$this->loader->add_filter( 'post_updated_messages', $plugin_admin, 'tab_post_updated_messages', 10, 2 );

		// Row actions
		$this->loader->add_filter( 'post_row_actions', $plugin_admin, 'tab_post_row_actions' , 10, 2 );

        // Hide publishing actions.
        $this->loader->add_action( 'admin_head-post.php', $plugin_admin, 'hide_publishing_actions' );
        $this->loader->add_action( 'admin_head-post-new.php', $plugin_admin, 'hide_publishing_actions' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Woocommerce_Product_Tabs_Public( $this->get_plugin_name(), $this->get_version() );

		// Public custom hooks
		$this->loader->add_filter( 'init', $plugin_public, 'custom_post_types' );
        $this->loader->add_filter( 'woocommerce_product_tabs', $plugin_public, 'custom_woocommerce_product_tabs', 20 );

		$this->loader->add_filter( 'wpt_filter_product_tabs', $plugin_public, 'tab_status_check' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woocommerce_Product_Tabs_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
