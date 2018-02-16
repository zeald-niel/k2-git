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

/**
 * WooCommerce Memberships CLI Command
 *
 * Base class that must be extended by any WooCommerce Memberships sub commands
 * It extends \WC_CLI_Command which in turn is a \WP_CLI_Command child
 *
 * @since 1.7.0
 */
class WC_Memberships_CLI_Command extends WC_CLI_Command {


	/**
	 * Get a Member from a user id, login name or email address
	 *
	 * @since 1.7.0
	 * @param int|string $customer A user id, email or login name
	 * @return null|false|\WP_User
	 */
	protected function get_member( $customer ) {

		$member = null;

		if ( is_numeric( $customer ) ) {
			$member = get_user_by( 'id', (int) $customer  );
		} elseif ( is_string( $customer ) ) {
			if ( is_email( $customer ) ) {
				$member = get_user_by( 'email', $customer );
			} else {
				$member = get_user_by( 'login', $customer );
			}
		}

		return $member;
	}


	/**
	 * Loosely parse a date for Memberships date use
	 *
	 * @since 1.7.0
	 * @param string $date A date in mysql string format
	 * @return false|string A datetime string or false if not a valid date
	 */
	protected function parse_membership_date( $date ) {
		return wc_memberships_parse_date( $date, 'mysql' );
	}


	/**
	 * Check if a Membership status is valid
	 *
	 * @since 1.7.0
	 * @param string $status Status to check
	 * @return bool
	 */
	protected function is_valid_membership_status( $status ) {

		$statuses = $this->get_membership_status_keys();
		$status   = $this->trim_membership_status_prefix( $status );

		return ! empty( $statuses ) ? in_array( $status, $statuses, true ) : false;
	}


	/**
	 * Get User Membership status keys
	 *
	 * @since 1.7.0
	 * @param bool $trim Trim prefix from the status keys
	 * @return array
	 */
	protected function get_membership_status_keys( $trim = true ) {

		$keys     = array();
		$statuses = wc_memberships_get_user_membership_statuses();

		if ( ! empty( $statuses ) ) {

			foreach ( array_keys( $statuses ) as $key ) {
				$keys[] = true === $trim ? $this->trim_membership_status_prefix( $key ) : $key;
			}
		}

		return $keys;
	}


	/**
	 * Removes the WooCommerce User Membership status prefix
	 *
	 * @since 1.7.0
	 * @param string $status
	 * @return string
	 */
	protected function trim_membership_status_prefix( $status ) {
		return SV_WC_Helper::str_starts_with( $status, 'wcm-' ) ? substr( $status, 4 ) : $status;
	}


	/**
	 * Add the WooCommerce User Membership status prefix
	 *
	 * @since 1.7.0
	 * @param $status
	 * @return string
	 */
	protected function add_membership_status_prefix( $status ) {
		return SV_WC_Helper::str_starts_with( $status, 'wcm-' ) ? $status : 'wcm-' . $status;
	}


	/**
	 * Get Meta Query arguments for date filtering
	 *
	 * @since 1.7.0
	 * @param string $meta_key
	 * @param string|array $dates
	 * @return array
	 */
	protected function get_date_range_meta_query_args( $meta_key, $dates ) {

		$args = array();

		if ( ! empty( $dates ) || ! empty( $meta_key ) ) {

			$dates   = ! is_array( $dates ) ? explode( ',', $dates ) : $dates;
			$count   = count( $dates );
			$compare = '>=';
			$errors  = 0;

			foreach ( $dates as $date ) {

				if ( false === $this->parse_membership_date( $date ) ) {

					WP_CLI::warning( sprintf( 'Date "%s" is not valid.', $date ) );
					$errors++;
				}
			}

			if ( 0 === $errors ) {

				if ( $count >= 3 ) {
					$compare = 'IN';
				} elseif ( 2 === $count ) {
					$compare = 'BETWEEN';
				}

				$args = array(
					'key'     => $meta_key,
					'value'   => 1 === $count ? $dates[0] : $dates,
					'compare' => $compare,
					'type'    => 'DATETIME',
				);
			}
		}

		return $args;
	}


}
