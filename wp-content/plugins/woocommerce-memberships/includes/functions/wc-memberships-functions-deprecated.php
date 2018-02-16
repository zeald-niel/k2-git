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
 * @package   WC-Memberships/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;


/**
 * Get valid restriction message types.
 *
 * TODO remove this function by version 1.9.0 {FN 2017-03-09}
 *
 * @deprecated since 1.7.0
 *
 * @since 1.0.0
 * @return array
 */
function wc_memberships_get_valid_restriction_message_types() {
	_deprecated_function( 'wc_memberships_get_valid_restriction_message_types()', '1.7.0', 'wc_memberships()->get_frontend_instance()->get_valid_restriction_message_types()' );
	return wc_memberships()->get_frontend_instance()->get_valid_restriction_message_types();
}


/**
 * Encode a variable into JSON via wp_json_encode().
 *
 * TODO remove this function by version 2.0.0 {FN 2017-03-09}
 *
 * @deprecated since 1.8.0
 *
 * @since 1.6.0
 * @param mixed $data Variable (usually an array or object) to encode as JSON
 * @param int $options Optional. Options to be passed to json_encode(). Default 0
 * @param int $depth Optional. Maximum depth to walk through $data. Must be greater than 0. Default 512
 * @return bool|string The JSON encoded string, or false if it cannot be encoded
 */
function wc_memberships_json_encode( $data, $options = 0, $depth = 512 ) {
	 _deprecated_function( 'wc_memberships_json_encode', '1.8.0', 'wp_json_encode' );
	return function_exists( 'wp_json_encode' ) ? wp_json_encode( $data, $options, $depth ) : json_encode( $data, $options, $depth );
}


/**
 * Check if user is a member with either active or delayed status
 * of either a particular or any membership plan
 *
 * TODO remove this function by version 2.0.0 {FN 2017-03-09}
 *
 * @deprecated since 1.8.0
 *
 * @since 1.7.0
 * @param int|\WP_User $user_id Optional, defaults to current user
 * @param int|string $plan Membership Plan slug, post object or related post ID
 * @return bool
 */
function wc_memberships_is_user_non_inactive_member( $user_id = null, $plan = null ) {
	_deprecated_function( 'wc_memberships_is_user_non_inactive_member()', '1.8.0', 'wc_memberships_is_user_active_or_delayed_member()' );
	return wc_memberships_is_user_active_or_delayed_member( $user_id, $plan );
}
