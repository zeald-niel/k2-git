<?php
/**
 * Booster for WooCommerce - Plus - Site Key Section
 *
 * @version 3.0.0
 * @since   3.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Plus_Site_Key_Section' ) ) :

class WCJ_Plus_Site_Key_Section {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function __construct() {
		if ( is_admin() ) {

			$this->update_server = wcj_plus_get_update_server();
			$this->site_url      = wcj_plus_get_site_url();

			add_filter( 'plugin_action_links_'   . plugin_basename( wcj_plugin_file() ), array( $this, 'add_manage_key_action_link' ), 10, 4 );
			add_filter( 'wcj_admin_bar_dashboard_nodes',                                 array( $this, 'add_site_key_admin_bar_dashboard_node' ) );
			add_filter( 'wcj_modules',                                                   array( $this, 'add_site_key_module' ) );
			add_filter( 'wcj_custom_dashboard_modules',                                  array( $this, 'add_site_key_section' ) );
		}
	}

	/**
	 * add_manage_key_action_link.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @param   mixed $links
	 * @return  array
	 */
	function add_manage_key_action_link( $actions, $plugin_file, $plugin_data, $context ) {
		$custom_actions = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=site_key' ) . '">' .
				__( 'Manage site key', 'woocommerce-jetpack' ) . '</a>'
		);
		return array_merge( $actions, $custom_actions );
	}

	/**
	 * add_site_key_admin_bar_dashboard_node.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function add_site_key_admin_bar_dashboard_node( $nodes ) {
		$nodes['site_key'] = array(
			'title'  => __( 'Site Key', 'woocommerce-jetpack' ),
			'href'   => admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=dashboard&section=site_key' ),
		);
		return $nodes;
	}

	/**
	 * add_site_key_section.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function add_site_key_section( $custom_dashboard_modules ) {
		$custom_dashboard_modules['site_key'] = array(
			'title'    => __( 'Site Key', 'woocommerce-jetpack' ),
			'desc'     => __( 'This section lets you manage site key for paid Booster Plus for WooCommerce plugin.', 'woocommerce-jetpack' ) . ' ' .
				sprintf( __( 'To get the key, please go to <a target="_blank" href="%s">your account page at %s</a>.', 'woocommerce-jetpack' ),
					'https://' . $this->update_server . '/my-account/', $this->update_server ),
			'settings' => array(
				array(
					'title'   => __( 'Site Key', 'woocommerce-jetpack' ),
					'desc'    => sprintf( __( 'Site URL: %s', 'woocommerce-jetpack' ), '<code>' . $this->site_url . '</code>' ),
					'type'    => 'text',
					'id'      => 'wcj_site_key',
					'default' => '',
					'class'   => 'widefat',
				),
			),
		);
		return $custom_dashboard_modules;
	}

	/**
	 * add_site_key_module.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function add_site_key_module( $modules_array ) {
		$modules_array['dashboard']['all_cat_ids'][] = 'site_key';
		return $modules_array;
	}

}

endif;

return new WCJ_Plus_Site_Key_Section();
