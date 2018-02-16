<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://mailchimp.com
 * @since      1.0.1
 *
 * @package    MailChimp_Woocommerce
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if (!function_exists('mailchimp_environment_variables')) {
    /**
     * @return object
     */
    function mailchimp_environment_variables() {
        global $wp_version;

        $o = get_option('mailchimp-woocommerce', false);

        return (object) array(
            'repo' => 'master',
            'environment' => 'production',
            'version' => '2.1.2',
            'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
            'wc_version' => class_exists('WC') ? WC()->version : null,
            'logging' => ($o && is_array($o) && isset($o['mailchimp_logging'])) ? $o['mailchimp_logging'] : 'none',
        );
    }
}

try {
    if (($options = get_option('mailchimp-woocommerce', false)) && is_array($options)) {
        if (isset($options['mailchimp_api_key'])) {
            $store_id = get_option('mailchimp-woocommerce-store_id', false);

            if (!empty($store_id)) {
                if (!class_exists('MailChimp_WooCommerce_MailChimpApi')) {
                    require_once 'includes/api/class-mailchimp-api.php';
                    require_once 'includes/api/errors/class-mailchimp-error.php';
                    require_once 'includes/api/errors/class-mailchimp-server-error.php';
                }
                $api = new MailChimp_WooCommerce_MailChimpApi($options['mailchimp_api_key']);
                $result = $api->deleteStore($store_id) ? 'has been deleted' : 'did not delete';
                error_log("store id {$store_id} {$result} MailChimp");
            }
        }
    }
} catch (\Exception $e) {
    error_log($e->getMessage().' on '.$e->getLine().' in '.$e->getFile());
}

delete_option('mailchimp-woocommerce-store_id');
delete_option('mailchimp-woocommerce');
delete_option('mailchimp-woocommerce-errors.store_info');
delete_option('mailchimp-woocommerce-sync.orders.completed_at');
delete_option('mailchimp-woocommerce-sync.orders.current_page');
delete_option('mailchimp-woocommerce-sync.products.completed_at');
delete_option('mailchimp-woocommerce-sync.products.current_page');
delete_option('mailchimp-woocommerce-sync.syncing');
delete_option('mailchimp-woocommerce-sync.started_at');
delete_option('mailchimp-woocommerce-sync.completed_at');
delete_option('mailchimp-woocommerce-validation.api.ping');
delete_option('mailchimp-woocommerce-cached-api-lists');
delete_option('mailchimp-woocommerce-cached-api-ping-check');
