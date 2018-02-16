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
 * Memberships Data Meta Box for all supported post types
 *
 * @since 1.0.0
 */
class WC_Memberships_Meta_Box_Post_Memberships_Data extends WC_Memberships_Meta_Box {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->id      = 'wc-memberships-post-memberships-data';
		$this->screens = array_keys( wc_memberships()->get_admin_instance()->get_valid_post_types_for_content_restriction() );

		parent::__construct();
	}


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Memberships', 'woocommerce-memberships' );
	}


	/**
	 * Get content restriction rules
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] Array of plan rules
	 */
	public function get_content_restriction_rules() {

		$content_restriction_rules = array();

		if ( $this->post instanceof WP_Post ) {

			// get applied restriction rules to pass to HTML view
			$content_restriction_rules = (array) wc_memberships()->get_rules_instance()->get_rules( array(
				'rule_type'         => 'content_restriction',
				'object_id'         => $this->post->ID,
				'content_type'      => 'post_type',
				'content_type_name' => $this->post->post_type,
				'exclude_inherited' => false,
				'plan_status'       => 'any',
			) );

			$membership_plan_options = array_keys( $this->get_membership_plan_options() );
			$membership_plan_id      = array_shift( $membership_plan_options );

			// add empty option to create a HTML template for new rules
			$content_restriction_rules['__INDEX__'] = new WC_Memberships_Membership_Plan_Rule( array(
				'rule_type'          => 'content_restriction',
				'object_ids'         => array( $this->post->ID ),
				'id'                 => '',
				'membership_plan_id' => $membership_plan_id,
				'access_schedule'    => 'immediate',
				'access_type'        => '',
			) );
		}

		return $content_restriction_rules;
	}


	/**
	 * Display the restrictions meta box
	 *
	 * @param \WP_Post $post
	 * @since 1.0.0
	 */
	public function output( WP_Post $post ) {

		$this->post = $post;

		?>
		<h4><?php esc_html_e( 'Content Restriction', 'woocommerce-memberships' ); ?></h4>

		<?php woocommerce_wp_checkbox( array(
			'id'          => '_wc_memberships_force_public',
			'class'       => 'js-toggle-rules',
			'label'       => __( 'Disable restrictions', 'woocommerce-memberships' ),
			'description' => __( 'Check this box if you want to force the content to be public regardless of any restriction rules that may apply now or in the future.', 'woocommerce-memberships' ),
		) ); ?>

		<div class="js-restrictions <?php if ( 'yes' === wc_memberships_get_content_meta( $post, '_wc_memberships_force_public', true ) ) : ?>hide<?php endif; ?>">
			<?php

			// load content restriction rules view
			require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-content-restriction-rules.php' );

			// output content restriction rules view
			$view = new WC_Memberships_Meta_Box_View_Content_Restriction_Rules( $this );
			$view->output();

			$membership_plans = $this->get_available_membership_plans();

			if ( ! empty( $membership_plans ) ) :

				?><p><em><?php esc_html_e( 'Need to add or edit a plan?', 'woocommerce-memberships' ); ?></em> <a target="_blank" href="<?php echo esc_url( admin_url( 'edit.php?post_type=wc_membership_plan' ) ); ?>"><?php esc_html_e( 'Manage Membership Plans', 'woocommerce-memberships' ); ?></a></p><?php

			endif;

			?>

			<h4><?php esc_html_e( 'Custom Restriction Message', 'woocommerce-memberships' ); ?></h4>

			<?php woocommerce_wp_checkbox( array(
				'id'          => '_wc_memberships_use_custom_content_restricted_message',
				'class'       => 'js-toggle-custom-message',
				'label'       => __( 'Use custom message', 'woocommerce-memberships' ),
				'description' => __( 'Check this box if you want to customize the content restricted message for this content.', 'woocommerce-memberships' ),
			) ); ?>

			<div class="js-custom-message-editor-container <?php if ( wc_memberships_get_content_meta( $post->ID, '_wc_memberships_use_custom_content_restricted_message', true ) !== 'yes' ) : ?>hide<?php endif; ?>">
				<?php $message = wc_memberships_get_content_meta( $post->ID, '_wc_memberships_content_restricted_message', true ); ?>
				<p>
					<?php /* translators: %1$s and %2$s placeholders are meant for {products} and {login_url} merge tags */
					printf( __( '%1$s automatically inserts the product(s) needed to gain access. %2$s inserts the URL to my account page. HTML is allowed.', 'woocommerce-memberships' ),
						'<strong><code>{products}</code></strong>',
						'<strong><code>{login_url}</code></strong>'
					); ?>
				</p>
				<?php

				wp_editor( $message, '_wc_memberships_content_restricted_message', array(
					'textarea_rows' => 5,
					'teeny'         => true,
				) );

				?>
			</div>

		</div>
		<?php
	}


	/**
	 * Process and save restriction rules
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function update_data( $post_id, WP_Post $post ) {

		$admin = wc_memberships()->get_admin_instance();

		// update restriction rules
		$admin->update_rules( $post_id, array( 'content_restriction' ), 'post' );
		$admin->update_custom_message( $post_id, array( 'content_restricted' ) );

		wc_memberships_set_content_meta(  $post_id, '_wc_memberships_force_public', isset( $_POST[ '_wc_memberships_force_public' ] ) ? 'yes' : 'no' );
	}


}
