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
 * View for purchasing discount rules table
 *
 * @since 1.7.0
 */
class WC_Memberships_Meta_Box_View_Purchasing_Discount_Rules extends WC_Memberships_Meta_Box_View {


	/**
	 * HTML output
	 *
	 * @since 1.7.0
	 * @param array $args
	 */
	public function output( $args = array() ) {

		?>
		<table class="widefat rules purchasing-discount-rules js-rules">

			<thead>
				<tr>

					<td class="check-column">
						<label class="screen-reader-text" for="product-discount-rules-select-all"><?php esc_html_e( 'Select all', 'woocommerce-memberships' ); ?></label>
						<input
							type="checkbox"
							id="product-discount-rules-select-all"
						>
					</td>

					<?php if ( 'wc_membership_plan' === $this->post->post_type ) : ?>

						<th scope="col" class="purchasing-discount-content-type content-type-column">
							<?php esc_html_e( 'Discount', 'woocommerce-memberships' ); ?>
						</th>

						<th scope="col" class="purchasing-discount-objects objects-column">
							<?php esc_html_e( 'Title', 'woocommerce-memberships' ); ?>
							<?php echo wc_help_tip( __( 'Search&hellip; or leave blank to apply to all', 'woocommerce-memberships' ) ); ?>
						</th>

					<?php else : ?>

						<th scope="col" class="purchasing-discount-membership-plan membership-plan-column">
							<?php esc_html_e( 'Plan', 'woocommerce-memberships' ); ?>
						</th>

					<?php endif; ?>

					<th scope="col" class="purchasing-discount-discount-type discount-type-column">
						<?php esc_html_e( 'Type', 'woocommerce-memberships' ); ?>
					</th>

					<th scope="col" class="purchasing-discount-discount-amount amount-column">
						<?php esc_html_e( 'Amount', 'woocommerce-memberships' ); ?>
					</th>

					<th scope="col" class="purchasing-discount-active active-column">
						<?php esc_html_e( 'Active', 'woocommerce-memberships' ); ?>
					</th>

				</tr>
			</thead>
			<?php

			// load purchasing discount rule view object
			require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-purchasing-discount-rule.php' );

			// get the purchasing discount rules
			$purchasing_discount_rules = $this->meta_box->get_purchasing_discount_rules();

			// output purchasing discount rule views
			foreach ( $purchasing_discount_rules as $index => $rule ) {

				$view = new WC_Memberships_Meta_Box_View_Purchasing_Discount_Rule( $this->meta_box, $rule );
				$view->output( array( 'index' => $index ) );
			}

			// get available membership plans
			$membership_plans = $this->meta_box->get_available_membership_plans();

			?>
			<tbody class="norules <?php if ( count( $purchasing_discount_rules ) > 1 ) : ?>hide<?php endif; ?>">
				<tr>
					<td colspan="<?php echo ( 'wc_membership_plan' === $this->post->post_type ) ? 6 : 5; ?>">
						<?php

						if ( 'wc_membership_plan' === $this->post->post_type || ! empty( $membership_plans ) ) {
							esc_html_e( 'There are no discounts yet. Click below to add one.', 'woocommerce-memberships' );
						} else {
							/* translators: Placeholder: %s - "Add a membership plan" link */
							printf( __( 'To create member discounts, please %s', 'woocommerce-memberships' ),
								'<a target="_blank" href="' . esc_url( admin_url( 'post-new.php?post_type=wc_membership_plan' ) ) . '">' .
								esc_html_e( 'Add a Membership Plan', 'woocommerce-memberships' ) .
								'</a>.'
							);
						}

						?>
					</td>
				</tr>
			</tbody>

			<?php if ( 'wc_membership_plan' === $this->post->post_type || ! empty( $membership_plans ) ) : ?>

				<tfoot>
					<tr>
						<th colspan="<?php echo ( 'wc_membership_plan' === $this->post->post_type ) ? 6 : 5; ?>">
							<button
								type="button"
								class="button button-primary add-rule js-add-rule">
								<?php esc_html_e( 'Add New Discount', 'woocommerce-memberships' ); ?>
							</button>
							<button
								type="button"
								class="button button-secondary remove-rules js-remove-rules
						        <?php if ( count( $purchasing_discount_rules ) < 2 ) : ?>hide<?php endif; ?>">
								<?php esc_html_e( 'Delete Selected', 'woocommerce-memberships' ); ?>
							</button>
						</th>
					</tr>
				</tfoot>

			<?php endif; ?>

		</table>
		<?php
	}


}
