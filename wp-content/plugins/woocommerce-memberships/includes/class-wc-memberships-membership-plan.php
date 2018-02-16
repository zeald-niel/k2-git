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
 * Membership Plan class
 *
 * This class represents a single membership plan, eg "silver" or "gold"
 * with it's specific configuration.
 *
 * @since 1.0.0
 */
class WC_Memberships_Membership_Plan {


	/** @var int Membership Plan (post) ID */
	public $id;

	/** @var string Membership Plan name */
	public $name;

	/** @var  string Membership Plan (post) slug */
	public $slug;

	/** @var \WP_Post Membership Plan post object */
	public $post;

	/** @var string access method meta */
	protected $access_method_meta = '';

	/** @var string the default access method */
	protected $default_access_method = '';

	/** @var string access length meta */
	protected $access_length_meta = '';

	/** @var string access start date meta */
	protected $access_start_date_meta = '';

	/** @var string access end date meta */
	protected $access_end_date_meta = '';

	/** @var string product ids meta */
	protected $product_ids_meta = '';

	/** @var string members area sections meta */
	protected $members_area_meta = '';

	/** @var string email content meta */
	protected $email_content_meta = '';

	/** @var array lazy rules getter */
	private $rules = array();


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param int|string|\WP_Post|\WC_Memberships_Membership_Plan $id Membership Plan slug, post object or related post ID
	 */
	public function __construct( $id ) {

		if ( ! $id ) {
			return;
		}

		if ( is_numeric( $id ) ) {

			$post = get_post( $id );

			if ( ! $post ) {
				return;
			}

			$this->post = $post;

		} elseif ( is_object( $id ) ) {

			$this->post = $id;
		}

		if ( $this->post ) {

			// load in post data
			$this->id   = $this->post->ID;
			$this->name = $this->post->post_title;
			$this->slug = $this->post->post_name;
		}

		// set meta keys
		$this->access_method_meta     = '_access_method';
		$this->access_length_meta     = '_access_length';
		$this->access_start_date_meta = '_access_start_date';
		$this->access_end_date_meta   = '_access_end_date';
		$this->product_ids_meta       = '_product_ids';
		$this->members_area_meta      = '_members_area_sections';
		$this->email_content_meta     = '_email_content';

		// set the default access method
		$this->default_access_method = 'unlimited';
	}


	/**
	 * Get the id
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Get the name
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Get the slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * Get product ids that grant access to this plan
	 *
	 * @since 1.0.0
	 * @return array Array of product ids
	 */
	public function get_product_ids() {

		$product_ids = get_post_meta( $this->id, $this->product_ids_meta, true );

		return ! empty( $product_ids ) ? (array) $product_ids : array();
	}


	/**
	 * Get products that grant access to plan
	 *
	 * @since 1.7.0
	 * @param bool $exclude_subscriptions Optional, whether to exclude subscription products (default false, include them)
	 * @return \WC_Product[] Array of products
	 */
	public function get_products( $exclude_subscriptions = false ) {

		$products = array();

		if ( $this->has_products() ) {

			foreach ( $this->get_product_ids() as $product_id ) {

				if ( ! is_numeric( $product_id ) || ! $product_id ) {
					continue;
				}

				$product = wc_get_product( $product_id );

				if ( ! $product || ( true === $exclude_subscriptions && $product->is_type( array( 'subscription', 'subscription_variation', 'variable-subscription' ) ) ) ) {
					continue;
				}

				$products[ $product_id ] = $product;
			}
		}

		return $products;
	}


	/**
	 * Set ids of products that can grant access to this plan
	 *
	 * @since 1.7.0
	 * @param string|int|int[] $product_ids Array or comma separated string of product ids or single id (numeric)
	 * @param bool $merge Whether to merge the specified product ids to the existing ones, rather than replace values
	 */
	public function set_product_ids( $product_ids, $merge = false ) {

		if ( is_string( $product_ids ) ){
			$product_ids = explode( ',', $product_ids );
		}

		$product_ids = array_map( 'intval', (array) $product_ids );

		// ensure all products are valid
		foreach ( $product_ids as $index => $product_id ) {

			if ( $product_id <= 0 || ! wc_get_product( $product_id ) ) {

				// remove invalid product
				unset( $product_ids[ $index ] );
			}
		}

		if ( true === $merge ) {
			$product_ids = array_merge( $this->get_product_ids(), $product_ids );
		}

		update_post_meta( $this->id, $this->product_ids_meta, array_unique( $product_ids ) );
	}


	/**
	 * Delete product ids meta
	 *
	 * @since 1.7.0
	 * @param null|string|int|int[] $product_ids Optional, if an array or single numeric value is passed,
	 *                                           one or more ids will be removed from the product ids meta
	 */
	public function delete_product_ids( $product_ids = null ) {

		if ( empty( $product_ids ) ) {

			delete_post_meta( $this->id, $this->product_ids_meta );

		} else {

			if ( is_numeric( $product_ids ) ) {
				$product_ids = (array) $product_ids;
			}

			$remove_ids   = array_map( 'intval', $product_ids );
			$existing_ids = $this->get_product_ids();

			update_post_meta( $this->id, $this->product_ids_meta, array_diff( $existing_ids, $remove_ids ) );
		}
	}


	/**
	 * Check if this plan has any products that grant access
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_products() {

		$product_ids = $this->get_product_ids();

		return ! empty( $product_ids );
	}


	/**
	 * Check if this plan has a specified product that grant access
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID to search for
	 * @return bool
	 */
	public function has_product( $product_id ) {
		return is_numeric( $product_id ) ? in_array( (int) $product_id, $this->get_product_ids(), true ) : false;
	}


	/**
	 * Ensures that an access method is one of the accepted types
	 *
	 * @since 1.7.0
	 * @param string $method Either 'manual-only', 'signup' or 'purchase'
	 * @return string Defaults to manual-only if an invalid method is supplied
	 */
	private function validate_access_method( $method ) {

		$valid_access_methods = wc_memberships()->get_plans_instance()->get_membership_plans_access_methods();

		return in_array( $method, $valid_access_methods, true ) ? $method : 'manual-only';
	}


	/**
	 * Set the method to grant access to the membership
	 *
	 * @since 1.7.0
	 * @param string $method Either 'manual-only', 'signup' or 'purchase'
	 */
	public function set_access_method( $method ) {

		update_post_meta( $this->id, $this->access_method_meta, $this->validate_access_method( $method ) );
	}


	/**
	 * Get the method to grant access to the membership
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_access_method() {

		$grant_access_type = get_post_meta( $this->id, $this->access_method_meta, true );

		// backwards compatibility check
		if ( empty( $grant_access_type ) ) {

			$product_ids = $this->get_product_ids();

			if ( ! empty( $product_ids ) ) {
				$grant_access_type = 'purchase';
			}
		}

		return $this->validate_access_method( $grant_access_type );
	}


	/**
	 * Removes the access method meta
	 * (will default the access method to manual-only)
	 *
	 * @since 1.7.0
	 */
	public function delete_access_method() {

		delete_post_meta( $this->id, $this->access_method_meta );
	}


	/**
	 * Check the plan's access method
	 *
	 * @since 1.7.0
	 * @param array|string $type Either 'manual-only', 'signup' or 'purchase'
	 * @return bool
	 */
	public function is_access_method( $type ) {
		return is_array( $type ) ? in_array( $this->get_access_method(), $type, true ) : $type === $this->get_access_method();
	}


	/**
	 * Set access length
	 *
	 * @since 1.7.0
	 * @param string $access_length An access period defined as "2 weeks", "5 months", "1 year" etc.
	 */
	public function set_access_length( $access_length ) {

		$access_length = (string) wc_memberships_parse_period_length( $access_length );

		if ( ! empty( $access_length ) ) {

			update_post_meta( $this->id, $this->access_length_meta, $access_length );
		}
	}


	/**
	 * Get access length amount
	 *
	 * Returns the amount part of the access length.
	 * For example, returns '5' for the period '5 days'
	 *
	 * @since 1.0.0
	 * @return int|string Amount or empty string if no schedule
	 */
	public function get_access_length_amount() {
		return wc_memberships_parse_period_length( $this->get_access_length(), 'amount' );
	}


	/**
	 * Get access length period
	 *
	 * Returns the period part of the access length.
	 * For example, returns 'days' for the period '5 days'
	 *
	 * @since 1.0.0
	 * @return string Period
	 */
	public function get_access_length_period() {
		return wc_memberships_parse_period_length( $this->get_access_length(), 'period' );
	}


	/**
	 * Whether this plan has a specific period length set
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	public function has_access_length() {

		$period = $this->get_access_length_period();
		$amount = $this->get_access_length_amount();

		return is_int( $amount ) && ! empty( $period );
	}


	/**
	 * Get access length
	 *
	 * @since 1.0.0
	 * @return string Access length in strtotime-friendly format,
	 *                eg. "5 days", or empty string when unlimited
	 */
	public function get_access_length() {

		// get access length for specific length membership plan
		$access_length = get_post_meta( $this->id, $this->access_length_meta, true );

		// get access length for fixed length membership plan
		if ( $access_end = wc_memberships_parse_date( $this->get_access_end_date_meta(), 'mysql' ) ) {

			// get the access length relative to remaining days from now to a certain date
			$start_time    = $this->get_access_start_date( 'timestamp' );
			$end_time      = strtotime( $access_end );
			$access_days   = ( $end_time - $start_time ) / DAY_IN_SECONDS;
			$access_length = sprintf( '%d days', max( 1, (int) $access_days ) );
		}

		return ! empty( $access_length ) ? $access_length : '';
	}


	/**
	 * Get the membership plan access length in a human readable format
	 *
	 * Note: this may result in approximations, e.g. "2 months (57 days)" and so on
	 *
	 * @since 1.7.0
	 * @return string Parses the access length and returns the number of years, months, etc.
	 *                and the total number of days of a membership plan length
	 */
	public function get_human_access_length() {

		$standard_length = $this->get_access_length();

		if ( empty( $standard_length ) ) {

			$human_length = __( 'Unlimited', 'woocommerce-memberships' );

		} else {

			$present = current_time( 'timestamp', true );
			$future  = strtotime( $standard_length, $present );
			$n_days  = ( $future - $present ) / DAY_IN_SECONDS;
			/* translators: Placeholders: %d - number of days */
			$days    = sprintf( _n( '%d day', '%d days', $n_days ), $n_days );
			$diff    = human_time_diff( $present, $future );

			if ( $n_days >= 31 ) {
				$human_length = is_rtl() ? "({$days}) " . $diff : $diff . " ({$days})";
			} else {
				$human_length = $days;
			}
		}

		/**
		 * Filter a User Membership access length in a human friendly form
		 *
		 * @since 1.7.2
		 * @param string $human_length The length in human friendly format
		 * @param string $standard_length The length in machine friendly format
		 * @param int $user_membership_id The User Membership ID
		 */
		return apply_filters( 'wc_memberships_membership_plan_human_access_length', $human_length, $standard_length, $this->id );
	}


	/**
	 * Removes the access length information
	 *
	 * Note this only removes the access length for specific-length membership plans
	 * if the membership has a fixed length, use the following methods:
	 *
	 * @see \WC_Memberships_Membership_Plan::delete_access_start_date()
	 * @see \WC_Memberships_Membership_Plan::delete_access_end_date()
	 *
	 * @since 1.7.0
	 */
	public function delete_access_length() {

		delete_post_meta( $this->id, $this->access_length_meta );
	}


	/**
	 * Get access length type
	 *
	 * @since 1.7.0
	 * @return string
	 */
	public function get_access_length_type() {

		$access_length = $this->default_access_method;
		$access_end    = $this->get_access_end_date_meta();

		if ( ! empty( $access_end ) ) {
			$access_length = 'fixed';
		} elseif ( $this->has_access_length() ) {
			$access_length = 'specific';
		}

		return $access_length;
	}


	/**
	 * Check the plan's access length type
	 *
	 * @since 1.7.0
	 * @param array|string $type Either 'specific', 'fixed' or 'unlimited'
	 * @return bool
	 */
	public function is_access_length_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_access_length_type(), $type, true ) : $type === $this->get_access_length_type();
	}


	/**
	 * Set the plan access start date
	 *
	 * Note: this only affects memberships of fixed length
	 *
	 * @since 1.7.0
	 * @param string|null $date Optional, defaults to now, otherwise a date in mysql format
	 */
	public function set_access_start_date( $date = null ) {

		if ( $start_date = wc_memberships_parse_date( $date, 'mysql' ) ) {

			update_post_meta( $this->id, $this->access_start_date_meta, $start_date );
		}
	}


	/**
	 * Get access start date
	 *
	 * This is usually 'today', but for fixed membership plans
	 * it could be a date in the future or in the past
	 *
	 * Note: this does not reflect a user membership start date
	 *
	 * @since 1.7.0
	 * @param string $format Optional, either 'mysql' (default) or 'timestamp' for timestamp
	 * @return string|int
	 */
	public function get_access_start_date( $format = 'mysql' ) {

		if ( $this->is_access_length_type( 'fixed' ) ) {
			$start_date = $this->validate_access_start_date( get_post_meta( $this->id, $this->access_start_date_meta, true ) );
		}

		if ( empty( $start_date ) ) {
			$start_date = strtotime( 'today', current_time( 'timestamp', true ) );
		}

		return wc_memberships_format_date( $start_date, $format );
	}


	/**
	 * Checks if the start access date is set after the end access date
	 * if so, rolls back the start access date to one day before the end access date
	 *
	 * @since 1.7.0
	 * @param string $access_start_date A date in mysql format
	 * @return false|string False on error or mysql date upon validation
	 */
	private function validate_access_start_date( $access_start_date ) {

		$start_date = wc_memberships_parse_date( $access_start_date, 'mysql' );

		if ( $start_date && ( $end_date = wc_memberships_parse_date( $this->get_access_end_date_meta(), 'mysql' ) ) ) {

			$start_time = strtotime( $start_date );
			$end_time   = strtotime( $end_date );

			if ( $start_time >= $end_time ) {

				// force push the fixed dates one day apart from each other
				$start_date = date( 'Y-m-d H:i:s', strtotime( 'yesterday', $end_time ) );
				$end_date   = date( 'Y-m-d H:i:s', strtotime( 'tomorrow',  $end_time ) );

				$this->set_access_start_date( $start_date );
				$this->set_access_end_date( $end_date );
			}
		}

		return $start_date;
	}


	/**
	 * Get access start date, adjusted for the local site timezone
	 *
	 * @since 1.7.0
	 * @param string $format Optional, the date format: either 'mysql' (default) or 'timestamp'
	 * @return string|int
	 */
	public function get_local_access_start_date( $format = 'mysql' ) {

		// get the date timestamp
		$date = $this->get_access_start_date( 'timestamp' );

		// adjust the date to the site's local timezone
		return wc_memberships_adjust_date_by_timezone( $date, $format );
	}


	/**
	 * Delete the access start date meta
	 *
	 * Note: this only affects membership plans of fixed length
	 *
	 * @since 1.7.0
	 */
	public function delete_access_start_date() {

		delete_post_meta( $this->id, $this->access_start_date_meta );
	}


	/**
	 * Set access end date
	 *
	 * Note: this only affects membership plans of fixed length
	 *
	 * @since 1.7.0
	 * @param string $date A date in mysql format
	 */
	public function set_access_end_date( $date ) {

		if ( $end_date = wc_memberships_parse_date( $date, 'mysql' ) ) {

			update_post_meta( $this->id, $this->access_end_date_meta, $end_date );
		}
	}


	/**
	 * Get access end date
	 *
	 * Note: this will return the access end date for fixed length membership plans
	 * otherwise it will return the expiration date
	 *
	 * @since 1.7.0
	 * @param string $format Optional, the date format: either 'mysql' (default) or 'timestamp'
	 * @param array $args Optional arguments passed to fallback method
	 * @return string|int Returns empty string regardless of $format for unlimited memberships
	 */
	public function get_access_end_date( $format = 'mysql', $args = array() ) {

		$end_date = get_post_meta( $this->id, $this->access_end_date_meta, true );
		$end_date = empty( $end_date ) ? $this->get_expiration_date( current_time( 'timestamp', true ), $args ) : $end_date;

		return ! empty( $end_date ) ? wc_memberships_format_date( $end_date, $format ) : '';
	}


	/**
	 * Get access end date, adjusted for the local site timezone
	 *
	 * @since 1.7.0
	 * @param string $format Optional, the date format: either 'mysql' (default) or 'timestamp'
	 * @return string|int Returns empty string regardless of $format for unlimited memberships
	 */
	public function get_local_access_end_date( $format = 'mysql' ) {

		$access_end_date = $this->get_access_end_date( $format );

		return ! empty( $access_end_date ) ? wc_memberships_adjust_date_by_timezone( $access_end_date, $format ) : '';
	}


	/**
	 * Get the access end date meta
	 *
	 * @see \WC_Memberships_Membership_Plan::get_expiration_date()
	 *
	 * @since 1.7.0
	 * @return string|null
	 */
	protected function get_access_end_date_meta() {

		$access_end_date = get_post_meta( $this->id, $this->access_end_date_meta, true );

		return ! empty( $access_end_date ) ? $access_end_date : null;
	}


	/**
	 * Delete the access end date meta
	 *
	 * @since 1.7.0
	 */
	public function delete_access_end_date() {

		delete_post_meta( $this->id, $this->access_end_date_meta );
	}


	/**
	 * Get membership plan expiration date
	 *
	 * Calculates when a membership plan will expire relatively to a start date
	 *
	 * @since 1.3.8
	 * @param int|string $start Optional: a date string or timestamp as the start time
	 *                          relative to the expiry date to calculate expiration for (default: current time)
	 * @param array $args Optional: additional arguments passed in hooks
	 * @return string Date in Y-m-d H:i:s format or empty for unlimited plans (no expiry)
	 */
	public function get_expiration_date( $start = '', $args = array() ) {

		$end      = '';
		$end_date = '';

		// start is placed here again for backwards compatibility reasons
		// (see filter arguments at the end of method)
		$args = wp_parse_args( $args, array(
			'plan_id' => $this->id,
			'start'   => $start,
		) );

		// unlimited length plans have no end date, calculate only for those who have
		if ( ! $this->is_access_length_type( 'unlimited' ) ) {

			// get the access length for fixed and specific length membership plans
			$access_length = $this->get_access_length();

			// get the start time to get the relative end time later
			if ( $this->is_access_length_type( 'fixed' ) ) {
				$start = $this->get_access_start_date( 'timestamp' );
			} elseif ( empty( $start ) ) {
				if ( ! empty( $args['start'] ) ) {
					$start = is_numeric( $args['start'] ) ? (int) $args['start'] : strtotime( $args['start'] );
				} else {
					$start = current_time( 'timestamp', true );
				}
			} elseif ( is_string( $start ) && ! is_numeric( $start ) ) {
				$start = strtotime( $start );
			} else {
				$start = is_numeric( $start ) ? (int) $start : current_time( 'timestamp', true );
			}

			// tweak end date for months calculation
			if ( SV_WC_Helper::str_ends_with( $access_length, 'months' ) ) {
				$end = wc_memberships_add_months_to_timestamp( (int) $start, $this->get_access_length_amount() );
			} else {
				$end = strtotime( '+ ' . $access_length, (int) $start );
			}

			// format the end date
			if ( isset( $args['format'] ) && 'timestamp' === $args['format'] ) {
				$end_date = $end;
			} else {
				$end_date = date( 'Y-m-d H:i:s', $end );
			}
		}

		/**
		 * Plan expiration date
		 *
		 * @since 1.5.3
		 * @param int|string $expiration_date Date in Y-m-d H:i:s format (or optionally timestamp), empty string for unlimited plans
		 * @param int|string $expiration_timestamp Timestamp, empty string for unlimited plans
		 * @param array $args Associative array of additional arguments as passed to get expiration method
		 */
		return apply_filters( 'wc_memberships_plan_expiration_date', $end_date, $end, $args );
	}


	/**
	 * Set members area sections for this plan.
	 *
	 * @see \wc_memberships_get_members_area_sections()
	 *
	 * @since 1.7.0
	 * @param null|string|array $sections Array of section keys or single section key (string).
	 */
	public function set_members_area_sections( $sections = null ) {

		$default_sections = wc_memberships_get_members_area_sections( $this->id );
		$sections         = null === $sections ? array_keys( $default_sections ) : $sections;

		// Validate sections.
		if ( is_string( $sections ) ) {
			$sections = array_key_exists( $sections, $default_sections ) ? (array) $sections : array();
		} elseif ( ! empty( $sections ) && is_array( $sections ) ) {
			$sections = array_intersect( $sections, array_keys( $default_sections ) );
		} else {
			$sections = array();
		}

		update_post_meta( $this->id, $this->members_area_meta, $sections );
	}


	/**
	 * Set members area sections for this plan.
	 *
	 * TODO remove this method by version 2.0.0 or before WC 2.8 compatibility release update {FN 2016-12-27}
	 *
	 * @deprecated since 1.7.4
	 * @see \WC_Memberships_Membership_Plan::set_members_area_sections()
	 *
	 * @since 1.7.0
	 * @param null|string|array $sections
	 */
	public function set_member_area_sections( $sections = null ) {
		_deprecated_function( __CLASS__ . '::set_member_area_sections()', '1.7.4', __CLASS__ . 'set_members_area_sectinos()' );
		$this->set_members_area_sections( $sections );
	}


	/**
	 * Get members area sections for this plan.
	 *
	 * @see \wc_memberships_get_members_area_sections()
	 *
	 * @since 1.4.0
	 * @return array
	 */
	public function get_members_area_sections() {

		$members_area_sections = get_post_meta( $this->id, $this->members_area_meta, true );

		return is_array( $members_area_sections ) ? $members_area_sections : array();
	}

	/**
	 * Remove the members area sections for this plan.
	 *
	 * @since 1.7.4
	 */
	public function delete_members_area_sections() {

		delete_post_meta( $this->id, $this->members_area_meta );
	}


	/**
	 * Remove the members area sections for this plan.
	 *
	 * TODO remove this method by version 2.0.0 or before WC 2.8 compatibility release update {FN 2016-12-27}
	 *
	 * @deprecated since 1.7.4
	 * @see \WC_Memberships_Membership_Plan::delete_members_area_sections()
	 *
	 * @since 1.7.0
	 */
	public function delete_member_area_sections() {
		_deprecated_function( __CLASS__ . '::delete_member_area_sections()', '1.7.4',  __CLASS__ . '::delete_members_area_sections()' );
		$this->delete_members_area_sections();
	}


	/**
	 * Set memberships plan email content
	 *
	 * @since 1.7.0
	 * @param array|string $email Email to update, or associative array with all emails to update
	 * @param string $content Content to set, default empty string
	 */
	public function set_email_content( $email, $content = '' ) {

		$emails        = wc_memberships()->get_emails_instance()->get_email_classes();
		$email_content = get_post_meta( $this->id, $this->email_content_meta, true );
		$email_content = ! is_array( $email_content ) ? array() : $email_content;

		if ( is_array( $email ) && ! empty( $email ) ) {

			foreach ( $email as $email_key => $new_content ) {

				// ensure the email class is capitalized
				$email_key = implode( '_', array_map( 'ucfirst', explode( '_', $email_key ) ) );

				if ( isset( $emails[ $email_key ] ) && method_exists( $emails[ $email_key ], 'get_default_body' ) ) {

					$new_content = empty( $new_content ) ? null : trim( $new_content );
					$new_content = empty( $new_content ) ? wp_kses_post( $emails[ $email_key ]->get_default_body() ) : $new_content;

					$email_content[ $email_key ] = $new_content;
				}
			}

			update_post_meta( $this->id, $this->email_content_meta, $email_content );

		} elseif ( is_string( $email ) ) {

			// ensure the email class is capitalized
			$email = implode( '_', array_map( 'ucfirst', explode( '_', $email ) ) );

			if (    isset( $emails[ $email ] )
			     && method_exists( $emails[ $email ], 'get_default_body' ) ) {

				$new_content = empty( $content ) ? null : trim( $content );
				$new_content = empty( $new_content ) ? wp_kses_post( $emails[ $email ]->get_default_body() ) : $new_content;

				$email_content[ $email ] = $new_content;

				update_post_meta( $this->id, $this->email_content_meta, $email_content );
			}
		}
	}


	/**
	 * Get membership plan email content
	 *
	 * @since 1.7.0
	 * @param string $email Which email content to retrieve
	 * @return string May contain HTML
	 */
	public function get_email_content( $email ) {

		// ensure the email class is capitalized
		$email  = implode( '_', array_map( 'ucfirst', explode( '_', $email ) ) );
		$emails = wc_memberships()->get_emails_instance()->get_email_classes();

		if ( ! isset( $emails[ $email ] ) || ! $emails[ $email ] instanceof WC_Memberships_User_Membership_Email ) {
			return '';
		}

		$email_content = get_post_meta( $this->id, $this->email_content_meta, true );

		if ( empty( $email_content ) || ! isset( $email_content[ $email ] ) ) {
			return wc_memberships()->get_emails_instance()->get_email_default_content( $email );
		} else {
			return is_string( $email_content[ $email ] ) ? $email_content[ $email ] : '';
		}
	}


	/**
	 * Delete membership plan email content
	 *
	 * @since 1.7.0
	 * @param string $email Email to delete content for, 'all' or 'any' for all
	 */
	public function delete_email_content( $email = '' ) {

		$emails = wc_memberships()->get_emails_instance()->get_email_classes();

		if ( in_array( $email, array( 'all', 'any' ), true ) ) {

			delete_post_meta( $this->id, $this->email_content_meta );

		} else {

			// ensure the email class is capitalized
			$email  = implode( '_', array_map( 'ucfirst', explode( '_', $email ) ) );

			if ( isset( $emails[ $email ] ) ) {

				$email_content = get_post_meta( $this->id, $this->email_content_meta, true );

				if ( ! empty( $email_content ) && is_array( $email_content ) ) {

					unset( $email_content[ $email ] );

					update_post_meta( $this->id, $this->email_content_meta, $email_content );
				}
			}
		}
	}


	/**
	 * Get membership plan rules
	 *
	 * General rules builder & getter.
	 *
	 * @since 1.0.0
	 * @param string $rule_type Rule type. One of 'content_restriction', 'product_restriction' or 'purchasing_discount'.
	 * @return array|bool $rules Array of rules or false on error
	 */
	private function get_rules( $rule_type ) {

		if ( ! isset( $this->rules[ $rule_type ] ) ) {

			$all_rules = get_option( 'wc_memberships_rules' );

			$this->rules[ $rule_type ] = array();

			if ( ! empty( $all_rules ) ) {

				foreach ( $all_rules as $rule ) {

					// skip empty items
					if ( empty( $rule ) || ! is_array( $rule ) ) {
						continue;
					}

					$rule = new WC_Memberships_Membership_Plan_Rule( $rule );

					if ( $rule_type === $rule->get_rule_type()
					     && (int) $rule->get_membership_plan_id() === (int) $this->id ) {

						$this->rules[ $rule_type ][] = $rule;
					}
				}
			}
		}

		return $this->rules[ $rule_type ];
	}


	/**
	 * Get content restriction rules
	 *
	 * @since 1.0.0
	 * @return array Array of content restriction rules
	 */
	public function get_content_restriction_rules() {
		return $this->get_rules( 'content_restriction' );
	}


	/**
	 * Get product restriction rules
	 *
	 * @since 1.0.0
	 * @return array Array of product restriction rules
	 */
	public function get_product_restriction_rules() {
		return $this->get_rules( 'product_restriction' );
	}


	/**
	 * Get purchasing discount rules
	 *
	 * @since 1.0.0
	 * @return array Array of purchasing discount rules
	 */
	public function get_purchasing_discount_rules() {
		return $this->get_rules( 'purchasing_discount' );
	}


	/**
	 * Get restricted posts (content or products)
	 *
	 * @since 1.4.0
	 * @param string $type 'content_restriction', 'product_restriction', 'purchasing_discount'
	 * @param int $paged Pagination (optional)
	 * @return null|\WP_Query Query results of restricted posts accessible to this membership
	 */
	private function get_restricted( $type, $paged = 1 ) {

		$query    = null;
		$post_ids = array();
		$rules    = $this->get_rules( $type );

		// sanity check
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return $query;
		}

		foreach ( $rules as $data ) {

			$plan_rules = (array) $data;

			foreach ( $plan_rules as $rule ) {

				if ( 'post_type' === $rule['content_type'] ) {

					if ( ! empty( $rule['object_ids'] ) ) {

						// specific posts are restricted for this rule
						$post_ids = array_merge( $post_ids, array_map( 'intval', array_values( $rule['object_ids'] ) ) );

					} else {

						// all posts of a type are restricted
						$post_ids_query = new WP_Query( array(
							'fields'    => 'ids',
							'nopaging'  => true,
							'post_type' => $rule['content_type_name'],
						) );

						$post_ids = ! empty( $post_ids_query->posts ) ? array_merge( $post_ids, array_map( 'intval', $post_ids_query->posts ) ) : $post_ids;
					}

				} elseif ( 'taxonomy' === $rule['content_type'] ) {

					if ( ! empty( $rule['content_type_name'] ) ) {

						if ( empty( $rule['object_ids'] ) ) {
							$terms = get_terms( $rule['content_type_name'], array(
								'fields' => 'ids',
							) );
						} else {
							$terms = $rule['object_ids'];
						}

						$taxonomy = new WP_Query( array(
							'fields'    => 'ids',
							'nopaging'  => true,
							'tax_query' => array(
								array(
									'taxonomy' => $rule['content_type_name'],
									'field'    => 'term_id',
									'terms'    => $terms,
								),
							),
						) );

						$post_ids = ! empty( $taxonomy->posts ) ? array_merge( $post_ids, array_map( 'intval', $taxonomy->posts ) ) : $post_ids;
					}
				}
			}
		}

		if ( ! empty( $post_ids ) ) {

			$post_ids = array_unique( $post_ids );

			// special handling for products
			if ( in_array( $type, array( 'product_restriction', 'purchasing_discount' ), true ) ) {

				// ensure that for variations we list parent variable products
				$post_types = array( 'product' );
				$parent_ids = array();

				/**
				 * Filter to show hidden products when queried from plan.
				 *
				 * @since 1.8.5
				 *
				 * @param bool $exclude_hidden Whether to show products marked hidden from catalog or not (default false: show all products, including hidden ones)
				 */
				$exclude_hidden = (bool) apply_filters( 'wc_memberships_plan_exclude_hidden_products', false );

				foreach ( $post_ids as $post_id ) {

					if ( $product = wc_get_product( $post_id ) ) {

						$product_id = $post_id;
						$parent     = SV_WC_Product_Compatibility::get_parent( $product );

						if ( $exclude_hidden && ! $product->is_visible() ) {
							continue;
						}

						if ( ! empty( $parent ) && $product->is_type( 'variation' ) && $parent->is_type( 'variable' ) ) {

							if ( $exclude_hidden && ! $parent->is_visible() ) {
								continue;
							}

							$parent_id        = SV_WC_Product_Compatibility::get_prop( $parent, 'id' );
							$can_list_product = true;

							// sanity check: maybe a variation is included in this plan
							// but the parent variable product is being restricted
							// by the rules of another plan the user is not member of
							if ( ! in_array( $parent_id, $post_ids, false ) ) {
								$can_list_product = wc_memberships_user_can( get_current_user_id(), 'view', array( 'product' => $parent_id ) );
							}

							if ( $can_list_product ) {
								$parent_ids[] = $parent_id;
							}

						} elseif ( $this->has_product_discount( $product ) || ( 'product_restriction' === $type && wc_memberships_user_can( get_current_user_id(), 'view', array( 'product' => $product_id ) ) ) ) {

							$parent_ids[] = $product_id;
						}
					}
				}

				$post_ids = array_unique( $parent_ids );

				// remove product ids that are marked to ignore member discounts
				if ( 'purchasing_discount' === $type && ! empty( $post_ids ) ) {
					$post_ids = $this->filter_products_excluding_member_discounts( $post_ids );
				}

			} else {

				// avoid use of 'any' in query args, to include post types
				// marked as 'excluded_from_search' which wouldn't be returned
				$post_types = get_post_types( array(
					'public' => true,
				) );
			}

			// sanity check, otherwise WP_Query will return all posts
			if ( ! empty( $post_ids ) ) {

				$query_args = array(
					'post_type'           => $post_types,
					'post__in'            => $post_ids,
					'ignore_sticky_posts' => true,
					'paged'               => $paged,
				);

				/**
				 * Filter restricted content query args
				 *
				 * @since 1.6.3
				 * @param array $query_args Args passed to WP_Query
				 * @param string $query_type Type of request: 'content_restriction', 'product_restriction', 'purchasing_discount'
				 * @param int $query_paged Pagination request
				 */
				$query_args = apply_filters( 'wc_memberships_get_restricted_posts_query_args', $query_args, $type, $paged );

				$query = new WP_Query( $query_args );

				return $query;
			}
		}

		return $query;
	}


	/**
	 * Filters out from an array of products ids
	 * the products that are marked to ignore member discounts
	 *
	 * @since 1.7.0
	 * @param int[] $product_ids Array of WC_Product post ids
	 * @return int[] Array of product ids
	 */
	private function filter_products_excluding_member_discounts( array $product_ids ) {

		// get products that are individually marked to be excluded
		// from member discounts
		$excluded_product_ids = get_posts( array(
			'post_type' => 'product',
			'post__in'  => $product_ids,
			'nopaging'  => true,
			'fields'    => 'ids',
			'meta_query' => array(
				array(
					'key'     => '_wc_memberships_exclude_discounts',
					'value'   => 'yes',
				),
			),
		) );

		// subtract products marked as excluded from member discounts
		// from array of product ids
		$product_ids = array_diff( $product_ids, (array) $excluded_product_ids );

		// if we are excluding products on sale from member discounts
		// we must also check if any of the remainder products are on sale
		$discounts = wc_memberships()->get_member_discounts_instance();

		if ( $discounts && ! empty( $product_ids ) && ( $exclude_on_sale_products = $discounts->excluding_on_sale_products_from_member_discounts() ) ) {

			foreach ( $product_ids as $product_id ) {

				if ( $exclude_on_sale_products && $discounts->product_is_on_sale_before_discount( $product_id ) ) {

					foreach ( array_keys( $product_ids, $product_id, true ) as $key ) {

						unset( $product_ids[ $key ] );
					}
				}
			}
		}

		return $product_ids;
	}


	/**
	 * Get restricted content
	 *
	 * @since 1.4.0
	 * @param int $paged Pagination (optional)
	 * @return \WP_Query
	 */
	public function get_restricted_content( $paged = 1 ) {
		return $this->get_restricted( 'content_restriction', $paged );
	}


	/**
	 * Get restricted products
	 *
	 * @since 1.4.0
	 * @param int $paged Pagination (optional)
	 * @return \WP_Query
	 */
	public function get_restricted_products( $paged = 1 ) {
		return $this->get_restricted( 'product_restriction', $paged );
	}


	/**
	 * Get discounted products
	 *
	 * @since 1.4.0
	 * @param int $paged Pagination (optional)
	 * @return \WP_Query
	 */
	public function get_discounted_products( $paged = 1 ) {
		return $this->get_restricted( 'purchasing_discount', $paged );
	}


	/**
	 * Check whether the plan offers a discount for the specified product
	 *
	 * @since 1.7.1
	 * @param int|\WC_Product $product The product
	 * @return bool
	 */
	public function has_product_discount( $product ) {
		return (bool) $this->get_product_discount( $product );
	}


	/**
	 * Get product discount fixed amount or percentage
	 *
	 * @since 1.4.0
	 * @param int|\WC_Product $product Product to check discounts for
	 * @return float|int|string A number as a fixed amount or % percentage amount
	 */
	public function get_product_discount( $product ) {

		$member_discount = '';

		// get all available discounts for this product
		$product_id    = $product instanceof WC_Product ? SV_WC_Product_Compatibility::get_prop( $product, 'id' ) : $product;
		$all_discounts = wc_memberships()->get_rules_instance()->get_product_purchasing_discount_rules( $product_id );

		foreach ( $all_discounts as $discount ) {

			// only get discounts that match the current membership plan & are active
			if ( $discount->is_active() && $this->id == $discount->get_membership_plan_id() ) {

				switch( $discount->get_discount_type() ) {

					case 'percentage' :
						$member_discount = abs( $discount->get_discount_amount() ) . '%';
					break;

					case 'amount' :
					default :
						$member_discount = abs( $discount->get_discount_amount() );
					break;
				}
			}
		}

		return ! empty( $member_discount ) ? $member_discount : '';
	}


	/**
	 * Get the formatted product discount
	 *
	 * @since 1.7.1
	 * @param \WC_Product|\WC_Product_Variation $product The product object
	 * @return string
	 */
	public function get_formatted_product_discount( $product ) {

		$member_discount = $this->get_product_discount( $product );

		if ( empty( $member_discount ) && ( $child_products = $product->get_children() ) ) {

			// if the product has no discount and it's variable,
			// check if the variations have direct discounts
			$child_discounts               = array();
			$children_fixed_discounts      = array();
			$children_percentage_discounts = array();					;

			foreach ( $child_products as $child_product_id ) {

				$child_discount = $this->get_product_discount( $child_product_id );

				if ( ! empty( $child_discount ) ) {

					if ( is_numeric ( $child_discount ) ) {
						$children_fixed_discounts[]      = (float) $child_discount;
					} else {
						$children_percentage_discounts[] = (float) rtrim( $child_discount, '%' );
					}
				}
			}

			if ( ! empty( $children_fixed_discounts ) ) {
				$child_discounts[] = $this->get_product_from_to_discount( $children_fixed_discounts, 'fixed' );
			}

			if ( ! empty( $children_percentage_discounts ) ) {
				$child_discounts[] = $this->get_product_from_to_discount( $children_percentage_discounts, 'percentage' );
			}

			if ( ! empty( $child_discounts ) ) {
				$member_discount = wc_memberships_list_items( $child_discounts );
			}

		} elseif ( ! empty( $member_discount ) && is_numeric( $member_discount ) ) {

			// format fixed amount discounts
			$member_discount = wc_price( $member_discount );
		}

		return $member_discount;
	}


	/**
	 * Get product discounts range (used for variations)
	 *
	 * @see \WC_Memberships_Membership_Plan::get_formatted_product_discount()
	 *
	 * @since 1.7.1
	 * @param int[]|float[] $discounts Array of numbers
	 * @param string $type Type of discount range, 'fixed' amount or 'percentage' amount
	 * @return string Formatted range
	 */
	private function get_product_from_to_discount( $discounts, $type ) {

		$member_discount = '';
		$min_discount    = min( $discounts );
		$max_discount    = max( $discounts );

		if ( $max_discount > $min_discount ) {

			if ( in_array( $type, array( 'fixed', 'percentage' ), true ) ) {

				$min_discount = 'fixed' === $type ? wc_price( $min_discount ) : $min_discount . '%';
				$max_discount = 'fixed' === $type ? wc_price( $max_discount ) : $max_discount . '%';

				if ( is_rtl() ) {
					$member_discount = $max_discount . '-' . $min_discount;
				} else {
					$member_discount = $min_discount . '-' . $max_discount;
				}
			}

		} elseif ( $min_discount > 0 ) {

			$member_discount = 'fixed' === $type ? wc_price( $min_discount ) : $min_discount . '%';
		}

		return $member_discount;
	}


	/**
	 * Get related user memberships
	 *
	 * @since 1.0.0
	 * @param array $args Optional arguments to pass to `get_posts()` with defaults
	 * @return \WC_Memberships_User_Membership[] Array of user memberships
	 */
	public function get_memberships( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'post_type'   => 'wc_user_membership',
			'post_status' => 'any',
			'post_parent' => $this->id,
			'nopaging'    => true,
		) );

		$posts = get_posts( $args );

		$user_memberships = array();

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $post ) {
				$user_memberships[] = wc_memberships_get_user_membership( $post );
			}
		}

		return $user_memberships;
	}


	/**
	 * Get number of related memberships
	 *
	 * @since 1.0.0
	 * @param string|array $status Members statuses to count - optional, defaults to 'any'
	 * @return int
	 */
	public function get_memberships_count( $status = 'any' ) {

		$default_statuses = array_keys( wc_memberships_get_user_membership_statuses() );

		if ( 'any' === $status ) {
			$status = $default_statuses;
		}

		$statuses    = (array) $status;
		$post_status = array();
		$members     = array();

		if ( ! empty( $statuses ) ) {

			// enforces a 'wcm-' prefix if missing
			foreach ( $statuses as $status_key ) {

				$status_key = SV_WC_Helper::str_starts_with( $status_key, 'wcm-' ) ? $status_key : 'wcm-' . $status_key;

				if ( in_array( $status_key, $default_statuses, true ) ) {
					$post_status[] = $status_key;
				}
			}
		}

		if ( ! empty( $post_status ) ) {

			$members = get_posts( array(
				'post_type'   => 'wc_user_membership',
				'post_status' => $post_status,
				'post_parent' => $this->id,
				'fields'      => 'ids',
				'nopaging'    => true,
			) );
		}

		return is_array( $members ) ? count( $members ) : 0;
	}


	/**
	 * Check if the plan has any active user memberships
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function has_active_memberships() {
		return $this->get_memberships_count( 'active' ) > 0;
	}


	/**
	 * Grant a user access to this plan from a purchase
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID
	 * @param int $product_id Product ID
	 * @param int $order_id Order ID
	 * @return int|null New/Existing User Membership ID or null on failure
	 */
	public function grant_access_from_purchase( $user_id, $product_id, $order_id ) {

		$user_membership_id = null;
		$action             = 'create';
		$product            = is_numeric( $product_id ) ? wc_get_product( $product_id ) : $product_id;
		$order              = is_numeric( $order_id )   ? wc_get_order( $order_id )     : $order_id;

		// sanity check
		if ( ! $product instanceof WC_Product || ! $order instanceof WC_Order || ! get_user_by( 'id', $user_id ) ) {
			return null;
		}

		$product_id     = $product->get_id();
		$order_status   = $order->get_status();
		$access_granted = wc_memberships_get_order_access_granted_memberships( $order_id );

		// check if user is perhaps a member, but membership is expired/cancelled
		if ( wc_memberships_is_user_member( $user_id, $this->id, false ) ) {

			$user_membership    = wc_memberships_get_user_membership( $user_id, $this->id );
			$user_membership_id = $user_membership->get_id();
			$past_order_id      = $user_membership->get_order_id();

			// do not allow the same order to renew or reactivate the membership:
			// this prevents admins changing order statuses
			// from extending/reactivating the membership
			if ( ! empty( $past_order_id ) && (int) $order_id === $past_order_id ) {

				// however, there is an exception when the intended behaviour
				// is to extend membership length when the option is enabled
				// and the purchase order includes multiple access granting products
				if ( wc_memberships_cumulative_granting_access_orders_allowed() ) {

					if (    isset( $access_granted[ $user_membership_id ] )
					     && $access_granted[ $user_membership_id ]['granting_order_status'] !== $order_status ) {

						// bail if this is an order status change and not a cumulative purchase
						if ( 'yes' === $access_granted[ $user_membership_id ]['already_granted'] ) {

							return null;
						}
					}

				} else {

					return null;
				}
			}

			// otherwise... continue as usual
			$action = 'renew';

			if ( $user_membership->is_active() || $user_membership->is_delayed() ) {

				/**
				 * Filter whether an already active (or delayed) membership will be renewed
				 *
				 * @since 1.0.0
				 * @param bool $renew
				 * @param WC_Memberships_Membership_Plan $plan
				 * @param array $args
				 */
				$renew_membership = apply_filters( 'wc_memberships_renew_membership', (bool) $this->get_access_length_amount(), $this, array(
					'user_id'    => $user_id,
					'product_id' => $product_id,
					'order_id'   => $order_id,
				) );

				if ( ! $renew_membership ) {
					return null;
				}
			}
		}

		// create/update the user membership
		$user_membership = wc_memberships_create_user_membership( array(
			'user_membership_id' => $user_membership_id,
			'user_id'            => $user_id,
			'product_id'         => $product_id,
			'order_id'           => $order_id,
			'plan_id'            => $this->id,
		), $action );

		// Add a membership note.
		if ( 'create' === $action ) {

			$user_membership->add_note(
				/* translators: Placeholders: %1$s - product name, %2$s - order number. */
				sprintf(__('Membership access granted from purchasing %1$s (Order %2$s)'),
					$product->get_title(),
					$order->get_order_number()
				)
			);

		} elseif ( 'renew' === $action ) {

			// Do not bother if the membership is fixed and is ended.
			if ( ! ( $this->is_access_length_type( 'fixed' ) && ! $user_membership->is_active() && ! $user_membership->is_delayed() ) ) {

				$user_membership->add_note(
					/* translators: Placeholders: %1$s - product name, %2$s - order number. */
					sprintf(__('Membership access renewed from purchasing %1$s (Order %2$s)'),
						$product->get_title(),
						$order->get_order_number()
					)
				);
			}
		}

		// save a post meta with the initial order status to check for later order status changes
		if ( ! isset( $access_granted[ $user_membership->get_id() ] ) ) {

			wc_memberships_set_order_access_granted_membership( $order, $user_membership, array(
				'already_granted'       => 'yes',
				'granting_order_status' => $order_status,
			) );
		}

		/**
		 * Fires after a user has been granted membership access from a purchase
		 *
		 * @since 1.0.0
		 * @param \WC_Memberships_Membership_Plan $membership_plan The plan that user was granted access to
		 * @param array $args
		 */
		do_action( 'wc_memberships_grant_membership_access_from_purchase', $this, array(
			'user_id'            => $user_id,
			'product_id'         => $product_id,
			'order_id'           => $order_id,
			'user_membership_id' => $user_membership->get_id(),
		) );

		return $user_membership->get_id();
	}


}
