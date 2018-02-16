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
 * User Memberships class
 *
 * This class handles general user memberships related functionality
 *
 * @since 1.0.0
 */
class WC_Memberships_User_Memberships {


	/** @var string helper pending note for a user membership */
	private $membership_status_transition_note;

	/** @var array memoization helper is user member check */
	private $is_user_member = array();


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		require_once( wc_memberships()->get_plugin_path() . '/includes/class-wc-memberships-user-membership.php' );

		// post lifecycle and statuses events handling
		add_filter( 'wp_insert_post_data',               array( $this, 'adjust_user_membership_post_data' ) );
		add_action( 'transition_post_status',            array( $this, 'transition_post_status' ), 10, 3 );
		add_action( 'save_post',                         array( $this, 'save_user_membership' ), 10, 3 );
		add_action( 'delete_user',                       array( $this, 'delete_user_memberships' ) );
		add_action( 'delete_post',                       array( $this, 'delete_related_data' ) );
		add_action( 'trashed_post',                      array( $this, 'handle_order_trashed' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'handle_order_refunded' ) );

		// expiration events handling
		add_action( 'wc_memberships_user_membership_expiry',           array( $this, 'trigger_expiration_events' ), 10, 1 );
		add_action( 'wc_memberships_user_membership_expiring_soon',    array( $this, 'trigger_expiration_events' ), 10, 1 );
		add_action( 'wc_memberships_user_membership_renewal_reminder', array( $this, 'trigger_expiration_events' ), 10, 1 );
	}


	/**
	 * Get all user memberships
	 *
	 * @since 1.0.0
	 * @param int $user_id Optional. Defaults to current user.
	 * @param array $args
	 * @return \WC_Memberships_User_Membership[]|null array of user memberships
	 */
	public function get_user_memberships( $user_id = null, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'status' => 'any',
		) );

		// add the wcm- prefix for the status if it's not "any"
		foreach ( (array) $args['status'] as $index => $status ) {

			if ( 'any' !== $status ) {
				$args['status'][ $index ] = 'wcm-' . $status;
			}
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return null;
		}

		$posts_args = array(
			'author'      => $user_id,
			'post_type'   => 'wc_user_membership',
			'post_status' => $args['status'],
			'nopaging'    => true,
		);

		$posts            = get_posts( $posts_args );
		$user_memberships = array();

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $post ) {
				$user_memberships[] = wc_memberships_get_user_membership( $post );
			}
		}

		return ! empty( $user_memberships ) ? $user_memberships : null;
	}


	/**
	 * Get a User Membership
	 *
	 * Supports getting user membership by membership id, post object
	 * or a combination of the user id and membership plan id/slug/post object
	 *
	 * If no $id is provided, defaults to getting the membership for the current user
	 *
	 * @since 1.0.0
	 * @param int|\WC_Memberships_User_Membership $id Optional: post object or post ID of the User Membership, or user id
	 * @param int|string|\WC_Memberships_Membership_Plan Optional : Membership Plan slug, post object or related post id
	 * @return false|\WC_Memberships_User_Membership
	 */
	public function get_user_membership( $id = null, $plan = null ) {

		// if a plan is provided, try to find the User Membership using user ID + plan ID
		if ( $plan ) {

			$user_id         = ! empty( $id ) ? (int) $id : get_current_user_id();
			$membership_plan = wc_memberships_get_membership_plan( $plan );

			// bail out if no user ID or membership plan
			if ( ! $membership_plan || ! $user_id || 0 === $user_id ) {
				return false;
			}

			$args = array(
				'author'      => $user_id,
				'post_type'   => 'wc_user_membership',
				'post_parent' => $membership_plan->get_id(),
				'post_status' => 'any',
			);

			$user_memberships = get_posts( $args );
			$post             = ! empty( $user_memberships ) ? $user_memberships[0] : null;

			// otherwise, try to get user membership directly
		} else {

			$post = $id;

			if ( false === $post ) {
				// try getting from global
				$post = $GLOBALS['post'];
			} elseif ( is_numeric( $post ) ) {
				// try getting by ID
				$post = get_post( $post );
			} elseif ( $post instanceof WC_Memberships_User_Membership ) {
				// try getting from a \WC_Memberships_User_Membership object instance
				$post = get_post( $post->get_id() );
			} elseif ( ! $post instanceof WP_Post ) {
				$post = null;
			}
		}

		// if no acceptable post is found, bail out
		if ( ! $post || 'wc_user_membership' !== get_post_type( $post ) ) {
			return false;
		}

		$user_membership = new WC_Memberships_User_Membership( $post );

		/**
		 * Get a user membership
		 *
		 * @since 1.7.0
		 * @param \WC_Memberships_User_Membership $user_membership The user membership
		 * @param \WP_Post $post The user membership post object
		 * @param int $id The user membership id or the user id if $plan is not null
		 * @param null|\WC_Memberships_Membership_Plan $plan Optional, the membership plan object
		 */
		return apply_filters( 'wc_memberships_user_membership', $user_membership, $post, $id, $plan );
	}


	/**
	 * Get user membership by order ID
	 *
	 * @since 1.0.1
	 * @param int|\WC_Order $order Order object or ID
	 * @return null|\WC_Memberships_User_Membership[]
	 */
	public function get_user_membership_by_order_id( $order ) {

		if ( is_numeric( $order ) ) {
			$order_id = (int) $order;
		} elseif ( $order instanceof WC_Order || $order instanceof WC_Order_Refund ) {
			$order_id = (int) SV_WC_Order_Compatibility::get_prop( $order, 'id' );
		} else {
			return null;
		}

		$user_memberships_query = new WP_Query( array(
			'fields'      => 'ids',
			'nopaging'    => true,
			'post_type'   => 'wc_user_membership',
			'post_status' => 'any',
			'meta_key'    => '_order_id',
			'meta_value'  => $order_id,
		) );

		if ( empty( $user_memberships_query ) ) {
			return null;
		}

		$user_memberships_posts = $user_memberships_query->get_posts();
		$user_memberships       = array();

		foreach ( $user_memberships_posts as $post_id ) {

			if ( $user_membership = $this->get_user_membership( $post_id ) ) {

				$user_memberships[] = $user_membership;
			}
		}

		return $user_memberships;
	}


	/**
	 * Check if user is a member of one particular or any membership plan
	 *
	 * @since 1.0.0
	 * @param int|\WP_User $user_id Optional, defaults to current user
	 * @param int|string|\WC_Memberships_Membership_Plan $membership_plan Optional: membership plan id, object or slug;
	 *                                                                    leave empty to check if the user is a member of any plan
	 * @param bool|string $check_if_active Optional additional check to see if the member has currently active access (pass param as true or 'active') or delayed access (use 'delayed')
	 * @param bool $cache Whether to use cached results (default true)
	 * @return bool
	 */
	public function is_user_member( $user_id = null, $membership_plan = null, $check_if_active = false, $cache = true ) {

		$is_member = false;

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		} elseif ( isset( $user_id->ID ) ) {
			$user_id = $user_id->ID;
		}

		// sanity check (invalid user or not logged in)
		if ( ! is_numeric( $user_id ) || 0 === $user_id ) {
			return $is_member;
		} else {
			$user_id = (int) $user_id;
		}

		$plan_id = null;

		if ( is_numeric( $membership_plan ) ) {
			$plan_id = $membership_plan;
		} elseif ( $membership_plan instanceof WC_Memberships_Membership_Plan ) {
			$plan_id = $membership_plan->get_id();
		}

		$member_status_cache_key = null;

		// set status check cache key
		if ( true === $check_if_active ) {
			$member_status_cache_key = 'is_active';
		} elseif ( ! $check_if_active ) {
			$member_status_cache_key = 'is_member';
		} elseif ( is_string( $check_if_active ) ) {
			$member_status_cache_key = "is_{$check_if_active}";
		}

		// use memoization to fetch a value faster, if user member status is cached
		if (    false !== $cache
		     && $member_status_cache_key
		     && is_numeric( $plan_id )
		     && isset( $this->is_user_member[ $user_id ][ $plan_id ][ $member_status_cache_key ] ) ) {

			$is_member = $this->is_user_member[ $user_id ][ $plan_id ][ $member_status_cache_key ];

		} else {

			// note 'true' is for legacy purposes here (check for active)
			$must_be_active_member = in_array( $check_if_active, array( 'active', 'delayed', true ), true );

			if ( null === $membership_plan ) {

				// check if the user is a member of at least one plan
				$plans = wc_memberships_get_membership_plans();

				if ( ! empty( $plans ) ) {

					foreach ( $plans as $plan ) {

						if ( $user_membership = $this->get_user_membership( $user_id, $plan ) ) {

							// if not checking for active memberships
							// $must_be_active_member === false, then $is_member === true
							$is_member = ! $must_be_active_member;

							if ( true === $must_be_active_member ) {

								if ( $is_member = ( $user_membership->is_active() && $user_membership->is_in_active_period() ) ) {

									// return true if we are checking for currently active
									break;

								} elseif ( 'delayed' === $check_if_active && ( $is_member = $user_membership->is_delayed() ) ) {

									// return true if we are checking if start is delayed
									break;
								}

							} else {

								// just returns true if user is a member
								break;
							}
						}
					}
				}

			} else {

				// check if the user is a member of a specific plan
				$user_membership = $this->get_user_membership( $user_id, $membership_plan );
				$is_member       = (bool) $user_membership;

				if ( $is_member && $must_be_active_member ) {

					$is_member = $user_membership->is_active() && $user_membership->is_in_active_period();

					// maybe we want to check if this is a delayed membership due to future access date
					if ( 'delayed' === $check_if_active ) {

						$is_member = $user_membership->is_delayed();
					}
				}
			}

			$this->is_user_member[ $user_id ][ $plan_id ][ $member_status_cache_key ] = $is_member;
		}

		return $is_member;
	}


	/**
	 * Check if user is a member with active access of one particular or any membership plan
	 *
	 * @since 1.0.0
	 * @param int|\WP_User $user_id Optional, defaults to current user
	 * @param int|string $membership_plan Optional: membership plan ID or slug;
	 *                                     leave empty to check if the user is a member of any plan
	 * @param bool $cache Whether to use cache results (default true)
	 * @return bool
	 */
	public function is_user_active_member( $user_id = null, $membership_plan = null, $cache = true ) {
		return $this->is_user_member( $user_id, $membership_plan, 'active', $cache );
	}


	/**
	 * Check if user is an active member of one particular or any membership plan
	 * but has not gained access yet because the start date of the plan is in the future
	 *
	 * @since 1.7.0
	 * @param int|\WP_User $user_id Optional, defaults to current user
	 * @param int|string $membership_plan Optional: membership plan ID or slug;
	 *                                     leave empty to check if the user is a member of any plan
	 * @param bool $cache Whether to use cache results (default true)
	 * @return bool
	 */
	public function is_user_delayed_member( $user_id = null, $membership_plan = null, $cache = true ) {
		return $this->is_user_member( $user_id, $membership_plan, 'delayed', $cache );
	}


	/**
	 * Check if user is either a member with active or delayed access
	 * of one particular or any membership plan
	 *
	 * Note: this isn't the equivalent of doing `! wc_memberships_is_user_active_member()`
	 * @see \WC_Memberships_User_Memberships::is_user_active_member()
	 * @see \WC_Memberships_User_Memberships::is_user_delayed_member()
	 *
	 * @since 1.7.0
	 * @param int|\WP_User $user_id Optional, defaults to current user
	 * @param int|string $membership_plan Optional: membership plan ID or slug;
	 *                                     leave empty to check if the user is a member of any plan
	 * @param bool $cache Whether to use cache results (default true)
	 * @return bool
	 */
	public function is_user_active_or_delayed_member( $user_id = null, $membership_plan = null, $cache = true ) {
		return    $this->is_user_active_member( $user_id, $membership_plan, $cache )
		       || $this->is_user_delayed_member( $user_id, $membership_plan, $cache );
	}


	/**
	 * Get the earliest date a user has been a member of any plan
	 *
	 * @since 1.7.0
	 * @param int $user_id The WP_User id
	 * @param string $format The format the date should be, either 'timestamp', 'mysql' or php date format (default timestamp)
	 * @return int|string|null Timestamp, date string or null if error or user isn't a member
	 */
	public function get_user_member_since_date( $user_id, $format = 'timestamp' ) {

		if ( ! is_numeric( $user_id ) ) {
			return null;
		}

		$user_memberships = $this->get_user_memberships( $user_id );
		$member_since     = null;

		if ( ! empty( $user_memberships ) ) {

			foreach ( $user_memberships as $user_membership ) {

				if ( ! $member_since || $member_since > $user_membership->get_start_date( 'timestamp' ) ) {

					$member_since = $user_membership->get_start_date( 'timestamp' );
				}
			}
		}

		return $member_since ? wc_memberships_format_date( $member_since, $format ) : null;
	}


	/**
	 * Get the earliest local date a user has been a member of any plan
	 *
	 * @since 1.7.0
	 * @param int $user_id The WP_User id
	 * @param string $format The format the date should be, either 'timestamp', 'mysql' or php date format (default timestamp)
	 * @return int|string|null Timestamp, date string or null if error or user isn't a member
	 */
	public function get_user_member_since_local_date( $user_id, $format = 'timestamp' ) {

		// get the date timestamp
		$date = $this->get_user_member_since_date( $user_id, $format );

		// adjust the date to the site's local timezone
		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Get all user membership statuses
	 *
	 * @since 1.0.0
	 * @return array associative array of statuses
	 */
	public function get_user_membership_statuses() {

		$statuses = array(

			'wcm-active'        => array(
				'label'       => _x( 'Active', 'Membership Status', 'woocommerce-memberships' ),
				/* translators: Active Membership(s) */
				'label_count' => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

			'wcm-delayed'       => array(
				'label'       => _x( 'Delayed', 'Membership status', 'woocommerce-memberships' ),
				/* translators: Delayed Membership(s) */
				'label_count' => _n_noop( 'Delayed <span class="count">(%s)</span>', 'Delayed <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

			'wcm-complimentary' => array(
				'label'       => _x( 'Complimentary', 'Membership Status', 'woocommerce-memberships' ),
				/* translators: Complimentary Membership(s) */
				'label_count' => _n_noop( 'Complimentary <span class="count">(%s)</span>', 'Complimentary <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

			'wcm-pending'       => array(
				'label'       => _x( 'Pending Cancellation', 'Membership Status', 'woocommerce-memberships' ),
				/* translators: Membership(s) Pending Cancellation */
				'label_count' => _n_noop( 'Pending Cancellation <span class="count">(%s)</span>', 'Pending Cancellation <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

			'wcm-paused'        => array(
				'label'       => _x( 'Paused', 'Membership Status', 'woocommerce-memberships' ),
				/* translators: Paused Membership(s) */
				'label_count' => _n_noop( 'Paused <span class="count">(%s)</span>', 'Paused <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

			'wcm-expired'       => array(
				'label'       => _x( 'Expired', 'Membership Status', 'woocommerce-memberships' ),
				/* translators: Expired Membership(s) */
				'label_count' => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

			'wcm-cancelled'     => array(
				'label'       => _x( 'Cancelled', 'Membership Status', 'woocommerce-memberships' ),
				/* translators: Cancelled Membership(s) */
				'label_count' => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'woocommerce-memberships' ),
			),

		);

		/**
		 * Filter user membership statuses
		 *
		 * @since 1.0.0
		 * @param array $statuses Associative array of statuses and labels
		 * @return array
		 */
		return apply_filters( 'wc_memberships_user_membership_statuses', $statuses );
	}


	/**
	 * Get valid membership statuses to be considered as active
	 *
	 * @since 1.7.0
	 * @return array
	 */
	public function get_active_access_membership_statuses() {

		/**
		 * Filter user membership statuses that have access
		 *
		 * @since 1.7.0
		 * @param string[] $statuses Array of statuses
		 */
		return (array) apply_filters( 'wc_memberships_active_access_membership_statuses', array(
			'active',
			'complimentary',
			'pending',
		) );
	}


	/**
	 * Get valid statuses for renewing a user membership on frontend
	 *
	 * @since 1.7.0
	 * @return string[] Array of statuses
	 */
	public function get_valid_user_membership_statuses_for_renewal() {

		/**
		 * Filter the valid statuses for renewing a user membership on frontend
		 *
		 * @since 1.0.0
		 * @param array $statuses Array of statuses valid for renewal
		 */
		return (array) apply_filters( 'wc_memberships_valid_membership_statuses_for_renewal', array(
			'active',
			'cancelled',
			'expired',
			'paused',
		) );
	}


	/**
	 * Get valid statuses for cancelling a user membership from frontend
	 *
	 * @since 1.7.0
	 * @return string[] Array of statuses
	 */
	public function get_valid_user_membership_statuses_for_cancellation() {

		/**
		 * Filter the valid statuses for cancelling a user membership on frontend
		 *
		 * @since 1.0.0
		 * @param array $statuses Array of statuses valid for cancellation
		 */
		return (array) apply_filters( 'wc_memberships_valid_membership_statuses_for_cancel', array(
			'active',
			'delayed',
		) );
	}


	/**
	 * Adjust new user membership post data
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 * @param array $data Original post data
	 * @return array $data Modified post data
	 */
	public function adjust_user_membership_post_data( $data ) {

		if ( 'wc_user_membership' === $data['post_type'] ) {

			// Password-protected user membership posts
			if ( ! $data['post_password'] ) {
				$data['post_password'] = uniqid( 'um_', false );
			}

			// Make sure the passed in user ID is used as post author
			if ( isset( $_GET['user'] ) && 'auto-draft' === $data['post_status'] ) {
				$data['post_author'] = absint( $_GET['user'] );
			}
		}

		return $data;
	}


	/**
	 * Handle post status transitions for user memberships
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param string $new_status New status slug
	 * @param string $old_status Old status slug
	 * @param \WP_Post $post Related WP_Post object
	 */
	public function transition_post_status( $new_status, $old_status, WP_Post $post ) {

		if ( 'wc_user_membership' !== $post->post_type || $new_status === $old_status ) {
			return;
		}

		// skip for new posts and auto drafts
		if ( 'new' === $old_status || 'auto-draft' === $old_status ) {
			return;
		}

		$user_membership = $this->get_user_membership( $post );

		$old_status = str_replace( 'wcm-', '', $old_status );
		$new_status = str_replace( 'wcm-', '', $new_status );

		/* translators: Placeholders: Membership status changed from status A (%1$s) to status B (%2$s) */
		$status_note   = sprintf( __( 'Membership status changed from %1$s to %2$s.', 'woocommerce-memberships' ), wc_memberships_get_user_membership_status_name( $old_status ), wc_memberships_get_user_membership_status_name( $new_status ) );
		$optional_note = $this->get_membership_status_transition_note();

		// prepend optional note to status note, if provided
		$note = $optional_note ? $optional_note . ' ' . $status_note : $status_note;

		$user_membership->add_note( $note );

		switch ( $new_status ) {

			case 'cancelled':

				$user_membership->set_cancelled_date( current_time( 'mysql', true ) );
				$user_membership->unschedule_expiration_events();

			break;

			case 'expired':

				// Loose check to see if this was a manually triggered expiration.
				$end_date = $user_membership->get_end_date( 'timestamp' );

				// If manually expired, set expire date to now and reschedule
				// expiration events (also when previously cancelled).
				if ( $end_date > 0 && current_time( 'timestamp', true ) < $end_date ) {
					$user_membership->set_end_date( current_time( 'mysql', true ) );
				} elseif ( 'cancelled' === $old_status ) {
					$user_membership->schedule_expiration_events( $user_membership->get_end_date( 'timestamp' ) );
				}

			break;

			case 'paused':

				$now = current_time( 'mysql', true );

				$user_membership->set_paused_date( $now );

				// Delayed memberships should disregard intervals at all.
				if ( 'delayed' !== $old_status ) {
					$user_membership->set_paused_interval( 'start', strtotime( $now ) );
				}

				// Restore expiration events if the Membership was cancelled.
				if ( 'cancelled' === $old_status ) {
					$user_membership->schedule_expiration_events( $user_membership->get_end_date( 'timestamp' ) );
				}

			break;

			case 'active':

				if ( 'delayed' === $old_status ) {

					// For sanity, delayed membership which are now active
					// shouldn't ever had any of these set.
					$user_membership->delete_paused_date();
					$user_membership->delete_paused_intervals();

				} elseif ( $user_membership->get_paused_date() ) {

					// Save the new membership end date and remove the paused date:
					// this means that if the membership was paused, or, for example,
					// paused and then cancelled, and then re-activated, the time paused
					// will be added to the expiry date, so that the end date is pushed back.
					$user_membership->set_end_date( $user_membership->get_end_date() );
					$user_membership->set_paused_interval( 'end', current_time( 'timestamp', true ) );
					$user_membership->delete_paused_date();

				} elseif ( 'cancelled' === $old_status ) {

					// Restore expiration events if previously cancelled.
					$user_membership->schedule_expiration_events( $user_membership->get_end_date( 'timestamp' ) );
				}

			break;

			default :

				// Restore expiration events if the Membership was cancelled.
				if ( 'cancelled' === $old_status ) {
					$user_membership->schedule_expiration_events( $user_membership->get_end_date( 'timestamp' ) );
				}

			break;

		}

		/**
		 * Fires when user membership status is updated
		 *
		 * @since 1.0.0
		 * @param \WC_Memberships_User_Membership $user_membership The membership
		 * @param string $old_status Old status, without the wcm- prefix
		 * @param string $new_status New status, without the wcm- prefix
		 */
		do_action( 'wc_memberships_user_membership_status_changed', $user_membership, $old_status, $new_status );
	}


	/**
	 * Set membership status transition note
	 *
	 * Set a note to be saved along with the general "status changed from %s to %s" note
	 * when the status of a user membership changes.
	 *
	 * @since 1.0.0
	 * @param string $note Note
	 */
	public function set_membership_status_transition_note( $note ) {
		$this->membership_status_transition_note = $note;
	}


	/**
	 * Get membership status transition note
	 *
	 * Gets the note and resets it, so it does not interfere with
	 * any following status transitions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @return string $note Note
	 */
	public function get_membership_status_transition_note() {

		$note = $this->membership_status_transition_note;

		$this->membership_status_transition_note = null;

		return $note;
	}


	/**
	 * Trigger user membership expiration events
	 *
	 * @since 1.7.0
	 * @param array $args Expiration event args
	 * @param string $force_event Event to trigger, only when calling the method directly and not as hook callback
	 */
	public function trigger_expiration_events( $args, $force_event = '' ) {

		$user_membership_id = isset( $args['user_membership_id'] ) ? (int) $args['user_membership_id'] : $args;
		$current_filter     = ! empty( $force_event ) ? $force_event : current_filter();

		if ( ! is_numeric( $user_membership_id ) || empty( $current_filter ) ) {
			return;
		}

		if ( $emails_instance = wc_memberships()->get_emails_instance() ) {

			if ( 'wc_memberships_user_membership_expiring_soon' === $current_filter ) {

				$emails_instance->send_membership_ending_soon_email( $user_membership_id );

			} elseif ( 'wc_memberships_user_membership_expiry' === $current_filter ) {

				$user_membership = $this->get_user_membership( $user_membership_id );

				if ( $user_membership ) {
					$user_membership->expire_membership();
				}

				$emails_instance->send_membership_ended_email( $user_membership_id );

			} elseif ( 'wc_memberships_user_membership_renewal_reminder' === $current_filter ) {

				$emails_instance->send_membership_renewal_reminder_email( $user_membership_id );
			}
		}
	}


	/**
	 * Callback for save_post when a user membership is created or updated
	 *
	 * Triggers `wc_memberships_user_membership_saved` action
	 * @see \wc_memberships_create_user_membership()
	 *
	 * @internal
	 *
	 * @since 1.3.8
	 * @param int $post_id WP_Post id
	 * @param WP_Post $post Post object
	 * @param bool $update Whether we are updating or creating a new post
	 */
	public function save_user_membership( $post_id, $post, $update ) {

		if ( 'wc_user_membership' === get_post_type( $post ) && ( $user_membership = wc_memberships_get_user_membership( $post_id ) ) ) {

			/**
			 * Fires after a user has been granted membership access.
			 *
			 * This hook is similar to `wc_memberships_user_membership_created`
			 * but will also fire when a membership is manually created in admin
			 * or upon an import or via command line interface, etc.
			 *
			 * @see \wc_memberships_create_user_membership()
			 *
			 * @since 1.3.8
			 * @param \WC_Memberships_Membership_Plan $membership_plan The plan that user was granted access to.
			 * @param array $args
			 * @param array $args {
			 *     Array of User Membership arguments:
			 *
			 *     @type int $user_id The user id the membership is assigned to.
			 *     @type int $user_membership_id The user membership id being saved.
			 *     @type bool $is_update Whether this is a post update or a newly created membership.
			 * }
			 */
			do_action( 'wc_memberships_user_membership_saved', $user_membership->get_plan(), array(
				'user_id'            => $user_membership->get_user_id(),
				'user_membership_id' => $user_membership->get_id(),
				'is_update'          => $update,
			) );
		}
	}


	/**
	 * Delete user memberships if a user is deleted
	 *
	 * @since 1.0.0
	 * @param int $user_id Id of a user being deleted
	 */
	public function delete_user_memberships( $user_id ) {

		$user_memberships = $this->get_user_memberships( $user_id );

		if ( ! empty( $user_memberships ) ) {

			foreach ( $user_memberships as $membership ) {

				wp_delete_post( $membership->get_id() );
			}
		}
	}


	/**
	 * Delete related data when a user membership is deleted
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 * @param int $post_id Post object id of the user membership being deleted
	 */
	public function delete_related_data( $post_id ) {

		// bail out if the post being deleted is not a user membership
		if ( 'wc_user_membership' !== get_post_type( $post_id ) ) {
			return;
		}

		// delete scheduled events
		if ( $user_membership = wc_memberships_get_user_membership( $post_id ) ) {
			$user_membership->unschedule_expiration_events();
		}
	}


	/**
	 * Cancel user membership when the associated order is trashed
	 *
	 * @internal
	 *
	 * @since 1.0.1
	 * @param int $order_id \WC_Order post id being trashed
	 */
	public function handle_order_trashed( $order_id ) {
		$this->handle_order_cancellation( $order_id, __( 'Membership cancelled because the associated order was trashed.', 'woocommerce-memberships' ) );
	}


	/**
	 * Cancel user membership when the associated order is refunded
	 *
	 * @internal
	 *
	 * @since 1.0.1
	 * @param int $order_id \WC_Order id being refunded
	 */
	public function handle_order_refunded( $order_id ) {
		$this->handle_order_cancellation( $order_id, __( 'Membership cancelled because the associated order was refunded.', 'woocommerce-memberships' ) );
	}


	/**
	 * Handle a cancellation due to an order event
	 *
	 * @since 1.6.0
	 * @param int $order_id \WC_Order id associated to the User Membership
	 * @param string $note Cancellation message
	 */
	private function handle_order_cancellation( $order_id, $note ) {

		if ( 'shop_order' !== get_post_type( $order_id ) ) {
			return;
		}

		if ( $user_memberships = $this->get_user_membership_by_order_id( $order_id ) ) {

			foreach ( $user_memberships as $user_membership ) {
				$user_membership->cancel_membership( $note );
			}
		}
	}


	/**
	 * Backwards compatibility handler for deprecated methods
	 *
	 * TODO remove these by version 2.0.0 {FN 2017-01-13}
	 *
	 * @since 1.7.0
	 * @param string $method Method called
	 * @param void|string|array|mixed $args Optional argument(s)
	 * @return null|void|mixed
	 */
	public function __call( $method, $args ) {

		$called = "wc_memberships()->get_user_memberships()->{$method}()";

		switch ( $method ) {

			/** @deprecated since 1.6.0 */
			case 'user_membership_post_date' :
				_deprecated_function( $called, '1.6.0', 'wc_memberships()->get_user_memberships_instance()->user_membership_post_data()' );
				return $this->adjust_user_membership_post_data( isset( $args[0] ) ? $args[0] : $args );

			/** @deprecated since 1.7.0 */
			case 'expire_user_membership' :

				_deprecated_function( $called, '1.7.0', 'wc_memberships_get_user_membership()->expire_membership()' );

				$user_membership_id = is_array( $args ) && isset( $args[0] ) ? $args[0] : $args;
				$user_membership    = wc_memberships_get_user_membership( $user_membership_id );

				if ( $user_membership instanceof WC_Memberships_User_Membership ) {
					$user_membership->expire_membership();
				}

				return null;

			/** @deprecated since 1.7.0 */
			case 'exclude_membership_notes_from_queries' :
				_deprecated_function( $called, '1.7.0', 'wc_memberships()->get_query_instance()->exclude_membership_notes_from_queries()' );
				return wc_memberships()->get_query_instance()->exclude_membership_notes_from_queries( isset( $args[0] ) ? $args[0] : $args );

			/** @deprecated since 1.7.0 */
			case 'exclude_membership_notes_from_feed_join' :
				_deprecated_function( $called, '1.7.0', 'wc_memberships()->get_query_instance()->exclude_membership_notes_from_feed_join()' );
				return wc_memberships()->get_query_instance()->exclude_membership_notes_from_feed_join( isset( $args[0] ) ? $args[0] : $args );

			/** @deprecated since 1.7.0 */
			case 'exclude_membership_notes_from_feed_where' :
				_deprecated_function( $called, '1.7.0', 'wc_memberships()->get_query_instance()->exclude_membership_notes_from_feed_where()' );
				return wc_memberships()->get_query_instance()->exclude_membership_notes_from_feed_where( isset( $args[0] ) ? $args[0] : $args );

			/** @deprecated since 1.8.0 */
			case 'is_user_non_inactive_member' :

				_deprecated_function( $called, '1.8.0', 'wc_memberships_is_user_active_or_delayed_member()' );

				$user_id = isset( $args[0] ) ? $args[0] : null;
				$plan    = isset( $args[1] ) ? $args[1] : null;

				return $this->is_user_active_or_delayed_member( $user_id, $plan );

			default :
				// you're probably doing it wrong
				trigger_error( 'Call to undefined property ' . __CLASS__ . '::' . $method, E_USER_ERROR );
				return null;

		}
	}


}
