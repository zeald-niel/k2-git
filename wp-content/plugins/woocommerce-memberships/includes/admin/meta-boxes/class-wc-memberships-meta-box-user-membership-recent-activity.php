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
 * User Membership Member Recent Activity Meta Box
 *
 * @since 1.0.0
 */
class WC_Memberships_Meta_Box_User_Membership_Recent_Activity extends WC_Memberships_Meta_Box {


	/**
	 * Constructor
	 *
	 * @since 1.7.0
	 */
	public function __construct() {

		$this->id      = 'wc-memberships-user-membership-recent-activity';
		$this->context = 'side';
		$this->screens = array( 'wc_user_membership' );

		parent::__construct();
	}


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Recent Activity', 'woocommerce-memberships' );
	}


	/**
	 * Display the member details meta box
	 *
	 * @param \WP_Post $post
	 * @since 1.0.0
	 */
	public function output( WP_Post $post ) {

		// prepare variables
		$this->post            = $post;
		$this->user_membership = $user_membership = wc_memberships_get_user_membership( $post );
		$this->order           = $order           = $user_membership->get_order();
		$this->product         = $product         = $user_membership->get_product();
		$this->user            = $user            = $this->get_membership_user( $user_membership );

		// bail out if no user ID
		if ( ! $user ) {
			return;
		}

		// get this user's memberships
		$the_user_memberships = wc_memberships_get_user_memberships( $user->ID );
		$user_memberships     = array();
		$notes                = null;

		if ( ! empty( $the_user_memberships ) ) {

			foreach ( $the_user_memberships as $user_membership ) {

				$user_memberships[ $user_membership->get_id() ] = $user_membership;
			}

			// get the membership notes as an associative array
			$notes = get_comments( array(
				'post__in' => array_keys( $user_memberships ),
				'approve'  => 'approve',
				'type'     => 'user_membership_note',
				'number'   => 5,
			) );
		}

		?>
		<ul class="wc-user-membership-recent-activity">
			<?php

			if ( ! empty( $notes ) ) :

				// load recent activity note view
				require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-membership-recent-activity-note.php' );

				foreach ( $notes as $note ) :

					// get notes for the current membership from array of notes
					$user_membership = $user_memberships[ $note->comment_post_ID ];
					$note_classes    = get_comment_meta( $note->comment_ID, 'notified', true ) ? array( 'notified', 'note' ) : array( 'note' );

					// output recent activity notes views
					$view = new WC_Memberships_Meta_Box_View_Membership_Recent_Activity_Note( $this );
					$view->output( array(
						'plan'         => $user_membership->get_plan(),
						'note'         => $note,
						'note_classes' => $note_classes,
					) );

				endforeach;

			else :

				?><li><?php esc_html_e( "It's been quiet here. No activity yet.", 'woocommerce-memberships' ); ?></li><?php

			endif;

			?>
		</ul>
		<?php
	}


}
