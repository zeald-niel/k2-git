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
 * Membership Ended Email
 *
 * Membership ended emails are sent to plan members
 * once their membership has expired
 *
 * @since 1.7.0
 */
class WC_Memberships_User_Membership_Ended_Email extends WC_Memberships_User_Membership_Email {


	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		$this->id             = __CLASS__;

		$this->title          = __( 'Membership ended', 'woocommerce-memberships' );

		$description  =  __( 'Membership ended emails are sent to plan members in the moment their membership expires.', 'woocommerce-memberships' );
		/* translators: Placeholders: %1$s - Opening <a> HTML tag, %2$s - Closing </a> HTML tag */
		$description .= '<br>' . sprintf( __( 'You can edit the content of this email for %1$seach one of your plans%2$s individually.', 'woocommerce-memberships' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=wc_membership_plan' )  ) . '">', '</a>' );

		$this->description    = $description;
		$this->subject        = __( 'Your {site_title} membership has expired', 'woocommerce-memberships');
		$this->heading        = __( 'Renew your {membership_plan}', 'woocommerce-memberships');

		$this->template_html  = 'emails/membership-ended.php';
		$this->template_plain = 'emails/plain/membership-ended.php';

		// call parent constructor
		parent::__construct();
	}


	/**
	 * Trigger the membership ended email
	 *
	 * @since 1.7.0
	 * @param int $user_membership_id The id of the expired user membership
	 */
	public function trigger( $user_membership_id ) {

		// set the email object, recipient and parse merge tags
		if (    is_numeric( $user_membership_id )
		     && ( $this->object = wc_memberships_get_user_membership( $user_membership_id ) ) ) {

			if ( $member = get_userdata( $this->object->get_user_id() ) ) {
				$this->recipient = $member->user_email;
			}

			$this->body = $this->object instanceof WC_Memberships_User_Membership ? $this->object->get_plan()->get_email_content( $this->id ) : '';

			$this->parse_merge_tags();
		}

		// sanity checks
		if (    ! $this->object instanceof WC_Memberships_User_Membership
		     || ! $this->body
		     || ! $this->is_enabled()
		     || ! $this->get_recipient()
		     || ! $this->object->is_expired() ) {

			return;
		}

		// send the email
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		// unschedule any expiration event to prevent any further email to be sent
		if ( $scheduled_expiry = wp_next_scheduled( 'wc_memberships_user_membership_expiry', array( $user_membership_id )  ) ) {
			wp_unschedule_event( $scheduled_expiry, 'wc_memberships_user_membership_expiry', array( $user_membership_id ) );
		}
	}


	/**
	 * Email settings form fields
	 *
	 * Extends and overrides parent method
	 *
	 * @since 1.7.0
	 */
	public function init_form_fields() {

		// set the default fields from parent
		parent::init_form_fields();

		$form_fields = $this->form_fields;

		if ( isset( $form_fields['enabled'] ) ) {

			// set email disabled by default
			$form_fields['enabled']['default'] = 'no';
		}

		if ( isset( $form_fields['subject'] ) ) {

			// adds a subject merge tag hint in field description
			$form_fields['subject']['desc_tip']    = $form_fields['subject']['description'];
			$form_fields['subject']['description'] = sprintf( __( '%s inserts your site name.', 'woocommerce-memberships' ), '<strong><code>{site_name}</code></strong>' );
		}

		if ( isset( $form_fields['heading'] ) ) {

			// adds a heading merge tag hint in field description
			$form_fields['heading']['desc_tip']    = $form_fields['heading']['description'];
			$form_fields['heading']['description'] = sprintf( __( '%s inserts the membership plan name.', 'woocommerce-memberships' ), '<strong><code>{membership_plan}</code></strong>' );
		}

		// email body is set on a membership plan basis in plan settings
		if ( isset( $form_fields['body'] ) ) {
			unset( $form_fields ['body'] );
		}

		// set the updated fields
		$this->form_fields = $form_fields;
	}


	/**
	 * Get the default body content
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_default_body() {

		/* translators: Placeholders: the text within curly braces consists of email merge tags that shouldn't be changed in translation */
		$body_html = __( '<p>Hey {member_name},</p><p>Oh no, your access to {membership_plan} at {site_title} has now ended!</p><p>If you would like to continue to access members-only content and perks, please renew your membership.</p><p><a href="{membership_renewal_url}">Click here to renew your membership now</a>.</p><p>{site_title}</p>', 'woocommerce-memberships' );

		return wp_kses_post( $body_html );
	}


	/**
	 * Get email HTML content
	 *
	 * @since 1.7.0
	 * @return string HTML content
	 */
	public function get_content_html() {

		ob_start();

		wc_get_template( $this->template_html, array(
			'user_membership' => $this->object,
			'email_heading'   => $this->get_heading(),
			'email_body'      => $this->get_body(),
			'sent_to_admin'   => false,
			'plain_text'      => false
		) );

		return ob_get_clean();
	}


	/**
	 * Get email plain text content
	 *
	 * @since 1.7.0
	 * @return string Plain text content
	 */
	public function get_content_plain() {

		ob_start();

		wc_get_template( $this->template_plain, array(
			'user_membership' => $this->object,
			'email_heading'   => $this->get_heading(),
			'email_body'      => $this->get_body(),
			'sent_to_admin'   => false,
			'plain_text'      => true
		) );

		return ob_get_clean();
	}


}
