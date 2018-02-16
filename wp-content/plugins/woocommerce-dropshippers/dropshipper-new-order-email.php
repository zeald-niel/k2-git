<?php
/**
* File: dropshipper-new-order-email.php
* Author: ArticNet LLC.
**/
	function dropshippers_delete_old_slip_folder($path){
		if(!empty($path)){
			if(is_dir($path) === true){
				$files = array_diff(scandir($path), array('.', '..'));
				foreach ($files as $file){
					unlink(realpath($path) . '/' . $file);
				}
				return rmdir($path);
			}
			return false;
		}
		return false;
	}

	/** SEND EMAIL TO DROPSHIPPERS **/
	function dropshippers_set_html_content_type() {
		return 'text/html';
	}
	// Check if Multidrop extension is active
	if ( is_plugin_active_for_network('woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php') || in_array( 'woocommerce-dropshippers-multidrop/woocommerce-dropshippers-multidrop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		add_action('woocommerce_order_status_processing', 'artic_wcdm_send_email_to_dropshippers');
	}
	else{
		add_action('woocommerce_order_status_processing', 'send_email_to_dropshippers');
		// revert
		add_action('woocommerce_order_status_processing_to_failed', 'dropshipper_cancel_earnings');
		add_action('woocommerce_order_status_processing_to_on-hold', 'dropshipper_cancel_earnings');
		add_action('woocommerce_order_status_processing_to_pending', 'dropshipper_cancel_earnings');
		add_action('woocommerce_order_status_processing_to_refunded', 'dropshipper_cancel_earnings');
		add_action('woocommerce_order_status_processing_to_cancelled', 'dropshipper_cancel_earnings');
	}

	// REMOVE DROPSHIPPERS EARNINGS
	function dropshipper_cancel_earnings($order_id){
		$dropshippers_earnings = get_post_meta( $order_id, 'dropshippers_earnings', true);
		if(!empty($dropshippers_earnings)){
			foreach($dropshippers_earnings as $user_id => $earnings){
				$dropshipper_earning = get_user_meta($user_id, 'dropshipper_earnings', true);
				if($dropshipper_earning){
					$dropshipper_earning = $dropshipper_earning - $earnings;
					update_user_meta($user_id, 'dropshipper_earnings', $dropshipper_earning);
				}
			}
		}
		delete_post_meta($order_id, 'dropshippers_earnings');

		//also delete one time keys for dropshippers
		$one_time_keys = get_option('woocommerce_dropshippers_one_time_keys');
		$new_one_time_keys = array();
		foreach ($one_time_keys as $key => $value) {
			if($value['order_id'] != $order_id){
				$new_one_time_keys[$key] = $value;
			}
		}
		update_option('woocommerce_dropshippers_one_time_keys', $new_one_time_keys);
	}

	// ADD DROPSHIPPERS EARNINGS AND SEND EMAILS
	function send_email_to_dropshippers($order_id){
		global $wp_filesystem;

		require_once('tfpdf.php');

		// Dropshippers involved in the order and their shipping information
		$final_dropshippers = array();
		$dropshippers_earnings = array();
		$real_products = array();
		$emails = array();
		$order = new WC_Order($order_id);
		$post = get_post($order_id);
		$items = $order->get_items();
		$dropshippers = get_users(array( 'role' => 'dropshipper'));
		$options = get_option('woocommerce_dropshippers_options');

		if(empty($options['send_pdf']) ){
			$options['send_pdf'] = 'Yes';
		}

		$latest_order_date = get_option('woocommerce_dropshippers_latest_order_date');
		$today = date('Y-m-d');
		$twoDaysAgo = date('Y-m-d', strtotime(''. $today . ' -2 days') );
		$slipDirectory = sprintf("%s/slips/%s", dirname(__FILE__), $today);

		if($latest_order_date){
			$todayInSeconds = strtotime(''. $today);
			$twoDaysAgoInSeconds = strtotime(''. $today . ' -2 days');
			$latestOrderDateInSeconds = strtotime(''. $latest_order_date);

			if( ($todayInSeconds - $latestOrderDateInSeconds) < $twoDaysAgoInSeconds ){
				$folderToDelete = $latest_order_date;

				while($folderToDelete != $twoDaysAgo){
					$pathToDelete = sprintf("%s/slips/%s", dirname(__FILE__), $folderToDelete);
					if( (!empty($pathToDelete)) && is_dir($pathToDelete) ) {
						dropshippers_delete_old_slip_folder($pathToDelete);
					}

					$folderToDelete = date('Y-m-d', strtotime(''. $folderToDelete . ' +1 day') );
				}
				update_option('woocommerce_dropshippers_latest_order_date', $twoDaysAgo);
			}

			$order_number = $order->get_order_number();
		}
		else{ // date not found
			update_option('woocommerce_dropshippers_latest_order_date', $twoDaysAgo);
		}


		if( is_array( $dropshippers ) && count( $dropshippers ) > 0 ) {
			// load the keys
			$one_time_keys = get_option('woocommerce_dropshippers_one_time_keys');
			foreach ($dropshippers as $dropshipper) {
				$dropshipper_earning = get_user_meta($dropshipper->ID, 'dropshipper_earnings', true);
				if(!$dropshipper_earning){
					$dropshipper_earning = 0;
				}
				$user_login = $dropshipper->user_login;
				foreach ($items as $item_id => $item) {
					$item['redunfed_items'] = 0;

					if(get_post_meta( $item["product_id"], 'woo_dropshipper', true) == $user_login){
						$refunded_items = $order->get_qty_refunded_for_item($item_id);
						$item['redunfed_items'] = $refunded_items;
						if($item['qty'] - $refunded_items > 0){
							if(! isset($real_products[$user_login])){
								$real_products[$user_login] = array();
							}
							array_push($real_products[$user_login], $item);
						}
					}
				}
				
				// Prepare the email and update earnings if there are products for this dropshipper
				if(!empty($real_products[$user_login])){
					// the dropshipper is involved, generate the one time key
					if(empty($one_time_keys)){
						$one_time_keys = array();
					}
					$one_time_key = '';
					while( empty($one_time_key) || isset($one_time_keys[$one_time_key]) ){
						$one_time_key = md5(''. $order_id .'-'. $user_login .'-'. rand());
					}
					$one_time_keys[$one_time_key] = array(
						'order_id' => $order_id,
						'user_login' => $user_login,
					);

					$one_time_url = admin_url('admin-ajax.php?action=woocommerce_dropshippers_mark_as_shipped&otk='.$one_time_key);

					$final_dropshippers[$user_login] = 'Not shipped yet';

					$billing_first_name = '';
					if(method_exists($order, 'get_billing_first_name')){
						$billing_first_name = $order->get_billing_first_name();
					}
					else{
						$billing_first_name = $order->billing_first_name;
					}
					$billing_last_name = '';
					if(method_exists($order, 'get_billing_last_name')){
						$billing_last_name = $order->get_billing_last_name();
					}
					else{
						$billing_last_name = $order->billing_last_name;
					}
					$order_date = '';
					if(method_exists($order, 'get_date_created')){
						$order_date = date('Y-m-d H:i:s', $order->get_date_created());
					}
					else{
						$order_date = $order->order_date;
					}

					ob_start();
					?>
					<div style="background-color: #f5f5f5; width: 100%; -webkit-text-size-adjust: none ; margin: 0; padding: 70px  0  70px  0;">
			        	<table width="100%" cellspacing="0" cellpadding="0" border="0" height="100%">
			        		<tbody><tr><td valign="top" align="center">
							<table width="600" cellspacing="0" cellpadding="0" border="0" style="-webkit-box-shadow: 0  0  0  3px  rgba; box-shadow: 0  0  0  3px  rgba; -webkit-border-radius: 6px ; border-radius: 6px ; background-color: #fdfdfd; border: 1px  solid  #dcdcdc; -webkit-border-radius: 6px ; border-radius: 6px ;" id="template_container"><tbody><tr><td valign="top" align="center">
								<table width="600" cellspacing="0" cellpadding="0" border="0" bgcolor="#557da1" style="background-color: #557da1; color: #ffffff; -webkit-border-top-left-radius: 6px ; -webkit-border-top-right-radius: 6px ; border-top-left-radius: 6px ; border-top-right-radius: 6px ; border-bottom: 0px; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;" id="template_header"><tbody><tr><td>
									<h1 style="color: #ffffff; margin: 0; padding: 28px  24px; text-shadow: 0  1px  0  #7797b4; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;"><?php echo __('New customer order','woocommerce-dropshippers'); ?></h1>
								</td></tr></tbody></table></td></tr><tr><td valign="top" align="center">
								<table width="600" cellspacing="0" cellpadding="0" border="0" id="template_body">
									<tbody><tr><td valign="top" style="background-color: #fdfdfd; -webkit-border-radius: 6px ; border-radius: 6px ;">
										<table width="100%" cellspacing="0" cellpadding="20" border="0"><tbody><tr><td valign="top">
										<div style="color: #737373; font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"><p><?php echo str_replace('%SURNAME%', $billing_last_name, str_replace('%NAME%', $billing_first_name, __('You have received an order from %NAME% %SURNAME%. Their order is as follows:','woocommerce-dropshippers'))); ?></p>
										<h2 style="color: #505050; display: block; font-family: Arial; font-size: 30px; font-weight: bold; margin-top: 10px; margin-right: 0px; margin-bottom: 10px; margin-left: 0px; text-align: left; line-height: 150%;"><?php echo str_replace('%NUMBER%', $order->get_order_number(), __('From order %NUMBER%','woocommerce-dropshippers')); ?> (<!-- time ignored --><?php echo $order_date; ?>)</h2>
										<?php echo apply_filters('woo_dropshipper_email_after_order_id', '', $order_id); ?>
										<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px  solid  #eee;">
										<thead><tr><th style="text-align: left; border: 1px  solid  #eee;"><?php echo __('Product','woocommerce-dropshippers'); ?></th>
						<th style="text-align: left; border: 1px  solid  #eee;"><?php echo __('Quantity','woocommerce-dropshippers'); ?></th>
						<?php
							if( (!isset($options['show_prices'])) || ( isset($options['show_prices']) && ($options['show_prices'] == 'Yes') ) ){
								echo '<th style="text-align: left; border: 1px  solid  #eee;">'. __('Price','woocommerce-dropshippers') .'</th>';
							}
						?>
					</tr></thead><tbody>
					<?php
						if($options['send_pdf'] == 'Yes'){
							// create pdf
							$pdf = new tFPDF();

							$pdf->AddPage();
							// Add a Unicode font (uses UTF-8)
							$pdf->AddFont('Unifont','','unifont-8.0.01.ttf',true);
							$pdf->SetFont('Unifont','',16);
							// write header
							if(!empty($options['company_logo'])){
								$pdf->Image($options['company_logo'], null, null, 60);	
							}
							else{
								$pdf->SetFont('Unifont','',30);
								$site_title = get_bloginfo( 'name' );
								$pdf->Cell(100, 8, $site_title);
								$pdf->Ln();
								$pdf->SetFont('Unifont','',16);
							}
							$pdf->Cell(10, 6, ' ');
							$pdf->Ln();
							$pdf->Cell(100, 6, str_replace("%NUMBER%", $order_id, __('Order slip #%NUMBER%','woocommerce-dropshippers')));
							$pdf->Ln();
							$pdf->Cell(100, 8, __('From','woocommerce-dropshippers'));
							$pdf->Cell(100, 8, __('Shipped to','woocommerce-dropshippers'));
							$pdfY = $pdf->getY();
							$pdf->Ln();
							$pdf->SetFont('Unifont','',12);
							$pdf->MultiCell(80, 5, (isset($options['billing_address'])?($options['billing_address']):'') );
							$last_pdfY = $pdf->getY();
							$pdf->setY($pdfY);
							$pdf->Cell(10, 8, ' ');
							$pdf->Ln();
							$pdf->Cell(100, 8, ' ');
							$pdf->MultiCell(100, 5, strip_tags(str_replace('<br/>', "\n", $order->get_formatted_shipping_address())) );

							// start table
							$pdf->setY(max($pdf->getY(), $last_pdfY));
							$pdf->SetFont('Unifont','',14);
							$pdf->Ln();
							$pdf->Cell(120, 8, __('Item','woocommerce-dropshippers'), 'TB');
							$pdf->Cell(66, 8, __('Quantity','woocommerce-dropshippers'), 'TB');
							$pdf->SetFont('Unifont','',10);
							$pdf->Ln();
							$pdf->Cell(10, 2, ' ');
							$pdf->Ln();
						}

						$drop_subtotal = 0;
						$drop_total_earnings = 0;
						$sudicio = '';
						foreach ($real_products[$user_login] as $item) {
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
							$decimal_sep = wp_specialchars_decode(stripslashes(get_option('woocommerce_price_decimal_sep')), ENT_QUOTES);

							$drop_price = get_post_meta( $item['product_id'], '_dropshipper_price', true );
							if(!$drop_price){ $drop_price = 0;}
							$drop_price = (float) str_replace($decimal_sep, '.', ''.$drop_price);

							$drop_subtotal += ( ((float) $item['line_total']) + ((float) $item['line_tax']) );
							echo '<tr><td style="text-align: left; vertical-align: middle; border: 1px  solid  #eee; word-wrap: break-word;">'. __($my_item_post->post_title) . ' (SKU: '.$SKU.')';
							$item_meta = '';
							if($item['variation_id'] != 0){
								$drop_price = get_post_meta( $item['variation_id'], '_dropshipper_price', true );
								if(!$drop_price){ $drop_price = 0;}
								$drop_price = (float) str_replace($decimal_sep, '.', ''.$drop_price);
								
								if(method_exists($item, 'get_meta_data')){ // new method for WooCommerce 2.7
									foreach ($item->get_meta_data() as $product_meta_key => $product_meta_value) {
										if(!empty($product_meta_value->id)){
											$display_key  = wc_attribute_label( $product_meta_value->key, $product_from_id );
											$item_meta .= '<br/><small>' . $display_key . ': ' . $product_meta_value->value . '</small>' . "\n";
										}
									}
								}
								else{ // old method
									$_product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
									$item_meta_object = new WC_Order_Item_Meta( $item, $product_from_id );
									if ( $item_meta_object->meta ){
										$item_meta .= '<br/><small>' . nl2br( $item_meta_object->display( true, true ) ) . '</small>' . "\n";
									}
								}
								echo $item_meta;
							}
							echo '<br><small></small></td>';
							echo '<td style="text-align: left; vertical-align: middle; border: 1px  solid  #eee;">' . ($item['qty'] - $item['redunfed_items']) .'</td>';
							$drop_total_earnings += ($drop_price*($item['qty']-$item['redunfed_items']) );

							if( (!isset($options['show_prices'])) || ( isset($options['show_prices']) && ($options['show_prices'] == 'Yes') ) ){
								if($options['text_string'] == "Yes"){ //show prices
									echo '<td style="text-align: left; vertical-align: middle; border: 1px  solid  #eee;"><span class="amount">'. wc_price(( ((float) $item['line_total']) + ((float) $item['line_tax']) )).'<br>(Your earning: '.wc_price((float) ($drop_price*($item['qty']-$item['redunfed_items'] )) ).')</span></td></tr>';
								}
								else{
									echo '<td style="text-align: left; vertical-align: middle; border: 1px  solid  #eee;"><span class="amount">Your earning: '.wc_price((float) ($drop_price*($item['qty']-$item['redunfed_items'])) ).'</span></td></tr>';
								}
							}

							if($options['send_pdf'] == 'Yes'){
								// write pdf produtc line
								$pdfY = $pdf->getY();
								$pdfPageNo = $pdf->PageNo();
								if($pdfY > 270){
									$pdf->AddPage();
									$pdfY = $pdf->getY();
									$pdfPageNo = $pdf->PageNo();
								}

								$pdf->MultiCell(100,4, __($my_item_post->post_title) . ' (SKU: '.$SKU.")\n". ((!empty($item_meta))?(strip_tags($item_meta)):'') );
								$pdf->Cell(10, 2, ' ');
								$pdf->Ln();

								$endPdfY = $pdf->getY();
								$endPdfPageNo = $pdf->PageNo();

								$pdf->setPage($pdfPageNo);
								$pdf->setY($pdfY);

								$pdf->Cell(120, 4, ' ');
								$pdf->MultiCell(100,4, 'x'.($item['qty']-$item['redunfed_items']));
								$pdf->Cell(10, 4, ' ');
								$pdf->Ln();

								$pdf->setPage($endPdfPageNo);
								$pdf->setY($endPdfY);
							}
						}
						
						if($options['send_pdf'] == 'Yes'){
							if(!empty($options['slip_footer'])){
								$pdfY = $pdf->getY();
								$pdfPageNo = $pdf->PageNo();
								if($pdfY > 270){
									$pdf->AddPage();
									$pdfY = $pdf->getY();
									$pdfPageNo = $pdf->PageNo();
								}
								$pdf->MultiCell(190,4, "\n\n\n" . $options['slip_footer'], 0, 'C');
							}

							if(!file_exists($slipDirectory)) {
								mkdir($slipDirectory, 0755, true);
							}
							if(is_dir($slipDirectory)){
								$fakeindexfile = fopen($slipDirectory . '/index.php', 'w');
								fwrite($fakeindexfile, '<?php // Silence is golden. ?>');
								$pdf->Output( $slipDirectory . '/'. $order_id . '-'. $dropshipper->ID .'.pdf', 'F', true );
							}
						}

						//get the values for shipping
						$drop_total_earnings_no_shipping = $drop_total_earnings;
						$drop_subtotal_plus_dropshipper_shipping = $drop_subtotal;
						if( !isset($options['can_add_droshipping_fee']) || ( isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee'] == 'Yes' ) ){
							$customer_shipping_country = get_post_meta($order_id, '_shipping_country', true);
							$country = get_user_meta($dropshipper->ID, 'dropshipper_country', true);
							if(empty($country)){ $country = 'US';}
							$nat = get_user_meta($dropshipper->ID, 'national_shipping_price', true);
							$inter = get_user_meta($dropshipper->ID, 'international_shipping_price', true);
							if(empty($nat)){$nat = 0;}
							if(empty($inter)){$inter = 0;}
							if($customer_shipping_country == $country){
			 					$drop_total_earnings += $nat;
			 					$drop_subtotal_plus_dropshipper_shipping = $drop_subtotal + $nat;
							}
							else{
								$drop_total_earnings += $inter;
								$drop_subtotal_plus_dropshipper_shipping = $drop_subtotal + $inter;
							}
						}
						if($drop_total_earnings){
							update_user_meta($dropshipper->ID, 'dropshipper_earnings', ($dropshipper_earning + $drop_total_earnings));
							$dropshippers_earnings[$dropshipper->ID] = $drop_total_earnings;
						}
					?>
					</tbody>
					<tfoot>
						<?php
							if( (!isset($options['show_prices'])) || ( isset($options['show_prices']) && ($options['show_prices'] == 'Yes') ) ){
						?>
							<tr><th style="text-align: left; border: 1px  solid  #eee; border-top-width: 4px;" colspan="2"><?php echo __('Cart Subtotal:','woocommerce-dropshippers'); ?></th>
							<td style="text-align: left; border: 1px  solid  #eee; border-top-width: 4px;"><span class="amount">
							<?php
								if($options['text_string'] == "Yes"){ //show full prices
									echo wc_price($drop_subtotal) . '<br>(Your earning: '.wc_price((float) $drop_total_earnings_no_shipping ).')';
								}
								else{
									echo 'Your earning: '.wc_price((float) $drop_total_earnings_no_shipping );
								}
							?>
							</span></td>
							</tr>
							<?php
								if(! isset($options['can_see_email_shipping'])){ $options['can_see_email_shipping'] = 'Yes'; }
								if($options['can_see_email_shipping'] == 'Yes'){
							?>
							<tr><th style="text-align: left; border: 1px  solid  #eee;" colspan="2"><?php echo __('Shipping:','woocommerce-dropshippers'); ?></th>
								<td style="text-align: left; border: 1px  solid  #eee;"><?php echo $order->get_shipping_method(); ?></td>
							</tr>
							<?php } ?>
							<?php
								if( !isset($options['can_add_droshipping_fee']) || ( isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee'] == 'Yes' ) ){
									if( (!isset($options['show_prices'])) || ( isset($options['show_prices']) && ($options['show_prices'] == 'Yes') ) ){
										if( ($customer_shipping_country == $country) && ($nat > 0) ){
										?>
											<tr><th style="text-align: left; border: 1px  solid  #eee;" colspan="2"><?php echo __('Dropshipper Shipping Fee:','woocommerce-dropshippers'); ?></th>
												<td style="text-align: left; border: 1px  solid  #eee;"><?php echo wc_price((float) $nat); ?></td>
											</tr>
										<?php
										}
										elseif( ($customer_shipping_country != $country) && ($inter > 0) ){
										?>
											<tr><th style="text-align: left; border: 1px  solid  #eee;" colspan="2"><?php echo __('Dropshipper Shipping Fee:','woocommerce-dropshippers'); ?></th>
												<td style="text-align: left; border: 1px  solid  #eee;"><?php echo wc_price((float) $inter); ?></td>
											</tr>
										<?php	
										}
									}
								}
							?>
							<tr><th style="text-align: left; border: 1px  solid  #eee;" colspan="2"><?php echo __('Order Total:','woocommerce-dropshippers'); ?></th>
								<td style="text-align: left; border: 1px  solid  #eee;"><span class="amount"><?php
									if($options['can_see_email_shipping'] == 'Yes'){
										if($options['text_string'] == "Yes"){ //show full prices
											echo wc_price( (float) ($drop_subtotal_plus_dropshipper_shipping + $order->get_total_shipping()));
											echo '<br>(Your earning: ' . wc_price( (float) $drop_total_earnings ) . ')';
										}
										else{
											echo 'Your earning: ' . wc_price( (float) $drop_total_earnings );
										}

									}
									else{
										if($options['text_string'] == "Yes"){ //show full prices
											echo wc_price( (float) $drop_subtotal_plus_dropshipper_shipping);
											echo '<br>(Your earning: ' . wc_price( (float) $drop_total_earnings_no_shipping ) .')';
										}
										else{
											echo 'Your earning: ' . wc_price( (float) $drop_total_earnings_no_shipping );
										}
									}
								?></span></td>
							</tr>
						<?php
							}
							else{
								if(! isset($options['can_see_email_shipping'])){ $options['can_see_email_shipping'] = 'Yes'; }
								if($options['can_see_email_shipping'] == 'Yes'){
							?>
							<tr><th style="text-align: left; border: 1px  solid  #eee;"><?php echo __('Shipping:','woocommerce-dropshippers'); ?></th>
								<td style="text-align: left; border: 1px  solid  #eee;"><?php echo $order->get_shipping_method(); ?></td>
							</tr>
							<?php }
								if( !isset($options['can_add_droshipping_fee']) || ( isset($options['can_add_droshipping_fee']) && $options['can_add_droshipping_fee'] == 'Yes' ) ){
									if( (!isset($options['show_prices'])) || ( isset($options['show_prices']) && ($options['show_prices'] == 'Yes') ) ){
										if( ($customer_shipping_country == $country) && ($nat > 0) ){
										?>
											<tr><th style="text-align: left; border: 1px  solid  #eee;" colspan="2"><?php echo __('Dropshipper Shipping Fee:','woocommerce-dropshippers'); ?></th>
												<td style="text-align: left; border: 1px  solid  #eee;"><?php echo wc_price((float) $nat); ?></td>
											</tr>
										<?php
										}
										elseif( ($customer_shipping_country != $country) && ($inter > 0) ){
										?>
											<tr><th style="text-align: left; border: 1px  solid  #eee;" colspan="2"><?php echo __('Dropshipper Shipping Fee:','woocommerce-dropshippers'); ?></th>
												<td style="text-align: left; border: 1px  solid  #eee;"><?php echo wc_price((float) $inter); ?></td>
											</tr>
										<?php	
										}
									}
								}

							}
						?>
					</tfoot></table>

					<?php
						if(isset($options['can_see_customer_order_notes']) && $options['can_see_customer_order_notes'] == 'Yes'){
							if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
								echo '<p><strong>' . __( 'Customer Provided Note', 'woocommerce' ) . ':</strong> ' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
							}
						}
						echo apply_filters( 'woocoommerce_dropshippers_email_after_order_details', '' );
					?>
					<?php echo apply_filters('woo_dropshipper_email_after_products', '', $order_id); ?>
					<h2 style="color: #505050; display: block; font-family: Arial; font-size: 30px; font-weight: bold; margin-top: 10px; margin-right: 0px; margin-bottom: 10px; margin-left: 0px; text-align: left; line-height: 150%;"><?php echo __('Customer details','woocommerce-dropshippers'); ?></h2>
					<?php
					if($options['can_see_email'] == 'Yes'){ ?>
						<p><strong><?php echo __('Email:','woocommerce-dropshippers'); ?></strong>
						<?php
							$billing_email = '';
							if(method_exists($order, 'get_billing_email')){
								$billing_email = $order->get_billing_email();
							}
							else{
								$billing_email = $order->billing_email;
							}
						?>
						<a onclick="return rcmail.command('compose','<?php echo $billing_email; ?>',this)" href="mailto:<?php echo $billing_email; ?>"><?php echo $billing_email; ?></a></p>
					<?php
					}
					if($options['can_see_phone'] == 'Yes'){ ?>
						<?php
							$billing_phone = '';
							if(method_exists($order, 'get_billing_phone')){
								$billing_phone = $order->get_billing_phone();
							}
							else{
								$billing_phone = $order->billing_phone;
							}
						?>
						<p><strong><?php echo __('Tel:','woocommerce-dropshippers'); ?></strong> <?php echo $billing_phone; ?></p>
					<?php
					}
					?>
					<?php echo apply_filters('woo_dropshipper_email_customer_details', '', $order_id); ?>
					<table cellspacing="0" cellpadding="0" border="0" style="width: 100%; vertical-align: top;"><tbody><tr><td width="50%" valign="top">
					<h3 style="color: #505050; display: block; font-family: Arial; font-size: 26px; font-weight: bold; margin-top: 10px; margin-right: 0px; margin-bottom: 10px; margin-left: 0px; text-align: left; line-height: 150%;"><?php echo __('Billing address','woocommerce-dropshippers'); ?></h3><p><?php echo (isset($options['billing_address'])?nl2br($options['billing_address']):''); ?></p>
					</td>
					<td width="50%" valign="top">
						<h3 style="color: #505050; display: block; font-family: Arial; font-size: 26px; font-weight: bold; margin-top: 10px; margin-right: 0px; margin-bottom: 10px; margin-left: 0px; text-align: left; line-height: 150%;"><?php echo __('Shipping address','woocommerce-dropshippers'); ?></h3><p><?php echo $order->get_formatted_shipping_address(); ?></p>

					</td>

				</tr></tbody></table></div>
				<?php echo apply_filters('woo_dropshipper_email_after_customer_details', '', $order_id); ?>
																	</td>
			                                                    </tr></tbody></table></td>
			                                        </tr></tbody></table></td>
			                            </tr><tr><td valign="top" align="center">
			                                    <table width="100%" cellspacing="0" cellpadding="10" border="0" style="border-top: 0px; -webkit-border-radius: 6px; text-align: center;"><tbody><tr><td valign="top">
                                                <p><?php
	                                                if(empty($options['mark_as_shipped'])){
	                                                	$options['mark_as_shipped'] = 'No';
	                                                }
                                                	if($options['mark_as_shipped'] == 'Yes'){
	                                                	echo str_replace('#URL#', $one_time_url, __('To mark this order as shipped please login to your dashboard or click the following link: <a href="#URL#">#URL#</a>','woocommerce-dropshippers') );
                                                	}
                                                	else{
                                                		echo __('To mark this order as shipped please login to your dashboard.','woocommerce-dropshippers');
                                                	}
                                                ?></p></td></tr></tbody></table>

			                                	<table width="600" cellspacing="0" cellpadding="10" border="0" style="border-top: 0px; -webkit-border-radius: 6px;" id="template_footer"><tbody><tr><td valign="top">
			                                                <table width="100%" cellspacing="0" cellpadding="10" border="0"><tbody><tr><td valign="middle" style="border: 0; color: #99b1c7; font-family: Arial; font-size: 12px; line-height: 125%; text-align: center;" id="credit" colspan="2"><p><?php echo bloginfo('name'); ?></p>
			                                                        </td>
			                                                    </tr></tbody></table></td>
			                                        </tr></tbody></table></td>
			                            </tr></tbody></table></td>
			                </tr></tbody></table></div>
					<?php
					$return_var = ob_get_clean();

					add_filter( 'wp_mail_content_type', 'dropshippers_set_html_content_type' );
					require_once(WP_PLUGIN_DIR . '/woocommerce/includes/emails/class-wc-email.php');
					require_once(WP_PLUGIN_DIR . '/woocommerce/includes/libraries/class-emogrifier.php');
					$emailer = new WC_Email();
					$emailer_attachments = $emailer->get_attachments();
					if(isset($options['send_pdf']) && $options['send_pdf'] == 'Yes'){
						$emailer_attachments[] = $slipDirectory . '/'. $order_id . '-'. $dropshipper->ID .'.pdf';
					}
					$headers = $emailer->get_headers();
					if(!empty($options['dropshipper_email_bcc'])){
						$headers .= 'Bcc: ' . $options['dropshipper_email_bcc'] . "\r\n";
					}
					if(!empty($options['from_email'])){
						$headers .= 'From: '.get_bloginfo('name'). ' <'. $options['from_email'] .'>' . "\r\n";
						// if from is changed, use standard wp_mail
						wp_mail( $dropshipper->user_email, '' . $order->get_order_number() . ' – ' . __('New customer order','woocommerce-dropshippers'), $return_var, $headers, $emailer_attachments );
					}
					else{
						$emailer->send( $dropshipper->user_email, '' . $order->get_order_number() . ' – ' . __('New customer order','woocommerce-dropshippers'), $return_var, $headers, $emailer_attachments );
					}
					// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
					remove_filter( 'wp_mail_content_type', 'dropshippers_set_html_content_type' );
				}
				update_post_meta($order_id, 'dropshippers', $final_dropshippers);
			}
			update_post_meta($order_id, 'dropshippers_earnings', $dropshippers_earnings);
			update_option('woocommerce_dropshippers_one_time_keys', $one_time_keys);
		}
	}
?>