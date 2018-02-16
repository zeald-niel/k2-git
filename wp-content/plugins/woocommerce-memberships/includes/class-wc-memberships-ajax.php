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
 * AJAX class
 *
 * @since 1.0.0
 */
class WC_Memberships_AJAX {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// determine user membership start date by plan start date
		add_action( 'wp_ajax_wc_memberships_get_membership_plan_start_date', array( $this, 'get_membership_start_date' ) );
		// determine user membership expiration date by plan end date
		add_action( 'wp_ajax_wc_memberships_get_membership_plan_end_date',   array( $this, 'get_membership_expiration_date' ) );

		// user membership notes
		add_action( 'wp_ajax_wc_memberships_add_user_membership_note',    array( $this, 'add_user_membership_note' ) );
		add_action( 'wp_ajax_wc_memberships_delete_user_membership_note', array( $this, 'delete_user_membership_note' ) );

		// transfer a membership from a user to another
		add_action( 'wp_ajax_wc_memberships_transfer_user_membership', array( $this, 'transfer_user_membership' ) );

		// enhanced select
		add_action( 'wp_ajax_wc_memberships_json_search_posts', array( $this, 'json_search_posts' ) );
		add_action( 'wp_ajax_wc_memberships_json_search_terms', array( $this, 'json_search_terms' ) );

		// filter out grouped products from WC JSON search results
		add_filter( 'woocommerce_json_search_found_products', array( $this, 'filter_json_search_found_products' ) );
	}


	/**
	 * Get a user membership date based on plan details
	 *
	 * @since 1.7.0
	 * @param string $which_date Either 'start' or 'end' date
	 */
	private function get_membership_date( $which_date ) {

		check_ajax_referer( 'get-membership-date', 'security' );

		if ( isset( $_POST['plan'] ) ) {

			$plan_id = (int) $_POST['plan'];

			if ( $plan  = wc_memberships_get_membership_plan( $plan_id ) ) {

				$date = null;

				if ( 'start' === $which_date ) {

					$date = $plan->get_local_access_start_date();

				} elseif ( 'end' === $which_date ) {

					$start_date     = ! empty( $_POST['start_date'] ) ? strtotime( $_POST['start_date'] ) : current_time( 'timestamp', true );
					$start_date_utc = wc_memberships_adjust_date_by_timezone( $start_date );

					$date = $plan->get_expiration_date( $start_date_utc );
				}

				if ( null !== $date ) {

					// might send a date or empty string
					wp_send_json_success( $date );
				}
			}
		}

		die();
	}


	/**
	 * Determine user membership start date based on plan start date
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 */
	public function get_membership_start_date() {

		$this->get_membership_date( 'start' );
	}


	/**
	 * Get membership expiration date
	 *
	 * @internal
	 *
	 * @since 1.3.8
	 */
	public function get_membership_expiration_date() {

		$this->get_membership_date( 'end' );
	}


	/**
	 * Search for posts and echo json
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function json_search_posts() {

		check_ajax_referer( 'search-posts', 'security' );

		$term      = (string) wc_clean( stripslashes( SV_WC_Helper::get_request( 'term' ) ) );
		$post_type = (string) wc_clean( SV_WC_Helper::get_request( 'post_type' ) );

		if ( empty( $term ) || empty( $post_type ) ) {
			die();
		}

		if ( is_numeric( $term ) ) {

			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__in'       => array( 0, $term ),
				'fields'         => 'ids'
			);

		} else {

			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				's'              => $term,
				'fields'         => 'ids'
			);

		}

		$posts = get_posts( $args );

		$found_posts = array();

		if ( $posts ) {
			foreach ( $posts as $post ) {
				// TODO $post is an illegal array key type (\WP_Post object vs int, string) and should be avoided {FN 2016-04-26}
				$found_posts[ $post ] = get_the_title( $post );
			}
		}

		/**
		 * Filter posts found for JSON (AJAX) search
		 *
		 * @since 1.0.0
		 * @param array $found_posts Array of the found posts
		 */
		$found_posts = apply_filters( 'wc_memberships_json_search_found_posts', $found_posts );

		wp_send_json( $found_posts );

	}


	/**
	 * Search for taxonomy terms and echo json
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function json_search_terms() {

		check_ajax_referer( 'search-terms', 'security' );

		$term     = (string) wc_clean( stripslashes( SV_WC_Helper::get_request( 'term' ) ) );
		$taxonomy = (string) wc_clean( SV_WC_Helper::get_request( 'taxonomy' ) );

		if ( empty( $term ) || empty( $taxonomy ) ) {
			die();
		}

		if ( is_numeric( $term ) ) {

			$args = array(
				'hide_empty' => false,
				'include'    => array( 0, $term ),
			);

		} else {

			$args = array(
				'hide_empty' => false,
				'search'     => $term,
			);
		}

		$terms = get_terms( array( $taxonomy ), $args );

		$found_terms = array();

		if ( is_array( $terms ) ) {

			foreach ( $terms as $term ) {

				$found_terms[ $term->term_id ] = $term->name;
			}
		}

		/**
		 * Filter terms found for JSON (AJAX) search
		 *
		 * @since 1.0.0
		 * @param array $found_terms Array of the found terms
		 */
		$found_terms = apply_filters( 'wc_memberships_json_search_found_terms', $found_terms );

		wp_send_json( $found_terms );
	}


	/**
	 * Add user membership note
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_user_membership_note() {

		check_ajax_referer( 'add-user-membership-note', 'security' );

		$post_id   = (int) $_POST['post_id'];
		$note_text = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );
		$notify    = isset( $_POST['notify'] ) && $_POST['notify'] === 'true';

		if ( $post_id > 0 ) {

			// load views abstract
			require_once( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/abstract-wc-memberships-meta-box-view.php' );

			// load views
			require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-membership-note.php' );
			require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-membership-recent-activity-note.php' );

			$new_note_view            = new WC_Memberships_Meta_Box_View_Membership_Note();
			$new_recent_activity_view = new WC_Memberships_Meta_Box_View_Membership_Recent_Activity_Note();

			// get variables to pass to templates
			$user_membership = wc_memberships_get_user_membership( $post_id );
			$comment_id      = $user_membership->add_note( $note_text, $notify );
			$note            = get_comment( $comment_id );
			$note_classes    = get_comment_meta( $note->comment_ID, 'notified', true ) ? array( 'notified', 'note' ) : array( 'note' );

			$args = array(
				'note'         => $note,
				'note_classes' => $note_classes,
				'plan'         => $user_membership->get_plan(),
			);

			?>
			<div>
				<ul id="notes">
					<?php $new_note_view->output( $args ); ?>
				</ul>
				<ul id="recent-activity">
					<?php $new_recent_activity_view->output( $args ); ?>
				</ul>
			</div>
			<?php
		}

		exit;
	}


	/**
	 * Delete user membership note
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function delete_user_membership_note() {

		check_ajax_referer( 'delete-user-membership-note', 'security' );

		$note_id = (int) $_POST['note_id'];

		if ( $note_id > 0 ) {
			wp_delete_comment( $note_id );
		}

		exit;
	}


	/**
	 * Remove grouped products from json search results
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $products
	 * @return array $products
	 */
	public function filter_json_search_found_products( $products ) {

		// Remove grouped products
		if ( isset( $_REQUEST['screen'] ) && 'wc_membership_plan' === $_REQUEST['screen'] ) {
			foreach( $products as $id => $title ) {

				$product = wc_get_product( $id );

				if ( $product->is_type('grouped') ) {
					unset( $products[ $id ] );
				}
			}
		}

		return $products;
	}


	/**
	 * Transfer a membership from one user to another
	 *
	 * If successful also stores the previous users history
	 * in a membership post meta '_previous_owners'
	 *
	 * @internal
	 *
	 * @since 1.4.0
	 */
	public function transfer_user_membership() {

		if ( ! empty( $_POST['prev_user'] ) && ! empty( $_POST['new_user'] ) && ! empty( $_POST['membership'] ) ) {

			$prev_user          = (int) $_POST['prev_user'];
			$new_user           = (int) $_POST['new_user'];
			$user_membership_id = (int) $_POST['membership'];
			$user_membership    = wc_memberships_get_user_membership( $user_membership_id );

			if (    $user_membership
			     && $prev_user !== $new_user
			     && (int) $user_membership->get_user_id() === $prev_user ) {

				$transferred = $user_membership->transfer_ownership( $new_user );

				if ( true === $transferred ) {
					wp_send_json_success( $user_membership->get_previous_owners() );
				}
			}
		}

		die();
	}


}
