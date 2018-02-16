<?php
/*
Plugin Name: WooCommerce Dropshippers
Plugin URI: http://articnet.jp/
Description: Integrates dropshippers in WooCommerce
Version: 3.0.1
Author: ArticNet LLC.
Author URI: http://articnet.jp/
*/

/**
 * Check if WooCommerce is active
 **/
if ( ! function_exists( 'is_plugin_active_for_network' ) )
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

if ( is_plugin_active_for_network('woocommerce/woocommerce.php') || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function artic_dropshippers_get_post_meta($post_id, $meta_key, $single){
		$metas = get_post_meta($post_id, 'dropshippers', false);
		//var_dump($metas);
		if(empty($metas)){
			return array();
		}
		else{	
			return end($metas);
		}
	}

	if(!class_exists('WooCommerce_Dropshippers'))
	{
		class WooCommerce_Dropshippers
		{
			/**
			 * Construct the plugin object
			 */
			public function __construct()
			{
				// Initialize Settings

				add_action( 'admin_menu', 'dropshipeprs_admin_menu_init' );
				function dropshipeprs_admin_menu_init() {
					add_menu_page( 'Dropshippers', 'Dropshippers', 'manage_woocommerce', 'WooCommerce_Dropshippers', '', 'dashicons-carrot', '55.5000000042' );
				}

				require_once(sprintf("%s/settings.php", dirname(__FILE__)));
				$WooCommerce_Dropshippers_Settings = new WooCommerce_Dropshippers_Settings();
				
			} // END public function __construct
			
			/**
			 * Activate the plugin
			 */
			public static function activate()
			{
				$result = add_role('dropshipper', 'Dropshipper', array(
					'show_dropshipper_widget' => true, //can see widget in dashboard
					'read' => true, // True allows that capability
					'edit_posts' => true,
				));
				if (null !== $result) {
					//echo 'Yay! New role created!';
				} else {
					//echo 'Oh... the dropshipper role already exists.';
				}
				$domain_name =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
				// SET DEFAULTS
				$options = get_option('woocommerce_dropshippers_options'); //first time return false
				if(!$options){
					$options = array(
						'text_string' => 'No',
						'billing_address' => "Admin's Billing Address\nSomewhere, 42\nPlanet Earth\n00000",
						//after initial release
						'can_see_email' => 'Yes',
						'can_see_phone' => 'No',
						//from 1.6
						'company_logo' => '',
						'slip_footer' => '',
						//from 1.7
						'admin_email' => '',
						//from 1.9
						'can_see_email_shipping' => 'Yes',
						//from 2.0
						'can_put_order_to_completed' => 'No',
						'can_add_droshipping_fee' => 'No',
						//from 2.3
						'can_see_customer_order_notes' => 'No',
						//from 2.4
						'show_prices' => 'Yes',
						//from 2.7
						'send_pdf' => 'Yes',
					);
				}
				else{
					// 1.1
					if(! isset($options['can_see_email']))
						$options['can_see_email'] = 'Yes';
					if(! isset($options['can_see_phone']))
						$options['can_see_phone'] = 'No';
					// 1.6
					if(! isset($options['company_logo']))
						$options['company_logo'] = '';
					if(! isset($options['slip_footer']))
						$options['slip_footer'] = '';
					// 1.7
					if(! isset($options['admin_email']))
						$options['admin_email'] = '';
					// 1.9
					if(! isset($options['can_see_email_shipping']))
						$options['can_see_email_shipping'] = 'Yes';
					// 2.0
					if(! isset($options['can_put_order_to_completed']))
						$options['can_put_order_to_completed'] = 'No';
					if(! isset($options['can_add_droshipping_fee']))
						$options['can_add_droshipping_fee'] = 'No';
					// 2.3
					if(! isset($options['can_see_customer_order_notes']))
						$options['can_see_customer_order_notes'] = 'No';
					// 2.4
					if(! isset($options['show_prices']))
						$options['show_prices'] = 'No';
					// 2.7
					if(! isset($options['send_pdf']))
						$options['send_pdf'] = 'Yes';

				}
				update_option('woocommerce_dropshippers_options', $options);
			} // END public static function activate
		
			/**
			 * Deactivate the plugin
			 */		
			public static function deactivate()
			{
				//remove_role('dropshipper');
			} // END public static function deactivate
		} // END class WooCommerce_Dropshippers
	} // END if(!class_exists('WooCommerce_Dropshippers'))

	if(class_exists('WooCommerce_Dropshippers'))
	{
		add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true', 1);

		/** SEND EMAIL TO DROPSHIPPERS **/
		require_once(sprintf("%s/dropshipper-new-order-email.php", dirname(__FILE__)));

		/** DROPSHIPPER ORDER LIST **/
		add_action( 'admin_menu', 'dropshipper_order_list', 42 );

		function dropshipper_order_list() {
			if( ! current_user_can('manage_network') ){
				// Check if Multidrop extension is active
				if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
					add_menu_page( __('Dropshipper Orders','woocommerce-dropshippers'), __('Order list','woocommerce-dropshippers'), 'show_dropshipper_widget', 'dropshipper_order_list_page', 'artic_wcdm_dropshipper_order_list_function', 'dashicons-list-view', '200.42' );
				}
				else{
					add_menu_page( __('Dropshipper Orders','woocommerce-dropshippers'), __('Order list','woocommerce-dropshippers'), 'show_dropshipper_widget', 'dropshipper_order_list_page', 'dropshipper_order_list_function', 'dashicons-list-view', '200.42' );
				}
			}
		}

		function dropshipper_order_list_function() {
			if ( !current_user_can( 'show_dropshipper_widget' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			require_once(sprintf("%s/orders.php", dirname(__FILE__)));
		}

		// Installation and uninstallation hooks
		register_activation_hook(__FILE__, array('WooCommerce_Dropshippers', 'activate'));
		register_deactivation_hook(__FILE__, array('WooCommerce_Dropshippers', 'deactivate'));

		// instantiate the plugin class
		$WooCommerce_Dropshippers = new WooCommerce_Dropshippers();
		
		// Check if plugin exists
		if(isset($WooCommerce_Dropshippers))
		{
			// Add the settings link to the plugins page
			function woocommerce_dropshipper_plugin_settings_link($links)
			{ 
				$settings_link = '<a href="options-general.php?page=WooCommerce_Dropshippers">'. __('Settings','woocommerce-dropshippers') .'</a>';
				array_unshift($links, $settings_link); 
				return $links; 
			}
			$plugin = plugin_basename(__FILE__); 
			add_filter("plugin_action_links_$plugin", 'woocommerce_dropshipper_plugin_settings_link');

			/* USEFUL FUNCTIONS */
			function woocommerce_dropshipper_get_woo_version_number() {
				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugin_folder = get_plugins( '/' . 'woocommerce' );
				$plugin_file = 'woocommerce.php';
				if ( isset( $plugin_folder[$plugin_file]['Version'] ) )
					return $plugin_folder[$plugin_file]['Version'];
				else
					return NULL;
			}

			/* WIDGET */
			// Add the widget for dropshippers
			function woocommerce_dropshipper_dashboard_right_now_function() {
				$current_user = wp_get_current_user()->user_login;
				$product_count = 0;
				$total_sales = 0;
				$orders_processing = 0;
				$orders_completed = 0;
				$table_string = '';
				$query = new WP_Query( array(
						'post_type' => 'product',
						'meta_key' => 'woo_dropshipper',
						'meta_query' => array(
							array(
								'key' => 'woo_dropshipper',
								'value' => $current_user,
								'compare' => '=',
							)
						),
						'posts_per_page' => -1
					)
				);
				// The Loop
				if ( $query->have_posts() ) {
					$product_count = $query->post_count;
					while ( $query->have_posts() ) {
						$variations_string = '<strong>'. __('No Options', 'woocommerce-dropshippers') .'</strong>';
						$query->the_post();
						$price = get_post_meta( get_the_ID(), '_sale_price', true);
						$product_sales = (int)get_post_meta(get_the_ID(), 'total_sales', true);
						$total_sales += $product_sales;
						$prod = wc_get_product(get_the_ID());
						$url = get_permalink(get_the_ID());
						//var_dump($prod->get_attributes());
						$product_type = '';
						if(method_exists('WC_Product_Factory', 'get_product_type')){
							$product_type = WC_Product_Factory::get_product_type(get_the_ID());
						}
						else{
							$product_type = $prod->product_type;
						}
						if($product_type == 'variable'){
							$variations_string = '';
							$attrs = $prod->get_variation_attributes();
							if( is_array( $attrs ) && count( $attrs ) > 0 ) {
								foreach ($attrs as $key => $value) {
									$variations_string .= '<strong>' . $key . '</strong>';
									foreach ($value as $val) {
										$variations_string .= '<br/>&ndash; '. $val;
									}
									$variations_string .= "<br/>\n";
								}
							}
						}
						$table_string .= '<tr class="alternate" style="padding: 4px 7px 2px;">';
						$table_string .= '<td class="column-columnname" style="padding: 4px 7px 2px;"><strong>' . get_the_title() . '</strong><div class="row-actions"><span><a href="'.$url.'">'. __('Product Page', 'woocommerce-dropshippers') .'</a></span></div></td>';
						$table_string .= '<td class="column-columnname" style="padding: 4px 7px 2px;">' . $variations_string . '</td>';
						$table_string .= '<td class="column-columnname" style="padding: 4px 7px 2px;"> x' . $product_sales . '</td>';
						$table_string .= '</tr>';
					}
				} else {
					// no posts found
				}
				/* Restore original Post Data */
				wp_reset_postdata();
				$woo_ver = woocommerce_dropshipper_get_woo_version_number();
				if($woo_ver >= 2.2){
					$query = new WP_Query(
						array(
							'post_type' => 'shop_order',
							'post_status' => array( 'wc-processing', 'wc-completed' ),
							'posts_per_page' => -1
						)
					);
				}
				else{
					$query = new WP_Query(
						array(
							'post_type' => 'shop_order',
							'post_status' => 'publish',
							'posts_per_page' => -1
						)
					);
				}

				// The Loop
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						/* actual product list of the dropshipper */
						$real_products = array();
						$query->the_post();
						$order = new WC_Order(get_the_ID());

						foreach ($order->get_items() as $item) {
							if(get_post_meta( $item["product_id"], 'woo_dropshipper', true) == $current_user){
								array_push($real_products, $item);
								break;
							}
						}
						if( (sizeof($real_products) > 0) && ($order->get_status() == "completed") ){
							$orders_completed++;
						}
						if( (sizeof($real_products) > 0) && ($order->get_status() == "processing") ){
							$orders_processing++;
						}
					}
				}
				else {
					// no posts found
				}
				/* Restore original Post Data */
				wp_reset_postdata();
				
				?>
				<div class="table table_shop_content">
					<p class="sub woocommerce_sub"><?php _e( 'Shop Content','woocommerce-dropshippers'); ?></p>
					<table>
					<tr class="first">
						<td class="first b b-products"><a href="#"><?php echo $product_count; ?></a></td>
						<td class="t products"><a href="#"><?php _e('Products','woocommerce-dropshippers'); ?></a></td>
					</tr>
					<tr class="first">
						<td class="first b b-products"><a href="<?php echo admin_url("admin.php?page=dropshipper_order_list_page") ?>"><?php echo $total_sales; ?></a></td>
						<td class="t products"><a href="<?php echo admin_url("admin.php?page=dropshipper_order_list_page") ?>"><?php _e('Sold','woocommerce-dropshippers'); ?></a></td>
					</tr>
					</table>
				</div>
				<div class="table table_orders">
					<p class="sub woocommerce_sub"><?php _e( 'Orders','woocommerce-dropshippers'); ?></p>
					<table>
					<tr class="first">
						<td class="b b-pending"><a href="<?php echo admin_url("admin.php?page=dropshipper_order_list_page") ?>"><?php echo $orders_processing ?></a></td>
						<td class="last t pending"><a href="<?php echo admin_url("admin.php?page=dropshipper_order_list_page") ?>"><?php _e('Processing','woocommerce-dropshippers'); ?></a></td>
					</tr>
					<tr class="first">
						<td class="b b-completed"><a href="<?php echo admin_url("admin.php?page=dropshipper_order_list_page") ?>"><?php echo $orders_completed; ?></a></td>
						<td class="last t completed"><a href="<?php echo admin_url("admin.php?page=dropshipper_order_list_page") ?>"><?php _e('Completed','woocommerce-dropshippers'); ?></a></td>
					</tr>
					</table>
				</div>
				<div class="table total_orders">
					<p class="sub woocommerce_sub"><?php _e( 'Total Earnings','woocommerce-dropshippers'); ?></p>
					<table>
					<tr class="first">
						<td class="last t"><a href="#"><?php _e('Total','woocommerce-dropshippers'); ?></a></td>
						<td class="b"><a href="#"><?php
							$dropshipper_earning = get_user_meta(get_current_user_id(), 'dropshipper_earnings', true);
							if(!$dropshipper_earning) $dropshipper_earning = 0;
							echo '<span class="artic-toberewritten">'. wc_price((float) $dropshipper_earning) .'</span><span class="artic-tobereconverted" style="display:none;">'. (float) $dropshipper_earning .'</span>';
						?></a></td>
					</tr>
					</table>
				</div>

				<div class="versions"></div>

				<table class="wp-list-table widefat fixed posts" cellspacing="0">
					<thead>
						<tr>
							<th id="co" class="manage-column column-columnname" scope="col"><?php echo __('Product','woocommerce-dropshippers'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php echo __('Options','woocommerce-dropshippers'); ?></th>
							<th width="40" id="columnname" class="manage-column column-columnname" scope="col"><?php echo __('Sold','woocommerce-dropshippers'); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="manage-column column-columnname" scope="col"><?php echo __('Product','woocommerce-dropshippers'); ?></th>
							<th class="manage-column column-columnname" scope="col"><?php echo __('Options','woocommerce-dropshippers'); ?></th>
							<th class="manage-column column-columnname" scope="col"><?php echo __('Sold','woocommerce-dropshippers'); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
							echo $table_string; 
						?>
					</tbody>
				</table>
				<p></p>
				<?php
					$currency = get_user_meta(get_current_user_id(), 'dropshipper_currency', true);
					if(!$currency) $currency = 'USD';
					$cur_symbols = array(
						"USD" => '&#36;',
						"AUD" => '&#36;',
						"BDT" => '&#2547;&nbsp;',
						"BRL" => '&#82;&#36;',
						"BGN" => '&#1083;&#1074;.',
						"CAD" => '&#36;',
						"CLP" => '&#36;',
						"CNY" => '&yen;',
						"COP" => '&#36;',
						"CZK" => '&#75;&#269;',
						"DKK" => '&#107;&#114;',
						"EUR" => '&euro;',
						"HKD" => '&#36;',
						"HRK" => 'Kn',
						"HUF" => '&#70;&#116;',
						"ISK" => 'Kr.',
						"IDR" => 'Rp',
						"INR" => 'Rs.',
						"ILS" => '&#8362;',
						"JPY" => '&yen;',
						"KRW" => '&#8361;',
						"MYR" => '&#82;&#77;',
						"MXN" => '&#36;',
						"NGN" => '&#8358;',
						"NOK" => '&#107;&#114;',
						"NZD" => '&#36;',
						"PHP" => '&#8369;',
						"PLN" => '&#122;&#322;',
						"GBP" => '&pound;',
						"RON" => 'lei',
						"RUB" => '&#1088;&#1091;&#1073;.',
						"SGD" => '&#36;',
						"ZAR" => '&#82;',
						"SEK" => '&#107;&#114;',
						"CHF" => '&#67;&#72;&#70;',
						"TWD" => '&#78;&#84;&#36;',
						"THB" => '&#3647;',
						"TRY" => '&#84;&#76;',
						"VND" => '&#8363;',
					);
				?>
				<script type="text/javascript">
					jQuery.ajax({
						url:"https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.xchange%20where%20pair%20in%20%28%22<?php echo get_woocommerce_currency() . $currency; ?>%22%29&format=json&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&callback=cbfunc",
						dataType: 'jsonp',
						jsonp: 'callback',
						jsonpCallback: 'cbfunc'
					});
					function cbfunc(data) {
						var convRate = data.query.results.rate.Rate;
						var toRewrite = jQuery('.artic-toberewritten');
						jQuery('.artic-tobereconverted').each(function(i,j){
							toRewrite.eq(i).html('<?php echo $cur_symbols[$currency]; ?> '+ (parseFloat(jQuery(j).text())*convRate).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
						});
					}
					Number.prototype.format = function(n, x) {
						var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
						return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
					};
				</script>
				<?php
			} 
			// Create the function use in the action hook
			function artic_woocommerce_dropshippers_add_dashboard_widgets() {
				if (current_user_can('show_dropshipper_widget') && ( (!in_array('administrator', wp_get_current_user()->roles)) && (!is_super_admin()) ) ){
					// Check if Multidrop extension is active
					if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
						wp_add_dashboard_widget('woocommerce_dashboard_right_now', __('WooCommerce Dropshipper Right Now','woocommerce-dropshippers'), 'artic_wcdm_woocommerce_dropshipper_dashboard_right_now_function');
					}
					else{
						wp_add_dashboard_widget('woocommerce_dashboard_right_now', __('WooCommerce Dropshipper Right Now','woocommerce-dropshippers'), 'woocommerce_dropshipper_dashboard_right_now_function');
					}
				}
			}
			// Hoook into the 'wp_dashboard_setup' action to register our other functions
			add_action('wp_dashboard_setup', 'artic_woocommerce_dropshippers_add_dashboard_widgets' );


			/* METABOX */
			function print_dropshipper_list_metabox(){
				global $post;
				$woo_dropshipper = get_post_meta( $post->ID, 'woo_dropshipper', true );
				$dropshipperz = get_users('role=dropshipper');
				?>
				<label for="dropshippers-select"> <?php _e( "Select a Dropshipper",'woocommerce-dropshippers') ?></label>
				<select name="dropshippers-select" id="dropshippers-select" style="width:100%;">
					<?php if($woo_dropshipper == null || $woo_dropshipper == '' || $woo_dropshipper == '--'){ ?>
						<option value="--" selected="selected">-- <?php echo __('No Dropshipper','woocommerce-dropshippers'); ?> --</option>
					<?php } else{ ?>
						<option value="--">-- <?php echo __('No Dropshipper','woocommerce-dropshippers'); ?> --</option>
					<?php } ?>
				<?php
				if( is_array( $dropshipperz ) && count( $dropshipperz ) > 0 ) {
					foreach ($dropshipperz as $drop) {
						if($woo_dropshipper == $drop->user_login){
							echo '<option value="' . $drop->user_login . '" selected="selected">' . ucwords($drop->user_nicename) . '</option>';
						}
						else{
							echo '<option value="' . $drop->user_login . '">' . ucwords($drop->user_nicename) . '</option>';
						}
					}
				}
				?>
				</select> 
				<?php
			}

			add_action( 'add_meta_boxes', 'add_dropshipper_metaboxes' );
			function add_dropshipper_metaboxes() {
				// Check if Multidrop extension is active
				if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
					add_meta_box('wc_dropshippers_location', __('Dropshipper','woocommerce-dropshippers'), 'artic_wcdm_print_dropshipper_list_metabox', 'product', 'side', 'default');
				}
				else{
					add_meta_box('wc_dropshippers_location', __('Dropshipper','woocommerce-dropshippers'), 'print_dropshipper_list_metabox', 'product', 'side', 'default');
				}
			}

			// Check if Multidrop extension is active
			if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'save_post', 'artic_wcdm_save_dropshipper', 10, 2 );
			}
			else{
				add_action( 'save_post', 'save_dropshipper', 10, 2 );
			}
			function save_dropshipper($post_id, $post){
				// Autosave, do nothing
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
					return $post_id;
				// AJAX? Not used here
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) 
					return $post_id;
				// Return if it's a post revision
				if ( false !== wp_is_post_revision( $post_id ) )
					return $post_id;

				/* Get the post type object. */
				$post_type = get_post_type_object( $post->post_type );
				/* Check if the current user has permission to edit the post. */
				if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
					return $post_id;
				/* Get the posted data and sanitize it for use as an HTML class. */
				if(isset( $_POST['dropshippers-select'])){
					$new_meta_value = $_POST['dropshippers-select'];//sanitize_html_class( $_POST['dropshippers-select'] );
					update_post_meta( $post_id, 'woo_dropshipper', $new_meta_value);
				}
			}

			/* ADD DROPSHIPPER COLUMN IN ADMIN ORDERS TABLE */
			function add_dropshippers_column($columns){
				$columns['dropshippers'] = __('Dropshippers','woocommerce-dropshippers');
				return $columns;
			}
			add_filter( 'manage_edit-shop_order_columns', 'add_dropshippers_column', 500 );
			
			function add_dropshippers_values_in_column($column){
				global $post, $the_order;
				$order_number = $post->ID;

				//start editing, I was saving my fields for the orders as custom post meta
				//if you did the same, follow this code
				if ( $column == 'dropshippers' ) {
					$row_dropshppers = artic_dropshippers_get_post_meta($post->ID, 'dropshippers', true);
					if( is_array( $row_dropshppers ) && count( $row_dropshppers ) > 0 ) {
						foreach ($row_dropshppers as $dropuser => $value) {
							$mark_type = 'processing';
							if($value == 'Shipped'){
								$mark_type = 'completed';
							}
							echo '<span class="order_status column-order_status" style="display:inline-block; width:28px"><mark class="'. $mark_type .' tips" data-tip="'. $dropuser .': '. $mark_type .'">'. $mark_type .'</mark></span>';
						}
					}
				}
				//stop editing
			}
			add_action( 'manage_shop_order_posts_custom_column', 'add_dropshippers_values_in_column', 2 );

			/* ADD METABOX WITH DROPSHIPPER STATUSES IN ADMIN ORDERS */
			function print_dropshipper_list_metabox_in_orders(){
				global $post;
				$row_dropshppers = artic_dropshippers_get_post_meta($post->ID, 'dropshippers', true);
				if( is_array( $row_dropshppers ) && count( $row_dropshppers ) > 0 ) {
					foreach ($row_dropshppers as $dropuser => $value) {
						$mydropuser = get_user_by('login', $dropuser);
						if($mydropuser){
							$dropshipper_shipping_info = get_post_meta($post->ID, 'dropshipper_shipping_info_'.$mydropuser->ID, true);
							if(!$dropshipper_shipping_info){
								$dropshipper_shipping_info = array(
									'date' => '-',
									'tracking_number' => '-',
									'shipping_company' => '-',
									'notes' => '-'
								);
							}
							echo '<h2>'. $dropuser .'</h2>'."\n";
							echo '<strong>'. __('Date', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_date">'. (empty($dropshipper_shipping_info['date'])? '-' :$dropshipper_shipping_info['date']) . '</span><br/>' ."\n";
							echo '<strong>'. __('Tracking Number(s)', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_tracking_number">'. (empty($dropshipper_shipping_info['tracking_number'])? '-' : $dropshipper_shipping_info['tracking_number']) . '</span><br/>'."\n";
							echo '<strong>'. __('Shipping Company', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_shipping_company">'. (empty($dropshipper_shipping_info['shipping_company'])? '-' : $dropshipper_shipping_info['shipping_company']) . '</span><br/>'."\n";
							echo '<strong>'. __('Notes', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_notes">'. (empty($dropshipper_shipping_info['notes'])? '-' : $dropshipper_shipping_info['notes']) . '</span><br/>'."\n";
							echo "<hr>\n";
						}
					}
				}
			}

			add_action( 'add_meta_boxes', 'add_dropshipper_metaboxes_in_orders' );
			function add_dropshipper_metaboxes_in_orders() {
				add_meta_box('wpt_dropshipper_list', __('Dropshippers','woocommerce-dropshippers'), 'print_dropshipper_list_metabox_in_orders', 'shop_order', 'side', 'default');
			}

			/* ADD SHIPPED BUTTON IN DROPSHIPPERS ORDERS */
			add_action( 'admin_footer', 'dropshipped_javascript' );
			function dropshipped_javascript() {
				if ( current_user_can( 'show_dropshipper_widget' ) && ( (!in_array('administrator', wp_get_current_user()->roles)) && (!is_super_admin()) ) )  {
			?>
				<script type="text/javascript" >
				function js_dropshipped(my_id) {
					if(confirm("<?php echo __('Are you sure?','woocommerce-dropshippers');?>")){
						var data = {
							action: 'dropshipped',
							id: my_id
						};
						// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
						jQuery.post(ajaxurl, data, function(response) {
							if(response == 'true'){
								jQuery('#mark_dropshipped_' + my_id).after("<?php echo __('Shipped','woocommerce-dropshippers'); ?>");
								jQuery('.tr-order-'+my_id).addClass('is-shipped');
							}
						});
						jQuery('#mark_dropshipped_' + my_id).fadeOut();
					}
					else{
						// do nothing
					}
				}
				</script>
				<?php
				}
			}

			function woocommerce_dropshippers_dropshipped($order_id, $user_id, $user_login){
				global $wpdb;
				if(isset($order_id)){
					$id = intval( $order_id );
					$my_wc_order = new WC_Order($order_id);
					$my_wc_order_number = $my_wc_order->get_order_number();
					$dropshippers = artic_dropshippers_get_post_meta($order_id, 'dropshippers', true);
					$dropshippers[$user_login] = "Shipped";
					$admin_email = get_option('admin_email');
					$options = get_option('woocommerce_dropshippers_options');
					// Check if should set order status completed
					if(isset($options['can_put_order_to_completed']) && $options['can_put_order_to_completed']=='Yes'){
						$can_set_order_shipped = true;
						foreach ($dropshippers as $tmp_drop) {
							if($tmp_drop != 'Shipped'){
								$can_set_order_shipped = false;
								break;
							}
						}
						if($can_set_order_shipped){
							$my_wc_order->update_status('completed');
						}
					}
					$dropshipper_shipping_info = get_post_meta($id, 'dropshipper_shipping_info_'.$user_id, true);
					if(!$dropshipper_shipping_info){
						$dropshipper_shipping_info = array(
							'date' => '',
							'tracking_number' => '',
							'shipping_company' => '',
							'notes' => ''
						);
					}
					update_post_meta($order_id, 'dropshippers', $dropshippers);
					if( isset($options['admin_email']) && (!empty($options['admin_email'])) ){
						$admin_email = $options['admin_email'];
					}
					$domain_name =  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
					
					$mail_headers = array('From: "'. get_option('blogname'). '" <no-reply@'. $domain_name .'>');
					if(!empty($options['admin_email_cc'])){
						 $mail_headers[]= 'Cc: ' . $options['admin_email_cc'] ;
					}

					add_filter( 'wp_mail_content_type', 'dropshippers_set_html_content_type' );
					require_once(WP_PLUGIN_DIR . '/woocommerce/includes/emails/class-wc-email.php');
					require_once(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/class-emogrifier.php');
					$emailer = new WC_Email();
					$emailer_attachments = $emailer->get_attachments();
					$headers = $emailer->get_headers();
					if(!empty($options['admin_email_cc'])){
						if(is_array($headers)){
							$headers[]= 'Cc: ' . $options['admin_email_cc'] ;
						}
						elseif(is_string($headers)){
							$headers .= 'Cc: ' . $options['admin_email_cc'] ."\r\n";
						}
					}

					$emailer->send( $admin_email, str_replace("%NUMBER%",$my_wc_order_number,__("Dropshipper order update %NUMBER%", 'woocommerce-dropshippers')),
						str_replace("%NUMBER%",$my_wc_order_number,str_replace("%NAME%",$user_login,__('The Dropshipper %NAME% has shipped order %NUMBER%', 'woocommerce-dropshippers'))) .
						"<br>\n". __('Date', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['date'] .
						"<br>\n". __('Tracking Number(s)', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['tracking_number'] .
						"<br>\n". __('Shipping Company', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['shipping_company'] .
						"<br>\n". __('Notes', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['notes'],
						$headers,
						$emailer_attachments
					);

					remove_filter( 'wp_mail_content_type', 'dropshippers_set_html_content_type' );
					



					//send the order email to the customer
					if( isset($options['send_tracking_info']) && ($options['send_tracking_info'] == 'Yes') && (!empty($dropshipper_shipping_info['tracking_number'])) ){
						$items = $my_wc_order->get_items();
						$real_items = array();
						// $user_login
						foreach ($items as $item_id => $item) {
							$item['redunfed_items'] = 0;
							$woo_dropshipper = get_post_meta( $item["product_id"], 'woo_dropshipper', true);
							$is_item_for_this_dropshipper == false;

							if(empty($woo_dropshipper)){
								$woo_dropshipper == '';
							}
							if(is_string($woo_dropshipper)){
								if($woo_dropshipper == $user_login){
									$refunded_items = $my_wc_order->get_qty_refunded_for_item($item_id);
									$item['redunfed_items'] = $refunded_items;
									if($item['qty'] - $refunded_items > 0){
										$real_items[] = $item;
									}
								}
							}
							else{
								if(in_array($user_login, $woo_dropshipper)){
									$refunded_items = $my_wc_order->get_qty_refunded_for_item($item_id);
									$item['redunfed_items'] = $refunded_items;
									if($item['qty'] - $refunded_items > 0){
										$real_items[] = $item;
									}
								}
							}
						}

						add_filter( 'wp_mail_content_type', 'dropshippers_set_html_content_type' );
						ob_start();

						?>

						<div style="background-color: #f5f5f5; width: 100%; -webkit-text-size-adjust: none ; margin: 0; padding: 70px  0  70px  0;">
						<table width="100%" cellspacing="0" cellpadding="0" border="0" height="100%">
						<tbody><tr><td valign="top" align="center">
						<table width="600" cellspacing="0" cellpadding="0" border="0" style="-webkit-box-shadow: 0  0  0  3px  rgba; box-shadow: 0  0  0  3px  rgba; -webkit-border-radius: 6px ; border-radius: 6px ; background-color: #fdfdfd; border: 1px  solid  #dcdcdc; -webkit-border-radius: 6px ; border-radius: 6px ;" id="template_container"><tbody><tr><td valign="top" align="center">
						<table width="600" cellspacing="0" cellpadding="0" border="0" bgcolor="#557da1" style="background-color: #557da1; color: #ffffff; -webkit-border-top-left-radius: 6px ; -webkit-border-top-right-radius: 6px ; border-top-left-radius: 6px ; border-top-right-radius: 6px ; border-bottom: 0px; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;" id="template_header"><tbody><tr><td>
							<h1 style="color: #ffffff; margin: 0; padding: 28px  24px; text-shadow: 0  1px  0  #7797b4; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;"><?php echo __('Order Update','woocommerce-dropshippers'); ?></h1>
						</td></tr></tbody></table></td></tr><tr><td valign="top" align="center">
						<table width="600" cellspacing="0" cellpadding="0" border="0" id="template_body">
						<tbody><tr><td valign="top" style="background-color: #fdfdfd; -webkit-border-radius: 6px ; border-radius: 6px ;">
						<table width="100%" cellspacing="0" cellpadding="20" border="0"><tbody><tr><td valign="top">
						<div style="color: #737373; font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"><p><?php echo __('The following products were shipped','woocommerce-dropshippers'); ?></p>
						<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px  solid  #eee;">
						<thead><tr><th style="text-align: left; border: 1px  solid  #eee;"><?php echo __('Product','woocommerce-dropshippers'); ?></th>
						<th style="text-align: left; border: 1px  solid  #eee;"><?php echo __('Quantity','woocommerce-dropshippers'); ?></th>
						</tr></thead><tbody>


						<?php foreach ($real_items as $item) : ?>
						<?php
							if($item['variation_id'] > 0){
								$product_id = $item['variation_id'];
								$product_from_id = new WC_Product_Variation($product_id);
								$SKU = $product_from_id->get_sku();
								if(empty($SKU)){
									$product_from_id = new WC_Product($item['product_id']);
									$SKU = $product_from_id->get_sku();
								}
							}
							else{
								$product_id = $item['product_id'];
								$product_from_id = new WC_Product($product_id);
								$SKU = $product_from_id->get_sku();
							}
							if(empty($SKU)){
								$SKU = '';
							}


							$my_item_post = get_post($item['product_id']);
							$item_meta = '';
						?>
						<tr>
							<td style="text-align: left; vertical-align: middle; border: 1px  solid  #eee; word-wrap: break-word;"><?php
								echo __($my_item_post->post_title) . ' (SKU: '.$SKU.')';

								if($item['variation_id'] != 0){
									if(method_exists($item, 'get_meta_data')){ // new method for WooCommerce 2.7
										foreach ($item->get_meta_data() as $product_meta_key => $product_meta_value) {
											if(!empty($product_meta_value->id)){
												$display_key  = wc_attribute_label( $product_meta_value->key, $product_from_id );
												$item_meta .= '<br/><small>' . $display_key . ': ' . $product_meta_value->value . '</small>' . "\n";
											}
										}
									}
									else{ // old method
										$_product = apply_filters( 'woocommerce_order_item_product', $my_wc_order->get_product_from_item( $item ), $item );
										$item_meta_object = new WC_Order_Item_Meta( $item, $product_from_id );
										if ( $item_meta_object->meta ){
											$item_meta .= '<br/><small>' . nl2br( $item_meta_object->display( true, true ) ) . '</small>' . "\n";
										}
									}
									
									echo $item_meta;
								}
							?></td>
							<td style="text-align: left; vertical-align: middle; border: 1px  solid  #eee;"><span class="amount"><?php echo ($item['qty'] - $item['redunfed_items']); ?></span></td>
						</tr>
						<?php endforeach; ?>


						</tfoot></table>
						</td>
						</tr></tbody></table></td>
						</tr></tbody></table></td>
						</tr><tr><td valign="top" align="center">
						<table width="100%" cellspacing="0" cellpadding="10" border="0" style="border-top: 0px; -webkit-border-radius: 6px; text-align: left; font-size:20px;"><tbody><tr><td valign="top">
						<?php echo
							__('Date', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['date'] .
							"<br>\n". __('Tracking Number(s)', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['tracking_number'] .
							"<br>\n". __('Shipping Company', 'woocommerce-dropshippers') .': '. $dropshipper_shipping_info['shipping_company'];
						?>
						</td></tr></tbody></table>

						<table width="600" cellspacing="0" cellpadding="10" border="0" style="border-top: 0px; -webkit-border-radius: 6px;" id="template_footer"><tbody><tr><td valign="top">
						<table width="100%" cellspacing="0" cellpadding="10" border="0"><tbody><tr><td valign="middle" style="border: 0; color: #99b1c7; font-family: Arial; font-size: 12px; line-height: 125%; text-align: center;" id="credit" colspan="2"><p><?php echo bloginfo('name'); ?></p>
						</td>
						</tr></tbody></table></td>
						</tr></tbody></table></td>
						</tr></tbody></table></td>
						</tr></tbody></table></div>


						<?php
						$email_body = ob_get_clean();

						$emailer = new WC_Email();
						$emailer_attachments = $emailer->get_attachments();
						$headers = $emailer->get_headers();
						$emailer->send($my_wc_order->billing_email, '' . $my_wc_order->get_order_number() . ' – ' . __('Order Update','woocommerce-dropshippers'), $email_body, $headers, $emailer_attachments );
						// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
						remove_filter( 'wp_mail_content_type', 'dropshippers_set_html_content_type' );
					}



					//also delete one time keys for dropshippers
					$one_time_keys = get_option('woocommerce_dropshippers_one_time_keys');
					$new_one_time_keys = array();
					foreach ($one_time_keys as $key => $value) {
						if(($value['user_login'] == $user_login) && ($value['order_id'] == $order_id) ){
							//   ¯\(°_o)/¯
						}
						else{
							$new_one_time_keys[$key] = $value;
						}
					}
					update_option('woocommerce_dropshippers_one_time_keys', $new_one_time_keys);
					return 'true';
				}
				else{
					return 'false';
				}
			}

			add_action('wp_ajax_dropshipped', 'dropshipped_callback');
			function dropshipped_callback() {
				$user = wp_get_current_user();
				echo woocommerce_dropshippers_dropshipped($_POST['id'], get_current_user_id(), $user->user_login, true);
				die(); // this is required to return a proper result
			}


			/* REMOVE ADMIN PANELS */
			function dropshippers_remove_menus () {
				if ( current_user_can( 'show_dropshipper_widget' ) && ( (!in_array('administrator', wp_get_current_user()->roles)) && (!is_super_admin()) ) )  {
					global $menu;
					$allowed = array(__('Dashboard'), __('Profile'));
					end ($menu);
					while (prev($menu)){
						$value = explode(' ',$menu[key($menu)][0]);
						if(!in_array($value[0] != NULL?$value[0]:"" , $allowed)){unset($menu[key($menu)]);}
					}
				}
			}
			add_action('admin_menu', 'dropshippers_remove_menus');

			function dropshippers_disable_dashboard_widgets() {  
				if ( current_user_can( 'show_dropshipper_widget' ) && ( (!in_array('administrator', wp_get_current_user()->roles)) && (!is_super_admin()) ) )  {
					remove_action('welcome_panel', 'wp_welcome_panel');
					remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');

					remove_meta_box('dashboard_right_now', 'dashboard', 'normal');   // Right Now
					remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Recent Comments
					remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');  // Incoming Links
					remove_meta_box('dashboard_plugins', 'dashboard', 'normal');   // Plugins
					remove_meta_box('dashboard_quick_press', 'dashboard', 'side');  // Quick Press
					remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');  // Recent Drafts
					remove_meta_box('dashboard_primary', 'dashboard', 'side');   // WordPress blog
					remove_meta_box('dashboard_secondary', 'dashboard', 'side');   // Other WordPress News
				}
			}  
			add_action('wp_dashboard_setup', 'dropshippers_disable_dashboard_widgets');

			function dropshippers_remove_admin_bar_links() {
				global $wp_admin_bar;
				if ( current_user_can( 'show_dropshipper_widget' ) && ( (!in_array('administrator', wp_get_current_user()->roles)) && (!is_super_admin()) ) )  {
					$wp_admin_bar->remove_menu('updates');		  // Remove the updates link
					$wp_admin_bar->remove_menu('comments');		 // Remove the comments link
					$wp_admin_bar->remove_menu('new-content');	  // Remove the content link
				}
			}
			add_action( 'wp_before_admin_bar_render', 'dropshippers_remove_admin_bar_links' );

			/* ADD DROPSHIPPER'S LIST IN ADMIN MENU */
			add_action('admin_menu', 'register_dropshippers_list_page');
			function register_dropshippers_list_page() {
				require_once(sprintf("%s/import-export.php", dirname(__FILE__)));
				add_submenu_page(
					'WooCommerce_Dropshippers',
					__('Dropshippers list','woocommerce-dropshippers'),
					__('Dropshippers list','woocommerce-dropshippers'),
					'manage_woocommerce',
					'drophippers-list-page',
					'dropshippers_list_page_callback'
				);
			}

			add_action('wp_ajax_dropshippers_admin_save_drop_settings', 'dropshippers_admin_save_drop_setting_callback');
			function dropshippers_admin_save_drop_setting_callback() {
				check_ajax_referer( 'SpaceRubberDuckSave', 'security' );
				$options = get_option('woocommerce_dropshippers_options');

				$user_id = $_POST['id'];
				if(!empty($user_id)){
					if(isset($_POST['dropshipper_paypal_email'])){
						$dropshipper_paypal_email = $_POST['dropshipper_paypal_email'];
						$dropshipper_paypal_email = sanitize_email($dropshipper_paypal_email);
						if(is_email($dropshipper_paypal_email, false)){
							update_user_meta($user_id, 'dropshipper_paypal_email', $dropshipper_paypal_email);
						}
					}
					
					if(isset($_POST['dropshipper_currency'])){
						$dropshipper_currency = $_POST['dropshipper_currency'];
						if(empty($dropshipper_currency)) $dropshipper_currency = 'USD';
						update_user_meta($user_id, 'dropshipper_currency', $dropshipper_currency);
					}
					
					if(isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee']=='Yes'){
						if(isset($_POST['dropshipper_country'])){
							$dropshipper_country = $_POST['dropshipper_country'];
							update_user_meta($user_id, 'dropshipper_country', $dropshipper_country);
						}

						if(isset($_POST['dropshipper_national_shipping_price'])){
							$dropshipper_national_shipping_price = $_POST['dropshipper_national_shipping_price'];
							update_user_meta($user_id, 'national_shipping_price', $dropshipper_national_shipping_price);
						}
						
						if(isset($_POST['dropshipper_international_shipping_price'])){
							$dropshipper_international_shipping_price = $_POST['dropshipper_international_shipping_price'];
							update_user_meta($user_id, 'international_shipping_price', $dropshipper_international_shipping_price);
						}
					}
				}
				die(); // this is required to return a proper result
			}

			function dropshippers_list_page_callback() {
				if( isset($_POST['single-dropshipper-csv-import']) && isset($_POST['security']) && isset($_POST['drop-id']) ){
					if(wp_verify_nonce( $_POST['security'], 'SpaceRubberDuckCSV')) {
						$user_id = $_POST['drop-id'];
						if(isset($_POST['csv-column-delimiter-' . $user_id]) && isset($_POST['csv-sku-column-' . $user_id]) && isset($_POST['csv-quantity-column-' . $user_id]) && isset($_FILES['csv-file-' . $user_id])){
							$csv_column_delimiter = $_POST['csv-column-delimiter-' . $user_id];
							$csv_sku_column =  $_POST['csv-sku-column-' . $user_id];
							$csv_quantity_column = $_POST['csv-quantity-column-' . $user_id];

							update_user_meta($user_id, 'dropshipper_csv_column_delimiter', $csv_column_delimiter);
							update_user_meta($user_id, 'dropshipper_csv_sku_column_number', $csv_sku_column);
							update_user_meta($user_id, 'dropshipper_csv_quantity_column_number', $csv_quantity_column);
							$done_products = 0;

							try {
								$there_was_an_error = false;
								// Undefined | Multiple Files | $_FILES Corruption Attack
								// If this request falls under any of them, treat it invalid.
								if ( !isset($_FILES['csv-file-'.$user_id]['error']) || is_array($_FILES['csv-file-'.$user_id]['error']) ) {
									$there_was_an_error = true;
									throw new Exception(__('Invalid file format.','woocommerce-dropshippers'));
								}
								// Check $_FILES['import-file']['error'] value.
								if(!$there_was_an_error){
									switch ($_FILES['csv-file-'.$user_id]['error']) {
										case UPLOAD_ERR_OK:
											break;
										case UPLOAD_ERR_NO_FILE:
											$there_was_an_error = true;
											throw new Exception(__('No file sent.','woocommerce-dropshippers'));
											break;
										case UPLOAD_ERR_INI_SIZE:
										case UPLOAD_ERR_FORM_SIZE:
											$there_was_an_error = true;
											throw new Exception(__('Exceeded filesize limit.','woocommerce-dropshippers'));
											break;
										default:
											$there_was_an_error = true;
											throw new Exception(__('Unknown errors.','woocommerce-dropshippers'));
											break;
									}
									// You should also check filesize here.
									if ($_FILES['csv-file-'.$user_id]['size'] > 1000000) {
										$there_was_an_error = true;
										throw new Exception(__('Exceeded filesize limit.','woocommerce-dropshippers'));
									}
									// DO NOT TRUST $_FILES['import-file']['mime'] VALUE !!
									// Check MIME Type by yourself.
									$finfo = new finfo(FILEINFO_MIME_TYPE);
									if (false === $ext = array_search(
										$finfo->file($_FILES['csv-file-'.$user_id]['tmp_name']),
										array(
											'text/csv',
											'text/html',
											'text/plain'
										),
										true
									)) {
										$there_was_an_error = true;
										throw new Exception(__('Invalid file format.','woocommerce-dropshippers'));
									}
								}
								$csv = woocommerce_dropshippers_csv_to_array($_FILES['csv-file-'.$user_id]['tmp_name'], $csv_column_delimiter, '"');
								if($csv && !empty($csv)){
									foreach ($csv as $csv_line => $csv_values){
										$column_number = 1;
										$sku_found = false;
										$quantity_found = false;
										if(!empty($csv_values)){
											foreach ($csv_values as $csv_value_key => $csv_value_value) {
												if($column_number == $csv_sku_column){ //sku found
													$sku_found = $csv_value_value;
												}
												if($column_number == $csv_quantity_column){ //quantity found
													$quantity_found = $csv_value_value;
												}
												$column_number++;
											}
											if( ($sku_found !== false) && (!empty($sku_found)) && ($quantity_found !== false) && ($quantity_found !== '') ){
												$product_id = wc_get_product_id_by_sku($sku_found);
												if(!empty($product_id)){
													$product = wc_get_product($product_id);
													if(function_exists('wc_update_product_stock')){
														wc_update_product_stock($product_id, $quantity_found);
													}
													else{
														$product->set_stock($quantity_found);
													}
													$done_products++;
												}
											}
										}
										
									}
								}
								else{
									$there_was_an_error = true;
									throw new Exception(__('CSV is empty.','woocommerce-dropshippers'));
								}
								echo '<div class="notice notice-success is-dismissible"><p>'. str_replace('%NUMBER%', $done_products, __('Products successfully updated: %NUMBER%','woocommerce-dropshippers') ) .'</p></div>';
								$there_was_an_error = true;
							} catch (Exception $e) {
								echo '<div class="notice notice-error is-dismissible"><p>'.$e->getMessage().'</p></div>';
								$there_was_an_error = true;
							}
						}
					}
				}

				$options = get_option('woocommerce_dropshippers_options');
				?>
				<div class="dropshippers-header" style="margin:0; padding:0; width:100%; height:100px; background: url('<?php echo plugins_url( 'images/headerbg.png', __FILE__ ) ?>'); background-repeat: repeat-x;">
					<img src="<?php echo plugins_url( 'images/woocommerce-dropshippers-header.png', __FILE__ ) ?>" style="margin:0; padding:0; width:auto; height:100px;">
				</div>
				<?php
				echo '<script type="text/javascript" src="'. plugins_url( 'pay_dropshipper.js' , __FILE__ ) .'"></script>';
				echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
				echo '<h2>'. __('WooCommerce Dropshippers','woocommerce-dropshippers') .'</h2>';
				echo '<h3>'. __('Dropshippers List','woocommerce-dropshippers') .'</h3>';
				$ajax_nonce_save = wp_create_nonce( "SpaceRubberDuckSave" );
				$csv_nonce = wp_create_nonce( "SpaceRubberDuckCSV" );
				$ajax_nonce = wp_create_nonce( "SpaceRubberDuck" );

				?>

				<style type="text/css">
				.dropshipper-overlay {
					height: 100%;
					width: 100%;
					position: fixed;
					z-index: 100000;
					left: 0;
					top: 0;
					background-color: rgb(0,0,0);
					background-color: rgba(0,0,0, 0.9);
					overflow-x: hidden;
				}
				</style>

				<script type="text/javascript">
					function js_reset_earnings(my_id) {
						if(confirm("<?php echo __('Do you really want to reset the earnings of this dropshipper?','woocommerce-dropshippers'); ?>")){
							var data = {
								action: 'reset_earnings',
								security: '<?php echo $ajax_nonce; ?>',
								id: my_id
							};
							// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
							jQuery.post(ajaxurl, data, function(response) {
								if(response == 'true'){
									location.reload(true);
								}
							});
						}
						else{
							// do nothing
						}
					}

					function toggleDropRow(user_id){
						jQuery('.user-details-row-'+ user_id +' .user-details-content').slideToggle();

						var currentDirection = jQuery('.user-row-'+ user_id +' .drop-toggle').text();
						if(currentDirection == '▼'){
							jQuery('.user-row-'+ user_id +' .drop-toggle').text('▲');
						}
						else if(currentDirection == '▲'){
							jQuery('.user-row-'+ user_id +' .drop-toggle').text('▼');
						}
					}

					function saveDropSettings(user_id){
						var dropshipper_paypal_email = jQuery('.user-details-row-'+ user_id + ' input[name="dropshipper_paypal_email"]').val();
						if(!dropshipper_paypal_email) dropshipper_paypal_email = '';
						var dropshipper_currency = jQuery('.user-details-row-'+ user_id + ' select[name="dropshipper_currency"]').val();
						if(!dropshipper_currency) dropshipper_currency = 'USD';

						var dropshipper_country = jQuery('.user-details-row-'+ user_id + ' select[name="dropshipper_country"]').val();
						if(!dropshipper_country) dropshipper_country = '';
						var dropshipper_national_shipping_price = jQuery('.user-details-row-'+ user_id + ' input[name="dropshipper_national_shipping_price"]').val();
						if(!dropshipper_national_shipping_price) dropshipper_national_shipping_price = '';
						var dropshipper_international_shipping_price = jQuery('.user-details-row-'+ user_id + ' input[name="dropshipper_international_shipping_price"]').val();
						if(!dropshipper_international_shipping_price) dropshipper_international_shipping_price = '';


						jQuery('body').append('<div class="dropshipper-overlay" style="display:none"></div>');
						jQuery('.dropshipper-overlay').fadeIn(200);
						var data = {
							action: 'dropshippers_admin_save_drop_settings',
							security: '<?php echo $ajax_nonce_save; ?>',
							id: user_id,
							dropshipper_paypal_email: dropshipper_paypal_email,
							dropshipper_currency: dropshipper_currency,
							dropshipper_country: dropshipper_country,
							dropshipper_national_shipping_price: dropshipper_national_shipping_price,
							dropshipper_international_shipping_price: dropshipper_international_shipping_price
						};
						// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
						jQuery.post(ajaxurl, data, function(response) {
							alert("<?php _e('Settings saved.','woocommerce-dropshippers') ?>");
							location.reload(true);
						});
					}

				</script>
				<table class="wp-list-table widefat fixed posts striped" cellspacing="0">
				<thead>
					<tr>
						<th class="manage-column column-columnname" scope="col"><?php echo __('User','woocommerce-dropshippers'); ?></th>
						<th class="manage-column column-columnname" scope="col"><?php echo __('Earnings','woocommerce-dropshippers'); ?></th>
						<th class="manage-column column-columnname" scope="col"><?php echo __('Actions','woocommerce-dropshippers'); ?></th>
						<th width="20"></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th class="manage-column column-columnname" scope="col"><?php echo __('User','woocommerce-dropshippers'); ?></th>
						<th class="manage-column column-columnname" scope="col"><?php echo __('Earnings','woocommerce-dropshippers'); ?></th>
						<th class="manage-column column-columnname" scope="col"><?php echo __('Actions', 'woocommerce-dropshippers'); ?></th>
						<th width="20"></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
						$countries_obj = new WC_Countries();
						$countries = $countries_obj->__get('countries');

						$dropshipperz = get_users('role=dropshipper');
						foreach ($dropshipperz as $drop_usr) {
							echo '<tr class="type-shop_order user-row-'. $drop_usr->ID .'"><td><strong>'.$drop_usr->user_login.'</strong></td>';
							$dropshipper_earning = get_user_meta($drop_usr->ID, 'dropshipper_earnings', true);
							if(!$dropshipper_earning){ $dropshipper_earning = 0; }
							echo '<td>'. wc_price((float) $dropshipper_earning).'</td>';
							echo '<td>';
							echo '<button class="button button-primary" style="margin-bottom: 3px;" onclick="js_reset_earnings(\''. $drop_usr->ID .'\')">'. __('Reset earnings','woocommerce-dropshippers') .'</button><br/>';
							$email = get_user_meta($drop_usr->ID, 'dropshipper_paypal_email',true);
							if($email){
								echo '<button class="button button-primary" onclick="payDropshipper(\''. $email .'\', \''.$dropshipper_earning.'\', \''.get_woocommerce_currency().'\')">'. __('Pay this dropshipper (PayPal)','woocommerce-dropshippers') .'</button>';
							}
							else{
								echo __('The dropshipper has not entered the PayPal email','woocommerce-dropshippers');
							}
							echo '</td>';
							echo '<td><a class="drop-toggle" href="#" onclick="toggleDropRow('. $drop_usr->ID .'); return false;" >▼</a></td>';
							echo '</tr>' . "\n";


							$dropshipper_paypal_email = get_user_meta($drop_usr->ID, 'dropshipper_paypal_email', true);
							$dropshipper_country = get_user_meta($drop_usr->ID, 'dropshipper_country', true);
							if(empty($dropshipper_country)) $dropshipper_country = 'US';
							$dropshipper_national_shipping_price = get_user_meta($drop_usr->ID, 'national_shipping_price', true);
							if(empty($dropshipper_national_shipping_price)) $dropshipper_national_shipping_price = 0;
							$dropshipper_international_shipping_price = get_user_meta($drop_usr->ID, 'international_shipping_price', true);
							if(empty($dropshipper_international_shipping_price)) $dropshipper_international_shipping_price = 0;
							$dropshipper_currency = get_user_meta($drop_usr->ID, 'dropshipper_currency', true);
							if(!$dropshipper_currency) $dropshipper_currency = 'USD';

					?>

							<tr class="user-details-row-<?php echo $drop_usr->ID; ?>" >
								<td colspan="2">
									<div class="user-details-content" style="display:none;">
										<h3><?php echo __('Dropshipper Settings','woocommerce-dropshippers'); ?></h3>
										<div method="post" action="">
											<table>
												<tr>	
													<td><label for="dropshipper_paypal_email"><strong><?php echo __('PayPal email','woocommerce-dropshippers'); ?></strong></label></td>
													<td><input type="text" name="dropshipper_paypal_email" value="<?php if($email) echo $email; ?>"></td>
												</tr>
												<tr>
													<td><label for="dropshipper_currency"><strong><?php echo __('Currency','woocommerce-dropshippers'); ?></strong></label></td>
													<td><select name="dropshipper_currency">
														<option value="USD" <?php if($dropshipper_currency=='USD') echo 'selected="selected"'; ?>>US Dollars (&#36;)</option>
														<option value="AUD" <?php if($dropshipper_currency=='AUD') echo 'selected="selected"'; ?>>Australian Dollars (&#36;)</option>
														<option value="BDT" <?php if($dropshipper_currency=='BDT') echo 'selected="selected"'; ?>>Bangladeshi Taka (&#2547;&nbsp;)</option>
														<option value="BRL" <?php if($dropshipper_currency=='BRL') echo 'selected="selected"'; ?>>Brazilian Real (&#82;&#36;)</option>
														<option value="BGN" <?php if($dropshipper_currency=='BGN') echo 'selected="selected"'; ?>>Bulgarian Lev (&#1083;&#1074;.)</option>
														<option value="CAD" <?php if($dropshipper_currency=='CAD') echo 'selected="selected"'; ?>>Canadian Dollars (&#36;)</option>
														<option value="CLP" <?php if($dropshipper_currency=='CLP') echo 'selected="selected"'; ?>>Chilean Peso (&#36;)</option>
														<option value="CNY" <?php if($dropshipper_currency=='CNY') echo 'selected="selected"'; ?>>Chinese Yuan (&yen;)</option>
														<option value="COP" <?php if($dropshipper_currency=='COP') echo 'selected="selected"'; ?>>Colombian Peso (&#36;)</option>
														<option value="CZK" <?php if($dropshipper_currency=='CZK') echo 'selected="selected"'; ?>>Czech Koruna (&#75;&#269;)</option>
														<option value="DKK" <?php if($dropshipper_currency=='DKK') echo 'selected="selected"'; ?>>Danish Krone (&#107;&#114;)</option>
														<option value="EUR" <?php if($dropshipper_currency=='EUR') echo 'selected="selected"'; ?>>Euros (&euro;)</option>
														<option value="HKD" <?php if($dropshipper_currency=='HKD') echo 'selected="selected"'; ?>>Hong Kong Dollar (&#36;)</option>
														<option value="HRK" <?php if($dropshipper_currency=='HRK') echo 'selected="selected"'; ?>>Croatia kuna (Kn)</option>
														<option value="HUF" <?php if($dropshipper_currency=='HUF') echo 'selected="selected"'; ?>>Hungarian Forint (&#70;&#116;)</option>
														<option value="ISK" <?php if($dropshipper_currency=='ISK') echo 'selected="selected"'; ?>>Icelandic krona (Kr.)</option>
														<option value="IDR" <?php if($dropshipper_currency=='IDR') echo 'selected="selected"'; ?>>Indonesia Rupiah (Rp)</option>
														<option value="INR" <?php if($dropshipper_currency=='INR') echo 'selected="selected"'; ?>>Indian Rupee (Rs.)</option>
														<option value="ILS" <?php if($dropshipper_currency=='ILS') echo 'selected="selected"'; ?>>Israeli Shekel (&#8362;)</option>
														<option value="JPY" <?php if($dropshipper_currency=='JPY') echo 'selected="selected"'; ?>>Japanese Yen (&yen;)</option>
														<option value="KRW" <?php if($dropshipper_currency=='KRW') echo 'selected="selected"'; ?>>South Korean Won (&#8361;)</option>
														<option value="MYR" <?php if($dropshipper_currency=='MYR') echo 'selected="selected"'; ?>>Malaysian Ringgits (&#82;&#77;)</option>
														<option value="MXN" <?php if($dropshipper_currency=='MXN') echo 'selected="selected"'; ?>>Mexican Peso (&#36;)</option>
														<option value="NGN" <?php if($dropshipper_currency=='NGN') echo 'selected="selected"'; ?>>Nigerian Naira (&#8358;)</option>
														<option value="NOK" <?php if($dropshipper_currency=='NOK') echo 'selected="selected"'; ?>>Norwegian Krone (&#107;&#114;)</option>
														<option value="NZD" <?php if($dropshipper_currency=='NZD') echo 'selected="selected"'; ?>>New Zealand Dollar (&#36;)</option>
														<option value="PHP" <?php if($dropshipper_currency=='PHP') echo 'selected="selected"'; ?>>Philippine Pesos (&#8369;)</option>
														<option value="PLN" <?php if($dropshipper_currency=='PLN') echo 'selected="selected"'; ?>>Polish Zloty (&#122;&#322;)</option>
														<option value="GBP" <?php if($dropshipper_currency=='GBP') echo 'selected="selected"'; ?>>Pounds Sterling (&pound;)</option>
														<option value="RON" <?php if($dropshipper_currency=='RON') echo 'selected="selected"'; ?>>Romanian Leu (lei)</option>
														<option value="RUB" <?php if($dropshipper_currency=='RUB') echo 'selected="selected"'; ?>>Russian Ruble (&#1088;&#1091;&#1073;.)</option>
														<option value="SGD" <?php if($dropshipper_currency=='SGD') echo 'selected="selected"'; ?>>Singapore Dollar (&#36;)</option>
														<option value="ZAR" <?php if($dropshipper_currency=='ZAR') echo 'selected="selected"'; ?>>South African rand (&#82;)</option>
														<option value="SEK" <?php if($dropshipper_currency=='SEK') echo 'selected="selected"'; ?>>Swedish Krona (&#107;&#114;)</option>
														<option value="CHF" <?php if($dropshipper_currency=='CHF') echo 'selected="selected"'; ?>>Swiss Franc (&#67;&#72;&#70;)</option>
														<option value="TWD" <?php if($dropshipper_currency=='TWD') echo 'selected="selected"'; ?>>Taiwan New Dollars (&#78;&#84;&#36;)</option>
														<option value="THB" <?php if($dropshipper_currency=='THB') echo 'selected="selected"'; ?>>Thai Baht (&#3647;)</option>
														<option value="TRY" <?php if($dropshipper_currency=='TRY') echo 'selected="selected"'; ?>>Turkish Lira (&#84;&#76;)</option>
														<option value="VND" <?php if($dropshipper_currency=='VND') echo 'selected="selected"'; ?>>Vietnamese Dong (&#8363;)</option>
													</select></td>
												</tr>
											<?php if(isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee']=='Yes'): ?>
												<tr>
													<td><label for="dropshipper_country"><strong><?php echo __('Country','woocommerce-dropshippers'); ?></strong></label></td>
													<td><select name="dropshipper_country">
													<?php
														foreach ($countries as $country_code => $country_name) {
															$selected = '';
															if($dropshipper_country == $country_code) $selected = 'selected="selected"';
															echo '<option value="'.$country_code.'" '.$selected.'>'. htmlspecialchars($country_name) .'</option>' . "\n";
														}
													?>
													</select>
													</td>
												</tr>
												<tr>
													<td><label for="dropshipper_national_shipping_price"><strong><?php echo str_replace('%SYMBOL%', get_woocommerce_currency_symbol(), __('National shipping price (in shop currency: %SYMBOL%)','woocommerce-dropshippers') ); ?></strong></label></td>
													<td><input type="text" name="dropshipper_national_shipping_price" value="<?php echo $dropshipper_national_shipping_price; ?>"></td>
												</tr>
												<tr>	
													<td><label for="dropshipper_international_shipping_price"><strong><?php echo str_replace('%SYMBOL%', get_woocommerce_currency_symbol(), __('International shipping price (in shop currency: %SYMBOL%)','woocommerce-dropshippers') ); ?></strong></label></td>
													<td><input type="text" name="dropshipper_international_shipping_price" value="<?php echo $dropshipper_international_shipping_price; ?>"></td>
												</tr>
											<?php endif; ?>
											</table>
											<button class="button button-primary dropshippers-save-settings" onclick="saveDropSettings(<?php echo $drop_usr->ID; ?>); return false;"><?php echo __('Save Settings','woocommerce-dropshippers'); ?></button>
										</div>
									</div>
								</td>
								<td colspan="2">
									<?php
										$csv_column_delimiter = get_user_meta($drop_usr->ID, 'dropshipper_csv_column_delimiter', true);
										if(empty($csv_column_delimiter)){
											$csv_column_delimiter = ',';
										}

										$csv_sku_column_number = get_user_meta($drop_usr->ID, 'dropshipper_csv_sku_column_number', true);
										if(empty($csv_sku_column_number)){
											$csv_sku_column_number = 1;
										}

										$csv_quantity_column_number = get_user_meta($drop_usr->ID, 'dropshipper_csv_quantity_column_number', true);
										if(empty($csv_quantity_column_number)){
											$csv_quantity_column_number = 2;
										}
									?>
									<div class="user-details-content" style="display:none;">
										<h3> <?php echo __('Dropshipper CSV Import','woocommerce-dropshippers'); ?></h3>
										<form action="" method="POST" enctype="multipart/form-data">
											<input type="hidden" name="single-dropshipper-csv-import" value="1" />
											<input type="hidden" name="security" value="<?php echo $csv_nonce; ?>" />
											<input type="hidden" name="drop-id" value="<?php echo $drop_usr->ID; ?>" />
											<table>
												<tr>
													<td><label for="csv-column-delimiter-<?php echo $drop_usr->ID; ?>">CSV column delimiter</label></td>
													<td><input type="text" name="csv-column-delimiter-<?php echo $drop_usr->ID; ?>" id="csv-column-delimiter-<?php echo $drop_usr->ID; ?>" value="<?php echo $csv_column_delimiter; ?>"></td>
												</tr>
												<tr>
													<td><label for="csv-sku-column-<?php echo $drop_usr->ID; ?>">CSV SKU column number</label></td>
													<td><input type="text" name="csv-sku-column-<?php echo $drop_usr->ID; ?>" id="csv-sku-column-<?php echo $drop_usr->ID; ?>" value="<?php echo $csv_sku_column_number; ?>"></td>
												</tr>
												<tr>
													<td><label for="csv-quantity-column-<?php echo $drop_usr->ID; ?>">CSV quantity column number</label></td>
													<td><input type="text" name="csv-quantity-column-<?php echo $drop_usr->ID; ?>" id="csv-quantity-column-<?php echo $drop_usr->ID; ?>" value="<?php echo $csv_quantity_column_number; ?>"></td>
												</tr>
												<tr>
													<td><label for="csv-file-<?php echo $drop_usr->ID; ?>">CSV File</label></td>
													<td><input type="file" accept="text/csv" name="csv-file-<?php echo $drop_usr->ID; ?>" id="csv-file-<?php echo $drop_usr->ID; ?>"></td>
												</tr>
											</table>

											<button class="button button-primary dropshippers-upload-csv" type="submit"><?php echo __('Import','woocommerce-dropshippers'); ?></button>

											<br/><br/><br/><br/>

										</form>
									</div>
								</td>
							</tr>
					<?php
						}
					?>
				</tbody>
				</table>
				</div>
				<?php
			}

			/** DROPSHIPPER SETTINGS PAGE **/
			add_action( 'admin_menu', 'dropshipper_settings_page' );

			function dropshipper_settings_page() {
				if( ! current_user_can('manage_network') ){
					add_menu_page( __('Dropshipper Settings','woocommerce-dropshippers'), __('Dropshipper Settings','woocommerce-dropshippers'), 'show_dropshipper_widget', 'dropshipper_settings_page', 'dropshipper_settings_page_function', 'dashicons-admin-generic', '210.42' );
				}
			}

			function dropshipper_settings_page_function() {
				if (!current_user_can( 'show_dropshipper_widget' ) ){
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}
				//require_once(sprintf("%s/orders.php", dirname(__FILE__)));
				$user_id = get_current_user_id();
				if(isset($_POST['dropshipper_paypal_email'])){
					$check_all_ok = true;
					$email = sanitize_email($_POST['dropshipper_paypal_email']);
					if(is_email($email, false)){
						update_user_meta($user_id, 'dropshipper_paypal_email', $email);
					}
					else{
						$check_all_ok = false;
					}
					if(isset($_POST['dropshipper_national_shipping_price']) && $_POST['dropshipper_national_shipping_price']!=''){
						if(is_numeric($_POST['dropshipper_national_shipping_price'])){
							update_user_meta($user_id, 'national_shipping_price', $_POST['dropshipper_national_shipping_price']);
						}
						else{
							$check_all_ok = false;
						}
					}
					if(isset($_POST['dropshipper_international_shipping_price']) && $_POST['dropshipper_international_shipping_price']!='' ){
						if(is_numeric($_POST['dropshipper_international_shipping_price'])){
							update_user_meta($user_id, 'international_shipping_price', $_POST['dropshipper_international_shipping_price']);
						}
						else{
							$check_all_ok = false;
						}
					}
					if($check_all_ok){ ?>
						<div id="message" class="updated">
							<p><strong><?php _e('Settings saved.','woocommerce-dropshippers') ?></strong></p>
						</div>
					<?php }
					else{ ?>
						<div id="message" class="error">
							<p><strong><?php _e('Check your fields.','woocommerce-dropshippers') ?></strong></p>
						</div>
					<?php }
				}
				$options = get_option('woocommerce_dropshippers_options');
				$email = get_user_meta($user_id, 'dropshipper_paypal_email', true);
				$country = get_user_meta($user_id, 'dropshipper_country', true);
				$national_shipping_price = get_user_meta($user_id, 'national_shipping_price', true);
				if(empty($national_shipping_price)) $national_shipping_price = 0;
				$international_shipping_price = get_user_meta($user_id, 'international_shipping_price', true);
				if(empty($international_shipping_price)) $international_shipping_price = 0;
				if(isset($_POST['dropshipper_currency'])){
					$currency = sanitize_text_field($_POST['dropshipper_currency']);
					update_user_meta($user_id, 'dropshipper_currency', $currency);
				}
				$currency = get_user_meta($user_id, 'dropshipper_currency', true);

				if(isset($_POST['dropshipper_country'])){
					$country = $_POST['dropshipper_country'];
					update_user_meta($user_id, 'dropshipper_country', $country);
				}
				$currency = get_user_meta($user_id, 'dropshipper_currency', true);
				if(!$currency) $currency = 'USD';
				?>
				<div class="dropshippers-header" style="margin:0; padding:0; width:100%; height:100px; background: url('<?php echo plugins_url( 'images/headerbg.png', __FILE__ ) ?>'); background-repeat: repeat-x;">
					<img src="<?php echo plugins_url( 'images/woocommerce-dropshippers-header.png', __FILE__ ) ?>" style="margin:0; padding:0; width:auto; height:100px;">
				</div>
				<?php
				echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
				echo '<h2>'. __('WooCommerce Dropshippers','woocommerce-dropshippers') .'</h2>';
				echo '<h3>'. __('Dropshipper Settings','woocommerce-dropshippers') .'</h3>';
				?>
				<form method="post" action="">
					<table>
						<tr>	
							<td><label for="dropshipper_paypal_email"><strong><?php echo __('PayPal email','woocommerce-dropshippers'); ?></strong></label></td>
							<td><input type="text" name="dropshipper_paypal_email" value="<?php if($email) echo $email; ?>"></td>
						</tr>
						<tr>
							<td><label for="dropshipper_currency"><strong><?php echo __('Currency','woocommerce-dropshippers'); ?></strong></label></td>
							<td><select name="dropshipper_currency">
								<option value="USD" <?php if($currency=='USD') echo 'selected="selected"'; ?>>US Dollars (&#36;)</option>
								<option value="AUD" <?php if($currency=='AUD') echo 'selected="selected"'; ?>>Australian Dollars (&#36;)</option>
								<option value="BDT" <?php if($currency=='BDT') echo 'selected="selected"'; ?>>Bangladeshi Taka (&#2547;&nbsp;)</option>
								<option value="BRL" <?php if($currency=='BRL') echo 'selected="selected"'; ?>>Brazilian Real (&#82;&#36;)</option>
								<option value="BGN" <?php if($currency=='BGN') echo 'selected="selected"'; ?>>Bulgarian Lev (&#1083;&#1074;.)</option>
								<option value="CAD" <?php if($currency=='CAD') echo 'selected="selected"'; ?>>Canadian Dollars (&#36;)</option>
								<option value="CLP" <?php if($currency=='CLP') echo 'selected="selected"'; ?>>Chilean Peso (&#36;)</option>
								<option value="CNY" <?php if($currency=='CNY') echo 'selected="selected"'; ?>>Chinese Yuan (&yen;)</option>
								<option value="COP" <?php if($currency=='COP') echo 'selected="selected"'; ?>>Colombian Peso (&#36;)</option>
								<option value="CZK" <?php if($currency=='CZK') echo 'selected="selected"'; ?>>Czech Koruna (&#75;&#269;)</option>
								<option value="DKK" <?php if($currency=='DKK') echo 'selected="selected"'; ?>>Danish Krone (&#107;&#114;)</option>
								<option value="EUR" <?php if($currency=='EUR') echo 'selected="selected"'; ?>>Euros (&euro;)</option>
								<option value="HKD" <?php if($currency=='HKD') echo 'selected="selected"'; ?>>Hong Kong Dollar (&#36;)</option>
								<option value="HRK" <?php if($currency=='HRK') echo 'selected="selected"'; ?>>Croatia kuna (Kn)</option>
								<option value="HUF" <?php if($currency=='HUF') echo 'selected="selected"'; ?>>Hungarian Forint (&#70;&#116;)</option>
								<option value="ISK" <?php if($currency=='ISK') echo 'selected="selected"'; ?>>Icelandic krona (Kr.)</option>
								<option value="IDR" <?php if($currency=='IDR') echo 'selected="selected"'; ?>>Indonesia Rupiah (Rp)</option>
								<option value="INR" <?php if($currency=='INR') echo 'selected="selected"'; ?>>Indian Rupee (Rs.)</option>
								<option value="ILS" <?php if($currency=='ILS') echo 'selected="selected"'; ?>>Israeli Shekel (&#8362;)</option>
								<option value="JPY" <?php if($currency=='JPY') echo 'selected="selected"'; ?>>Japanese Yen (&yen;)</option>
								<option value="KRW" <?php if($currency=='KRW') echo 'selected="selected"'; ?>>South Korean Won (&#8361;)</option>
								<option value="MYR" <?php if($currency=='MYR') echo 'selected="selected"'; ?>>Malaysian Ringgits (&#82;&#77;)</option>
								<option value="MXN" <?php if($currency=='MXN') echo 'selected="selected"'; ?>>Mexican Peso (&#36;)</option>
								<option value="NGN" <?php if($currency=='NGN') echo 'selected="selected"'; ?>>Nigerian Naira (&#8358;)</option>
								<option value="NOK" <?php if($currency=='NOK') echo 'selected="selected"'; ?>>Norwegian Krone (&#107;&#114;)</option>
								<option value="NZD" <?php if($currency=='NZD') echo 'selected="selected"'; ?>>New Zealand Dollar (&#36;)</option>
								<option value="PHP" <?php if($currency=='PHP') echo 'selected="selected"'; ?>>Philippine Pesos (&#8369;)</option>
								<option value="PLN" <?php if($currency=='PLN') echo 'selected="selected"'; ?>>Polish Zloty (&#122;&#322;)</option>
								<option value="GBP" <?php if($currency=='GBP') echo 'selected="selected"'; ?>>Pounds Sterling (&pound;)</option>
								<option value="RON" <?php if($currency=='RON') echo 'selected="selected"'; ?>>Romanian Leu (lei)</option>
								<option value="RUB" <?php if($currency=='RUB') echo 'selected="selected"'; ?>>Russian Ruble (&#1088;&#1091;&#1073;.)</option>
								<option value="SGD" <?php if($currency=='SGD') echo 'selected="selected"'; ?>>Singapore Dollar (&#36;)</option>
								<option value="ZAR" <?php if($currency=='ZAR') echo 'selected="selected"'; ?>>South African rand (&#82;)</option>
								<option value="SEK" <?php if($currency=='SEK') echo 'selected="selected"'; ?>>Swedish Krona (&#107;&#114;)</option>
								<option value="CHF" <?php if($currency=='CHF') echo 'selected="selected"'; ?>>Swiss Franc (&#67;&#72;&#70;)</option>
								<option value="TWD" <?php if($currency=='TWD') echo 'selected="selected"'; ?>>Taiwan New Dollars (&#78;&#84;&#36;)</option>
								<option value="THB" <?php if($currency=='THB') echo 'selected="selected"'; ?>>Thai Baht (&#3647;)</option>
								<option value="TRY" <?php if($currency=='TRY') echo 'selected="selected"'; ?>>Turkish Lira (&#84;&#76;)</option>
								<option value="VND" <?php if($currency=='VND') echo 'selected="selected"'; ?>>Vietnamese Dong (&#8363;)</option>
							</select></td>
						</tr>
						<?php if(isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee']=='Yes'): ?>
						<?php
							$countries = apply_filters('woocommerce_countries', array(
								'AF' => __( 'Afghanistan', 'woocommerce' ),
								'AX' => __( 'Åland Islands', 'woocommerce' ),
								'AL' => __( 'Albania', 'woocommerce' ),
								'DZ' => __( 'Algeria', 'woocommerce' ),
								'AD' => __( 'Andorra', 'woocommerce' ),
								'AO' => __( 'Angola', 'woocommerce' ),
								'AI' => __( 'Anguilla', 'woocommerce' ),
								'AQ' => __( 'Antarctica', 'woocommerce' ),
								'AG' => __( 'Antigua and Barbuda', 'woocommerce' ),
								'AR' => __( 'Argentina', 'woocommerce' ),
								'AM' => __( 'Armenia', 'woocommerce' ),
								'AW' => __( 'Aruba', 'woocommerce' ),
								'AU' => __( 'Australia', 'woocommerce' ),
								'AT' => __( 'Austria', 'woocommerce' ),
								'AZ' => __( 'Azerbaijan', 'woocommerce' ),
								'BS' => __( 'Bahamas', 'woocommerce' ),
								'BH' => __( 'Bahrain', 'woocommerce' ),
								'BD' => __( 'Bangladesh', 'woocommerce' ),
								'BB' => __( 'Barbados', 'woocommerce' ),
								'BY' => __( 'Belarus', 'woocommerce' ),
								'BE' => __( 'Belgium', 'woocommerce' ),
								'PW' => __( 'Belau', 'woocommerce' ),
								'BZ' => __( 'Belize', 'woocommerce' ),
								'BJ' => __( 'Benin', 'woocommerce' ),
								'BM' => __( 'Bermuda', 'woocommerce' ),
								'BT' => __( 'Bhutan', 'woocommerce' ),
								'BO' => __( 'Bolivia', 'woocommerce' ),
								'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'woocommerce' ),
								'BA' => __( 'Bosnia and Herzegovina', 'woocommerce' ),
								'BW' => __( 'Botswana', 'woocommerce' ),
								'BV' => __( 'Bouvet Island', 'woocommerce' ),
								'BR' => __( 'Brazil', 'woocommerce' ),
								'IO' => __( 'British Indian Ocean Territory', 'woocommerce' ),
								'VG' => __( 'British Virgin Islands', 'woocommerce' ),
								'BN' => __( 'Brunei', 'woocommerce' ),
								'BG' => __( 'Bulgaria', 'woocommerce' ),
								'BF' => __( 'Burkina Faso', 'woocommerce' ),
								'BI' => __( 'Burundi', 'woocommerce' ),
								'KH' => __( 'Cambodia', 'woocommerce' ),
								'CM' => __( 'Cameroon', 'woocommerce' ),
								'CA' => __( 'Canada', 'woocommerce' ),
								'CV' => __( 'Cape Verde', 'woocommerce' ),
								'KY' => __( 'Cayman Islands', 'woocommerce' ),
								'CF' => __( 'Central African Republic', 'woocommerce' ),
								'TD' => __( 'Chad', 'woocommerce' ),
								'CL' => __( 'Chile', 'woocommerce' ),
								'CN' => __( 'China', 'woocommerce' ),
								'CX' => __( 'Christmas Island', 'woocommerce' ),
								'CC' => __( 'Cocos (Keeling) Islands', 'woocommerce' ),
								'CO' => __( 'Colombia', 'woocommerce' ),
								'KM' => __( 'Comoros', 'woocommerce' ),
								'CG' => __( 'Congo (Brazzaville)', 'woocommerce' ),
								'CD' => __( 'Congo (Kinshasa)', 'woocommerce' ),
								'CK' => __( 'Cook Islands', 'woocommerce' ),
								'CR' => __( 'Costa Rica', 'woocommerce' ),
								'HR' => __( 'Croatia', 'woocommerce' ),
								'CU' => __( 'Cuba', 'woocommerce' ),
								'CW' => __( 'Cura&Ccedil;ao', 'woocommerce' ),
								'CY' => __( 'Cyprus', 'woocommerce' ),
								'CZ' => __( 'Czech Republic', 'woocommerce' ),
								'DK' => __( 'Denmark', 'woocommerce' ),
								'DJ' => __( 'Djibouti', 'woocommerce' ),
								'DM' => __( 'Dominica', 'woocommerce' ),
								'DO' => __( 'Dominican Republic', 'woocommerce' ),
								'EC' => __( 'Ecuador', 'woocommerce' ),
								'EG' => __( 'Egypt', 'woocommerce' ),
								'SV' => __( 'El Salvador', 'woocommerce' ),
								'GQ' => __( 'Equatorial Guinea', 'woocommerce' ),
								'ER' => __( 'Eritrea', 'woocommerce' ),
								'EE' => __( 'Estonia', 'woocommerce' ),
								'ET' => __( 'Ethiopia', 'woocommerce' ),
								'FK' => __( 'Falkland Islands', 'woocommerce' ),
								'FO' => __( 'Faroe Islands', 'woocommerce' ),
								'FJ' => __( 'Fiji', 'woocommerce' ),
								'FI' => __( 'Finland', 'woocommerce' ),
								'FR' => __( 'France', 'woocommerce' ),
								'GF' => __( 'French Guiana', 'woocommerce' ),
								'PF' => __( 'French Polynesia', 'woocommerce' ),
								'TF' => __( 'French Southern Territories', 'woocommerce' ),
								'GA' => __( 'Gabon', 'woocommerce' ),
								'GM' => __( 'Gambia', 'woocommerce' ),
								'GE' => __( 'Georgia', 'woocommerce' ),
								'DE' => __( 'Germany', 'woocommerce' ),
								'GH' => __( 'Ghana', 'woocommerce' ),
								'GI' => __( 'Gibraltar', 'woocommerce' ),
								'GR' => __( 'Greece', 'woocommerce' ),
								'GL' => __( 'Greenland', 'woocommerce' ),
								'GD' => __( 'Grenada', 'woocommerce' ),
								'GP' => __( 'Guadeloupe', 'woocommerce' ),
								'GT' => __( 'Guatemala', 'woocommerce' ),
								'GG' => __( 'Guernsey', 'woocommerce' ),
								'GN' => __( 'Guinea', 'woocommerce' ),
								'GW' => __( 'Guinea-Bissau', 'woocommerce' ),
								'GY' => __( 'Guyana', 'woocommerce' ),
								'HT' => __( 'Haiti', 'woocommerce' ),
								'HM' => __( 'Heard Island and McDonald Islands', 'woocommerce' ),
								'HN' => __( 'Honduras', 'woocommerce' ),
								'HK' => __( 'Hong Kong', 'woocommerce' ),
								'HU' => __( 'Hungary', 'woocommerce' ),
								'IS' => __( 'Iceland', 'woocommerce' ),
								'IN' => __( 'India', 'woocommerce' ),
								'ID' => __( 'Indonesia', 'woocommerce' ),
								'IR' => __( 'Iran', 'woocommerce' ),
								'IQ' => __( 'Iraq', 'woocommerce' ),
								'IE' => __( 'Republic of Ireland', 'woocommerce' ),
								'IM' => __( 'Isle of Man', 'woocommerce' ),
								'IL' => __( 'Israel', 'woocommerce' ),
								'IT' => __( 'Italy', 'woocommerce' ),
								'CI' => __( 'Ivory Coast', 'woocommerce' ),
								'JM' => __( 'Jamaica', 'woocommerce' ),
								'JP' => __( 'Japan', 'woocommerce' ),
								'JE' => __( 'Jersey', 'woocommerce' ),
								'JO' => __( 'Jordan', 'woocommerce' ),
								'KZ' => __( 'Kazakhstan', 'woocommerce' ),
								'KE' => __( 'Kenya', 'woocommerce' ),
								'KI' => __( 'Kiribati', 'woocommerce' ),
								'KW' => __( 'Kuwait', 'woocommerce' ),
								'KG' => __( 'Kyrgyzstan', 'woocommerce' ),
								'LA' => __( 'Laos', 'woocommerce' ),
								'LV' => __( 'Latvia', 'woocommerce' ),
								'LB' => __( 'Lebanon', 'woocommerce' ),
								'LS' => __( 'Lesotho', 'woocommerce' ),
								'LR' => __( 'Liberia', 'woocommerce' ),
								'LY' => __( 'Libya', 'woocommerce' ),
								'LI' => __( 'Liechtenstein', 'woocommerce' ),
								'LT' => __( 'Lithuania', 'woocommerce' ),
								'LU' => __( 'Luxembourg', 'woocommerce' ),
								'MO' => __( 'Macao S.A.R., China', 'woocommerce' ),
								'MK' => __( 'Macedonia', 'woocommerce' ),
								'MG' => __( 'Madagascar', 'woocommerce' ),
								'MW' => __( 'Malawi', 'woocommerce' ),
								'MY' => __( 'Malaysia', 'woocommerce' ),
								'MV' => __( 'Maldives', 'woocommerce' ),
								'ML' => __( 'Mali', 'woocommerce' ),
								'MT' => __( 'Malta', 'woocommerce' ),
								'MH' => __( 'Marshall Islands', 'woocommerce' ),
								'MQ' => __( 'Martinique', 'woocommerce' ),
								'MR' => __( 'Mauritania', 'woocommerce' ),
								'MU' => __( 'Mauritius', 'woocommerce' ),
								'YT' => __( 'Mayotte', 'woocommerce' ),
								'MX' => __( 'Mexico', 'woocommerce' ),
								'FM' => __( 'Micronesia', 'woocommerce' ),
								'MD' => __( 'Moldova', 'woocommerce' ),
								'MC' => __( 'Monaco', 'woocommerce' ),
								'MN' => __( 'Mongolia', 'woocommerce' ),
								'ME' => __( 'Montenegro', 'woocommerce' ),
								'MS' => __( 'Montserrat', 'woocommerce' ),
								'MA' => __( 'Morocco', 'woocommerce' ),
								'MZ' => __( 'Mozambique', 'woocommerce' ),
								'MM' => __( 'Myanmar', 'woocommerce' ),
								'NA' => __( 'Namibia', 'woocommerce' ),
								'NR' => __( 'Nauru', 'woocommerce' ),
								'NP' => __( 'Nepal', 'woocommerce' ),
								'NL' => __( 'Netherlands', 'woocommerce' ),
								'AN' => __( 'Netherlands Antilles', 'woocommerce' ),
								'NC' => __( 'New Caledonia', 'woocommerce' ),
								'NZ' => __( 'New Zealand', 'woocommerce' ),
								'NI' => __( 'Nicaragua', 'woocommerce' ),
								'NE' => __( 'Niger', 'woocommerce' ),
								'NG' => __( 'Nigeria', 'woocommerce' ),
								'NU' => __( 'Niue', 'woocommerce' ),
								'NF' => __( 'Norfolk Island', 'woocommerce' ),
								'KP' => __( 'North Korea', 'woocommerce' ),
								'NO' => __( 'Norway', 'woocommerce' ),
								'OM' => __( 'Oman', 'woocommerce' ),
								'PK' => __( 'Pakistan', 'woocommerce' ),
								'PS' => __( 'Palestinian Territory', 'woocommerce' ),
								'PA' => __( 'Panama', 'woocommerce' ),
								'PG' => __( 'Papua New Guinea', 'woocommerce' ),
								'PY' => __( 'Paraguay', 'woocommerce' ),
								'PE' => __( 'Peru', 'woocommerce' ),
								'PH' => __( 'Philippines', 'woocommerce' ),
								'PN' => __( 'Pitcairn', 'woocommerce' ),
								'PL' => __( 'Poland', 'woocommerce' ),
								'PT' => __( 'Portugal', 'woocommerce' ),
								'QA' => __( 'Qatar', 'woocommerce' ),
								'RE' => __( 'Reunion', 'woocommerce' ),
								'RO' => __( 'Romania', 'woocommerce' ),
								'RU' => __( 'Russia', 'woocommerce' ),
								'RW' => __( 'Rwanda', 'woocommerce' ),
								'BL' => __( 'Saint Barth&eacute;lemy', 'woocommerce' ),
								'SH' => __( 'Saint Helena', 'woocommerce' ),
								'KN' => __( 'Saint Kitts and Nevis', 'woocommerce' ),
								'LC' => __( 'Saint Lucia', 'woocommerce' ),
								'MF' => __( 'Saint Martin (French part)', 'woocommerce' ),
								'SX' => __( 'Saint Martin (Dutch part)', 'woocommerce' ),
								'PM' => __( 'Saint Pierre and Miquelon', 'woocommerce' ),
								'VC' => __( 'Saint Vincent and the Grenadines', 'woocommerce' ),
								'SM' => __( 'San Marino', 'woocommerce' ),
								'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'woocommerce' ),
								'SA' => __( 'Saudi Arabia', 'woocommerce' ),
								'SN' => __( 'Senegal', 'woocommerce' ),
								'RS' => __( 'Serbia', 'woocommerce' ),
								'SC' => __( 'Seychelles', 'woocommerce' ),
								'SL' => __( 'Sierra Leone', 'woocommerce' ),
								'SG' => __( 'Singapore', 'woocommerce' ),
								'SK' => __( 'Slovakia', 'woocommerce' ),
								'SI' => __( 'Slovenia', 'woocommerce' ),
								'SB' => __( 'Solomon Islands', 'woocommerce' ),
								'SO' => __( 'Somalia', 'woocommerce' ),
								'ZA' => __( 'South Africa', 'woocommerce' ),
								'GS' => __( 'South Georgia/Sandwich Islands', 'woocommerce' ),
								'KR' => __( 'South Korea', 'woocommerce' ),
								'SS' => __( 'South Sudan', 'woocommerce' ),
								'ES' => __( 'Spain', 'woocommerce' ),
								'LK' => __( 'Sri Lanka', 'woocommerce' ),
								'SD' => __( 'Sudan', 'woocommerce' ),
								'SR' => __( 'Suriname', 'woocommerce' ),
								'SJ' => __( 'Svalbard and Jan Mayen', 'woocommerce' ),
								'SZ' => __( 'Swaziland', 'woocommerce' ),
								'SE' => __( 'Sweden', 'woocommerce' ),
								'CH' => __( 'Switzerland', 'woocommerce' ),
								'SY' => __( 'Syria', 'woocommerce' ),
								'TW' => __( 'Taiwan', 'woocommerce' ),
								'TJ' => __( 'Tajikistan', 'woocommerce' ),
								'TZ' => __( 'Tanzania', 'woocommerce' ),
								'TH' => __( 'Thailand', 'woocommerce' ),
								'TL' => __( 'Timor-Leste', 'woocommerce' ),
								'TG' => __( 'Togo', 'woocommerce' ),
								'TK' => __( 'Tokelau', 'woocommerce' ),
								'TO' => __( 'Tonga', 'woocommerce' ),
								'TT' => __( 'Trinidad and Tobago', 'woocommerce' ),
								'TN' => __( 'Tunisia', 'woocommerce' ),
								'TR' => __( 'Turkey', 'woocommerce' ),
								'TM' => __( 'Turkmenistan', 'woocommerce' ),
								'TC' => __( 'Turks and Caicos Islands', 'woocommerce' ),
								'TV' => __( 'Tuvalu', 'woocommerce' ),
								'UG' => __( 'Uganda', 'woocommerce' ),
								'UA' => __( 'Ukraine', 'woocommerce' ),
								'AE' => __( 'United Arab Emirates', 'woocommerce' ),
								'GB' => __( 'United Kingdom', 'woocommerce' ),
								'US' => __( 'United States', 'woocommerce' ),
								'UY' => __( 'Uruguay', 'woocommerce' ),
								'UZ' => __( 'Uzbekistan', 'woocommerce' ),
								'VU' => __( 'Vanuatu', 'woocommerce' ),
								'VA' => __( 'Vatican', 'woocommerce' ),
								'VE' => __( 'Venezuela', 'woocommerce' ),
								'VN' => __( 'Vietnam', 'woocommerce' ),
								'WF' => __( 'Wallis and Futuna', 'woocommerce' ),
								'EH' => __( 'Western Sahara', 'woocommerce' ),
								'WS' => __( 'Western Samoa', 'woocommerce' ),
								'YE' => __( 'Yemen', 'woocommerce' ),
								'ZM' => __( 'Zambia', 'woocommerce' ),
								'ZW' => __( 'Zimbabwe', 'woocommerce' )
							));
							if(empty($country)){
								$country = 'US';
							}
						?>
						<tr>
							<td><label for="dropshipper_country"><strong><?php echo __('Country','woocommerce-dropshippers'); ?></strong></label></td>
							<td><select name="dropshipper_country">
							<?php
								foreach ($countries as $country_code => $country_name) {
									$selected = '';
									if($country == $country_code) $selected = 'selected="selected"';
									echo '<option value="'.$country_code.'" '.$selected.'>'. htmlspecialchars($country_name) .'</option>' . "\n";
								}
							?>
							</select>
							</td>
						</tr>
						<tr>
							<td><label for="dropshipper_national_shipping_price"><strong><?php echo str_replace('%SYMBOL%', get_woocommerce_currency_symbol(), __('National shipping price (in shop currency: %SYMBOL%)','woocommerce-dropshippers') ); ?></strong></label></td>
							<td><input type="text" name="dropshipper_national_shipping_price" value="<?php echo $national_shipping_price; ?>"></td>
						</tr>
						<tr>	
							<td><label for="dropshipper_international_shipping_price"><strong><?php echo str_replace('%SYMBOL%', get_woocommerce_currency_symbol(), __('International shipping price (in shop currency: %SYMBOL%)','woocommerce-dropshippers') ); ?></strong></label></td>
							<td><input type="text" name="dropshipper_international_shipping_price" value="<?php echo $international_shipping_price; ?>"></td>
						</tr>
						<?php endif; ?>
					</table>
					<?php
						/*settings_fields( 'WooCommerce_Dropshippers' );
						do_settings_sections( 'WooCommerce_Dropshippers' );*/
					?>
					<?php submit_button(__('Save Settings','woocommerce-dropshippers')); ?>
				</form>
				<?php
			}

			/** DROPSHIPPER PRICE HOOK IN ADMIN PRODUCTS **/
			// Check if Multidrop extension is active
			if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'save_post', 'artic_wcdm_dropshipper_save_admin_simple_dropshipper_price' );
			}
			else{
				add_action( 'save_post', 'dropshipper_save_admin_simple_dropshipper_price' );
			}
			function dropshipper_save_admin_simple_dropshipper_price( $post_id ) {
				if (isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce'))return;
				if(isset($_POST['dropshipper_price'])){
					$new_data = $_POST['dropshipper_price'];
					$post_ID = $_POST['post_ID'];
					update_post_meta($post_ID, '_dropshipper_price', $new_data) ;
				}
			}
			// Check if Multidrop extension is active
			if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'woocommerce_product_options_pricing', 'artic_wcdm_dropshipper_add_admin_dropshipper_price', 10, 2 );
			}
			else{
				add_action( 'woocommerce_product_options_pricing', 'dropshipper_add_admin_dropshipper_price', 10, 2 );
			}

			function dropshipper_add_admin_dropshipper_price( $loop ){ 
			$drop_price = get_post_meta( get_the_ID(), '_dropshipper_price', true );
			if(!$drop_price){ $drop_price = ''; }
			?>
			<tr>
			  <td><div>
				  <p class="form-field _regular_price_field ">
					<label><?php echo __( 'Dropshipper Price','woocommerce-dropshippers' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
					<input step="any" type="text" class="wc_input_price short" name="dropshipper_price" value="<?php echo $drop_price; ?>"/>
				  </p>
				</div></td>
			</tr>
			<?php }

			// Check if Multidrop extension is active
			if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				//Display Fields
				add_action( 'woocommerce_product_after_variable_attributes', 'artic_wcdm_dropshipper_add_admin_variable_dropshipper_price', 10, 3 );
				//JS to add fields for new variations
				add_action( 'woocommerce_product_after_variable_attributes_js', 'artic_wcdm_dropshipper_add_admin_variable_dropshipper_price_js' );
				//Save variation fields
				add_action( 'woocommerce_process_product_meta_variable', 'artic_wcdm_dropshipper_admin_variable_dropshipper_price_process', 10, 1 );
			}
			else{
				//Display Fields
				add_action( 'woocommerce_product_after_variable_attributes', 'dropshipper_add_admin_variable_dropshipper_price', 10, 3 );
				//JS to add fields for new variations
				add_action( 'woocommerce_product_after_variable_attributes_js', 'dropshipper_add_admin_variable_dropshipper_price_js' );
				//Save variation fields
				add_action( 'woocommerce_process_product_meta_variable', 'dropshipper_admin_variable_dropshipper_price_process', 10, 1 );
			}

			function dropshipper_add_admin_variable_dropshipper_price( $loop, $variation_data, $post ) {
				$dropshipper_price = get_post_meta( $post->ID, '_dropshipper_price', true);
			?>
			<tr>
			  <td><div>
			  	<p class="form-row form-row-full">
				  <label><?php echo __( 'Dropshipper Price','woocommerce-dropshippers' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
				  <input  step="any" type="text" size="5" class="wc_input_price short" name="dropshipper[<?php  echo $loop; ?>]" value="<?php 
				  	if(!empty($dropshipper_price)){
				  		echo $dropshipper_price;
				  	}
				  ?>"/>
				</p>
				</div></td>
			</tr>
			<?php
			}

			function dropshipper_add_admin_variable_dropshipper_price_js() {
			?>
			<tr>
			  <td><div>
				  <label><?php echo __( 'Dropshipper Price', 'woocommerce' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
				  <input step="any" type="text" size="5" name="dropshipper[' + loop + ']" />
				</div></td>
			</tr>
			<?php
			}
			function dropshipper_admin_variable_dropshipper_price_process( $post_id ) {
				if (isset( $_POST['variable_sku'] ) ) :
					$variable_sku = $_POST['variable_sku'];
					$variable_post_id = $_POST['variable_post_id'];

					$dropshipper_field = $_POST['dropshipper'];
					
					for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) :
						$variation_id = (int) $variable_post_id[$i];
						if ( isset( $dropshipper_field[$i] ) ) {
							update_post_meta( $variation_id, '_dropshipper_price', stripslashes( $dropshipper_field[$i] ) );
							update_post_meta( $variation_id, '_parent_product', $post_id );
						}
					endfor;
					update_post_meta( $post_id, '_variation_prices', $dropshipper_field );
					update_post_meta( $post_id, '_dropshipper_price', '' );
				endif;
			}

			// Check if Multidrop extension is active
			if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				add_action( 'woocommerce_save_product_variation', 'artic_wcdm_woocommerce_save_dropshippers_product_variation', 10, 2 );
			}
			else{
				add_action( 'woocommerce_save_product_variation', 'woocommerce_save_dropshippers_product_variation', 10, 2 );
			}
			function woocommerce_save_dropshippers_product_variation( $variation, $index ){
				if (isset( $_POST['variable_sku'] ) ) :
					$post_ids = $_POST['variable_post_id'];
					$dropshipper_field = $_POST['dropshipper'];
					foreach ($post_ids as $key => $variation_id) {
						update_post_meta( $variation_id, '_dropshipper_price', stripslashes( $dropshipper_field[$key] ) );
					}
				endif;
			}

			/* ADD RESET EARNINGS AJAX IN DROPSHIPPERS LIST */
			add_action('wp_ajax_reset_earnings', 'reset_earnings_callback');
			function reset_earnings_callback() {
				check_ajax_referer( 'SpaceRubberDuck', 'security' );
				if(isset($_POST['id'])){
					$id = intval( $_POST['id'] );
					update_user_meta($id, 'dropshipper_earnings', 0);
					echo 'true';
				}
				else{
					echo 'false';
				}
				die(); // this is required to return a proper result
			}

			/* AJAX SLIP REQUEST FOR DROPSHIPPERS */
			require_once(sprintf("%s/dropshipper-slip.php", dirname(__FILE__)));

			/* ADD MEDIA UPLOADER IN PLUGIN SETTINGS */
			add_action('admin_enqueue_scripts', 'woocommerce_dropshippers_enqueue_media');
			function woocommerce_dropshippers_enqueue_media() {
				if (isset($_GET['page']) && $_GET['page'] == 'WooCommerce_Dropshippers') {
					wp_enqueue_media();
					wp_register_script('woocommerce_admin_settings', WP_PLUGIN_URL.'/woocommerce-dropshippers/admin_settings.js', array('jquery'));
					wp_enqueue_script('woocommerce_admin_settings');
				}
			}

			/* ADD MULTILINGUAL SUPPORT */
			add_action( 'plugins_loaded', 'woocommerce_dropshippers_load_textdomain' );
			function woocommerce_dropshippers_load_textdomain() {
			  load_plugin_textdomain( 'woocommerce-dropshippers', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' ); 
			}

			/* DROPSHIPPERS EDITING SHIPPING INFO */
			add_action( 'admin_footer', 'dropshipper_edit_shipping_info' );
			function dropshipper_edit_shipping_info() {
				if ( current_user_can( 'show_dropshipper_widget' ) && (!in_array('administrator', wp_get_current_user()->roles)) )  {
			?>
				<script type="text/javascript" >
				function js_save_dropshipper_shipping_info(my_order_id, my_info) {
					var data = {
						action: 'dropshipper_shipping_info_edited',
						id: my_order_id,
						info: my_info
					};
					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.post(ajaxurl, data, function(response) {
						if(response == 'true'){
							jQuery('#dropshipper_shipping_info_'+my_order_id+' .dropshipper_date').html(jQuery('#input-dialog-date').val());
							jQuery('#dropshipper_shipping_info_'+my_order_id+' .dropshipper_tracking_number').html(jQuery('#input-dialog-trackingnumber').val());
							jQuery('#dropshipper_shipping_info_'+my_order_id+' .dropshipper_shipping_company').html(jQuery('#input-dialog-shippingcompany').val());
							jQuery('#dropshipper_shipping_info_'+my_order_id+' .dropshipper_notes').html(jQuery('#input-dialog-notes').val());
						}
					});
				}
				</script>
				<?php
				}
			}
			add_action('wp_ajax_dropshipper_shipping_info_edited', 'dropshipper_shipping_info_edited_callback');
			function dropshipper_shipping_info_edited_callback() {
				global $wpdb;
				if(isset($_POST['id']) && isset($_POST['info']) ){
					$id = intval( $_POST['id'] );
					$info = $_POST['info'];
					update_post_meta($_POST['id'], 'dropshipper_shipping_info_'.get_current_user_id(), $info);
					echo 'true';
				}
				else{
					echo 'false';
				}
				die(); // this is required to return a proper result
			}

			/* ADD A FEE */
			add_action( 'woocommerce_cart_calculate_fees','artic_woocommerce_custom_surcharge' );
			function artic_woocommerce_custom_surcharge() {
				global $woocommerce;
				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;
				$options = get_option('woocommerce_dropshippers_options');
				if(isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee']=='Yes'){
				 	$customer_country = $woocommerce->customer->get_shipping_country();
					$cart = $woocommerce->cart->get_cart();
					$products_ids = array();
					$products_dropshippers = array();
					$surcharge = 0;

					foreach ($cart as $key => $product) {
						if(! in_array($product['product_id'], $products_ids)){
							$products_ids[] = $product['product_id'];
							$dropshipper = get_post_meta( $product['product_id'], 'woo_dropshipper', true);
							if(!empty($dropshipper)){
								$drop_user = get_user_by( 'login', $dropshipper );
								if(!empty($drop_user)){
									$drop_user_id = $drop_user->ID;
								}
								else{
									$drop_user_id = 0;
								}
								$nat = 0;
								$inter = 0;
								if(! isset($products_dropshippers[$dropshipper]) ){
									//get the values for shipping
									$nat = get_user_meta($drop_user_id, 'national_shipping_price', true);
									$inter = get_user_meta($drop_user_id, 'international_shipping_price', true);
									if(empty($nat)){$nat = 0;}
									if(empty($inter)){$inter = 0;}
									$products_dropshippers[$dropshipper] = array(
										'national' => $nat,
										'international' => $inter
									);
									
									$country = get_user_meta($drop_user_id, 'dropshipper_country', true);
									if(empty($country)){ $country = 'US';}
									if($customer_country == $country){
					 					$surcharge += $products_dropshippers[$dropshipper]['national'];
									}
									else{
										$surcharge += $products_dropshippers[$dropshipper]['international'];
									}
								}
							}
						}
					}
					if($surcharge > 0){
						// get fee name from admin config
						$woocommerce->cart->add_fee( 'Dropshipping Fee', $surcharge, false, '' ); // false = tax included
					}
				}
			}

			/* DROPSHIPPERS BULK ASSIGN */
			add_action( 'admin_footer', 'dropshippers_bulk_assign' );
			function dropshippers_bulk_assign() {
				global $pagenow;
				if ( current_user_can( 'manage_woocommerce' ) && $pagenow=='admin.php' && $_GET['page']=='dropshippers_bulk_assign')  {
			?>
				<script type="text/javascript" >
				function js_dropshippers_bulk_assign(user, taxonomy, term) {
					jQuery('.dropassign').hide();
					jQuery('.dropassign').attr("disabled", "disabled");
					jQuery('#dropspinner_' + taxonomy).show();
					jQuery('#bulk-updated').hide(300);
					jQuery('#bulk-error').hide(300);

					var data = {
						action: 'dropshippers_bulk_assign',
						my_user: user,
						my_taxonomy: taxonomy,
						my_term: term,
					};
					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.post(ajaxurl, data, function(response) {
						if(response == 'true'){
							jQuery('#bulk-updated').show(300);
						}
						else{
							jQuery('#bulk-error').show(300);
						}
						jQuery('.dropassign').show();
						jQuery('.dropassign').removeAttr("disabled");
						jQuery('#dropspinner_' + taxonomy).hide();
					});
				}
				</script>
				<?php
				}
			}

			add_action('wp_ajax_dropshippers_bulk_assign', 'dropshippers_bulk_assign_callback');
			function dropshippers_bulk_assign_callback() {
				global $wpdb;
				$args = array(
					'posts_per_page' => -1,
					'post_type' => 'product',
					'tax_query' => array(
						array(
							'taxonomy' => $_POST['my_taxonomy'],
							'field'    => 'slug',
							'terms'    => $_POST['my_term'],
						),
					),
				);
				$the_query = new WP_Query( $args );
				// The Loop
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$id = get_the_ID();
					update_post_meta($id, 'woo_dropshipper', $_POST['my_user']);
				}
				wp_reset_postdata();
				echo 'true';
				die(); // this is required to return a proper result
			}

			/* DROPSHIPPERS BULK PRICE */
			add_action( 'admin_footer', 'dropshippers_bulk_price' );
			function dropshippers_bulk_price() {
				global $pagenow;
				if ( current_user_can( 'manage_woocommerce' ) && $pagenow=='admin.php' && $_GET['page']=='dropshippers_bulk_price')  {
			?>
				<script type="text/javascript" >
				function js_dropshippers_bulk_price(user, operation, value, mode) {
					jQuery('.dropassign').hide();
					jQuery('.dropassign').attr("disabled", "disabled");
					jQuery('.dropspinner').show();
					jQuery('#bulk-updated').hide(300);
					jQuery('#bulk-error').hide(300);

					var data = {
						action: 'dropshippers_bulk_price',
						my_user: user,
						my_operation: operation,
						my_value: value,
						my_mode: mode
					};
					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.post(ajaxurl, data, function(response) {
						if(response == 'true'){
							jQuery('#bulk-updated').show(300);
						}
						else{
							jQuery('#bulk-error').show(300);
						}
						jQuery('.dropassign').show();
						jQuery('.dropassign').removeAttr("disabled");
						jQuery('.dropspinner').hide();
					});
				}
				</script>
				<?php
				}
			}
			add_action('wp_ajax_dropshippers_bulk_price', 'dropshippers_bulk_price_callback');
			function dropshippers_bulk_price_callback() {
				global $wpdb;
				$user = $_POST['my_user'];
				$operation = $_POST['my_operation'];
				$value = floatval($_POST['my_value']);
				$mode = $_POST['my_mode'];

				$decimal_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_decimal_sep')), ENT_QUOTES);

				if($value < 0){
					$value = 0;
				}

				$the_query = new WP_Query(
					array(
						'post_type' => 'product',
						'meta_key' => 'woo_dropshipper',
						'meta_query' => array(
							'relation' => 'OR',
							array(
								'key' => 'woo_dropshipper',
								'value' => $_POST['my_user'],
								'compare' => '=',
							),
							array(
								'key' => 'woo_dropshipper',
								'value' => ':"'. $_POST['my_user'] .'";',
								'compare' => 'LIKE',
							)
						),
						'posts_per_page' => -1
					)
				);

				if($mode == 'drop-price-from-regular'){
					// The Loop
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						$id = get_the_ID();
						$product = new WC_Product_Variable($id);
						$price = floatval($product->price);
						$variations = $product->get_available_variations();

						$new_price = 0;
						if($operation == '%'){
							$new_price = $price/100*$value;
						}
						elseif($operation == '+'){
							$new_price = $price - $value;
							if($new_price < 0) {
								$new_price = 0;
							}
						}
						$new_price = str_replace('.', $decimal_sep, ''.$new_price);

						$woo_dropshipper = get_post_meta( $id, 'woo_dropshipper', true );
						if(empty($woo_dropshipper) || ($woo_dropshipper == '--')){
							// do nothing
						}
						else{
							if(is_string($woo_dropshipper)){
								update_post_meta($id, '_dropshipper_price', $new_price);
							}
							else{
								$drop_prices = get_post_meta($id, '_dropshipper_prices', true );
								if(empty($drop_prices)){
									$drop_prices = array();	
								}
								$drop_prices[$user] = $new_price;
								update_post_meta($id, '_dropshipper_prices', $drop_prices);
							}
						}

						foreach ($variations as $key => $variation) {
							$tmp_id = $variation['variation_id'];
							$tmp_product = new WC_Product_Variation($tmp_id);
							$tmp_price = floatval($tmp_product->price);

							$tmp_new_price = 0;
							if($operation == '%'){
								$tmp_new_price = $tmp_price/100*$value;
							}
							elseif($operation == '+'){
								$tmp_new_price = $tmp_price - $value;
								if($tmp_new_price < 0) {
									$tmp_new_price = 0;
								}
							}
							$tmp_new_price = str_replace('.', $decimal_sep, ''.$tmp_new_price);

							$drop_prices = get_post_meta($tmp_id, '_dropshipper_prices', true );
							if(empty($woo_dropshipper) || ($woo_dropshipper == '--')){
								// do nothing
							}
							else{
								if(is_string($woo_dropshipper)){
									update_post_meta($tmp_id, '_dropshipper_price', $tmp_new_price);
								}
								else{
									$drop_prices = get_post_meta($tmp_id, '_dropshipper_prices', true );
									if(empty($drop_prices)){
										$drop_prices = array();	
									}
									$drop_prices[$user] = $tmp_new_price;
									update_post_meta($tmp_id, '_dropshipper_prices', $drop_prices);
								}
							}
						}
					}
				}
				elseif($mode == 'regular-price-from-drop'){
					// Check if Multidrop extension is active
					if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
						$is_multidrop_active = true;
					}
					else{
						$is_multidrop_active = false;
					}
					// The Loop
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						$id = get_the_ID();
						$product = new WC_Product_Variable($id);
						$price = get_post_meta( $id, '_dropshipper_price', true);
						if(empty($price)){
							$price = 0;
						}
						$price = (float) str_replace($decimal_sep, '.', ''.$price);

						if($is_multidrop_active){
							$drop_prices = get_post_meta($id, '_dropshipper_prices', true );
							if(empty($drop_prices)){
								$drop_prices = array();	
							}
							if(isset($drop_prices[$user])){
								$price = (float) str_replace($decimal_sep, '.', ''.$drop_prices[$user]);
							}
						}

						$variations = $product->get_available_variations();

						$new_price = 0;
						if($operation == '%'){
							$new_price = $price/100*$value;
						}
						elseif($operation == '+'){
							$new_price = $price + $value;
							if($new_price < 0) {
								$new_price = 0;
							}
						}
						update_post_meta($id, '_regular_price', $new_price);
						update_post_meta($id, '_price', $new_price);

						foreach ($variations as $key => $variation) {
							$tmp_id = $variation['variation_id'];
							$tmp_product = new WC_Product_Variation($tmp_id);
							$tmp_price = floatval($tmp_product->price);
							$tmp_price = get_post_meta($tmp_id, '_dropshipper_price', true);
							if(empty($tmp_price)){
								$tmp_price = 0;
							}
							$tmp_price = (float) str_replace($decimal_sep, '.', ''.$tmp_price);

							if($is_multidrop_active){
								$drop_prices = get_post_meta($tmp_id, '_dropshipper_prices', true );
								if(empty($drop_prices)){
									$drop_prices = array();	
								}
								if(isset($drop_prices[$user])){
									$tmp_price = (float) str_replace($decimal_sep, '.', ''.$drop_prices[$user]);
								}
							}

							$tmp_new_price = 0;
							if($operation == '%'){
								$tmp_new_price = $tmp_price/100*$value;
							}
							elseif($operation == '+'){
								$tmp_new_price = $tmp_price - $value;
								if($tmp_new_price < 0) {
									$tmp_new_price = 0;
								}
							}
							update_post_meta($tmp_id, '_regular_price', $tmp_new_price);
							update_post_meta($tmp_id, '_price', $tmp_new_price);
						}
					}
				}
				
				wp_reset_postdata();
				echo 'true';
				die(); // this is required to return a proper result
			}

			if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				//	¯\(°_o)/¯
			}
			else{
				/* ADD IMPORT/EXPORT TO ADMIN MENU */
				require_once(sprintf("%s/import-export.php", dirname(__FILE__)));
				function dropshippers_importexport_menu(){
					add_submenu_page(
						'WooCommerce_Dropshippers',
						__('WooCommerce Dropshipper Stock Update','woocommerce-dropshippers'),
						__('Stock Update','woocommerce-dropshippers'),
						'manage_woocommerce',
						'dropshippers_importexport',
						'dropshippers_importexport_page'
					);
				}
				add_action('admin_menu', 'dropshippers_importexport_menu');
			}
		}

		add_action('wp_ajax_woocommerce_dropshippers_mark_as_shipped', 'woocommerce_dropshippers_mark_as_shipped_callback');
		add_action('wp_ajax_nopriv_woocommerce_dropshippers_mark_as_shipped', 'woocommerce_dropshippers_mark_as_shipped_callback');
		function woocommerce_dropshippers_mark_as_shipped_callback(){
			$one_time_keys = get_option('woocommerce_dropshippers_one_time_keys');
			if(empty($one_time_keys)){
				$one_time_keys = array();
			}

			if(empty($_GET['otk'])){
				echo '<h1>'. __('Order Shipping Notification Error','woocommerce-dropshippers') .'</h1>';
				echo '<p>'. __('There was a problem in marking the order you requested as shipped.<br>This is likely due to an error, or the order has already been marked as shipped.<br>Please login to your dropshipper dashboard to check the status.<br>Thanks!','woocommerce-dropshippers') .'</p>';
			}
			else{
				if(!isset($one_time_keys[$_GET['otk']])){
					echo '<h1>'. __('Order Shipping Notification Error','woocommerce-dropshippers') .'</h1>';
					echo '<p>'. __('There was a problem in marking the order you requested as shipped.<br>This is likely due to an error, or the order has already been marked as shipped.<br>Please login to your dropshipper dashboard to check the status.<br>Thanks!','woocommerce-dropshippers') .'</p>';
				}
				else{
					$order_id = $one_time_keys[$_GET['otk']]['order_id'];
					$user_login = $one_time_keys[$_GET['otk']]['user_login'];
					$user = get_user_by('login', $user_login);
					$user_id = $user->ID;
					
					$result = woocommerce_dropshippers_dropshipped($order_id, $user_id, $user_login);
					if($result == 'true'){
						$order = new WC_Order($order_id);
						$order_number = $order->get_order_number();
						echo '<h1>'. str_replace('#NUM#', $order_number, __('Order ##NUM# Shipping Notification','woocommerce-dropshippers') ) .'</h1>';
						echo '<p>'. str_replace('#NUM#', $order_number, __('The order <strong>##NUM#</strong> has been notified as shipped to the store owner.<br>Thanks!','woocommerce-dropshippers') ) .'</p>';
					}
					else{
						echo '<h1>'. __('Order Shipping Notification Error','woocommerce-dropshippers') .'</h1>';
						echo '<p>'. __('There was a problem in marking the order you requested as shipped.<br>This is likely due to an error, or the order has already been marked as shipped.<br>Please login to your dropshipper dashboard to check the status.<br>Thanks!','woocommerce-dropshippers') .'</p>';
					}
				}
			}
		
			die();
		}
	}
}

?>