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
 * @package   WC-Memberships/Frontend
 * @author    SkyVerge
 * @category  Frontend
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Class for handling main query and rewrite rules
 *
 * @since 1.6.0
 */
class WC_Memberships_Query {


	/** @var array Custom query vars used in Memberships endpoints. */
	private $query_vars = array();


	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		$this->init_endpoints();

		// Add new endpoints.
		add_action( 'init',       array( $this, 'add_endpoints' ), 1 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// User Membership Notes (comments on user memberships posts) handling.
		add_filter( 'comments_clauses',   array( $this, 'exclude_membership_notes_from_queries' ), 10, 1 );
		add_action( 'comment_feed_join',  array( $this, 'exclude_membership_notes_from_feed_join' ) );
		add_action( 'comment_feed_where', array( $this, 'exclude_membership_notes_from_feed_where' ) );
	}


	/**
	 * Init query vars used by Memberships.
	 *
	 * @see \WC_Query::init_query_vars()
	 * @see \WC_Query::add_endpoints()
	 *
	 * @since 1.7.4
	 */
	private function init_endpoints() {

		$this->query_vars = array(
			'members_area' => get_option( 'woocommerce_myaccount_members_area_endpoint', 'members-area' ),
		);
	}


	/**
	 * Add endpoints.
	 *
	 * @internal
	 *
	 * @see \WC_Query
	 * @see \WC_Memberships_Members_Area
	 *
	 * @since 1.6.0
	 */
	public function add_endpoints() {

		WC()->query->query_vars = array_merge( WC()->query->query_vars, $this->query_vars );
	}


	/**
	 * Add query vars.
	 *
	 * @internal
	 *
	 * @since 1.7.4
	 * @param string[] $query_vars WordPress query vars.
	 * @return string[]
	 */
	public function add_query_vars( $query_vars ) {

		foreach ( array_keys( $this->query_vars ) as $query_var ) {
			if ( ! in_array( $query_var, $query_vars, true ) ) {
				$query_vars[] = $query_var;
			}
		}

		return $query_vars;
	}


	/**
	 * Exclude user membership notes from queries and RSS
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 * @param array $clauses
	 * @return array
	 */
	public function exclude_membership_notes_from_queries( $clauses ) {
		global $wpdb, $typenow;

		if ( 'wc_user_membership' === $typenow && is_admin() && current_user_can( 'manage_woocommerce' ) ) {
			return $clauses; // Don't hide when viewing user memberships in admin
		}

		if ( ! $clauses['join'] ) {
			$clauses['join'] = '';
		}

		if ( ! strstr( $clauses['join'], "JOIN $wpdb->posts" ) ) {
			$clauses['join'] .= " LEFT JOIN $wpdb->posts ON comment_post_ID = $wpdb->posts.ID ";
		}

		if ( $clauses['where'] ) {
			$clauses['where'] .= ' AND ';
		}

		$clauses['where'] .= " $wpdb->posts.post_type <> 'wc_user_membership' ";

		return $clauses;
	}


	/**
	 * Exclude user membership notes from queries and RSS
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 * @param string $join
	 * @return string
	 */
	public function exclude_membership_notes_from_feed_join( $join ) {
		global $wpdb;

		if ( ! strstr( $join, $wpdb->posts ) ) {
			$join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";
		}

		return $join;
	}


	/**
	 * Exclude user membership notes from queries and RSS
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 * @param string $where
	 * @return string
	 */
	public function exclude_membership_notes_from_feed_where( $where ) {
		global $wpdb;

		if ( $where ) {
			$where .= ' AND ';
		}

		$where .= " $wpdb->posts.post_type <> 'wc_user_membership' ";

		return $where;
	}


}
