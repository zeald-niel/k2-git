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

if ( ! class_exists( 'WC_Memberships_Membership_Note_Email' ) ) :

/**
 * Membership Note Order Email
 *
 * Membership note emails are sent when you add a membership note and notify the member
 *
 * @since 1.0.0
 */
class WC_Memberships_User_Membership_Note_Email extends WC_Memberships_User_Membership_Email {


	/** @private object Membership note */
	private $membership_note;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->id             = __CLASS__;

		$this->title          = __( 'Membership note', 'woocommerce-memberships' );
		$this->description    = __( 'Membership note emails are sent when you add a membership note and notify member.', 'woocommerce-memberships' );
		$this->subject        = __( 'Note added to your {site_title} membership', 'woocommerce-memberships');
		$this->heading        = __( 'A note has been added about your membership', 'woocommerce-memberships');

		$this->template_html  = 'emails/membership-note.php';
		$this->template_plain = 'emails/plain/membership-note.php';

		// call parent constructor
		parent::__construct();
	}


	/**
	 * Trigger the membership note email
	 *
	 * @since 1.0.0
	 * @param array $args Optional
	 */
	public function trigger( $args ) {

		// set the email object, recipient and parse merge tags
		if (    $args
		     && isset( $args['notify'], $args['user_membership_id'], $args['membership_note'] )
		     && ( $this->object = wc_memberships_get_user_membership( $args['user_membership_id'] ) ) ) {

			if ( $member = get_userdata( $this->object->get_user_id() ) ) {
				$this->recipient = $member->user_email;
			}

			$this->membership_note = $args['membership_note'];

			$this->parse_merge_tags();
		}

		// sanity checks
		if (    ! $this->object instanceof WC_Memberships_User_Membership
		     ||   empty( $this->membership_note )
		     || ! $this->is_enabled()
		     || ! $this->get_recipient() ) {

			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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

		// apply modified form fields
		$this->form_fields = $form_fields;
	}


	/**
	 * Get email HTML content
	 *
	 * @since 1.0.0
	 * @return string HTML content
	 */
	public function get_content_html() {

		ob_start();

		wc_get_template( $this->template_html, array(
			'user_membership' => $this->object,
			'email_heading'   => $this->get_heading(),
			'membership_note' => $this->membership_note,
			'sent_to_admin'   => false,
			'plain_text'      => false
		) );

		return ob_get_clean();
	}


	/**
	 * Get email plain text content
	 *
	 * @since 1.0.0
	 * @return string Plain text content
	 */
	public function get_content_plain() {

		ob_start();

		wc_get_template( $this->template_plain, array(
			'user_membership' => $this->object,
			'email_heading'   => $this->get_heading(),
			'membership_note' => $this->membership_note,
			'sent_to_admin'   => false,
			'plain_text'      => true
		) );

		return ob_get_clean();
	}


}

endif;
