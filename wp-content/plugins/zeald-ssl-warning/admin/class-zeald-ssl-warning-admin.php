<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://zeald.com
 * @since      1.0.0
 *
 * @package    Zeald_Ssl_Warning
 * @subpackage Zeald_Ssl_Warning/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Zeald_Ssl_Warning
 * @subpackage Zeald_Ssl_Warning/admin
 * @author     Zeald <info@zeald.com>
 */
class Zeald_Ssl_Warning_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Zeald_Ssl_Warning_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Zeald_Ssl_Warning_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/zeald-ssl-warning-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Zeald_Ssl_Warning_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Zeald_Ssl_Warning_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/zeald-ssl-warning-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function ssl_notice(){
	    if(!is_ssl()){//ssl is not installed
            $class = 'update-nag';// use this one to avoid this particular notice to be moved in div.wrap

            printf("<div class='$class' style='border-color: #dc3232;'>Your website is not secured by SSL. This will impact your rankings & results. <a  target='_blank' href='http://store.zeald.com/shop/Website+Package/SSL+Certificate+Register+Install+Manage.html?sku=SSLCert&gotopage=95'>Fix this here.</a></div>");
        }
    }

}
