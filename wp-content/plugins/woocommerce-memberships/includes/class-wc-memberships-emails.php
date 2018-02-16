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
 * Membership Emails class
 *
 * This class handles all email-related functionality in Memberships
 *
 * @since 1.0.0
 */
class WC_Memberships_Emails {


	/**
	 * Set up membership emails
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// add emails
		add_filter( 'woocommerce_email_classes', array( $this, 'get_email_classes' ) );

		// add links to members area if active for the purchased plan(s)
		add_action( 'woocommerce_email_order_meta', array( $this, 'maybe_render_thank_you_content' ), 5, 2 );

		// add triggers
		foreach ( $this->get_email_class_names() as $email ) {
			add_action( $email, array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
		}
	}


	/**
	 * Get Memberships emails classes
	 *
	 * @since 1.7.0
	 * @param bool $include_paths Whether to return an associative array with class paths
	 * @return array Indexed or Associative array
	 */
	public function get_email_class_names( $include_paths = false )  {

		$email_classes = array(
			'WC_Memberships_User_Membership_Note_Email'             => '/includes/emails/class-wc-memberships-user-membership-note-email.php',
			'WC_Memberships_User_Membership_Ending_Soon_Email'      => '/includes/emails/class-wc-memberships-user-membership-ending-soon-email.php',
			'WC_Memberships_User_Membership_Ended_Email'            => '/includes/emails/class-wc-memberships-user-membership-ended-email.php',
			'WC_Memberships_User_Membership_Renewal_Reminder_Email' => '/includes/emails/class-wc-memberships-user-membership-renewal-reminder-email.php',
		);

		return true !== $include_paths ? array_keys( $email_classes ) : $email_classes;
	}


	/**
	 * Add custom memberships emails to WC emails
	 *
	 * @since 1.7.0
	 * @param array $emails Optional, associative array of email objects
	 * @return \WC_Email[]|\WC_Memberships_Emails[] Associative array with email objects as values
	 */
	public function get_email_classes( $emails = array() ) {

		// applies when this method is called directly and not as WooCommerce hook callback
		if ( empty( $emails ) && ! class_exists( 'WC_Email' ) ) {
			WC()->mailer();
		}

		require_once( wc_memberships()->get_plugin_path() . '/includes/emails/abstract-wc-memberships-user-membership-email.php' );

		foreach ( $this->get_email_class_names( true ) as $class => $path ) {

			$file = wc_memberships()->get_plugin_path() . $path;

			if ( is_readable( $file ) ) {

				require_once( $file );

				if ( class_exists( $class ) ) {

					$emails[ $class ] = new $class();
				}
			}
		}

		return $emails;
	}


	/**
	 * Get a membership email default content
	 *
	 * @since 1.7.0
	 * @param string $email
	 * @return string May contain HTML
	 */
	public function get_email_default_content( $email ) {

		// ensure the email class is capitalized
		$email   = implode( '_', array_map( 'ucfirst', explode( '_', $email ) ) );
		$emails  = $this->get_email_classes();
		$content = '';

		if ( isset( $emails[ $email ] ) && method_exists( $emails[ $email ], 'get_default_body' ) ) {
			$content = $emails[ $email ]->get_default_body();
		}

		return $content;
	}


	/**
	 * Send a user membership email
	 *
	 * @since 1.7.0
	 * @param string $email The type of membership email to send
	 * @param mixed $args The param to pass to the email to be sent
	 */
	public function send_email( $email, $args ) {

		// ensure the email class is capitalized
		$email  = implode( '_', array_map( 'ucfirst', explode( '_', $email ) ) );
		$emails = $this->get_email_classes();

		if ( ! isset( $emails[ $email ] ) || ! method_exists( $emails[ $email ], 'trigger' ) ) {
			return;
		}

		$emails[ $email ]->trigger( $args );
	}


	/**
	 * Send expiring soon email for a user membership
	 *
	 * @see \WC_Memberships_Membership_Ending_Soon_Email
	 *
	 * @since 1.7.0
	 * @param int $user_membership_id Id of the expiring membership
	 */
	public function send_membership_ending_soon_email( $user_membership_id ) {
		$this->send_email( 'WC_Memberships_User_Membership_Ending_Soon_Email', $user_membership_id );
	}


	/**
	 * Send ended email for a user membership
	 *
	 * @see \WC_Memberships_Membership_Ended_Email
	 *
	 * @since 1.7.0
	 * @param int $user_membership_id Id of the expired membership
	 */
	public function send_membership_ended_email( $user_membership_id ) {
		$this->send_email( 'WC_Memberships_User_Membership_Ended_Email', $user_membership_id );
	}


	/**
	 * Send renewal reminder email for a user membership
	 *
	 * @see \WC_Memberships_Membership_Renewal_Reminder_Email
	 *
	 * @since 1.7.0
	 * @param int $user_membership_id Id of the expired membership
	 */
	public function send_membership_renewal_reminder_email( $user_membership_id ) {
		$this->send_email( 'WC_Memberships_User_Membership_Renewal_Reminder_Email', $user_membership_id );
	}


	/**
	 * Send a new user membership note notification for the member
	 *
	 * @since 1.7.0
	 * @param array $args {
	 *     Array of arguments passed to the email object:
	 *
	 *     @type int $user_membership_id The user membership the email is for
	 *     @type string $membership_note The contents of the note to send
	 * }
	 */
	public function send_new_membership_note_email( array $args ) {
		$this->send_email( 'WC_Memberships_User_Membership_Note_Email', $args );
	}


	/**
	 * Get merge tags help strings
	 *
	 * @since 1.7.0
	 * @return string[] Array of text strings
	 */
	public function get_emails_merge_tags_help() {

		$merge_tags_help = array(
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts your site name.', 'woocommerce-memberships' ),
				'<strong><code>{site_title}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the member display name.', 'woocommerce-memberships' ),
				'<strong><code>{member_name}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the member first name.', 'woocommerce-memberships' ),
				'<strong><code>{member_first_name}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the member last name.', 'woocommerce-memberships' ),
				'<strong><code>{member_last_name}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the member full name (or display name, if full name is not set).', 'woocommerce-memberships' ),
				'<strong><code>{member_full_name}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the membership plan name.', 'woocommerce-memberships' ),
				'<strong><code>{membership_plan}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the expiration date of the membership.', 'woocommerce-memberships' ),
				'<strong><code>{membership_expiration_date}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts the time difference between now and the date when the membership expires or has expired (e.g. "2 days", or "1 week", etc.).', 'woocommerce-memberships' ),
				'<strong><code>{membership_expiry_time_diff}</code></strong>' ),
			/* translators: Placeholder: %s - merge tag */
			sprintf( __( '%s inserts a plain membership renewal URL.', 'woocommerce-memberships' ),
				'<strong><code>{membership_renewal_url}</code></strong>' ),
		);

		return $merge_tags_help;
	}


	/**
	 * Renders a thank you message in order emails when a membership is purchased.
	 *
	 * @since 1.8.4
	 *
	 * @param \WC_Order $order the order for the given email
	 * @param bool $sent_to_admin true if the email is sent to admins
	 */
	public function maybe_render_thank_you_content( $order, $sent_to_admin ) {

		if ( ! $sent_to_admin ) {
			echo '<br />' . wp_kses_post( wc_memberships_get_order_thank_you_links( $order ) );
		}
	}


}
