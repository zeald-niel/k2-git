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


if ( ! function_exists( 'wc_memberships_restrict' ) ) {

	/**
	 * Restrict content to specified membership plans
	 *
	 * @since 1.0.0
	 * @param string $content
	 * @param string|int|array $membership_plans Optional: the membership plan or plans to check against,
	 *                                            Accepts a plan slug, id, or an array of slugs or IDs. Default: all plans
	 * @param string $delay
	 * @param bool $exclude_trial
	 */
	function wc_memberships_restrict( $content, $membership_plans = null, $delay = null, $exclude_trial = false ) {

		$has_access   = false;
		$member_since = null;
		$access_time  = null;

		// grant access to super users
		if ( current_user_can( 'wc_memberships_access_all_restricted_content' ) ) {
			$has_access = true;
		}

		// convert to an array in all cases
		$membership_plans = (array) $membership_plans;

		// default to use all plans if no plan is specified
		if ( empty( $membership_plans ) ) {
			$membership_plans = wc_memberships_get_membership_plans();
		}

		foreach ( $membership_plans as $plan_id_or_slug ) {

			$membership_plan = wc_memberships_get_membership_plan( $plan_id_or_slug );

			if ( $membership_plan && wc_memberships_is_user_active_member( get_current_user_id(), $membership_plan->get_id() ) ) {

				$has_access = true;

				if ( ! $delay && ! $exclude_trial ) {
					break;
				}

				// determine the earliest membership for the user
				if ( $user_membership = wc_memberships()->get_user_memberships_instance()->get_user_membership( get_current_user_id(), $membership_plan->get_id() ) ) {

					// create a pseudo-rule to help applying filters
					$rule = new WC_Memberships_Membership_Plan_Rule( array(
						'access_schedule_exclude_trial' => $exclude_trial ? 'yes' : 'no'
					) );

					/** This filter is documented in includes/class-wc-memberships-capabilities.php **/
					$from_time = apply_filters( 'wc_memberships_access_from_time', $user_membership->get_start_date( 'timestamp' ), $rule, $user_membership );

					// if there is no time to calculate the access time from,
					// simply use the current time as access start time
					if ( ! $from_time ) {
						$from_time = current_time( 'timestamp', true );
					}

					if ( null === $member_since || $from_time < $member_since ) {
						$member_since = $from_time;
					}
				}
			}
		}

		// add delay
		if ( $has_access && ( $delay || $exclude_trial ) && $member_since ) {

			$access_time = $member_since;

			// determine access time
			if ( strpos( $delay, 'month' ) !== false ) {

				$parts  = explode( ' ', $delay );
				$amount = isset( $parts[1] ) ? (int) $parts[0] : '';

				$access_time = wc_memberships_add_months_to_timestamp( $member_since, $amount );

			} elseif ( $delay ) {

				$access_time = strtotime( $delay, $member_since );

			}

			// output or show delayed access message
			if ( $access_time <= current_time( 'timestamp', true ) ) {

				echo $content;

			} else {

				$message = __( 'This content is part of your membership, but not yet! You will gain access on {date}', 'woocommerce-memberships' );

				// TODO remove the deprecated filter and notice triggered by version 1.9.0 {FN 2017-04-18}

				// apply the deprecated filter
				if ( has_filter( 'get_content_delayed_message' ) ) {
					/** This filter is documented in includes/frontend/class-wc-memberships-frontend.php **/
					$message = apply_filters( 'get_content_delayed_message', $message, null, $access_time );
					// notify developers that this filter is deprecated
					_deprecated_function( 'The get_content_delayed_message filter', '1.3.1', 'wc_memberships_get_content_delayed_message' );
				}

				/** This filter is documented in includes/frontend/class-wc-memberships-frontend.php **/
				$message = apply_filters( 'wc_memberships_get_content_delayed_message', $message, null, $access_time );
				$message = str_replace( '{date}', date_i18n( wc_date_format(), $access_time ), $message );
				$output  = '<div class="wc-memberships-content-delayed-message">' . $message . '</div>';

				echo $output;

			}

		} elseif ( $has_access ) {

			echo $content;
		}
	}

}


if ( ! function_exists( 'wc_memberships_is_post_content_restricted' ) ) {

	/**
	 * Check if a post/page content is restricted
	 *
	 * @since 1.0.0
	 * @param int|null $post_id Optional, defaults to current post
	 * @return bool
	 */
	function wc_memberships_is_post_content_restricted( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = isset( $post->ID ) ? $post->ID : null;
		}

		$rules = $post_id ? wc_memberships()->get_rules_instance()->get_post_content_restriction_rules( $post_id ) : '';

		return ! empty( $rules );
	}

}


if ( ! function_exists( 'wc_memberships_is_product_viewing_restricted' ) ) {

	/**
	 * Check if viewing a product is restricted
	 *
	 * @since 1.0.0
	 * @param int|null $post_id Optional, defaults to current post
	 * @return bool
	 */
	function wc_memberships_is_product_viewing_restricted( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = isset( $post->ID ) ? $post->ID : null;
		}

		$rules         = $post_id > 0 ? wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $post_id ) : null;
		$is_restricted = false;

		if ( ! empty( $rules ) ) {

			foreach ( $rules as $rule ) {

				if ( 'view' === $rule->get_access_type() ) {
					$is_restricted = true;
				}
			}
		}

		return $is_restricted;
	}

}


if ( ! function_exists( 'wc_memberships_is_product_purchasing_restricted' ) ) {

	/**
	 * Check if purchasing a product is restricted
	 *
	 * @since 1.0.0
	 * @param int|null $post_id Optional, defaults to current post
	 * @return bool
	 */
	function wc_memberships_is_product_purchasing_restricted( $post_id = null ) {

		if ( ! $post_id ) {
			global $post;

			$post_id = isset( $post->ID ) ? $post->ID : null;
		}

		if ( ! $post_id || 'product' !== get_post_type( $post_id ) ) {

			$is_restricted = false;

		} else {

			$rules = wc_memberships()->get_rules_instance()->get_the_product_restriction_rules( $post_id );

			$is_restricted = false;

			if ( ! empty( $rules ) ) {

				foreach ( $rules as $rule ) {

					if ( 'purchase' === $rule->get_access_type() ) {
						$is_restricted = true;
					}
				}
			}
		}

		return $is_restricted;
	}

}


if ( ! function_exists( 'wc_memberships_product_has_member_discount' ) ) {

	/**
	 * Check if the product (or current product) has any member discounts
	 *
	 * @since 1.0.0
	 * @param int|null $product_id Product ID: optional, defaults to current product
	 * @return bool
	 */
	function wc_memberships_product_has_member_discount( $product_id = null ) {

		if ( ! $product_id ) {
			global $product;

			$product_id = $product instanceof WC_Product ? $product->get_id() : null;
		}

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) || wc_memberships_is_product_excluded_from_member_discounts( $product_id ) ) {
			$has_member_discount = false;
		} else {
			$has_member_discount = wc_memberships()->get_rules_instance()->product_has_member_discount( $product_id );
		}

		return $has_member_discount;
	}

}


if ( ! function_exists( 'wc_memberships_is_product_excluded_from_member_discounts' ) ) {

	/**
	 * Check if a product is set to be excluded from member discount rules
	 *
	 * @since 1.7.0
	 * @param int|\WC_Product $product
	 * @return bool If false, discounts may still apply depending on the rules and member status
	 */
	function wc_memberships_is_product_excluded_from_member_discounts( $product ) {
		return wc_memberships()->get_member_discounts_instance()->is_product_excluded_from_member_discounts( $product );
	}

}


if ( ! function_exists( 'wc_memberships_user_has_member_discount' ) ) {

	/**
	 * Check if a user is eligible for member discount for the current product
	 *
	 * @since 1.0.0
	 * @param int|\WC_Product|null $product_id Optional, product id or object, if not set will attempt to get the current one
	 * @param int|\WP_User|null Optional, user to check for (defaults to current logged in user)
	 * @return bool
	 */
	function wc_memberships_user_has_member_discount( $product_id = null, $user_id = null ) {
		return wc_memberships()->get_member_discounts_instance()->user_has_member_discount( $product_id, $user_id );
	}

}


if ( ! function_exists( 'wc_memberships_get_user_access_time' ) ) {

	/**
	 * Get user access start timestamp for a content or product
	 *
	 * Returns the time in local time (according to site timezone)
	 *
	 * TODO for now $target only supports 'post' => id or 'product' => id  {FN 2016-04-26}
	 *
	 * @since 1.4.0
	 * @param int $user_id User to get access time for
	 * @param array $content Associative array of content type and content id to access to
	 * @param string $action Type of access, 'view' or 'purchase' (products only)
	 * @param bool $gmt Whether to return a UTC timestamp (default false, uses site timezone)
	 * @return int|null Timestamp of start access time
	 */
	function wc_memberships_get_user_access_start_time( $user_id, $action, $content, $gmt = false ) {

		$access_time = wc_memberships()->get_capabilities_instance()->get_user_access_start_time_for_post( $user_id, reset( $content ), $action );

		if ( null !== $access_time ) {
			return ! $gmt ? wc_memberships_adjust_date_by_timezone( $access_time, 'timestamp' ) : $access_time;
		}

		return null;
	}

}


if ( ! function_exists( 'wc_memberships_show_product_loop_member_discount_badge' ) ) {

	/**
	 * Get the member discount badge for the loop.
	 *
	 * @since 1.0.0
	 */
	function wc_memberships_show_product_loop_member_discount_badge() {
		wc_get_template( 'loop/member-discount-badge.php' );
	}

}


if ( ! function_exists( 'wc_memberships_show_product_member_discount_badge' ) ) {

	/**
	 * Get the member discount badge for the single product page.
	 *
	 * @since 1.0.0
	 */
	function wc_memberships_show_product_member_discount_badge() {
		wc_get_template( 'single-product/member-discount-badge.php' );
	}

}


if ( ! function_exists( 'wc_memberships_get_member_discount_badge' ) ) {

	/**
	 * Get member discount badge
	 *
	 * @since 1.6.4
	 * @param \WC_Product $product The product object to output a badge for (passed to filter)
	 * @param bool $variation Whether to output a discount badge for a product variation (default false)
	 * @return string
	 */
	function wc_memberships_get_member_discount_badge( $product, $variation = false ) {
		return wc_memberships()->get_member_discounts_instance()->get_member_discount_badge( $product, $variation );
	}

}


if ( ! function_exists( 'wc_memberships_get_member_product_discount' ) ) {

	/**
	 * Get member product discount
	 *
	 * @since 1.4.0
	 * @param \WC_Memberships_User_Membership $user_membership The user membership object
	 * @param int|\WC_Product $product The product object or id to get discount for
	 * @param bool $formatted Whether to return a formatted amount or a numerical string
	 *                        (default false, return a discount as a fixed amount or a percentage amount)
	 * @return string
	 */
	function wc_memberships_get_member_product_discount( $user_membership, $product, $formatted = false ) {

		$plan     = $user_membership->get_plan();
		$discount = '';

		if ( $plan ) {
			$discount = $formatted ? $plan->get_formatted_product_discount( $product ) : $plan->get_product_discount( $product );
		}

		return $discount;
	}

}


if ( ! function_exists( 'wc_memberships_get_members_area_url' ) ) {

	/**
	 * Get the members area URL.
	 *
	 * @since 1.4.0
	 * @param int|\WC_Memberships_Membership_Plan $membership_plan Object or id.
	 * @param string $members_area_section Optional, which section of the members area to point to.
	 * @param int|string $paged Optional, for paged sections.
	 * @return string Unescaped URL.
	 */
	function wc_memberships_get_members_area_url( $membership_plan, $members_area_section = '', $paged = '' ) {

		$my_account_page_id = wc_get_page_id( 'myaccount' );
		$membership_plan_id = is_object( $membership_plan ) ? $membership_plan->get_id() : (int) $membership_plan;

		// Bail out if something is wrong.
		if ( ! $my_account_page_id || ! $membership_plan_id || 0 === $membership_plan_id ) {
			return '';
		}

		// If unspecified, will get the first tab as set in membership plan in admin.
		if ( empty( $members_area_section ) ) {

			$membership_plan = is_int( $membership_plan ) ? wc_memberships_get_membership_plan( $membership_plan_id ) : $membership_plan;

			if ( ! $membership_plan ) {
				return '';
			}

			$plan_sections       = (array) $membership_plan->get_members_area_sections();
			$available_sections  = array_intersect_key( wc_memberships_get_members_area_sections(), array_flip( $plan_sections ) );
			$members_area_section = key( $available_sections );
		}

		if ( ! empty( $paged ) ) {
			$paged = max( absint( $paged ), 1 );
		}

		// Grab the base URL according to rewrite structure used.
		if ( $using_permalinks = get_option( 'permalink_structure' ) ) {
			$my_account_url = wc_get_page_permalink( 'myaccount' );
		} else {
			$my_account_url = get_home_url();
		}

		// Grab any query strings (sometimes set by translation plugins, e.g. ?lang=it).
		$url_pieces     = parse_url( $my_account_url );
		$query_strings  = ! empty( $url_pieces['query'] ) && is_string( $url_pieces['query'] ) ? explode( '&', $url_pieces['query'] ) : array();
		$my_account_url = preg_replace( '/\?.*/', '', $my_account_url );

		// Return an URL according to rewrite structure used:
		if ( $using_permalinks ) {

			$endpoint = get_option( 'woocommerce_myaccount_members_area_endpoint', 'members-area' );

			// Using permalinks:
			// e.g. /my-account/members-area/123/my-membership-content/2
			$url = trailingslashit( $my_account_url ) . $endpoint . '/' . $membership_plan_id . '/' . $members_area_section . '/' . $paged;

		} else {

			// Not using permalinks:
			// e.g. /?page_id=123&members_area=456&members_area_section=my_membership_content&members_area_section_page=2
			$url = add_query_arg(
				array(
					'page_id'                   => $my_account_page_id,
					'members_area'              => $membership_plan_id,
					'members_area_section'      => $members_area_section,
					'members_area_section_page' => $paged,
				),
				$my_account_url
			);
		}

		// Puts back any query arg at the end of the Members Area URL.
		if ( ! empty( $query_strings ) ) {
			foreach ( $query_strings as $query_string ) {
				$arg = explode( '=', $query_string );
				$url = add_query_arg( array( $arg[0] => isset( $arg[1] ) ? $arg[1] : '' ), $url );
			}
		}

		return $url;
	}

}


if ( ! function_exists( 'wc_memberships_get_members_area_action_links' ) ) {

	/**
	 * Get Members Area action links
	 *
	 * @since 1.4.0
	 * @param string $section Members area section to display actions for
	 * @param \WC_Memberships_User_Membership $user_membership
	 * @param \WC_Product|\WP_Post|object $object An object to pass to a filter hook (optional)
	 * @return string Action links HTML
	 */
	function wc_memberships_get_members_area_action_links( $section, $user_membership, $object ) {

		$default_actions = array();
		$object_id       = 0;

		if ( $object instanceof WC_Product ) {
			$object_id = $object->get_id();
		} elseif ( $object instanceof WP_Post || isset( $object->ID ) ) {
			$object_id = $object->ID;
		}

		switch ( $section ) {

			case 'my-memberships' :

				$members_area = $user_membership->get_plan()->get_members_area_sections();

				// Renew: Show only for expired memberships that can be renewed
				if (    $user_membership->is_expired()
				     && $user_membership->can_be_renewed()
				     && current_user_can( 'wc_memberships_renew_membership', $user_membership->get_id() ) ) {

					$default_actions['renew'] = array(
						'url'  => $user_membership->get_renew_membership_url(),
						'name' => __( 'Renew', 'woocommerce-memberships' ),
					);
				}

				// Cancel: Show only for memberships that can be cancelled
				if (    $user_membership->can_be_cancelled()
				     && current_user_can( 'wc_memberships_cancel_membership', $user_membership->get_id() ) ) {

					$default_actions['cancel'] = array(
						'url'  => $user_membership->get_cancel_membership_url(),
						'name' => __( 'Cancel', 'woocommerce-memberships' ),
					);
				}

				// View: Do not show for cancelled, expired, paused memberships,
				// or memberships without a Members Area
				if (    ( ! empty ( $members_area ) && is_array( $members_area ) )
				     && $user_membership->has_status( wc_memberships()->get_user_memberships_instance()->get_active_access_membership_statuses() ) ) {

					$default_actions['view'] = array(
						'url' => wc_memberships_get_members_area_url( $user_membership->get_plan_id(), $members_area[0] ),
						'name' => __( 'View', 'woocommerce-memberships' ),
					);
				}

			break;

			case 'my-membership-content'   :

				if ( ! empty( $object_id ) && wc_memberships_user_can( $user_membership->get_user_id(), 'view', array( 'post' => $object_id ) ) ) {

					$default_actions['view'] = array(
						'url'  => get_permalink( $object_id ),
						'name' => __( 'View', 'woocommerce-memberships' ),
					);
				}

			break;

			case 'my-membership-products'  :
			case 'my-membership-discounts' :

				$can_view_product     = wc_memberships_user_can( $user_membership->get_user_id(), 'view',     array( 'product' => $object_id ) );
				$can_purchase_product = wc_memberships_user_can( $user_membership->get_user_id(), 'purchase', array( 'product' => $object_id ) );

				if ( $can_view_product ) {

					$default_actions['view'] = array(
						'url'  => get_permalink( $object_id ),
						'name' => __( 'View', 'woocommerce-memberships' ),
					);
				}

				if ( $can_view_product && $can_purchase_product ) {

					$default_actions['add-to-cart'] = array(
						'url'	=> $object->add_to_cart_url(),
						'name'	=> $object->add_to_cart_text(),
					);
				}

			break;

		}

		/**
		 * Filter membership actions on My Account and Members Area pages
		 *
		 * @since 1.4.0
		 * @param array $default_actions Associative array of actions
		 * @param \WC_Memberships_User_Membership $user_membership User Membership object
		 * @param \WC_Product|\WP_Post|object $object Current object where the action is run (optional)
		 */
		$actions = apply_filters( "wc_memberships_members_area_{$section}_actions", $default_actions, $user_membership, $object );

		// TODO throw a deprecation notice for now, later remove the deprecated filter by version 2.0.0 or earlier {FN 2017-03-20}
		if ( 'my-memberships' === $section && has_filter( 'wc_memberships_my_account_my_memberships_actions' ) ) {

			_deprecated_function(
				'The "wc_memberships_my_account_my_memberships_actions" filter',
				'1.8.0',
				'"wc_memberships_members_area_my-memberships_actions"'
			);

			/**
			 * Filter membership actions on my account page
			 *
			 * @since 1.0.0
			 * @deprecated since 1.4.0
			 * @param array $actions
			 * @param \WC_Memberships_User_Membership $membership
			 */
			$actions = apply_filters( 'wc_memberships_my_account_my_memberships_actions', $actions, $user_membership );
		}

		$links = '';

		if ( ! empty( $actions ) ) {

			foreach ( $actions as $key => $action ) {

				$links .= '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a> ';
			}
		}

		return $links;
	}

}


if ( ! function_exists( 'wc_memberships_get_members_area_page_links' ) ) {

	/**
	 * Get Members Area pagination links
	 *
	 * @since 1.4.0
	 * @param string $section Members Area section
	 * @param int|\WC_Memberships_Membership_Plan $membership_plan Membership
	 * @param \WP_Query|\WP_Comment_Query $query Current query
	 * @return string HTML or empty output if query is not paged
	 */
	function wc_memberships_get_members_area_page_links( $membership_plan, $section, $query ) {

		$links     = '';
		$max_pages = (int) $query->max_num_pages;

		if ( $max_pages > 1 ) {

			$current_page = (int) $query->get( 'paged' );

			if ( is_numeric( $membership_plan ) ) {
				$membership_plan = wc_memberships_get_membership_plan( (int) $membership_plan );
			}

			if ( $membership_plan ) {

				// l10n for rtl text direction
				$left  = is_rtl() ? 'right' : 'left';
				$right = is_rtl() ? 'left'  : 'right';

				if ( 1 === $current_page ) {
					// first page, show next
					$links .= '<a href="' . esc_url( wc_memberships_get_members_area_url( $membership_plan, $section, 2 ) ). '" class="wc-memberships-members-area-page-link ' . $right . '">' . esc_html__( 'Next &rarr;', 'woocommerce-memberships' ) . '</a>';
				} elseif ( (int) $max_pages === $current_page ) {
					// last page, show prev
					$links .= '<a href="' . esc_url( wc_memberships_get_members_area_url( $membership_plan, $section, $current_page - 1 ) ) . '" class="wc-memberships-members-area-page-link ' . $left . '">' . esc_html__( '&larr; Previous', 'woocommerce-memberships' ) . '</a>';
				} else {
					// in the middle of pages, show both
					$links .= '<a href="' . esc_url( wc_memberships_get_members_area_url( $membership_plan, $section, $current_page - 1 ) ) . '" class="wc-memberships-members-area-page-link ' . $left . '">' . esc_html__( '&larr; Previous', 'woocommerce-memberships' ) . '</a>';
					$links .= '<a href="' . esc_url( wc_memberships_get_members_area_url( $membership_plan, $section, $current_page + 1 ) ) . '" class="wc-memberships-members-area-page-link ' . $right . '">' . esc_html__( 'Next &rarr;', 'woocommerce-memberships' ) . '</a>';
				}
			}
		}

		return $links;
	}

}
