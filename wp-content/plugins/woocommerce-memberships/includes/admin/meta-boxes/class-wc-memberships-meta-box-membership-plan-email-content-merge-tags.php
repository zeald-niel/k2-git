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
 * @package   WC-Memberships/Admin/Meta-Boxes
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Membership Plan Email Content Merge Tags Meta Box
 *
 * @since 1.7.0
 */
class WC_Memberships_Meta_Box_Membership_Plan_Email_Content_Merge_Tags extends WC_Memberships_Meta_Box {


	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 * @see WC_Memberships_Meta_Box::__construct()
	 */
	public function __construct() {

		$this->id       = 'wc-memberships-membership-plan-email-content-merge-tags';
		$this->context  = 'side';
		$this->priority = 'low';
		$this->screens  = array( 'wc_membership_plan' );

		parent::__construct();
	}


	/**
	 * Get the meta box title
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Email Content Merge Tags', 'woocommerce-memberships' );
	}


	/**
	 * Display the membership data meta box
	 *
	 * @since 1.7.0
	 * @param \WP_Post $post
	 */
	public function output( WP_Post $post ) {

		$this->post            = $post;
		$this->membership_plan = $membership_plan = wc_memberships_get_membership_plan( $post );

		?>
		<div class="panel-wrap data">

			<small><?php esc_html_e( 'For any of the emails, or the renewal prompt content, you can use any of the following merge tags to add dynamic content to the membership plan emails:', 'woocommerce-memberships' ); ?><br><br></small>

			<ul>
				<?php

				$merge_tags_help = wc_memberships()->get_emails_instance()->get_emails_merge_tags_help();

				foreach ( $merge_tags_help as $merge_tag_help ) {

					echo '<li><small>' . $merge_tag_help . '</small></li>';

				}

				?>
			</ul>
		</div>
		<?php
	}


}
