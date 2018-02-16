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
 * View for a purchasing discount rule
 *
 * @since 1.7.0
 */
class WC_Memberships_Meta_Box_View_Purchasing_Discount_Rule extends WC_Memberships_Meta_Box_View {


	/**
	 * HTML Output
	 *
	 * @since 1.7.0
	 * @param array $args
	 */
	public function output( $args = array() ) {

		$index = $this->get_rule_index( $args );

		?>
		<tbody class="rule purchasing-discount-rule purchasing-discount-rule-<?php echo esc_attr( $index ); ?> <?php if ( ! $this->rule->current_user_can_edit() || ! $this->rule->current_context_allows_editing() ) : ?>disabled<?php endif; ?>">

			<tr>

				<th scope="row" class="check-column">
					<p class="form-field">
						<label for="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_checkbox"><?php esc_html_e( 'Select', 'woocommerce-memberships' ); ?>:</label>

						<?php if ( $this->rule->current_user_can_edit() && $this->rule->current_context_allows_editing() ) : ?>

							<input
								type="checkbox"
								id="_purchasing_discount_rules_<?php echo esc_attr( $index ); ?>_checkbox"
							/>

						<?php endif; ?>

						<input
							type="hidden"
							name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][membership_plan_id]"
							value="<?php echo esc_attr( $this->rule->get_membership_plan_id() ); ?>"
						/>
						<input
							type="hidden"
							name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][id]"
							class="js-rule-id"
							value="<?php echo esc_attr( $this->rule->get_id() ); ?>"
						/>
						<input
							type="hidden"
							name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][remove]"
							class="js-rule-remove"
							value=""
						/>

						<?php if ( (int) $this->rule->get_membership_plan_id() !== (int) $this->post->ID && $this->rule->has_objects() ) : ?>

							<?php foreach ( $this->rule->get_object_ids() as $id ) : ?>

								<input
									type="hidden"
									name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][object_ids][]"
									value="<?php echo esc_attr( $id ); ?>"
								/>

							<?php endforeach; ?>

						<?php endif; ?>
					</p>
				</th>

				<?php if ( (int) $this->rule->get_membership_plan_id() === (int) $this->post->ID ) : ?>

					<td class="purchasing-discount-content-type content-type-column">
						<p class="form-field">
							<label for="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_content_type_key"><?php esc_html_e( 'Discount', 'woocommerce-memberships' ); ?>:</label>

							<?php

							// get product restriction access type options
							$purchasing_discount_content_type_options = array(
								'post_types' => array(
									'post_type|product' => get_post_type_object( 'product' ),
								),
								'taxonomies' => array(),
							);

							foreach ( wc_memberships()->get_admin_instance()->get_valid_taxonomies_for_purchasing_discounts() as $taxonomy_name => $taxonomy ) {
								$purchasing_discount_content_type_options['taxonomies'][ 'taxonomy|' . $taxonomy_name ] = $taxonomy;
							}

							?>
							<select
								name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][content_type_key]"
								id="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_content_type_key"
								class="js-content-type"
								<?php if ( ! $this->rule->current_user_can_edit() ) : ?>disabled<?php endif; ?>>

								<?php foreach ( $purchasing_discount_content_type_options['post_types'] as $key => $post_type ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $this->rule->get_content_type_key() ); ?> <?php if ( ! ( current_user_can( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_others_posts ) ) ) : ?>disabled<?php endif; ?>><?php echo esc_html( $post_type->label ); ?></option>
								<?php endforeach; ?>

								<?php foreach ( $purchasing_discount_content_type_options['taxonomies'] as $key => $taxonomy ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $this->rule->get_content_type_key() ); ?> <?php if ( ! ( current_user_can( $taxonomy->cap->manage_terms ) && current_user_can( $taxonomy->cap->edit_terms ) ) ) : ?>disabled<?php endif; ?> ><?php echo esc_html( $taxonomy->label ); ?></option>
								<?php endforeach; ?>

								<?php if ( ! $this->rule->is_new() && ! $this->rule->content_type_exists() ) : ?>
									<option value="<?php echo esc_attr( $this->rule->get_content_type_key() ); ?>" selected><?php echo esc_html( $this->rule->get_content_type_key() ); ?></option>
								<?php endif; ?>

							</select>
						</p>
					</td>

					<td class="purchasing-discount-objects objects-column">
						<p class="form-field">
							<label for="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_object_ids"><?php esc_html_e( 'Title', 'woocommerce-memberships' ); ?>:</label>

							<?php if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) : ?>

								<select
									name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][object_ids][]"
									id="_purchasing_discount_rules_<?php echo esc_attr( $index ); ?>_object_ids"
									class="wc-memberships-object-search js-object-ids"
									style="width: 90%;"
									multiple="multiple"
									data-placeholder="<?php esc_attr_e( 'Search&hellip; or leave blank to apply to all', 'woocommerce-memberships' ); ?>"
									data-action="<?php echo esc_attr( $this->rule->get_object_search_action_name() ); ?>"
									<?php if ( ! $this->rule->current_user_can_edit() ) : ?>disabled<?php endif; ?>>
									<?php if ( $this->rule->has_objects() ) : ?>
										<?php foreach ( $this->rule->get_object_ids() as $object_id ) : ?>
											<?php if ( $this->rule->get_object_label( $object_id ) ) : ?>
												<option value="<?php echo $object_id; ?>" selected><?php echo esc_html( $this->rule->get_object_label( $object_id ) ); ?></option>
											<?php endif; ?>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>

							<?php else : ?>

								<input
									type="hidden"
									name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][object_ids]"
									id="_purchasing_discount_rules_<?php echo esc_attr( $index ); ?>_object_ids"
									class="wc-memberships-object-search js-object-ids"
									style="width: 90%;"
									data-placeholder="<?php esc_attr_e( 'Search&hellip; or leave blank to apply to all', 'woocommerce-memberships' ); ?>"
									data-action="<?php echo esc_attr( $this->rule->get_object_search_action_name() ); ?>"
									data-multiple="true"
									data-selected="<?php
										$json_ids = array();

										if ( $this->rule->has_objects() ) {

											foreach ( $this->rule->get_object_ids() as $object_id ) {

												if ( $this->rule->get_object_label( $object_id ) ) {
													$json_ids[ $object_id ] = wp_kses_post( html_entity_decode( $this->rule->get_object_label( $object_id ) ) );
												}
											}
										}

										echo esc_attr( wp_json_encode( $json_ids ) );?>"
									value="<?php echo esc_attr( implode( ',', array_keys( $json_ids ) ) ); ?>"
									<?php if ( ! $this->rule->current_user_can_edit() ) : ?>disabled<?php endif; ?>
								/>

							<?php endif; ?>

						</p>
					</td>

				<?php else : ?>

					<td class="purchasing-discount-membership-plan membership-plan-column">
						<p class="form-field">
							<label for="_purchasing_discount_rules_<?php echo esc_attr( $index ); ?>_membership_plan_id"><?php _e( 'Plan', 'woocommerce-memberships' ); ?>:</label>

							<select
								name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][membership_plan_id]"
								id="_purchasing_discount_rules_<?php echo esc_attr( $index ); ?>_membership_plan_id"
								<?php if ( ! $this->rule->current_user_can_edit() || ! $this->rule->current_context_allows_editing() ) : ?>disabled<?php endif; ?>>
								<?php foreach ( $this->meta_box->get_membership_plan_options() as $id => $label ) : ?>
									<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $this->rule->get_membership_plan_id() ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
					</td>

				<?php endif; ?>

				<td class="purchasing-discount-type discount-type-column">
					<p class="form-field">
						<label for="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_discount_type"><?php esc_html_e( 'Type', 'woocommerce-memberships' ); ?>:</label>

						<select
							name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][discount_type]"
							id="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_discount_type"
							<?php if ( ! $this->rule->current_user_can_edit() || ! $this->rule->current_context_allows_editing() ) : ?>disabled<?php endif; ?>>
							<?php foreach ( $this->meta_box->get_discount_type_options() as $key => $name ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $this->rule->get_discount_type() ); ?>><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</td>

				<td class="purchasing-discount-amount amount-column">
					<p class="form-field">
						<label for="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_discount_amount"><?php esc_html_e( 'Amount', 'woocommerce-memberships' ); ?>:</label>

						<input
							type="number"
							name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][discount_amount]"
							id="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_discount_amount"
							value="<?php echo esc_attr( $this->rule->get_discount_amount() ); ?>"
							step="0.01"
							min="0"
							<?php if ( ! $this->rule->current_user_can_edit() || ! $this->rule->current_context_allows_editing() ) : ?>disabled<?php endif; ?>
						/>
					</p>
				</td>

				<td class="purchasing-discount-active active-columns">
					<p class="form-field">
						<label for="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_discount_active"><?php esc_html_e( 'Active', 'woocommerce-memberships' ); ?>:</label>
						<input
							type="checkbox"
							name="_purchasing_discount_rules[<?php echo esc_attr( $index ); ?>][active]"
							id="_purchasing_discount_rules<?php echo esc_attr( $index ); ?>_discount_active"
							value="yes"
							<?php checked( $this->rule->is_active(), true ); ?> <?php if ( ! $this->rule->current_user_can_edit() || ! $this->rule->current_context_allows_editing() ) : ?>disabled<?php endif; ?>
						/>
					</p>
				</td>

			</tr>

			<?php if ( ! $this->rule->current_user_can_edit() || ! $this->rule->current_context_allows_editing() ) : ?>

				<tr class="disabled-notice">

					<td class="check-column"></td>
					<td colspan="<?php echo ( 'wc_membership_plan' === $this->post->post_type ) ? 4 : 3; ?>">

						<?php if ( ! $this->rule->is_new() && ! $this->rule->content_type_exists() ) : ?>

							<span class="description"><?php esc_html_e( 'This rule applies to content generated by a plugin or theme that has been deactivated or deleted.', 'woocommerce-memberships' ); ?></span>

						<?php elseif ( ! $this->rule->current_user_can_edit() ) : ?>

							<span class="description"><?php esc_html_e( 'You are not allowed to edit this rule', 'woocommerce-memberships' ); ?></span>

						<?php else : ?>

							<span class="description"><?php
								/* translators: Placeholders: %1$s - opening HTML <a> tag, %2$s - closing </a> HTML tag */
								printf( esc_html__( 'This rule cannot be edited here because it applies to multiple products. You can %1$sedit this rule on the membership plan screen%2$s.', 'woocommerce-memberships' ),
									'<a href="' . esc_url( get_edit_post_link( $this->rule->get_membership_plan_id() ) ) . '">',
									'</a>'
								); ?></span>

						<?php endif; ?>

					</td>

				</tr>

			<?php endif; ?>

		</tbody>
		<?php
	}


}
