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
 * The Members Area.
 *
 * @since 1.6.0
 */
class WC_Memberships_Members_Area {


	/** @var string The endpoint query var key used by the Members Area */
	private $query_var = '';

	/** @var string The endpoint used by the Members Area */
	private $endpoint = '';


	/**
	 * Members Area constructor.
	 *
	 * The Members Area lists the current customer's Memberships and content information
	 * for each Memberships they have access to on the WooCommerce My Account page.
	 *
	 * We add an endpoint to WooCommerce My Account through a 'members_area' query variable.
	 * This translates as a slug that can be customer defined, just like other slugs
	 * managed by WooCommerce core for the My Account area.
	 *
	 * Unlike other My Account endpoints, the Members Area expects more information coming
	 * from the URL (or via query strings if not using a permalink structure).
	 *
	 * Not just the User Membership needs to be passed (much like an Order ID when
	 * viewing orders) but also the Membership content to display (post content, products,
	 * discounts, membership notes are the default ones). Since the content a plan
	 * discloses access to might be very big to display in a single page we also paginate
	 * it, and we need to pass in the URL a paged number information.
	 *
	 * Another difference with other endpoints, either WC native or introduced by other
	 * plugins, is that the Members Area does not add a navigation tab (introduced in
	 * WooCommerce 2.6).
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		$this->query_var = 'members_area';
		$this->endpoint  = get_option( 'woocommerce_myaccount_members_area_endpoint', 'members-area' );

		// Show memberships on My Account page dashboard.
		add_action( 'woocommerce_before_my_account', array( $this, 'my_account_memberships' ) );

		// Render the Members Area content.
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_6() ) {
			add_action( 'woocommerce_account_members_area_endpoint', array( $this, 'render_members_area_content' ) );
		} else {
			// TODO drop this filter when removing support for WooCommerce 2.5 {FN 2016-12-30}
			add_filter( 'the_content',                               array( $this, 'filter_members_area_content' ) );
		}

		// Filter the breadcrumbs in My Account area.
		add_filter( 'woocommerce_get_breadcrumb', array( $this, 'filter_breadcrumbs' ), 100 );
	}


	/**
	 * Output the customer's Memberships table in My Account page.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function my_account_memberships() {

		$customer_memberships = wc_memberships_get_user_memberships();

		if ( ! empty( $customer_memberships ) ) {

			wc_get_template( 'myaccount/my-memberships.php', array(
				'customer_memberships' => $customer_memberships,
				'user_id'              => get_current_user_id(),
			) );
		}
	}


	/**
	 * Check if we are on the members area endpoint.
	 *
	 * @since 1.7.4
	 * @return bool
	 */
	public function is_members_area() {
		global $wp;

		if ( get_option( 'permalink_structure' ) ) {
			$is_endpoint_url = is_wc_endpoint_url( $this->query_var );
		} else {
			$is_endpoint_url = ! empty( $_GET[ $this->query_var ] ) && is_numeric( $_GET[ $this->query_var ] );
		}

		return $is_endpoint_url && null !== $wp->query_vars ? array_key_exists( $this->query_var, $wp->query_vars ) && is_account_page() : false;
	}


	/**
	 * Get members area query vars.
	 *
	 * @since 1.7.4
	 * @return string[] Array of members area query vars.
	 */
	private function get_members_area_query_vars() {

		$query_vars = array();

		if ( ! get_option( 'permalink_structure' ) ) {
			if ( isset( $_GET['members_area'] ) && is_numeric( $_GET['members_area'] ) ) {
				$query_vars[] = (int) $_GET['members_area'];
			}
			if ( isset( $_GET['members_area_section'] ) ) {
				$query_vars[] = $_GET['members_area_section'];
			}
			if ( isset( $_GET['members_area_section_page'] ) && is_numeric( $_GET['members_area_section_page'] ) ) {
				$query_vars[] = $_GET['members_area_section_page'];
			}
		} else {
			global $wp;
			$query_vars = ! empty( $wp->query_vars[ $this->endpoint ] ) ? explode( '/',  $wp->query_vars[ $this->endpoint ] ) : $query_vars;
		}

		return $query_vars;
	}


	/**
	 * Get the members area current membership plan ID.
	 *
	 * @since 1.7.4
	 * @return int
	 */
	private function get_members_area_membership_plan_id() {

		$query_vars = $this->get_members_area_query_vars();

		return isset( $query_vars[0] ) && is_numeric( $query_vars[0] ) ? $query_vars[0] : 0;
	}


	/**
	 * Get the members area current membership plan to display.
	 *
	 * @since 1.7.4
	 * @return false|WC_Memberships_Integration_Subscriptions_Membership_Plan|WC_Memberships_Membership_Plan
	 */
	private function get_members_area_membership_plan() {
		return wc_memberships_get_membership_plan( $this->get_members_area_membership_plan_id() );
	}


	/**
	 * Get the user membership to display in members area.
	 *
	 * @since 1.7.4
	 * @return false|\WC_Memberships_Integration_Subscriptions_User_Membership|\WC_Memberships_User_Membership
	 */
	private function get_members_area_user_membership() {
		return wc_memberships_get_user_membership( get_current_user_id(), $this->get_members_area_membership_plan_id() );
	}


	/**
	 * Get the members area current section to display.
	 *
	 * @since 1.7.4
	 * @return string
	 */
	private function get_members_area_section() {

		$query_vars = $this->get_members_area_query_vars();

		return ! empty( $query_vars[1] ) ? $query_vars[1] : '';
	}


	/**
	 * Get the members area current page.
	 *
	 * @since 1.7.4
	 * @return int
	 */
	private function get_members_area_section_page() {

		$query_vars = $this->get_members_area_query_vars();

		return ! empty( $query_vars[2] ) ? max( 1, (int) $query_vars[2] ) : 1;
	}


	/**
	 * Filter WooCommerce My Account area breadcrumbs.
	 *
	 * @since 1.6.0
	 * @param array $crumbs WooCommerce My Account breadcrumbs.
	 * @return array
	 */
	public function filter_breadcrumbs( $crumbs ) {
		global $wp;

		// Sanity check to see if we're at the right endpoint:
		if ( isset( $wp->query_vars[ $this->endpoint ] ) && is_account_page() && ( count( $crumbs ) > 0 ) ) {

			// get membership data
			$current_user_id = (int) get_current_user_id();
			$user_membership = wc_memberships_get_user_membership( $current_user_id, (int) $wp->query_vars[ $this->endpoint ] );

			// check if membership exists and the current logged in user is an active or delayed member
			if (    ( $user_membership && ( $current_user_id === (int) $user_membership->get_user_id() ) )
			     && ( wc_memberships_is_user_active_member( $current_user_id, $user_membership->get_plan() ) || wc_memberships_is_user_delayed_member( $current_user_id, $user_membership->get_plan() ) ) ) {

				array_push( $crumbs, array(
					$user_membership->get_plan()->get_name(),
					wc_memberships_get_members_area_url( $user_membership->get_plan() ),
				) );
			}
		}

		return $crumbs;
	}


	/**
	 * Render the members area content.
	 *
	 * @internal
	 *
	 * @since 1.7.4
	 */
	public function render_members_area_content() {

		$the_content = '';

		if ( $this->is_members_area() ) {

			$user_id         = (int) get_current_user_id();
			$user_membership = $this->get_members_area_user_membership();

			// Check if membership exists and the current logged in user
			// is an active or at least a delayed member.
			if (    ( $user_membership && ( $user_id === (int) $user_membership->get_user_id() ) )
			     && ( wc_memberships_is_user_active_member( $user_id, $user_membership->get_plan() ) || wc_memberships_is_user_delayed_member( $user_id, $user_membership->get_plan() ) ) ) {

				// Sections for this membership defined in admin.
				$sections     = (array) $user_membership->get_plan()->get_members_area_sections();
				$members_area = array_intersect_key( wc_memberships_get_members_area_sections(), array_flip( $sections ) );

				// Members Area should have at least one section enabled.
				if ( ! empty( $members_area ) ) {

				    $my_account_page = get_post( wc_get_page_id( 'myaccount' ) );

				    if ( $my_account_page && SV_WC_Plugin_Compatibility::is_wc_version_lt_2_6() ) {
					    $shortcode      = '[' . apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) . ']';
					    $content_pieces = explode( $shortcode, $my_account_page->post_content );
                    }

					$html_before = isset( $content_pieces[0] ) ? $content_pieces[0] : '';
					$html_after  = isset( $content_pieces[1] ) ? $content_pieces[1] : '';

					ob_start();

					echo $html_before;

					// Get the section to display,
					// or use the first designated section as fallback:
					$section = $this->get_members_area_section();
					$section = ! empty( $section ) && array_key_exists( $section, $members_area ) ? $section : $sections[0];
					// Get a paged request for the given section:
					$paged   = $this->get_members_area_section_page();

					?>
					<div
						class="my-membership member-<?php echo esc_attr( $user_id ); ?>"
						id="wc-memberships-members-area"
						data-member="<?php echo esc_attr( $user_id ); ?>"
						data-membership="<?php echo esc_attr( $user_membership->get_plan()->get_id() ); ?>">
						<?php // Members Area navigation tabs:
						wc_get_template( 'myaccount/my-membership-tabs.php', array(
							'members_area_sections' => $members_area,
							'current_section'       => $section,
							'customer_membership'   => $user_membership,
						) ); ?>
						<div
							class="my-membership-section <?php echo sanitize_html_class( $section ); ?>"
							id="wc-memberships-members-area-section"
							data-section="<?php echo esc_attr( $section ); ?>"
							data-page="1">
							<?php // Members Area current section:
							$this->get_template( $section, array(
								'user_membership' => $user_membership,
								'user_id'         => $user_id,
								'paged'           => $paged,
							) ); ?>
						</div>
					</div>
					<?php

                    echo $html_after;

					$the_content = do_shortcode( ob_get_clean() );
				}
			}
		}

		echo $the_content;
	}


	/**
	 * Filter the My Account page content to replace with the Members Area content.
	 *
	 * This is a "the_content" callback workaround for earlier versions of WooCommerce.
	 *
	 * TODO deprecate/remove this callback when support for WooCommerce 2.5 is dropped {FN 2016-12-30}
	 *
	 * @internal
	 *
	 * @since 1.7.4
	 * @param string $the_content HTML.
	 * @return string HTML.
	 */
	public function filter_members_area_content( $the_content = '' ) {
		global $wp_query;

		if ( isset( $wp_query->query_vars['members_area'] ) && is_account_page() ) {

			ob_start();

			$this->render_members_area_content();

			return ob_get_clean();
		}

		return $the_content;
	}


	/**
	 * Load members area templates.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 * @param string $section
	 * @param array $args {
	 *      @type \WC_Memberships_User_Membership $user_membership User Membership object.
	 *      @type int $user_id Member id.
	 *      @type int $paged Optional, pagination.
	 * }
	 */
	public function get_template( $section, $args ) {

		// Bail out: no args, no party.
		if ( empty( $args['user_membership'] ) && empty( $args['user_id'] ) && ( ! $args['user_membership'] instanceof WC_Memberships_User_Membership ) ) {
			return;
		}

		// Optional pagination
		$paged = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;

		if ( 'my-membership-content' === $section ) {

			wc_get_template( 'myaccount/my-membership-content.php', array(
				/* @see \WC_Memberships_User_Membership */
				'customer_membership' => $args['user_membership'],
				/* @see \WC_Memberships_Membership_Plan::get_restricted_content() */
				'restricted_content'  => $args['user_membership']->get_plan()->get_restricted_content( $paged ),
				'user_id'             => $args['user_id'],
			) );

		} elseif ( 'my-membership-products' === $section ) {

			wc_get_template( 'myaccount/my-membership-products.php', array(
				/* @see \WC_Memberships_User_Membership */
				'customer_membership' => $args['user_membership'],
				/* @see \WC_Memberships_Membership_Plan::get_restricted_products() */
				'restricted_products' => $args['user_membership']->get_plan()->get_restricted_products( $paged ),
				'user_id'             => $args['user_id'],
			) );

		} elseif ( 'my-membership-discounts' === $section ) {

			wc_get_template( 'myaccount/my-membership-discounts.php', array(
				/* @see \WC_Memberships_User_Membership */
				'customer_membership' => $args['user_membership'],
				/* @see \WC_Memberships_Membership_Plan::get_discounted_products() */
				'discounted_products' => $args['user_membership']->get_plan()->get_discounted_products( $paged ),
				'user_id'             => $args['user_id'],
			) );

		} elseif ( 'my-membership-notes' === $section ) {

			$dateTime = new DateTime();
			$dateTime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
			$timezone = $dateTime->format( 'T' );

			wc_get_template( 'myaccount/my-membership-notes.php', array(
				/* @see \WC_Memberships_User_Membership */
				'customer_membership' => $args['user_membership'],
				/* @see \WC_Memberships_User_Membership::get_notes() */
				'customer_notes'      => $args['user_membership']->get_notes( 'customer', $paged ),
				'timezone'            => $timezone,
				'user_id'             => $args['user_id'],
			) );

		} else {

			// Allow custom sections if wc_membership_plan_members_area_sections is filtered.
			$located = wc_locate_template( "myaccount/{$section}.php" );

			if ( is_readable( $located ) ) {
				wc_get_template( "myaccount/{$section}.php", $args );
			}
		}
	}


	/**
	 * Filter content for the Members Area page.
	 *
	 * TODO remove this method by version 2.0.0 or before WC 2.8 compatibility release update {FN 2016-12-27}
	 *
	 * @deprecated since 1.7.4
	 * @see \WC_Memberships_Members_Area::render_members_area_content()
	 *
	 * @since 1.6.0
	 * @param string $the_content Page HTML content.
	 * @return string HTML.
	 */
	public function render_member_area_content( $the_content = '' ) {
		_deprecated_function( __CLASS__ . '::render_member_area_content()', '1.7.4', __CLASS__ . '::render_members_area_content()' );
		$this->render_members_area_content();
		return null;
	}


}
