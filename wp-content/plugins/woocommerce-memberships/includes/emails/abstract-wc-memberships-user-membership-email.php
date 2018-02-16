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
 * User Membership notification emails abstract
 *
 * Handles common methods and hooks for emails
 * related to a user membership's events
 *
 * @since 1.7.0
 */
abstract class WC_Memberships_User_Membership_Email extends WC_Email {


	/** @var string The email content body */
	protected $body = '';


	/**
	 * Is customer email
	 *
	 * @since 1.7.0
	 * @return true
	 */
	public function is_customer_email() {
		return true;
	}


	/**
	 * Parse merge tags
	 *
	 * @since 1.7.0
	 */
	protected function parse_merge_tags() {

		if ( ! $this->object instanceof WC_Memberships_User_Membership ) {
			return;
		}

		$user_membership = $this->object;

		// get member data
		$member            = get_user_by( 'id', $user_membership->get_user_id() );
		$member_name       = ! empty( $member->display_name ) ? $member->display_name : '';
		$member_first_name = ! empty( $member->first_name )   ? $member->first_name   : $member_name;
		$member_last_name  = ! empty( $member->last_name )    ? $member->last_name    : '';
		$member_full_name  = $member_first_name && $member_last_name ? $member_first_name . ' ' . $member->last_name : $member_name;

		// membership expiry date
		$expiration_date_timestamp = $user_membership->get_local_end_date( 'timestamp' );

		$this->find['member_name']                        = '{member_name}';
		$this->find['member_first_name']                  = '{member_first_name}';
		$this->find['member_last_name']                   = '{member_last_name}';
		$this->find['member_full_name']                   = '{member_full_name}';
		$this->find['membership_plan']                    = '{membership_plan}';
		$this->find['membership_expiration_date']         = '{membership_expiration_date}';
		$this->find['membership_expiry_time_diff']        = '{membership_expiry_time_diff}';
		$this->find['membership_renewal_url']             = '{membership_renewal_url}';

		$this->replace['member_name']                     = $member_name;
		$this->replace['member_first_name']               = $member_first_name;
		$this->replace['member_last_name']                = $member_last_name;
		$this->replace['member_full_name']                = $member_full_name;
		$this->replace['membership_plan']                 = $user_membership->get_plan() ? $user_membership->get_plan()->get_name() : '';
		$this->replace['membership_expiration_date']      = date_i18n( wc_date_format(), $expiration_date_timestamp );
		$this->replace['membership_expiry_time_diff']     = human_time_diff( current_time( 'timestamp', true ), $expiration_date_timestamp );
		$this->replace['membership_renewal_url']          = esc_url( $user_membership->get_renew_membership_url() );
	}


	/**
	 * Get the email default body content
	 *
	 * This method should be overridden by child classes
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_default_body() {
		return '';
	}


	/**
	 * Get the email body content
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_body() {

		$email_id = strtolower( $this->id );

		/**
		 * Filter the membership email body
		 *
		 * @since 1.7.0
		 * @param string $body Email body content
		 * @param \WC_Memberships_User_Membership_Email Email instance
		 */
		$body = (string) apply_filters( "{$email_id}_email_body", $this->format_string( $this->body ), $this->object );

		if ( empty( $body ) || ! is_string( $body ) || '' === trim( $body ) ) {
			$body = $this->get_default_body();
		}

		// convert relative URLs to absolute
		// for links href and images src attributes
		$domain  = get_home_url();
		$replace = array();
		$replace['/href="(?!https?:\/\/)(?!data:)(?!#)/'] = 'href="' . $domain;
		$replace['/src="(?!https?:\/\/)(?!data:)(?!#)/']  = 'src="'  . $domain;

		$body = preg_replace( array_keys( $replace ), array_values( $replace ), $body );

		return $body;
	}


}
