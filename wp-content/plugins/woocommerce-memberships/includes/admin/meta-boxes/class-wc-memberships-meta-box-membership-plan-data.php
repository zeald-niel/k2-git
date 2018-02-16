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
 * Membership Plan Data Meta Box
 *
 * @since 1.0.0
 */
class WC_Memberships_Meta_Box_Membership_Plan_Data extends WC_Memberships_Meta_Box {


	/**
	 * Constructor
	 *
	 * @since 1.0.1
	 * @see WC_Memberships_Meta_Box::__construct()
	 */
	public function __construct() {

		$this->id       = 'wc-memberships-membership-plan-data';
		$this->priority = 'high';
		$this->screens  = array( 'wc_membership_plan' );

		parent::__construct();

		// handle dismissible admin notices
		$this->add_admin_notices();

		add_action( 'admin_footer', array( $this, 'render_admin_notice_js' ), 20 );
	}


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Membership Plan Data', 'woocommerce-memberships' );
	}


	/**
	 * Get content restriction rules
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] Array of rules
	 */
	public function get_content_restriction_rules() {

		$content_restriction_rules = array();

		if ( $this->membership_plan instanceof WC_Memberships_Membership_Plan ) {

			// get applied content restriction rules
			$content_restriction_rules = $this->membership_plan->get_content_restriction_rules();

			// add empty option to create a HTML template for new rules
			$content_restriction_rules['__INDEX__'] = new WC_Memberships_Membership_Plan_Rule( array(
				'rule_type'                     => 'content_restriction',
				'membership_plan_id'            => $this->post->ID,
				'id'                            => '',
				'content_type'                  => '',
				'content_type_name'             => '',
				'object_ids'                    => array(),
				'access_schedule'               => 'immediate',
				'access_schedule_exclude_trial' => 'no',
			) );
		}

		return $content_restriction_rules;
	}


	/**
	 * Get product restriction rules
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] array of rules
	 */
	public function get_product_restriction_rules() {

		$product_restriction_rules = array();

		if ( $this->membership_plan instanceof WC_Memberships_Membership_Plan ) {

			// get applied product restriction rules
			$product_restriction_rules = $this->membership_plan->get_product_restriction_rules();

			// add empty option to create a HTML template for new rules
			$product_restriction_rules['__INDEX__'] = new WC_Memberships_Membership_Plan_Rule( array(
				'rule_type'                     => 'product_restriction',
				'membership_plan_id'            => $this->post->ID,
				'id'                            => '',
				'content_type'                  => '',
				'content_type_name'             => '',
				'object_ids'                    => array(),
				'access_type'                   => '',
				'access_schedule'               => 'immediate',
				'access_schedule_exclude_trial' => 'no',
			) );
		}

		return $product_restriction_rules;
	}


	/**
	 * Get purchasing discount rules
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] Array of rules
	 */
	public function get_purchasing_discount_rules() {

		$purchasing_discount_rules = array();

		if ( $this->membership_plan instanceof WC_Memberships_Membership_Plan ) {

			// get applied product restriction rules
			$purchasing_discount_rules = $this->membership_plan->get_purchasing_discount_rules();

			// add empty option to create a HTML template for new rules
			$purchasing_discount_rules['__INDEX__'] = new WC_Memberships_Membership_Plan_Rule( array(
				'rule_type'          => 'purchasing_discount',
				'membership_plan_id' => $this->post->ID,
				'id'                 => '',
				'content_type'       => '',
				'content_type_name'  => '',
				'object_ids'         => array(),
				'discount_type'      => '',
				'discount_amount'    => '',
				'active'             => '',
			) );
		}

		return $purchasing_discount_rules;
	}


	/**
	 * Display the membership data meta box
	 *
	 * @param \WP_Post $post
	 * @since 1.0.0
	 */
	public function output( WP_Post $post ) {

		$this->post            = $post;
		$this->membership_plan = $membership_plan = wc_memberships_get_membership_plan( $post );

		?>
		<div class="panel-wrap data">

			<ul class="membership_plan_data_tabs wc-tabs">
				<?php

				/**
				 * Filter membership plan data tabs
				 *
				 * @since 1.0.0
				 * @param array $tabs Associative array of membership plan tabs
				 */
				$membership_plan_data_tabs = apply_filters( 'wc_membership_plan_data_tabs', array(

					'general'             => array(
						'label'  => __( 'General', 'woocommerce-memberships' ),
						'target' => 'membership-plan-data-general',
						'class'  => array( 'active' ),
					),

					'restrict_content'    => array(
						'label'  => __( 'Restrict Content', 'woocommerce-memberships' ),
						'target' => 'membership-plan-data-restrict-content',
					),

					'restrict_products'   => array(
						'label'  => __( 'Restrict Products', 'woocommerce-memberships' ),
						'target' => 'membership-plan-data-restrict-products',
					),

					'purchasing_discounts' => array(
						'label'  => __( 'Purchasing Discounts', 'woocommerce-memberships' ),
						'target' => 'membership-plan-data-purchasing-discounts',
					),

					'members_area'         => array(
						'label'  => __( 'Members Area', 'woocommerce-memberships' ),
						'target' => 'membership-plan-members-area',
					),

					'email_content'        => array(
						'label'  => __( 'Email Content', 'woocommerce-memberships' ),
						'target' => 'membership-plan-email-content',
					),

				) );

				// output the meta box navigation tabs
				foreach ( $membership_plan_data_tabs as $key => $tab ) :

					$class = isset( $tab['class'] ) ? $tab['class'] : array();
					?>
					<li class="<?php echo sanitize_html_class( $key ); ?>_options <?php echo sanitize_html_class( $key ); ?>_tab <?php echo implode( ' ' , array_map( 'sanitize_html_class', $class ) ); ?>">
						<a href="#<?php echo esc_attr( $tab['target'] ); ?>"><span><?php echo esc_html( $tab['label'] ); ?></span></a>
					</li>
					<?php

				endforeach;

				/**
				 * Fires after the membership plan write panel tabs are displayed
				 *
				 * @since 1.0.0
				 */
				do_action( 'wc_membership_plan_write_panel_tabs' );

				?>
			</ul>
			<?php

			if ( ! empty( $membership_plan_data_tabs ) ) {

				// output the individual panels
				foreach ( array_keys( $membership_plan_data_tabs ) as $tab ) {

					$panel = "output_{$tab}_panel";

					if ( method_exists( $this, $panel ) ) {
						$this->$panel( $membership_plan, $post );
					}
				}
			}

			/**
			 * Fires after the membership plan data panels are displayed
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_membership_plan_data_panels' );

			?>
			<div class="clear"></div>
		</div><!-- //.panel-wrap -->
		<?php
	}


	/**
	 * Output the general settings panel
	 *
	 * @see WC_Memberships_Meta_Box_Membership_Plan_Data::output()
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan
	 * @param \WP_Post $post
	 */
	private function output_general_panel( $membership_plan, $post ) {

		?>
		<div id="membership-plan-data-general" class="panel woocommerce_options_panel">

			<div class="options_group">
				<?php // membership plan slug
				woocommerce_wp_text_input( array(
					'id' => 'post_name',
					'label' => __( 'Slug', 'woocommerce-memberships' ),
					'value' => $post->post_name,
				) ); ?>
			</div>

			<div class="options_group">

				<?php $current_access_type = $membership_plan->get_access_method(); ?>

				<p class="form-field plan-access-method-field">
					<label for="_access_method"><?php esc_html_e( 'Grant access upon', 'woocommerce-memberships' ); ?></label>

					<span class="plan-access-method-selectors">
						<?php

						$access_method_options = wc_memberships()->get_plans_instance()->get_membership_plans_access_methods( true );

						foreach ( $access_method_options as $value => $label ) :

							?>
							<label class="label-radio">
								<input
									type="radio"
									name="_access_method"
									class="js-access-method-selector js-access-method-type"
									value="<?php echo esc_attr( $value ); ?>"
									<?php checked( $value, $current_access_type ); ?>
								/> <?php echo esc_html( strtolower( $label ) ); ?>
							</label>
							<?php

						endforeach;

						echo wc_help_tip( __( 'Choose how customers will gain access to this membership plan. Memberships can always be manually assigned.', 'woocommerce-memberships' ) );

						?>
					</span>
				</p>

				<p class="form-field js-show-if-access-method-purchase <?php if ( 'purchase' !== $current_access_type ) : ?>hide<?php endif; ?>">
					<label for="_product_ids"><?php esc_html_e( 'Products', 'woocommerce-memberships' ); ?></label>

					<?php if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) : ?>

						<select
							name="_product_ids[]"
							id="_product_ids"
							class="js-ajax-select-products"
							style="width: 90%;"
							multiple="multiple"
							data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce-memberships' ); ?>">
							<?php $product_ids = $membership_plan->get_product_ids(); ?>
							<?php foreach ( $product_ids as $product_id ) : ?>
								<?php if ( $product = wc_get_product( $product_id ) ) : ?>
									<option value="<?php echo $product_id; ?>" selected><?php echo esc_html( $product->get_formatted_name() ); ?></option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>

					<?php else : ?>

						<input
							type="hidden"
							name="_product_ids"
							id="_product_ids"
							class="js-ajax-select-products"
							style="width: 90%;"
							data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce-memberships' ); ?>"
							data-multiple="true"
							data-selected="<?php
							$product_ids = array_filter( array_map( 'absint', $membership_plan->get_product_ids() ) );
							$json_ids    = array();

							foreach ( $product_ids as $product_id ) {

								$product = wc_get_product( $product_id );

								if ( is_object( $product ) ) {
									$json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name() ) );
								}
							}

							echo esc_attr( wp_json_encode( $json_ids ) ); ?>"
							value="<?php echo esc_attr( implode( ',', array_keys( $json_ids ) ) ); ?>"
						/>

					<?php endif; ?>

					<?php echo wc_help_tip( __( 'Leave empty to only allow members you manually assign.', 'woocommerce-memberships' ) ); ?>
				</p>

			</div>

			<div class="options_group">

				<?php $current_access_length = $membership_plan->get_access_length_type(); ?>

				<p class="form-field plan-access-length-field">
					<label for="_access_length"><?php esc_html_e( 'Membership length', 'woocommerce-memberships' ); ?></label>

					<span class="plan-access-length-selectors">
						<?php

						$access_length_period_toggler_options = wc_memberships()->get_plans_instance()->get_membership_plans_access_length_types( true );

						foreach ( $access_length_period_toggler_options as $value => $label ) :

							?>
							<label class="label-radio">
								<input
									type="radio"
									name="_access_length"
									class="js-access-length-type-selector js-access-length-type"
									value="<?php echo esc_attr( $value ); ?>"
									<?php checked( $value, $current_access_length ); ?>
								/> <?php echo esc_html( strtolower( $label ) ); ?>
							</label>
							<?php

						endforeach;

						echo wc_help_tip( __( 'When does the membership expire?', 'woocommerce-memberships' ) )

						?>
					</span>

					<span class="plan-access-length-specific js-show-if-access-length-type-specific <?php if ( 'specific' !== $current_access_length ) : ?>hide<?php endif;?>">
						<?php

						if ( 'specific' === $current_access_length && $membership_plan->has_access_length() ) {
							$access_length_amount = $membership_plan->get_access_length_amount();
							$access_length_period = $membership_plan->get_access_length_period();
						} else {
							$access_length_amount = 1;
							$access_length_period = '';
						}

						?>
						<span>
							<input
								type="number"
								name="_access_length_amount"
								id="_access_length_amount"
								class="access_length-amount"
								value="<?php echo esc_attr( max( 1, (int) $access_length_amount ) ); ?>"
								min="1"
								step="1"
							/>
							<select
								name="_access_length_period"
								id="_access_length_period"
								class="short access_length-period js-access-length-period-selector">
								<?php foreach ( $this->get_access_period_options() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $access_length_period ); ?>><?php echo esc_html( strtolower( $label ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</span>
					</span>

					<span class="plan-access-length-fixed js-show-if-access-length-type-fixed <?php if ( 'fixed' !== $current_access_length ) : ?>hide<?php endif;?>">
						<?php

						// get saved/default start and end access dates to populate fields
						$current_access_start_date = date( 'Y-m-d', $membership_plan->get_local_access_start_date( 'timestamp' ) );
						$current_access_end_time   = $membership_plan->get_local_access_end_date( 'timestamp' );
						$current_access_end_date   = empty( $current_access_end_time ) ? date( 'Y-m-d', strtotime( 'tomorrow', $membership_plan->get_local_access_start_date( 'timestamp' ) ) ) : date( 'Y-m-d', $current_access_end_time );

						?>
						<span>
							<label for="_access_start_date"><?php esc_html_e( 'Start date', 'woocommerce-memberships' ); ?></label>
							<input
								type="text"
								id="_access_start_date"
							    name="_access_start_date"
							    class="access_length-start-date js-plan-access-set-date"
							    value="<?php echo esc_attr( $current_access_start_date ); ?>"
							><span class="description"><?php echo 'YYYY-MM-DD'; ?></span>
						</span>
						<span>
							<label for="_access_end_date"><?php esc_html_e( 'End date', 'woocommerce-memberships' ); ?></label>
							<input
								type="text"
								id="_access_end_date"
								name="_access_end_date"
								class="access_length-end-date js-plan-access-set-date"
								value="<?php echo esc_attr( $current_access_end_date ); ?>"
							/><span class="description"><?php echo 'YYYY-MM-DD'; ?></span>
						</span>
					</span>

				</p>

			</div>
			<?php

			/**
			 * Fires after the membership plan general data panel is displayed
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_membership_plan_options_membership_plan_data_general' );

			?>
		</div><!-- //#membership-plan-data-general -->
		<?php
	}


	/**
	 * Output the restrict content panel
	 *
	 * @see WC_Memberships_Meta_Box_Membership_Plan_Data::output()
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan
	 * @param \WP_Post $post
	 */
	private function output_restrict_content_panel( $membership_plan, $post ) {

		?>
		<div id="membership-plan-data-restrict-content" class="panel woocommerce_options_panel">
			<div class="table-wrap">
				<?php

				// load content restriction rules view
				require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-content-restriction-rules.php' );

				// output content restriction rules view
				$view = new WC_Memberships_Meta_Box_View_Content_Restriction_Rules( $this );
				$view->output();

				?>
			</div>
			<?php

			if ( $public_posts = wc_memberships()->get_rules_instance()->get_public_posts() ) {
				printf( '<p>' . __( 'These posts are public, and will be excluded from all restriction rules: %s', 'woocommerce-memberships' ) . '<p>', $this->list_post_links( $public_posts ) );
			}

			/**
			 * Fires after the membership plan content restriction panel is displayed
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_membership_plan_options_membership_plan_data_restrict_content' );

			?>
		</div><!-- //#membership-plan-data-restrict-content -->
		<?php
	}


	/**
	 * Output the restrict products panel
	 *
	 * @see WC_Memberships_Meta_Box_Membership_Plan_Data::output()
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan
	 * @param \WP_Post $post
	 */
	private function output_restrict_products_panel( $membership_plan, $post ) {

		?>
		<div id="membership-plan-data-restrict-products" class="panel woocommerce_options_panel">
			<div class="table-wrap">
				<?php

				// load product restriction rules view
				require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-product-restriction-rules.php' );

				// output product restriction rules view
				$view = new WC_Memberships_Meta_Box_View_Product_Restriction_Rules( $this );
				$view->output();

				?>
			</div>
			<?php

			if ( $public_products = wc_memberships()->get_rules_instance()->get_public_products() ) {
				printf( '<p>' . __( 'These products are public, and will be excluded from all restriction rules: %s', 'woocommerce-memberships' ) . '</p>', $this->list_post_links( $public_products ) );
			}

			/**
			 * Fires after the membership plan product restriction panel is displayed
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_membership_plan_options_membership_plan_data_restrict_products' );

			?>
		</div><!-- //#membership-plan-data-restrict-products -->
		<?php
	}


	/**
	 * Output the purchasing discounts panel
	 *
	 * @see WC_Memberships_Meta_Box_Membership_Plan_Data::output()
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan
	 * @param \WP_Post $post
	 */
	private function output_purchasing_discounts_panel( $membership_plan, $post ) {

		?>
		<div id="membership-plan-data-purchasing-discounts" class="panel woocommerce_options_panel">
			<div class="table-wrap">
				<?php

				// load purchasing discounts rules view
				require( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/class-wc-memberships-meta-box-view-purchasing-discount-rules.php' );

				// output purchasing discounts rules view
				$view = new WC_Memberships_Meta_Box_View_Purchasing_Discount_Rules( $this );
				$view->output();

				?>
			</div>
			<?php

			/**
			 * Fires after the membership plan purchasing discounts panel is displayed
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_membership_plan_options_membership_plan_data_purchasing_discounts' );

			?>
		</div><!-- //#membership-plan-data-purchase-discounts -->
		<?php
	}


	/**
	 * Output the members area panel.
	 *
	 * @see \WC_Memberships_Meta_Box_Membership_Plan_Data::output()
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan
	 * @param \WP_Post $post
	 */
	private function output_members_area_panel( $membership_plan, $post ) {

		?>
		<div id="membership-plan-members-area" class="panel woocommerce_options_panel">

			<p><?php esc_html_e( 'The Members Area is a portion of the My Accounts page that shows the member lists of available content, products, discounts, and / or membership notes.', 'woocommerce-memberships' ); ?></p>

			<p class="form-field">
				<label for="_members_area_sections"><?php esc_html_e( 'Include sections for:', 'woocommerce-memberships' ); ?></label>

				<?php

				// get members area sections for the current plan
				$members_area_sections = wc_memberships_get_members_area_sections( $post->ID );

				if ( 'auto-draft' === $post->post_status ) {
					$members_area_selected_sections = array_keys( $members_area_sections );
				} else {
					$members_area_selected_sections = $membership_plan->get_members_area_sections();
				}

				?>
				<select
					name="_members_area_sections[]"
					id="_members_area_sections"
					class="wc-enhanced-select-nostd"
					multiple="multiple"
					data-allow_clear="true"
					data-placeholder="<?php esc_html_e( 'Choose sections for this plan&hellip;', 'woocommerce-memberships' ); ?>"
					style="width: 90%;">
					<?php foreach( $members_area_sections as $section_id => $section_name ) : ?>
						<option value="<?php echo esc_attr( $section_id ); ?>" <?php selected( true, in_array( $section_id, $members_area_selected_sections, true ) ); ?>><?php echo esc_html( $section_name ); ?></option>
					<?php endforeach;  ?>
				</select>
				<?php echo wc_help_tip( __( 'Leave empty to hide members area for this membership.', 'woocommerce-memberships' ) ); ?>

			</p>

			<p><!-- // legend -->

				<?php if ( array_key_exists( 'my-membership-content', $members_area_sections ) ) : ?>
					<em><?php esc_html_e( '"My Content" will show all pages, posts and other content.', 'woocommerce-memberships' ); ?></em><br>
				<?php endif; ?>

				<?php if ( array_key_exists( 'my-membership-products', $members_area_sections ) ) : ?>
					<em><?php esc_html_e( '"My Products" will show products that are viewable or purchaseable.', 'woocommerce-memberships' ); ?></em><br>
				<?php endif; ?>

				<?php if ( array_key_exists( 'my-membership-discounts', $members_area_sections ) ) : ?>
					<em><?php esc_html_e( '"My Discounts" will list products carrying membership discounts.', 'woocommerce-memberships' ); ?></em><br>
				<?php endif; ?>

				<?php if ( array_key_exists( 'my-membership-notes', $members_area_sections ) ) : ?>
					<em><?php esc_html_e( '"Membership Notes" will only display notes that have been emailed to the customer (no internal membership notes).', 'woocommerce-memberships' ); ?></em>
				<?php endif; ?>

			</p>

			<?php

			/**
			 * Fires after the membership plan members area panel is displayed
			 *
			 * @since 1.4.0
			 */
			do_action( 'wc_membership_plan_options_membership_plan_members_area' );

			?>
		</div><!-- //#membership-plan-members-area -->
		<?php
	}


	/**
	 * Output the email content panel
	 *
	 * @see WC_Memberships_Meta_Box_Membership_Plan_Data::output()
	 *
	 * @since 1.7.0
	 * @param \WC_Memberships_Membership_Plan $membership_plan
	 * @param \WP_Post $post
	 */
	private function output_email_content_panel( $membership_plan, $post ) {

		?>
		<div id="membership-plan-email-content" class="panel woocommerce_options_panel">
			<?php $emails  = wc_memberships()->get_emails_instance()->get_email_classes(); ?>
			<?php $enabled = 0; ?>
			<?php foreach ( $emails as $id => $email ) :

				if ( 'WC_Memberships_User_Membership_Note_Email' === $id || ! $email->is_enabled() ) {
					continue;
				}

				$enabled++;
				$email_settings_page_id = strtolower( $id );
				$email_setting_link     = admin_url( "admin.php?page=wc-settings&tab=email&section={$email_settings_page_id}" );

				?>
				<div class="options_group" style="padding: 10px;">
					<h4><?php echo esc_html( $email->get_title() ); ?> <small>(<a href="<?php echo esc_url( $email_setting_link ); ?>"><?php echo strtolower( esc_html__( 'Configure Email', 'woocommerce-memberships' ) ); ?></a>)</small></h4>

					<?php wp_editor( $membership_plan->get_email_content( $id ), $id, array(
						'media_buttons' => false,
						'teeny'         => true,
						'editor_height' => 200,
					) ); ?>

				</div>

			<?php endforeach; ?>

			<?php if ( 0 === $enabled ) : ?>

				<p><em><?php printf( __( 'It looks like you haven\'t enabled any of the Memberships emails. To configure email content for this plan, you need to enable at least one membership email from the %1$sWooCommerce Emails settings%2$s', 'woocommerce-memberships' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ) . '">', '</a>' ); ?></em></p>

			<?php endif; ?>

		</div>
		<?php
	}


	/**
	 * Return a list of edit post links for the provided posts.
	 *
	 * @since 1.8.0
	 * @param \WP_Post[] $posts Array of post objects.
	 * @return string
	 */
	private function list_post_links( $posts ) {

		$post_links = '';

		if ( ! empty( $posts ) ) {

			$items = array();

			foreach ( $posts as $post ) {
				$items[] = '<a href="' . get_edit_post_link( $post->ID ) . '">' . get_the_title( $post->ID ) . '</a>';
			}

			$post_links = wc_memberships_list_items( $items, __( 'and', 'woocommerce-memberships' ) );
		}

		return $post_links;
	}


	/**
	 * Add dismissible admin notices for content & product restriction tabs
	 *
	 * @since 1.7.0
	 */
	private function add_admin_notices() {

		$notice_classes = 'updated force-hide js-memberships-restrict-notice';

		wc_memberships()->get_admin_notice_handler()->add_admin_notice(
			/* translators: %1$s - line break, %2$s - opening <a> link tag, %3$s - closing </a> tag */
			sprintf( __( 'When you add a restriction rule for content, it will no longer be public on your site. By adding a rule for a page, post, or taxonomy, it will become restricted, and can only be accessed by members of this plan, or by members of another plan that grants access to the content.%1$sLearn more about %2$srestriction rules in the documentation%3$s.', 'woocommerce-memberships' ),
				'<br />',
				'<em><a href="https://docs.woocommerce.com/document/woocommerce-memberships-restrict-content/">',
				'</a></em>'
			),
			'restrict-content-notice',
			array(
				'always_show_on_settings' => false,
				'notice_class'            => $notice_classes . ' ' . 'js-memberships-restrict-content-notice',
			)
		);

		wc_memberships()->get_admin_notice_handler()->add_admin_notice(
			/* translators: these %s placeholders consist of pairs of opening a closing <strong> HTML tags highlighting text */
			sprintf( __( 'When you add a %1$sviewing%2$s restriction rule for a product, it will no longer be public on your site, and can only be accessed by members of this plan, or by members of another plan that grants access to the product. By adding a %3$spurchasing%4$s restriction rule, the product can be viewed publicly, but only purchased by members.', 'woocommerce-memberships' ),
				'<strong>', '</strong>',
				'<strong>',	'</strong>'
			),
			'restrict-products-notice',
			array(
				'always_show_on_settings' => false,
				'notice_class'            => $notice_classes . ' ' . 'js-memberships-restrict-products-notice',
			)
		);
	}


	/**
	 * Render admin notices inline javascript
	 *
	 * @internal
	 *
	 * @see \WC_Memberships_Meta_Box_Membership_Plan_Data::add_admin_notices()
	 *
	 * @since 1.0.1
	 */
	public function render_admin_notice_js() {

		// remove force-hide class (which prevents message flicker on page load)
		// and simply hide the hidden notices
		wc_enqueue_js( "
			$( '.js-wc-plugin-framework-admin-notice.force-hide' ).removeClass( 'force-hide' ).hide()
		" );
	}


	/**
	 * Save membership plan data
	 *
	 * @since 1.0.0
	 * @param int $post_id The Membership Plan post id
	 * @param \WP_Post $post The Membership Plan post object
	 */
	public function update_data( $post_id, WP_Post $post ) {

		// get the plan
		$membership_plan = new WC_Memberships_Membership_Plan( $post );
		$access_methods  = wc_memberships()->get_plans_instance()->get_membership_plans_access_methods();

		// save membership plan data
		if (    $membership_plan
		     && isset( $_POST['_access_method'] )
		     && in_array( $_POST['_access_method'], $access_methods, true ) ) {

			// save access method type
			$membership_plan->set_access_method( $_POST['_access_method'] );

			// start off with an unlimited membership, reset meta
			$membership_plan->delete_access_length();
			$membership_plan->delete_access_start_date();
			$membership_plan->delete_access_end_date();

			// save limited membership plan length
			if ( ! empty( $_POST['_access_length'] ) ) {

				if (    'specific' === $_POST['_access_length']
				     && isset( $_POST['_access_length_amount'], $_POST['_access_length_period'] ) ) {

					$access_length = sprintf( '%d %s',
						max( 1, (int) $_POST['_access_length_amount'] ),
						sanitize_text_field( $_POST['_access_length_period']
					) );

					// set period relative from start date
					$membership_plan->set_access_length( $access_length );

				} elseif (    'fixed' === $_POST['_access_length']
				           && isset( $_POST['_access_start_date'], $_POST['_access_end_date'] )
				           && ( $access_start_date = wc_memberships_parse_date( $_POST['_access_start_date'], 'mysql' ) )
				           && ( $access_end_date   = wc_memberships_parse_date( $_POST['_access_end_date'], 'mysql' ) ) ) {

					$timezone   = 'UTC'; // there is no need to use wc_timezone_string();
					$time_start = strtotime( 'today', strtotime( $access_start_date ) );
					$time_end   = strtotime( 'today', strtotime( $access_end_date ) );

					// set start date regardless of membership assignment date
					$membership_plan->set_access_start_date(
						date( 'Y-m-d H:i:s', wc_memberships_adjust_date_by_timezone( $time_start, 'timestamp', $timezone ) )
					);

					// set end date regardless of grant access date
					$membership_plan->set_access_end_date(
						date( 'Y-m-d H:i:s', wc_memberships_adjust_date_by_timezone( $time_end, 'timestamp', $timezone ) )
					);

					// sanity check: start date can't be after end date
					if ( $time_start >= $time_end ) {

						wc_memberships()->get_admin_instance()->get_message_handler()->add_error(
							__( 'You cannot set an access start date after the access end date, or on the same day. The two dates have been set one day apart from each other.', 'woocommerce-memberships' )
						);
					}

					if ( $time_end < strtotime( 'midnight', current_time( 'timestamp', true ) ) ) {

						wc_memberships()->get_admin_instance()->get_message_handler()->add_error(
							__( 'You have chosen an end date that is set in the past. The selected access dates have been saved, but please make sure that this is correct.', 'woocommerce-memberships' )
						);
					}
				}
			}

			// save product ids that may grant access to this membership
			if ( $membership_plan->is_access_method( 'purchase' ) ) {

				if ( ! empty( $_POST['_product_ids'] ) ) {

					$membership_plan->set_product_ids( $_POST['_product_ids'] );

				} else {

					// if purchase method is chosen but no products are specified
					// then this should roll back to manual only type
					$membership_plan->delete_product_ids();
					$membership_plan->set_access_method( 'manual-only' );
				}

			} else {

				// if a new access method is specified,
				// then remove any previously aved product ids to grant access
				$membership_plan->delete_product_ids();
			}
		}

		// save the Members Area sections defined for the current plan
		$members_area_sections = isset( $_POST['_members_area_sections'] ) ? array_map( 'sanitize_key', (array) $_POST['_members_area_sections'] ) : null;

		if ( ! empty( $members_area_sections ) ) {
			$membership_plan->set_members_area_sections( $members_area_sections );
		} else {
			$membership_plan->delete_members_area_sections();
		}

		// update emails content
		if ( isset( $_POST['WC_Memberships_User_Membership_Ending_Soon_Email'] ) ) {
			$membership_plan->set_email_content( 'WC_Memberships_User_Membership_Ending_Soon_Email', wp_kses_post( $_POST['WC_Memberships_User_Membership_Ending_Soon_Email'] ) );
		}
		if ( isset( $_POST['WC_Memberships_User_Membership_Ended_Email'] ) ) {
			$membership_plan->set_email_content( 'WC_Memberships_User_Membership_Ended_Email', wp_kses_post( $_POST['WC_Memberships_User_Membership_Ended_Email'] ) );
		}
		if ( isset( $_POST['WC_Memberships_User_Membership_Renewal_Reminder_Email'] ) ) {
			$membership_plan->set_email_content( 'WC_Memberships_User_Membership_Renewal_Reminder_Email', wp_kses_post( $_POST['WC_Memberships_User_Membership_Renewal_Reminder_Email'] ) );
		}

		// update restriction & discount rules
		wc_memberships()->get_admin_instance()->update_rules( $post_id, array( 'content_restriction', 'product_restriction', 'purchasing_discount' ), 'plan' );

		// perform a check for conflicts between restricted products and products that grant access
		$this->check_for_conflicting_products( $membership_plan );
	}


	/**
	 * Check if there is any product that could grant access among restricted products in plan rules.
	 *
	 * Raises an admin notice message if conflicting products are found.
	 *
	 * @since 1.8.2
	 *
	 * @param WC_Memberships_Membership_Plan $membership_plan
	 */
	private function check_for_conflicting_products( $membership_plan ) {

		if ( $access_products = $membership_plan->get_product_ids() ) {

			add_filter( 'wc_memberships_get_restricted_posts_query_args', array( $this, 'query_product_ids' ), 100 );

			$restricted_products = $membership_plan->get_restricted_products( -1 );
			$restricted_products = $restricted_products instanceof WP_Query ? $restricted_products->get_posts() : $restricted_products;

			remove_filter( 'wc_memberships_get_restricted_posts_query_args', array( $this, 'query_product_ids' ), 100 );

			if ( is_array( $restricted_products ) ) {

				$conflicting_products = array_intersect( $access_products, $restricted_products );

				if ( ! empty( $conflicting_products ) ) {
					wc_memberships()->get_admin_instance()->get_message_handler()->add_error(
						__( 'It looks like that you have restricted one or more products that could grant access to this membership plan: this could make it impossible for shop customers to become members via purchase. Please double check your plan rules and the chosen products that grant access.', 'woocommerce-memberships' )
					);
				}
			}
		}
	}


	/**
	 * Filter query args to get a plan's restricted posts.
	 *
	 * @internal
	 *
	 * @since 1.8.2
	 *
	 * @param array $query_args
	 *
	 * @return array
	 */
	public function query_product_ids( $query_args ) {

		$query_args['fields'] = 'ids';

		return $query_args;
	}


}
