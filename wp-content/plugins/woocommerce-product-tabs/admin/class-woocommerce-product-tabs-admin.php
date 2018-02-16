<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://nilambar.net
 * @since      1.0.0
 *
 * @package    Woocommerce_Product_Tabs
 * @subpackage Woocommerce_Product_Tabs/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Product_Tabs
 * @subpackage Woocommerce_Product_Tabs/admin
 * @author     Nilambar Sharma <nilambar@outlook.com>
 */
class Woocommerce_Product_Tabs_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $product_tabs_list;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->product_tabs_list = get_posts(
			array(
				'post_type'      => WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'asc',
				)
			);
		if ( ! empty( $this->product_tabs_list ) ) {
			foreach ($this->product_tabs_list as $key => $t) {
				$this->product_tabs_list[$key]->post_meta = get_post_meta($this->product_tabs_list[$key]->ID);
			}
		}

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {


		$screen = get_current_screen();

		if ( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB == $screen->id ) {
			wp_enqueue_style( $this->plugin_name . '-tab', plugin_dir_url( __FILE__ ) . 'css/tab.css', array(), $this->version, 'all' );
		}

	}

	public function add_tab_meta_boxes(){

		$screens = array( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB );

			foreach ( $screens as $screen ) {

				// Settings Metabox
				add_meta_box(
					WOOCOMMERCE_PRODUCT_TABS_SLUG . '_tab_section',
					__( 'Tab: Settings', 'woocommerce-product-tabs' ),
					array($this,'tab_meta_box_callback'),
					$screen,
					'side'
				);
				// Conditions Metabox
				add_meta_box(
					WOOCOMMERCE_PRODUCT_TABS_SLUG . '_tab_condition_section',
					__( 'Conditions', 'woocommerce-product-tabs' ),
					array($this,'tab_conditions_meta_box_callback'),
					$screen,
					'normal'
				);
			}

	}
	public function tab_meta_box_callback( $post  ){

		$post_id = $post->ID;

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpt_tab_meta_box', 'wpt_meta_box_tab_nonce' );


		$use_default_for_all = get_post_meta( $post->ID, '_wpt_option_use_default_for_all', true );
		echo '<p><label for="_wpt_option_use_default_for_all"><strong>'.__( 'Use Default for All', 'woocommerce-product-tabs' ). '</strong>' ;
    echo '</label>';
		echo '</p>';
    echo '<input type="checkbox" name="_wpt_option_use_default_for_all" id="_wpt_option_use_default_for_all" value="yes" ';
    checked( 'yes', $use_default_for_all, true );
    echo ' />';
    echo '<span>' . __( 'Check to display default content in all products', 'woocommerce-product-tabs' ) . '</span>';

		$priority = $post->menu_order;
		echo '<p><label for="_wpt_option_priority"><strong>';
        echo __( 'Priority', 'woocommerce-product-tabs' );
		echo '</strong></label></p>';
    echo '<input type="number" name="_wpt_option_priority" id="_wpt_option_priority" value="'.$priority.'" min="0" style="max-width:70px;"/>';
	}

	public function tab_conditions_meta_box_callback(  $post  ){

		$post_id = $post->ID;

		wp_nonce_field( 'wpt_tab_meta_box', 'wpt_meta_box_tab_conditions_nonce' );

		$wpt_conditions_category = array();
		$wpt_conditions_category = get_post_meta( $post_id, '_wpt_conditions_category', true );

    echo '<h4>'.__( 'Product Categories', 'woocommerce-product-tabs' ).'</h4>';
		echo '<p>'.__( 'Select categories if you want to show tab in selected category only. If nothing is selected it will be shown for all categories. ', 'woocommerce-product-tabs' ).'</p>';

		$args = array(
			'walker'     => new Woocommerce_Product_Tabs_Product_Category_Walker(),
			'taxonomy'   => 'product_cat',
			'hide_empty' => 0,
			'title_li'   => '',
			'checked'   => $wpt_conditions_category,
			);

		echo '<ul class="condition-category-list">';
		wp_list_categories($args);
		echo '</ul><!-- .condition-category-list -->';

	}

	public function add_product_meta_boxes(){

		$screens = array( 'product' );

			foreach ( $screens as $screen ) {

				add_meta_box(
					WOOCOMMERCE_PRODUCT_TABS_SLUG . '_meta_section',
					__( 'Custom Product Tabs', 'woocommerce-product-tabs' ),
					array($this,'product_tabs_meta_box_callback'),
					$screen
				);
			}

	}

	public function product_tabs_meta_box_callback( $post ){

		$post_id = $post->ID;

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpt_product_meta_box', 'wpt_meta_box_product_nonce' );

		$required_tabs = $this->product_tabs_list;

		// Check for All flag;
		if ( ! empty( $required_tabs ) && is_array( $required_tabs ) ) {
			foreach ( $required_tabs as $k => $t ) {
				if ( isset( $t->post_meta['_wpt_option_use_default_for_all'] ) ) {
					if ( 'yes' == array_shift($t->post_meta['_wpt_option_use_default_for_all'])) {
						unset($required_tabs[$k]);
					}
				}
			}
		}

		if ( ! empty( $required_tabs ) ) {

			foreach ($required_tabs as $key => $tab) {

				echo '<h3><strong>' . esc_attr($tab->post_title) . '</strong></h3>';
				$tab_value = get_post_meta( $post_id, '_wpt_field_'.$tab->post_name, true );

				$settings = array(
					'textarea_name' => '_wpt_field_'.$tab->post_name,
					'textarea_rows' => 10,
					);
				wp_editor( $tab_value, '_wpt_field_'.$tab->post_name , $settings);
				echo '<br />';

			}

		}
		else {
			?>
			<div>
				<p><?php esc_html_e( 'There is no any custom tab to be set specific to the product.', 'woocommerce-product-tabs' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=woo_product_tab' ) ); ?>" target="_blank"><?php esc_html_e( 'Click here to add custom tabs.', 'woocommerce-product-tabs' ); ?></a>

			</div>
			<?php
		}

	}

	public function save_tab_meta_box_conditions_content( $post_id ){

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpt_meta_box_tab_nonce'] ) ) {
			return;
		}
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpt_meta_box_tab_conditions_nonce'], 'wpt_tab_meta_box' ) ) {
			return;
		}
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB != $_POST['post_type'] ) {
			return;
		}

		$wpt_conditions_category = '';
		if ( isset( $_POST['wpt_product_category'] ) && ! empty( $_POST['wpt_product_category'] ) ) {
			$wpt_conditions_category = $_POST['wpt_product_category'];
		}
		if ( empty( $wpt_conditions_category ) ) {
			delete_post_meta( $post_id, '_wpt_conditions_category' );
			return;
		}
		update_post_meta( $post_id, '_wpt_conditions_category', $wpt_conditions_category );

	}

	public function save_tab_meta_box_content( $post_id ){

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpt_meta_box_tab_nonce'] ) ) {
			return;
		}
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpt_meta_box_tab_nonce'], 'wpt_tab_meta_box' ) ) {
			return;
		}
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB != $_POST['post_type'] ) {
			return;
		}

		// use_default_for_all
		$use_default_for_all = '';
		if (isset($_POST['_wpt_option_use_default_for_all'])) {
			$use_default_for_all = $_POST['_wpt_option_use_default_for_all'];
		}
		if ( ! isset($_POST['_wpt_option_use_default_for_all'] ) ) {
			$use_default_for_all = 'no';
		}
		update_post_meta( $post_id, '_wpt_option_use_default_for_all', $use_default_for_all );

		// priority
		$priority = $_POST['_wpt_option_priority'];
		$priority = absint( $priority );

		global $wpdb;
		$sql = $wpdb->prepare('UPDATE '. $wpdb->posts.' SET `menu_order`=%d WHERE ID=%d',
			$priority,
			$post_id
			);
		$wpdb->query($sql);

	}
	public function save_meta_box_content( $post_id ){

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpt_meta_box_product_nonce'] ) ) {
			return;
		}
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpt_meta_box_product_nonce'], 'wpt_product_meta_box' ) ) {
			return;
		}
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		foreach ( $_POST as $key => $p ) {
			$str = substr($key, 0, 11);
			if ( '_wpt_field_' === $str ) {
				// Update the meta field in the database.
				update_post_meta( $post_id, $key, $p );
			}
		}

	}

	public function content_after_editor(){

		global $post;
		if( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB !=  $post->post_type ){
			return;
		}
		echo sprintf('%s%s%s',
			'<p><strong>',
			__('Default Content will be displayed if nothing is entered in the Product','woocommerce-product-tabs'),
			'</strong></p>'
			);
	}

	public function add_columns_in_tab_listing( $columns ){

		unset($columns['date']);
		$columns['priority']        = __('Priority','woocommerce-product-tabs');
		$columns['default-for-all'] = __('Default for All','woocommerce-product-tabs');
		$columns['tab-key']         = __('Tab Key','woocommerce-product-tabs');

		return $columns;
	}

	public function custom_columns_in_tab_listing( $column, $post_id ){

		$post = get_post($post_id);
		switch ( $column ) {
			case 'priority':
				echo $post->menu_order;
				break;
			case 'tab-key':
				echo '<code>'.$post->post_name.'</code>';
				break;
			case 'default-for-all':
				$flag_default_for_all = get_post_meta( $post_id,'_wpt_option_use_default_for_all', true );
				if ( 'yes' == $flag_default_for_all) {
					echo '<span class="dashicons dashicons-yes"></span>';
				}
				else{
					echo '<span class="dashicons dashicons-no-alt"></span>';
				}
				break;
			default:
			break;
		}

	}

	public function sortable_tab_columns( $columns ){

		$columns['priority'] = 'menu_order';
		return $columns;

	}

	public function tab_post_updated_messages( $messages ){

		$post = get_post();

		$messages['woo_product_tab'] = array(

			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Tab updated.', 'woocommerce-product-tabs' ),
			2 => __( 'Custom field updated.', 'woocommerce-product-tabs' ),
			3 => __( 'Custom field deleted.', 'woocommerce-product-tabs' ),
			4 => __( 'Tab updated.', 'woocommerce-product-tabs' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Tab restored to revision from %s', 'woocommerce-product-tabs' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Tab published.', 'woocommerce-product-tabs' ),
			7 => __( 'Tab saved.', 'woocommerce-product-tabs' ),
			8 => __( 'Tab submitted.', 'woocommerce-product-tabs' ),
			9  => sprintf(
						__( 'Tab scheduled for: <strong>%1$s</strong>.', 'woocommerce-product-tabs' ),
						date_i18n( __( 'M j, Y @ G:i', 'woocommerce-product-tabs' ), strtotime( $post->post_date ) )
					),
			10 => __( 'Tab draft updated.', 'woocommerce-product-tabs' )
			);
		return $messages;

	}

	public function tab_post_row_actions( $actions, $post ){

		if ( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB == $post->post_type && isset( $actions['inline hide-if-no-js'] ) ){
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;

	}

    /**
     * Hide publishing actions.
     *
     * @since 1.0.0
     */
    function hide_publishing_actions() {
        global $post;
        if ( WOOCOMMERCE_PRODUCT_TABS_POST_TYPE_TAB !== $post->post_type ) {
            return;
        }
        ?>
        <style type="text/css">
        #misc-publishing-actions,#minor-publishing-actions{
            display:none;
        }
        </style>
        <?php
        return;
    }




} //end class
