<?php
/**
 * Booster for WooCommerce - Plus - Site Key Manager
 *
 * @version 3.0.0
 * @since   3.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Plus_Site_Key_Manager' ) ) :

class WCJ_Plus_Site_Key_Manager {

	/**
	 * Constructor.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @see     http://w-shadow.com/blog/2010/09/02/automatic-updates-for-any-plugin/
	 * @todo    (maybe) fix errors (empty metadata file etc.) `trigger_error` (now it's just disabled)
	 * @todo    (maybe) option to update to dev version
	 * @todo    (maybe) discount coupons
	 */
	function __construct() {

		$this->update_server_url = 'https://' . wcj_plus_get_update_server();
		$this->site_url          = wcj_plus_get_site_url();

		// Key management
		add_action( 'woojetpack_after_settings_save', array( $this, 'check_site_key_on_set_key' ), 10, 2 );
		add_action( 'admin_notices',                  array( $this, 'admin_notice_site_key_status' ) );

		// Debug
		add_action( 'admin_notices', array( $this, 'debug_site_key_data' ), PHP_INT_MAX );

		// Cron
		add_action( 'init',               array( $this, 'schedule_the_events' ) );
		add_action( 'admin_init',         array( $this, 'schedule_the_events' ) );
		add_action( 'wcj_check_site_key', array( $this, 'check_site_key_cron' ) );

		// Handle plugins update
		require_once( 'lib/plugin-update-checker/plugin-update-checker.php' );
		$this->update_checker = new PluginUpdateChecker_3_1( $this->update_server_url . '/?alg_update_action=get_metadata&alg_update_slug=booster-plus-for-woocommerce', wcj_plugin_file() );
		$this->update_checker->addQueryArgFilter( array( $this, 'add_updater_query_args' ) );
		if ( is_admin() ) {
			add_action( 'after_plugin_row_' . plugin_basename( wcj_plugin_file() ), array( $this, 'maybe_add_after_plugin_row_key_error_message' ), 1, 3 );
		}

	}

	/**
	 * debug_site_key_data.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function debug_site_key_data() {
		if ( isset( $_GET['wcj_site_key_debug'] ) ) {
			$site_key_status = $this->get_site_key_status();
			$message = '';
			$message .= '<pre>' . print_r( $site_key_status, true ) . '</pre>';
			$message .= '<pre>' . sprintf( __( 'Status last checked at %s.', 'woocommerce-jetpack' ), date( 'Y-m-d H:i:s', $site_key_status['time_checked'] ) ) . '</pre>';
			$message .= '<pre>' . sprintf( __( 'Cron scheduled at %s.', 'woocommerce-jetpack' ),      date( 'Y-m-d H:i:s', get_option( 'wcj_check_site_key_cron_time_schedule', '' ) ) ) . '</pre>';
			$message .= '<pre>' . sprintf( __( 'Cron last run at %s.', 'woocommerce-jetpack' ),       date( 'Y-m-d H:i:s', get_option( 'wcj_check_site_key_cron_time_last_run', '' ) ) ) . '</pre>';
			printf( '<div class="notice notice-info"><p>%s</p></div>', $message );
		}
	}

	/**
	 * admin_notice_site_key_status.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function admin_notice_site_key_status() {
		if (
			isset( $_GET['page'] )    && 'wc-settings' === $_GET['page'] &&
			isset( $_GET['tab'] )     && 'jetpack'     === $_GET['tab'] &&
			isset( $_GET['wcj-cat'] ) && 'dashboard'   === $_GET['wcj-cat'] &&
			isset( $_GET['section'] ) && 'site_key'    === $_GET['section']
		) {
			if ( false !== ( $site_key_status = $this->get_site_key_status() ) ) {
				$class   = ( $this->is_site_key_valid() ? 'notice notice-success is-dismissible' : 'notice notice-error' );
				$message = $this->get_site_key_status_message();
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
			}
		}
	}

	/**
	 * get_site_key_status_message.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 * @todo    (maybe) fill `client_message` directly
	 */
	function get_site_key_status_message() {
		$site_key_status = $this->get_site_key_status();
		if ( isset( $site_key_status['server_response']->error->message ) ) {
			return $site_key_status['server_response']->error->message;
		} else {
			if ( isset( $site_key_status['client_data'] ) ) {
				switch ( $site_key_status['client_data'] ) {
					case 'EMPTY_SITE_KEY':
						return __( 'No key set.', 'woocommerce-jetpack' ) . ' ' .
							sprintf( __( 'To get the key, please visit <a target="_blank" href="%s">your account page at %s</a>.', 'woocommerce-jetpack' ),
								'https://' . wcj_plus_get_update_server() . '/my-account/downloads/', wcj_plus_get_update_server() );
					case 'NO_RESPONSE':
						return __( 'No response from server. Please try again later.', 'woocommerce-jetpack' );
					case 'SERVER_ERROR':
						return __( 'Server error. Please try again later.', 'woocommerce-jetpack' );
				}
			}
			return false;
		}
	}

	/**
	 * maybe_add_after_plugin_row_key_error_message.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function maybe_add_after_plugin_row_key_error_message( $plugin_file, $plugin_data, $status ) {
		if ( ! $this->is_site_key_valid() && false !== ( $site_key_status = $this->get_site_key_status_message() ) ) {
			echo '<tr class="plugin-update-tr active" id="booster-plus-for-woocommerce-update-site-key" data-slug="booster-plus-for-woocommerce" data-plugin="' . plugin_basename( wcj_plugin_file() ) . '">' .
				'<td colspan="3" class="plugin-update colspanchange">' .
					'<div class="update-message notice inline notice-warning notice-alt">' .
						'<p>' . $site_key_status . '</p>' .
					'</div>' .
				'</td>' .
			'</tr>';
		}
	}

	/**
	 * get_site_key_status.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function get_site_key_status() {
		return get_option( 'wcj_site_key_status', false );
	}

	/**
	 * is_site_key_valid.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function is_site_key_valid() {
		$site_key_status = $this->get_site_key_status();
		return ( isset( $site_key_status['server_response']->status ) && $site_key_status['server_response']->status );
	}

	/**
	 * update_site_key_status.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function update_site_key_status( $server_response, $client_data = '' ) {
		update_option( 'wcj_site_key_status', array(
			'server_response' => $server_response,
			'client_data'     => $client_data,
			'time_checked'    => time(),
		) );
	}

	/**
	 * check_site_key.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function check_site_key() {
		if ( '' != ( $site_key = $this->get_site_key() ) ) {
			$url = $this->update_server_url . '/?check_site_key=' . $site_key . '&item_slug=booster-plus-for-woocommerce&site_url=' . $this->site_url;
			if ( ! function_exists( 'download_url' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			$response_file_name = download_url( $url );
			if ( ! is_wp_error( $response_file_name ) ) {
				if ( $response = file_get_contents( $response_file_name ) ) {
					$this->update_site_key_status( json_decode( $response ) );
				} else {
					$this->update_site_key_status( array(), 'NO_RESPONSE' );
				}
				unlink( $response_file_name );
			} else {
				$this->update_site_key_status( array(), 'SERVER_ERROR' );
			}
		} else {
			$this->update_site_key_status( array(), 'EMPTY_SITE_KEY' );
		}
	}

	/**
	 * check_site_key_on_set_key.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function check_site_key_on_set_key( $sections, $current_section ) {
		if ( 'site_key' === $current_section ) {
			$this->check_site_key();
			$this->update_checker->checkForUpdates();
			wp_safe_redirect( add_query_arg( '', '' ) );
			exit;
		}
	}

	/**
	 * add_updater_query_args.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function add_updater_query_args( $query ) {
		$query['alg_site_key'] = $this->get_site_key();
		$query['alg_site_url'] = $this->site_url;
		return $query;
	}

	/**
	 * get_site_key.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function get_site_key() {
		return get_option( 'wcj_site_key', '' );
	}

	/**
	 * schedule_the_events.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function schedule_the_events() {
		$event_timestamp = wp_next_scheduled( 'wcj_check_site_key', array( 'daily' ) );
		update_option( 'wcj_check_site_key_cron_time_schedule', $event_timestamp );
		if ( ! $event_timestamp ) {
			wp_schedule_event( time(), 'daily', 'wcj_check_site_key', array( 'daily' ) );
		}
	}

	/**
	 * On the scheduled action hook, run a function.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 */
	function check_site_key_cron( $interval ) {
		update_option( 'wcj_check_site_key_cron_time_last_run', time() );
		$this->check_site_key();
	}

}

endif;

return new WCJ_Plus_Site_Key_Manager();
