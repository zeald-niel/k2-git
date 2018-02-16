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
 * View for product restriction rules table
 *
 * @since 1.7.0
 */
class WC_Memberships_Meta_Box_View_Product_Restriction_Rules extends WC_Memberships_Meta_Box_View {


	/**
	 * HTML output
	 *
	 * @since 1.7.0
	 * @param array $args
	 */
	public function output( $args = array() ) {

		?>
		<table class="widefat rules product-restriction-rules js-rules">

			<thead>
				<tr>

					<td class="check-column" style="width: 5%;">
						<label class="screen-reader-text" for="product-restriction-rules-select-all"> <?php esc_html_e( 'Select all', 'woocommerce-memberships' ); ?></label>
						<input type="checkbox"
						       id="product-restriction-rules-select-all">
					</td>

					<?php if ( 'wc_membership_plan' === $this->post->post_type ) : ?>

						<th scope="col" class="product-restriction-content-type content-type-column" style="width: 15%;">
							<?php esc_html_e( 'Type', 'woocommerce-memberships' ); ?>
						</th>

						<th scope="col" class="product-restriction-objects objects-column" style="width: 40%;">
							<?php esc_html_e( 'Title', 'woocommerce-memberships' ); ?>
							<?php echo wc_help_tip( __( 'Search&hellip; or leave blank to apply to all', 'woocommerce-memberships' ) ); ?>
						</th>

					<?php else : ?>

						<th scope="col" class="product-restriction-membership-plan membership-plan-column" style="width: 30%;">
							<?php esc_html_e( 'Plan', 'woocommerce-memberships' ); ?>
						</th>

					<?php endif; ?>

					<th scope="col" class="product-restriction-access-type access-type-column" style="width: 15%;">
						<?php esc_html_e( 'Only Members Can', 'woocommerce-memberships' ); ?>
					</th>

					<th scope="col" class="product-restriction-access-schedule access-schedule-column" style="<?php echo 'product' === $this->post->post_type ? 'width: 40%;' : 'width: 25%;'; ?>">
						<?php esc_html_e( 'Accessible', 'woocommerce-memberships' ); ?>
						<?php echo wc_help_tip( __( 'When will members gain access to products?', 'woocommerce-memberships' ) ); ?>
					</th>

				</tr>
			</thead>
			<?php

			// load product restriction rule view object
			require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-product-restriction-rule.php' );

			// get applied product restriction rules
			$product_restriction_rules = $this->meta_box->get_product_restriction_rules();

			// output product restriction rule views
			foreach ( $product_restriction_rules as $index => $rule ) {

				$view = new WC_Memberships_Meta_Box_View_Product_Restriction_Rule( $this->meta_box, $rule );
				$view->output( array( 'index' => $index ) );
			}

			// get available membership plans
			$membership_plans = $this->meta_box->get_available_membership_plans();

			?>
			<tbody class="norules <?php if ( count( $product_restriction_rules ) > 1 ) : ?>hide<?php endif; ?>">
				<tr>
					<td colspan="<?php echo ( 'wc_membership_plan' === $this->post->post_type ) ? 5 : 4; ?>">
						<?php

						if ( 'wc_membership_plan' === $this->post->post_type ) {

							esc_html_e( 'There are no rules yet. Click below to add one.', 'woocommerce-memberships' );

						} else {

							if ( empty( $membership_plans ) ) {
								/* translators: Placeholder: %s outputs "Add a Membership Plan" action link */
								$add_membership_plan_link = '<a target="_blank" href="' . esc_url( admin_url( 'post-new.php?post_type=wc_membership_plan' ) ) . '">' . esc_html__( 'Add a Membership Plan', 'woocommerce-memberships' ) . '</a>';
								printf( __( 'To create restriction rules, please %s', 'woocommerce-memberships' ), $add_membership_plan_link );
							} else {
								esc_html_e( 'This product can be viewed & purchased by all customers. Add a rule to restrict viewing and/or purchasing to members.', 'woocommerce-memberships' );
							}
						}

						?>
					</td>
				</tr>
			</tbody>

			<?php if ( 'wc_membership_plan' === $this->post->post_type || ! empty( $membership_plans ) ) : ?>

				<tfoot>
					<tr>
						<th colspan="<?php echo ( 'wc_membership_plan' === $this->post->post_type ) ? 5 : 4; ?>">
							<button
								type="button"
								class="button button-primary add-rule js-add-rule"><?php esc_html_e( 'Add New Rule', 'woocommerce-memberships' ); ?></button>
							<button
								type="button"
								class="button button-secondary remove-rules js-remove-rules
						        <?php if ( count( $product_restriction_rules ) < 2 ) : ?>hide<?php endif; ?>"><?php esc_html_e( 'Delete Selected', 'woocommerce-memberships' ); ?></button>
						</th>
					</tr>
				</tfoot>

			<?php endif; ?>

		</table>
		<?php
	}


}
