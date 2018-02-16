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
 * Membership Capabilities class
 *
 * This class handles all capability-related functionality, as well as providing
 * start times for when a user can access a specific piece of content
 *
 * @since 1.0.0
 */
class WC_Memberships_Capabilities {


	/** @var array helper for user post access start time results */
	private $_user_access_start_time = array();


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Adjust user capabilities
		add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 9, 3 );
	}


	/**
	 * Check if the passed in caps contain a positive 'manage_woocommerce' capability
	 *
	 * @since 1.0.0
	 * @param array $caps
	 * @return bool
	 */
	private function can_manage_woocommerce( $caps ) {
		return isset( $caps['manage_woocommerce'] ) && $caps['manage_woocommerce'];
	}


	/**
	 * Checks if a user has a certain capability
	 *
	 * @since 1.0.0
	 * @param array $allcaps
	 * @param array $caps
	 * @param array $args
	 * @return array|bool
	 */
	public function user_has_cap( $allcaps, $caps, $args ) {
		global $pagenow, $typenow;

		if ( isset( $caps[0] ) ) {

			switch ( $caps[0] ) {

				case 'wc_memberships_access_all_restricted_content':

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

				break;

				case 'wc_memberships_view_restricted_post_content' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id = $args[1];
					$post_id = $args[2];

					if ( $this->post_is_public( $post_id ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$rules               = wc_memberships()->get_rules_instance()->get_post_content_restriction_rules( $post_id );
					$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_content_access_from_rules( $user_id, $rules, $post_id );

				break;

				case 'wc_memberships_view_restricted_product' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id = $args[1];
					$post_id = $args[2];

					if ( $this->post_is_public( $post_id ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$rules               = wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $post_id );
					$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_product_view_access_from_rules( $user_id, $rules, $post_id );

				break;

				case 'wc_memberships_purchase_restricted_product' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id = $args[1];
					$post_id = $args[2];

					if ( $this->post_is_public( $post_id ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$rules               = wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $post_id );
					$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_product_purchase_access_from_rules( $user_id, $rules, $post_id );

				break;

				case 'wc_memberships_view_restricted_product_taxonomy_term':

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id  = $args[1];
					$taxonomy = $args[2];
					$term_id  = $args[3];

					$rules               = wc_memberships()->get_rules_instance()->get_taxonomy_term_product_restriction_rules( $taxonomy, $term_id );
					$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_content_access_from_rules( $user_id, $rules, $term_id );

				break;

				case 'wc_memberships_view_delayed_product_taxonomy_term';

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id    = $args[1];
					$taxonomy   = $args[2];
					$term       = $args[3];
					$has_access = false;

					$access_time = $this->get_user_access_start_time_for_taxonomy_term( $user_id, $taxonomy, $term );

					if ( $access_time && current_time( 'timestamp', true ) >= $access_time ) {
						$has_access = true;
					}

					$allcaps[ $caps[0] ] = $has_access;

				break;

				case 'wc_memberships_view_restricted_taxonomy_term' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id  = $args[1];
					$taxonomy = $args[2];
					$term_id  = $args[3];

					$rules               = wc_memberships()->get_rules_instance()->get_taxonomy_term_content_restriction_rules( $taxonomy, $term_id );
					$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_content_access_from_rules( $user_id, $rules, $term_id );

				break;

				case 'wc_memberships_view_restricted_taxonomy' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id  = $args[1];
					$taxonomy = $args[2];

					$rules               = wc_memberships()->get_rules_instance()->get_taxonomy_content_restriction_rules( $taxonomy );
					$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_content_access_from_rules( $user_id, $rules );

				break;

				case 'wc_memberships_view_restricted_post_type' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id   = $args[1];
					$post_type = $args[2];

					if ( in_array( $post_type, array( 'product', 'product_variation' ) ) ) {

						$rules = wc_memberships()->get_rules_instance()->get_product_restriction_rules( array(
							'content_type'      => 'post_type',
							'content_type_name' => 'product',
						) );

						$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_product_view_access_from_rules( $user_id, $rules );

					} else {

						$rules               = wc_memberships()->get_rules_instance()->get_post_type_content_restriction_rules( $post_type );
						$allcaps[ $caps[0] ] = wc_memberships()->get_rules_instance()->user_has_content_access_from_rules( $user_id, $rules );
					}

				break;

				case 'wc_memberships_view_delayed_post_type';

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id    = $args[1];
					$post_type  = $args[2];
					$has_access = false;

					$access_time = $this->get_user_access_start_time_for_post_type( $user_id, $post_type );

					if ( $access_time && current_time( 'timestamp', true ) >= $access_time ) {
						$has_access = true;
					}

					$allcaps[ $caps[0] ] = $has_access;

					break;

				case 'wc_memberships_view_delayed_taxonomy';

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id    = $args[1];
					$taxonomy   = $args[2];
					$has_access = false;

					$access_time = $this->get_user_access_start_time_for_taxonomy( $user_id, $taxonomy );

					if ( $access_time && current_time( 'timestamp', true ) >= $access_time ) {
						$has_access = true;
					}

					$allcaps[ $caps[0] ] = $has_access;
					break;

				case 'wc_memberships_view_delayed_taxonomy_term';

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id    = $args[1];
					$taxonomy   = $args[2];
					$term       = $args[3];
					$has_access = false;

					$access_time = $this->get_user_access_start_time_for_taxonomy_term( $user_id, $taxonomy, $term );

					if ( $access_time && current_time( 'timestamp', true ) >= $access_time ) {
						$has_access = true;
					}

					$allcaps[ $caps[0] ] = $has_access;
					break;

				case 'wc_memberships_view_delayed_post_content' :
				case 'wc_memberships_view_delayed_product' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id    = $args[1];
					$post_id    = $args[2];
					$has_access = false;

					if ( $this->post_is_public( $post_id ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$access_time = $this->get_user_access_start_time_for_post( $user_id, $post_id, 'view' );

					if ( $access_time && current_time( 'timestamp', true ) >= $access_time ) {
						$has_access = true;
					}

					$allcaps[ $caps[0] ] = $has_access;

				break;

				case 'wc_memberships_purchase_delayed_product' :

					if ( $this->can_manage_woocommerce( $allcaps ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$user_id    = $args[1];
					$post_id    = $args[2];
					$has_access = false;

					if ( $this->post_is_public( $post_id ) ) {
						$allcaps[ $caps[0] ] = true;
						break;
					}

					$access_time = $this->get_user_access_start_time_for_post( $user_id, $post_id, 'purchase' );

					if ( $access_time && current_time( 'timestamp', true ) >= $access_time ) {
						$has_access = true;
					}

					$allcaps[ $caps[0] ] = $has_access;

				break;

				// Editing a rule depends on the rule's content type and related capabilities
				case 'wc_memberships_edit_rule' :

					$user_id  = $args[1];
					$rule_id  = $args[2];
					$can_edit = false;

					$rule = wc_memberships()->get_rules_instance()->get_rule( $rule_id );

					if ( $rule ) {

						switch ( $rule->get_content_type() ) {

							case 'post_type':

								$post_type = get_post_type_object( $rule->get_content_type_name() );

								if ( ! $post_type ) {
									return false;
								}

								$can_edit = current_user_can( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_others_posts );

							break;

							case 'taxonomy':

								$taxonomy = get_taxonomy( $rule->get_content_type_name() );

								if ( ! $taxonomy ) {
									return false;
								}

								$can_edit = current_user_can( $taxonomy->cap->manage_terms ) && current_user_can( $taxonomy->cap->edit_terms );

							break;

						}

					}

					$allcaps[ $caps[0] ] = $can_edit;

				break;

				case 'wc_memberships_cancel_membership' :
				case 'wc_memberships_renew_membership' :

					list( $cap, $user_id, $user_membership_id ) = $args;

					$user_membership = wc_memberships_get_user_membership( $user_membership_id );

					// complimentary memberships cannot be cancelled or renewed by the user
					$allcaps[ $caps[0] ] = $user_membership && (int) $user_membership->get_user_id() === (int) $user_id && ! $user_membership->has_status( 'complimentary' );

				break;

				// Prevent deleting membership plans with active memberships
				case 'delete_published_membership_plan' :
				case 'delete_published_membership_plans' :

					// This workaround (*hack*, *cough*) allows displaying the trash/delete
					// link on membership plans list table even if the plan has active members
					if ( is_admin() && 'edit.php' == $pagenow && 'wc_membership_plan' == $typenow && empty( $_POST ) ) {
						break;
					}

					$post_id = $args[2];

					$plan = wc_memberships_get_membership_plan( $post_id );

					if ( $plan->has_active_memberships() ) {
						$allcaps[ $caps[0] ] = false;
					}

				break;

			}
		}

		return $allcaps;
	}


	/**
	 * Check if a given post or page is public and not subject to restrictions.
	 *
	 * This can happen either by an except set via post meta on the post,
	 * or if redirection is used and the content restricted page must be shown.
	 *
	 * @since 1.3.0
	 * @param int $post_id ID of a post, page or product.
	 * @return bool
	 */
	protected function post_is_public( $post_id ) {

		$is_public = 'yes' === wc_memberships_get_content_meta( $post_id, '_wc_memberships_force_public', true );

		// If using redirect mode, the redirect page must be made public regardless.
		if (    ! $is_public
		     &&   'page' === get_post_type( $post_id )
		     &&    wc_memberships()->get_frontend_instance()->get_restrictions_instance()->is_restriction_mode( 'redirect' ) ) {

			$is_public = (int) $post_id === wc_memberships()->get_frontend_instance()->get_restrictions_instance()->get_restricted_content_redirect_page_id();
		}

		return $is_public;
	}


	/**
	 * Get user access date for a post
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 * @param int $post_id
	 * @param string $access_type Optional. Defaults to "view". Applies only to products.
	 * @return int|null Timestamp of start time or null if no access
	 */
	public function get_user_access_start_time_for_post( $user_id, $post_id, $access_type = 'view' ) {

		$post_type = get_post_type( $post_id );

		if ( 'product_variation' === $post_type ) {
			$post_type = 'product';
		}

		$rule_type = 'product' === $post_type ? 'product_restriction' : 'content_restriction';

		return $this->get_user_access_start_time( array(
			'rule_type'         => $rule_type,
			'user_id'           => $user_id,
			'content_type'      => 'post_type',
			'content_type_name' => $post_type,
			'object_id'         => $post_id,
			'access_type'       => $access_type,
		) );
	}


	/**
	 * Get user access date for a post type
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 * @param string $post_type
	 * @param string $access_type Optional. Defaults to "view". Applies only to products and variations.
	 * @return int|null Timestamp of start time or null if no access
	 */
	public function get_user_access_start_time_for_post_type( $user_id, $post_type, $access_type = 'view' ) {

		if ( 'product_variation' === $post_type ) {
			$post_type = 'product';
		}

		$rule_type = 'product' === $post_type ? 'product_restriction' : 'content_restriction';

		return $this->get_user_access_start_time( array(
			'rule_type'         => $rule_type,
			'user_id'           => $user_id,
			'content_type'      => 'post_type',
			'content_type_name' => $post_type,
			'access_type'       => $access_type,
		) );
	}


	/**
	 * Get user access date for a taxonomy
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 * @param string $taxonomy
	 * @param string $access_type Optional. Defaults to "view". Applies only to product taxonomies.
	 * @return int|null Timestamp of start time or null if no access
	 */
	public function get_user_access_start_time_for_taxonomy( $user_id, $taxonomy, $access_type = 'view' ) {

		return $this->get_user_access_start_time( array(
			'user_id'           => $user_id,
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
			'access_type'       => $access_type,
		) );
	}


	/**
	 * Get user access date for a taxonomy term
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 * @param string $taxonomy
	 * @param string|int $term
	 * @param string $access_type Optional. Defaults to "view". Applies only to product taxonomy terms.
	 * @return int|null Timestamp of start time or null if no access
	 */
	public function get_user_access_start_time_for_taxonomy_term( $user_id, $taxonomy, $term, $access_type = 'view' ) {

		return $this->get_user_access_start_time( array(
			'user_id'           => $user_id,
			'content_type'      => 'taxonomy',
			'content_type_name' => $taxonomy,
			'object_id'         => $term,
			'access_type'       => $access_type,
		) );
	}


	/**
	 * Get user access date for a piece of content
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $args {
	 *   Optional. An array of arguments.
	 *
	 *   @type string|array $rule_type Optional. Content type. One or more of 'content_restriction' or 'product_restriction'
	 *   @type string $content_type Optional. Content type. One of 'post_type' or 'taxonomy'
	 *   @type string $content_type_name Optional. Content type name. A valid post type or taxonomy name.
	 *   @type string|int $object_id Optional. Post or taxonomy term ID/slug
	 *   @type string $access_type Optional. Defaults to "view". Applies only to products and product taxonomies/terms.
	 * }
	 * @return int|null Timestamp of start time or null if no access
	 */
	public function get_user_access_start_time( $args = array() ) {

		// prepare args
		$args = wp_parse_args( $args, array(
			'rule_type'          => array( 'content_restriction', 'product_restriction' ),
			'user_id'            => get_current_user_id(),
			'content_type'       => null,
			'content_type_name'  => null,
			'object_id'          => null,
			'access_type'        => 'view',
		) );

		if ( is_string( $args['rule_type'] ) ) {
			$args['rule_type'] = (array) $args['rule_type'];
		}

		// use memoization to speed up subsequent checks
		$cache_key = http_build_query( $args );

		if ( ! isset( $this->_user_access_start_time[ $cache_key ] ) ) {

			// set defaults, access is immediate
			$access_time   = current_time( 'timestamp', true );
			$inactive_time = 0;
			$user_id       = $args['user_id'];
			$access_type   = $args['access_type'];

			// get rules args
			$rules_args = $args;
			unset( $rules_args['access_type'], $rules_args['user_id'] );

			$rules = wc_memberships()->get_rules_instance()->get_rules( $rules_args );

			if ( ! empty( $rules ) ) {

				if ( ! in_array( 'product_restriction', $rules_args['rule_type'], true ) ) {

					// if there are no product restriction rules,
					// then we can safely say that access is restricted...
					$access_time = null;

				} else {

					// ...otherwise, we need to check if there are any content restriction rules
					// or any product restriction rules that restrict the queried access type
					foreach ( $rules as $rule ) {

						if ( 'product_restriction' === $rule->get_rule_type() ) {

							// check if the product restriction rule applies
							// to the correct access type
							if ( $access_type === $rule->get_access_type() ) {

								$access_time = null;
								break;
							}

						} else {

							// content restriction rules indicate that access is restricted
							$access_time = null;
							break;
						}
					}
				}

				// if access is restricted, determine if user has access
				// and if he/she has, determine beginning from when
				if ( ! $access_time ) {

					$last_priority = 0;

					foreach ( $rules as $rule ) {

						// by default any rule applies
						$rule_applies = true;

						// check if rule applies to products, based on the access type
						if ( 'product_restriction' === $rule->get_rule_type() ) {

							if ( 'view' === $access_type ) {
								$rule_applies = in_array( $rule->get_access_type(), array( 'view', 'purchase' ), true );
							} else {
								$rule_applies = $access_type === $rule->get_access_type();
							}
						}

						if ( $rule_applies && ( $user_membership = wc_memberships()->get_user_memberships_instance()->get_user_membership( $user_id, $rule->get_membership_plan_id() ) ) ) {

							if (    ( $membership_is_delayed = $user_membership->is_delayed() )
							     || ( $user_membership->is_active() && $user_membership->is_in_active_period() ) ) {

								/**
								 * Filter the rule's content 'access from' time for a user membership
								 *
								 * The 'access from' time is used as the base time for calculating
								 * the access start time for scheduled content
								 *
								 * @since 1.0.0
								 * @param int $from_time Access from time, as a timestamp
								 * @param \WC_Memberships_Membership_Plan_Rule $rule
								 * @param \WC_Memberships_User_Membership $user_membership
								 */
								$from_time = apply_filters( 'wc_memberships_access_from_time', $user_membership->get_start_date( 'timestamp' ), $rule, $user_membership );

								// if there is no time to calculate the access time from,
								// simply use the current time as access start time
								if ( ! $from_time ) {
									$access_time = current_time( 'timestamp', true );
									break;
								}

								$inactive_time    = $membership_is_delayed ? 0 : $user_membership->get_total_inactive_time();
								$rule_access_time = $rule->get_access_start_time( $membership_is_delayed ? wc_memberships_adjust_date_by_timezone( $from_time + $inactive_time, 'timestamp' ) : $from_time + $inactive_time );
								$rule_priority    = $rule->get_priority();

								// - if this rule has higher priority than last rule,
								// override the previous access time
								// - if this has the same priority as the last rule,
								// and grants earlier access, override previous access time
								if (    ( $rule_priority > $last_priority )
								     || ( $rule_priority === $last_priority && ( ! $access_time || $rule_access_time < $access_time ) ) ) {

									$access_time   = $rule_access_time;
									$last_priority = $rule_priority;
								}
							}
						}
					}
				}
			}

			/**
			 * Filter user's access start time to a piece of content
			 *
			 * @since 1.0.0
			 * @param int|null $access_time Access start timestamp or null if no access should be granted
			 * @param array $args {
			 *   An array of arguments.
			 *
			 *   @type string $content_type Content type. One of 'post_type' or 'taxonomy'
			 *   @type string $content_type_name Content type name. A valid post type or taxonomy name.
			 *   @type string|int $object_id Optional. Post or taxonomy term ID/slug
			 *   @type string $access_type
			 * }
			 */
			$access_time = apply_filters( 'wc_memberships_user_object_access_start_time', $access_time, $args );

			$this->_user_access_start_time[ $cache_key ] = is_numeric( $access_time ) ? (int) $access_time + $inactive_time : null;
		}

		return $this->_user_access_start_time[ $cache_key ];
	}


	/**
	 * Check if user can view a post or a product
	 *
	 * @since 1.7.1
	 * @param int $user_id User id
	 * @param int $post_id WP_Post or WC_Product id
	 * @return bool
	 */
	private function user_can_view( $user_id, $post_id ) {

		if ( $this->post_is_public( $post_id ) ) {
			$can_view = true;
		} else {
			if ( 'product' === get_post_type( $post_id ) ) {
				$rules    = wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $post_id );
				$can_view = wc_memberships()->get_rules_instance()->user_has_product_view_access_from_rules( $user_id, $rules, $post_id );
			} else {
				$rules    = wc_memberships()->get_rules_instance()->get_post_content_restriction_rules( $post_id );
				$can_view = wc_memberships()->get_rules_instance()->user_has_content_access_from_rules( $user_id, $rules, $post_id );
			}
		}

		return (bool) $can_view;
	}


	/**
	 * Check if a user can purchase a product
	 *
	 * @since 1.7.1
	 * @param int $user_id User id
	 * @param int $product_id WC_Product id
	 * @return bool
	 */
	private function user_can_purchase( $user_id, $product_id )  {

		if ( $this->post_is_public( $product_id ) ) {
			$can_purchase = true;
		} else {
			$rules        = wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $product_id );
			$can_purchase = wc_memberships()->get_rules_instance()->user_has_product_purchase_access_from_rules( $user_id, $rules, $product_id );
		}

		return (bool) $can_purchase;
	}


	/**
	 * Check if a post (post type or product) is accessible (viewable or purchasable)
	 *
	 * TODO for now $target only supports 'post' => id or 'product' => id  {FN 2016-04-26}
	 * having an array can be more future proof compatible if we decide to check for other content types
	 * such as taxonomies, terms, etc.
	 *
	 * @since 1.4.0
	 * @param int $user_id The user id to check for access
	 * @param string|array $action Type of action(s): 'view', 'purchase' (products only)
	 * @param array $content Associative array of content type and content id to access to
	 * @param int|string $access_time UTC timestamp to compare for content access (optional, defaults to now)
	 * @return bool
	 */
	public function user_can( $user_id, $action, $content, $access_time = '' ) {

		if ( $user_id > 0 && user_can( $user_id, 'manage_woocommerce' ) ) {
			// do not bother further for shop managers
			return true;
		} elseif ( ! $user_id > 0 || ! wc_memberships_is_user_active_member( $user_id ) ) {
			// sanity check: bail out early if we are checking capabilities for an invalid user
			return false;
		} elseif ( empty( $access_time ) ) {
			// default value for start access time is now
			$access_time = current_time( 'timestamp', true );
		}

		$user_can   = false;
		$content_id = reset( $content );

		if ( is_array( $action ) ) {

			$conditions = array();

			foreach ( $action as $capability ) {

				if ( 'view' === $capability ) {
					$user_can = $this->user_can_view( $user_id, $content_id );
				} elseif ( 'purchase' === $capability ) {
					$user_can = $this->user_can_purchase( $user_id, $content_id );
				}

				$conditions[] = $user_can && $access_time >= $this->get_user_access_start_time_for_post( $user_id, $content_id, $capability );
			}

			$user_can = in_array( true, $conditions, true );

		} else {

			if ( 'view' === $action ) {
				$user_can = $this->user_can_view( $user_id, $content_id );
			} elseif ( 'purchase' === $action ) {
				$user_can = $this->user_can_purchase( $user_id, $content_id );
			}

			$user_start_time = $this->get_user_access_start_time_for_post( $user_id, $content_id, $action );

			$user_can = null === $user_start_time || ! $user_can ? false : $access_time >= $user_start_time;
		}

		return (bool) $user_can;
	}


}
