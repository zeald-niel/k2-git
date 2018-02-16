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
 * Creates a human readable list of an array
 *
 * @since 1.6.0
 * @param string[] $items array to list items of
 * @param string|void $conjunction optional. The word to join together the penultimate and last item. Defaults to 'or'
 * @return string e.g. "item1, item2, item3 or item4"
 */
function wc_memberships_list_items( $items, $conjunction = '' ) {

	if ( ! $conjunction ) {
		$conjunction = __( 'or', 'woocommerce-memberships' );
	}

	array_splice( $items, -2, 2, implode( ' ' . $conjunction . ' ', array_slice( $items, -2, 2 ) ) );

	return implode( ', ', $items );
}


/**
 * Get the label of a post type
 *
 * e.g. 'some_post-type' becomes 'Some Post Type Name'
 *
 * @since 1.6.2
 * @param \WP_Post $post
 * @return string
 */
function wc_memberships_get_content_type_name( $post ) {

	// sanity check
	if ( ! $post instanceof WP_Post || ! isset( $post->post_type ) ) {
		return '';
	}

	$post_type_object = get_post_type_object( $post->post_type );

	return ucwords( $post_type_object->labels->singular_name );
}


/**
 * Get metadata from an object that could be a post or a product.
 *
 * TODO improve this method for clarity, to account for WC 3.0 WC_Product::get_meta() and keep in mind to avoid infinite loops with wc_get_product() {FN 2017-04-05}
 *
 * @since 1.8.0
 * @param int|\WP_Post|\WC_Product $object A content object or ID.
 * @param string $meta Key of the meta data to retrieve.
 * @param bool $single whether to get the meta as a single item. Default: true.
 * @return mixed
 */
function wc_memberships_get_content_meta( $object, $meta, $single = true ) {

	$value = false;

	// get_post_type can accept an ID or WP_Post, but not WC_Product
	$_object   = $object instanceof WC_Product ? $object->get_id() : $object;
	$post_type = get_post_type( $_object );

	if ( $object instanceof WC_Product && in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {

		if ( 'product_variation' === $post_type ) {
			$product_id = SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $object->get_parent_id() : $object->id;
		} else {
			$product_id = $object->get_id();
		}

		if ( ! empty( $product_id ) ) {
			$value = get_post_meta( $product_id, $meta, $single );
		}

	} else {

		if ( is_numeric( $object ) ) {
			$post_id = (int) $object;
		} elseif ( isset( $object->ID ) ) {
			$post_id = (int) $object->ID;
		}

		if ( ! empty( $post_id ) ) {
			$value = get_post_meta( $post_id, $meta, $single );
		}
	}

	return $value;
}


/**
 * Set metadata on an object that could be a post or a product.
 *
 * TODO improve this method for clarity, to account for WC 3.0 WC_Product::update_meta_data() and keep in mind to avoid infinite loops with wc_get_product() {FN 2017-04-05}
 *
 * @since 1.8.0
 * @param int|\WP_Post|\WC_Product $object
 * @param string $meta_key
 * @param array|int|string $meta_value
 */
function wc_memberships_set_content_meta( $object, $meta_key, $meta_value ) {

	// get_post_type can accept an ID or WP_Post, but not WC_Product
	$_object   = $object instanceof WC_Product ? $object->get_id() : $object;
	$post_type = get_post_type( $_object );

	if ( $object instanceof WC_Product && in_array( $post_type, array( 'product', 'product_variation' ), true ) ) {

		if ( 'product_variation' === $post_type ) {
			$product_id = SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $object->get_parent_id() : $object->id;
		} else {
			$product_id = $object->get_id();
		}

		if ( ! empty( $product_id ) ) {
			update_post_meta( $product_id, $meta_key, $meta_value );
		}

	} else {

		if ( is_numeric( $object ) ) {
			$post_id = (int) $object;
		} elseif ( isset( $object->ID ) ) {
			$post_id = (int) $object->ID;
		}

		if ( ! empty( $post_id ) ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}
	}
}
