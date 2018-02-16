<?php
/**
* File: orders.php
* Author: ArticNet LLC.
**/
?>
<div class="dropshippers-header" style="margin:0; padding:0; width:100%; height:100px; background: url('<?php echo plugins_url( 'images/headerbg.png', __FILE__ ) ?>'); background-repeat: repeat-x;">
	<img src="<?php echo plugins_url( 'images/woocommerce-dropshippers-header.png', __FILE__ ) ?>" style="margin:0; padding:0; width:auto; height:100px;">
</div>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
<h1> <?php _e('Dropshipper Orders','woocommerce-dropshippers'); ?></h1>
<div id="input-dialog-template" style="display:none">
	<label for="input-dialog-date"><?php echo __('Date', 'woocommerce-dropshippers'); ?></label>
	<input type="text" name="input-dialog-date" id="input-dialog-date" style="width:100%">
	<label for="input-dialog-trackingnumber"><?php echo __('Tracking Number(s)', 'woocommerce-dropshippers'); ?></label>
	<textarea name="input-dialog-trackingnumber" id="input-dialog-trackingnumber" style="width:100%"></textarea>
	<label for="input-dialog-shippingcompany"><?php echo __('Shipping Company', 'woocommerce-dropshippers'); ?></label>
	<textarea name="input-dialog-shippingcompany" id="input-dialog-shippingcompany" style="width:100%"></textarea>
	<label for="input-dialog-notes"><?php echo __('Notes', 'woocommerce-dropshippers'); ?></label>
	<textarea name="input-dialog-notes" id="input-dialog-notes" style="width:100%"></textarea>
</div>
<script type="text/javascript">
jQuery( "#input-dialog-date" ).datepicker({ dateFormat: 'yy-mm-dd' });
function open_dropshipper_dialog(my_id) {
	jQuery('#input-dialog-date').val(jQuery('#dropshipper_shipping_info_'+my_id+' .dropshipper_date').html());
	jQuery('#input-dialog-trackingnumber').val(jQuery('#dropshipper_shipping_info_'+my_id+' .dropshipper_tracking_number').html());
	jQuery('#input-dialog-shippingcompany').val(jQuery('#dropshipper_shipping_info_'+my_id+' .dropshipper_shipping_company').html());
	jQuery('#input-dialog-notes').val(jQuery('#dropshipper_shipping_info_'+my_id+' .dropshipper_notes').html());
	jQuery('#input-dialog-template').dialog({
		title: '<?php echo __('Shipping Info','woocommerce-dropshippers'); ?>',
		buttons: [{
			text: '<?php echo __('Save','woocommerce-dropshippers'); ?>',
			click: function() {
				js_save_dropshipper_shipping_info(my_id, {
					date: jQuery('#input-dialog-date').val(),
					tracking_number: jQuery('#input-dialog-trackingnumber').val(),
					shipping_company: jQuery('#input-dialog-shippingcompany').val(),
					notes: jQuery('#input-dialog-notes').val()
				});
				jQuery( this ).dialog( "close" );
			}
		}]
	});
}
</script>
<?php
	global $woocommerce;
	$ajax_nonce = wp_create_nonce( "SpaceRubberDuck" );

	$table_string = ''; // the <table></table>
	$current_user = wp_get_current_user()->user_login;

	$actual_month = intval(date('m'));
	$actual_year = intval(date('Y'));
	$selected_month = $actual_month;
	$selected_year = $actual_year;
	if(isset($_GET['filtermonth'])){
		$filter_date = explode('-', $_GET['filtermonth']);
		if(count($filter_date == 2)){
			$selected_month = intval($filter_date[0]);
			$selected_year = intval($filter_date[1]);
		}
	}
	$user_date = wp_get_current_user()->user_registered;
	$user_month = intval(date('m', strtotime($user_date)));
	$user_year = intval(date('Y', strtotime($user_date)));

	$woo_ver = woocommerce_dropshipper_get_woo_version_number();
	if($woo_ver >= 2.2){
		$query = new WP_Query(
			array(
				'post_type' => 'shop_order',
				'post_status' => array( 'wc-processing', 'wc-completed' ),
				'monthnum' => $selected_month,
				'year' => $selected_year,
				'posts_per_page' => -1
			)
		);
	}
	else{
		$query = new WP_Query(
			array(
				'post_type' => 'shop_order',
				'post_status' => 'publish',
				'monthnum' => $selected_month,
				'year' => $selected_year,
				'posts_per_page' => -1
			)
		);
	}
	$order_count = 0;
	$options = get_option('woocommerce_dropshippers_options');
	$decimal_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_decimal_sep')), ENT_QUOTES);

	// The Loop
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			// actual product list of the dropshipper
			$real_products = array();

			$query->the_post();
			$order = new WC_Order(get_the_ID());
			$order_number = $order->get_order_number();
			$order_total = 0;
			$order_dropshippers = artic_dropshippers_get_post_meta(get_the_ID(), 'dropshippers', true);

			$tel = '';
			if(method_exists($order, 'get_billing_phone')){
				$tel = $order->get_billing_phone();
			}
			else{
				$tel = $order->billing_phone;
			}
			if(empty($tel)){
				$tel = '-';
			}
			foreach ($order->get_items() as $item) {
				if(get_post_meta( $item["product_id"], 'woo_dropshipper', true) == $current_user){
					array_push($real_products, $item);
				}
			}
			if( (sizeof($real_products) > 0) && ($order->get_status() == 'completed' || $order->get_status() == 'processing') ){
				$order_count++;
				$is_shipped = false;
				if(isset($order_dropshippers[wp_get_current_user()->user_login])){
					if($order_dropshippers[wp_get_current_user()->user_login] == "Shipped"){
						$is_shipped = true;
					}
				}
				$order_date = '';
				if(method_exists($order, 'get_date_created')){
					$order_date = date('Y-m-d H:i:s', $order->get_date_created());
				}
				else{
					$order_date = $order->order_date;
				}
				$table_string .= '<tr class="alternate'. ($is_shipped?' is-shipped':'') .' tr-order-'.get_the_ID().'">';
				$table_string .= '<td class="column-columnname"><strong>' . $order_number . '</strong></td>';
				$table_string .= '<td class="column-columnname"><strong>' . $order_date . '</strong></td>';
				$table_string .= '<td class="column-columnname"><ul style="margin:0;">';
				$drop_total_price = 0;
				foreach ($real_products as $item) {
					$order_total += ( ((float) $item['line_total']) + ((float) $item['line_tax']) );
					$meta = new WC_Order_Item_Meta( $item );	
					$my_meta = $meta->display( true, true );
					
					$my_item_post = get_post($item['product_id']);
					$drop_price = get_post_meta( $item['product_id'], '_dropshipper_price', true );
					if(!$drop_price){ $drop_price = 0;}
					$drop_price = (float) str_replace($decimal_sep, '.', ''.$drop_price);
					if($item['variation_id'] != 0){
						$drop_price = get_post_meta( $item['variation_id'], '_dropshipper_price', true );
						if(!$drop_price){ $drop_price = 0;}
						$drop_price = (float) str_replace($decimal_sep, '.', ''.$drop_price);
					}
					$my_item_post_title = __($my_item_post->post_title);
					if(isset($options['text_string']) && $options['text_string'] == "Yes"){
						$table_string .= "<li>" . $my_item_post_title . ' ' . $my_meta . ' x' . $item['qty'] . ' ('. __('FULL PRICE:','woocommerce-dropshippers') .' <span class="artic-toberewritten">' . wc_price(( ((float) $item['line_total']) + ((float) $item['line_tax']) )) .'</span><span class="artic-tobereconverted" style="display:none;">'.( ((float) $item['line_total']) + ((float) $item['line_tax']) ).'</span> - '. __('MY EARNINGS:','woocommerce-dropshippers') .' <span class="artic-toberewritten">' . wc_price((float) $drop_price*$item['qty']) .'</span><span class="artic-tobereconverted" style="display:none;">'.((float) $drop_price*$item['qty']).'</span>)';
					}
					else{
						$table_string .= "<li>" . $my_item_post_title . ' ' . $my_meta . ' x' . $item['qty'] . '('. __('MY EARNINGS:','woocommerce-dropshippers') .' <span class="artic-toberewritten">' . wc_price((float) $drop_price*$item['qty']) .'</span><span class="artic-tobereconverted" style="display:none;">'.((float) $drop_price*$item['qty']).'</span>)';
					}
					$table_string .= '</li>' . "\n";
					$drop_total_price += ($drop_price*$item['qty']);
				}
				$table_string .= '</ul></td>';
				$shipping_country = '';
				if(method_exists($order, 'get_shipping_country')){
					$shipping_country = $order->get_shipping_country();
				}
				else{
					$shipping_country = $order->shipping_country;
				}
				$table_string .= '<td class="column-columnname">' . $woocommerce->countries->countries[$shipping_country] .'<br/>'. $order->get_formatted_shipping_address() . '</td>';
				if($options['can_see_email'] == "Yes" || $options['can_see_phone'] == "Yes"){
					$table_string .= '<td class="column-columnname">';
					$billing_email = '';
					if(method_exists($order, 'get_billing_email')){
						$billing_email = $order->get_billing_email();
					}
					else{
						$billing_email = $order->billing_email;
					}
					
					if($options['can_see_email'] == "Yes"){
						$table_string .= $billing_email . '<br/>';
					}
					if($options['can_see_phone'] == "Yes"){
						$table_string .= 'Tel: '. $tel;
					}
					if($options['can_see_email'] == "Yes"){
						$table_string .= '<div class="row-actions"><span><a href="mailto:'.$billing_email.'">'. __('Send an Email','woocommerce-dropshippers') .'</a></span></div>';
					}
					$table_string .= '</td>';
				}
				$table_string .= '<td class="column-columnname">';
				if(isset($options['text_string']) && $options['text_string'] == "Yes"){
					$table_string .= __('FULL TOTAL:','woocommerce-dropshippers') .'<span class="artic-toberewritten">' . wc_price((float) $order_total) . '</span><span class="artic-tobereconverted" style="display:none;">'.((float) $order_total).'</span><br>';
				}
				$table_string .= __('MY EARNINGS:','woocommerce-dropshippers') . '<span class="artic-toberewritten">' . wc_price((float) $drop_total_price) . '</span><span class="artic-tobereconverted" style="display:none;">'.((float) $drop_total_price).'</span>';
				$table_string .= "</td>\n";
				$dropshipper_shipping_info = get_post_meta(get_the_ID(), 'dropshipper_shipping_info_'.get_current_user_id(), true);
				if(!$dropshipper_shipping_info){
					$dropshipper_shipping_info = array(
						'date' => '',
						'tracking_number' => '',
						'shipping_company' => '',
						'notes' => ''
					);
				}
				$table_string .= '<td class="column-columnname" id="dropshipper_shipping_info_'.get_the_ID().'">';
				$table_string .= '<strong>'. __('Date', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_date">'. $dropshipper_shipping_info['date']. '</span><br/>';
				$table_string .= '<strong>'. __('Tracking Number(s)', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_tracking_number">'. $dropshipper_shipping_info['tracking_number']. '</span><br/>';
				$table_string .= '<strong>'. __('Shipping Company', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_shipping_company">'. $dropshipper_shipping_info['shipping_company']. '</span><br/>';
				$table_string .= '<strong>'. __('Notes', 'woocommerce-dropshippers') .'</strong>: <span class="dropshipper_notes">'. $dropshipper_shipping_info['notes']. '</span><br/>';
				if(!$is_shipped){
					$table_string .= '<button id="open_dropshipper_dialog_'. get_the_ID() .'" class="button button-primary" onclick="open_dropshipper_dialog('. get_the_ID() .')" style="margin-top:2px" >'. __('Edit Shipping Info','woocommerce-dropshippers') .'</button>';
				}
				$table_string .= '</td><td class="column-columnname">' . __( $order->get_status(), 'woocommerce' );
				if($is_shipped){
					$table_string .= '<br/>'. __('Shipped','woocommerce-dropshippers');
				}
				else{
					$table_string .= '<br/><button id="mark_dropshipped_'. get_the_ID() .'" class="button button-primary" onclick="js_dropshipped('. get_the_ID() .')" style="margin-top:2px" >'. __('Mark as Shipped','woocommerce-dropshippers') .'</button>';
				}
				$fake_ajax_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_dropshippers_get_slip&order_id=' . get_the_ID()), 'woocommerce_dropshippers_get_slip' );
				$table_string .= '<br/><button id="print_slip_'. get_the_ID() .'" class="button button-primary" onclick="js_print_slip(\''. $fake_ajax_url .'\')" style="margin-top:2px" >'. __('Print packing slip','woocommerce-dropshippers') .'</button>';
				$table_string .= '</td></tr>' . "\n";
			}
		}
		$table_string .= '</table>';
	} else {
		// no posts found
	}
	?>
	<?php
	/* Restore original Post Data */
	wp_reset_postdata();
	echo '<div class="wrap"><h2></h2></wrap>';
	echo '<div class="wrap">';
	echo '<p>'. __('Order count:','woocommerce-dropshippers') ." $order_count</p>";
	$options = get_option('woocommerce_dropshippers_options');
	?>
	<table class="wp-list-table widefat fixed posts" cellspacing="0">
		<thead>
			<tr>
				<th class="manage-column column-cb column-columnname" style=""><h2><?php echo __('Shop Owner Billing Info','woocommerce-dropshippers'); ?></h2></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><p><?php echo (isset($options['billing_address'])?nl2br($options['billing_address']):''); ?></p></td>
			</tr>
		</tbody>
	</table>
	<hr>
	<form name="month" action="" method="GET"> 
		<label for="filtermonth"><?php echo __('Select month','woocommerce-dropshippers' ); ?>: </label>
		<input type="hidden" name="page" value="dropshipper_order_list_page" />
		<select name="filtermonth">
		<?php
			$first_ride = true;
			do{
				if($first_ride){
					$first_ride = false;
				}
				else{
					$user_month++;
					if($user_month == 13){
						$user_month = 1;
						$user_year++;
					}
				}
				$my_selected = '';
				if( ($user_month == $selected_month) && ($user_year == $selected_year) ){
					$my_selected = ' selected="selected"';
				}
				echo '<option value="'. sprintf('%02d', $user_month) .'-'. $user_year .'"'. $my_selected .'>'. sprintf('%02d', $user_month) .'-'. $user_year."</option>\n";
			}
			while( ($user_month != $actual_month) || ($user_year != $actual_year) );
		?>
		</select>
		<input class="button" type="submit" value="<?php echo __('Filter orders','woocommerce-dropshippers'); ?>" />
		<label for="hide-shipped" style="margin-left:20px;"><?php echo __('Hide Shipped','woocommerce-dropshippers'); ?></label>
		<input type="checkbox" id="hide-shipped" />
	</form>
	<hr/>
	<table class="wp-list-table widefat fixed posts" cellspacing="0">
	<thead>
	<tr>
		<tr>
			<th id="co" class="manage-column column-cb column-columnname" scope="col" style=""><?php echo __('ID','woocommerce-dropshippers'); ?></th>
			<th id="columnname" class="manage-column column-columnname" scope="col"><?php echo __('Date','woocommerce-dropshippers'); ?></th>
			<th id="columnname" class="manage-column column-columnname" scope="col"><?php echo __('Product','woocommerce-dropshippers'); ?></th>
			<th id="columnname" class="manage-column column-columnname" scope="col"><?php echo __('Client Info','woocommerce-dropshippers'); ?></th>
			<?php
				if($options['can_see_email'] == "Yes" || $options['can_see_phone'] == "Yes"){
					echo '<th id="columnname" class="manage-column column-columnname" scope="col">'. __('Contact Info','woocommerce-dropshippers') .'</th>';
				}
			?>
			<th class="manage-column column-columnname" scope="col"><?php echo __('Earnings','woocommerce-dropshippers'); ?></th>
			<th id="columnname" class="manage-column column-columnname" scope="col"><?php echo __('Shipping Info','woocommerce-dropshippers'); ?></th>
			<th id="columnname" class="manage-column column-columnname num" scope="col"><?php echo __('Status','woocommerce-dropshippers'); ?></th>
		</tr>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<tr>
			<th class="manage-column column-cb column-columnname" scope="col"><?php echo __('ID','woocommerce-dropshippers'); ?></th>
			<th class="manage-column column-columnname" scope="col"><?php echo __('Date','woocommerce-dropshippers'); ?></th>
			<th class="manage-column column-columnname" scope="col"><?php echo __('Product','woocommerce-dropshippers'); ?></th>
			<th class="manage-column column-columnname" scope="col"><?php echo __('Client Info','woocommerce-dropshippers'); ?></th>
			<?php
				if($options['can_see_email'] == "Yes" || $options['can_see_phone'] == "Yes"){
					echo '<th id="columnname" class="manage-column column-columnname" scope="col">'. __('Contact Info','woocommerce-dropshippers') .'</th>';
				}
			?>
			<th class="manage-column column-columnname" scope="col"><?php echo __('Earnings','woocommerce-dropshippers'); ?></th>
			<th class="manage-column column-columnname" scope="col"><?php echo __('Shipping Info','woocommerce-dropshippers'); ?></th>
			<th class="manage-column column-columnname num" scope="col"><?php echo __('Status','woocommerce-dropshippers'); ?></th>
		</tr>
	</tr>
	</tfoot>

	<tbody>
		<?php
			echo $table_string;
		?>
	</tbody>
</table>
<script type="text/javascript">
	jQuery('#hide-shipped').change(function(){
		if(jQuery(this).is(':checked')){
			jQuery('.is-shipped').hide();
		}
		else{
			jQuery('.is-shipped').show();
		}
	});

	function js_print_slip(url){
		var newwindow = window.open(url, 'DropshipperSlip',
			'toolbar=no, scrollbars=yes, resizable=yes, width=400, height=400, width=600, height=400');
		newwindow.resizeTo(600,400);
		if (window.focus) {newwindow.focus()}
		return false;
	}
</script>
	<?php
	echo '</div>';
	$user_id = get_current_user_id();
	$currency = get_user_meta($user_id, 'dropshipper_currency', true);
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