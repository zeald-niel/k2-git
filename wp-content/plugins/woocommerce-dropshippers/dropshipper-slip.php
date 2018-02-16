<?php
/**
* File: dropshipper-slip.php
* Author: ArticNet LLC.
**/

/* Fake ajax request */
add_action('wp_ajax_woocommerce_dropshippers_get_slip', 'woocommerce_dropshippers_get_slip');

function woocommerce_dropshippers_get_slip(){
	if( empty( $_GET['action'] ) || ! is_user_logged_in() || !check_admin_referer( $_GET['action'] ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.','woocommerce-dropshippers' ) );
	}
	else{
		$options = get_option('woocommerce_dropshippers_options');
		$current_user = wp_get_current_user()->user_login;
		$order_id = $_GET['order_id'];
		$order = new WC_Order($order_id);
		?>
		<html>
		<head>
			<title><?php echo str_replace("%NUMBER%", $order_id, __('Order slip #%NUMBER%','woocommerce-dropshippers')); ?></title>
			<style type="text/css">
				body{
					font-family: Arial;
				}
				.tableWrapper {
					margin:0px;padding:0px;
					width:100%;
					border:1px solid #000000;
				}
				.tableWrapper table{
				    border-collapse: collapse;
				    border-spacing: 0;
					width:100%;
					margin:0px;padding:0px;
				}
				.tableWrapper tr:first-child{
					background-color:#dddddd;
					text-transform: uppercase;
				}
				.tableWrapper td{
					vertical-align:middle;
					border:1px solid #000000;
					border-width:0px 0px 1px 0px;
					text-align:center;
					padding:7px;
					font-size:14px;
					font-family:Arial;
					font-weight:normal;
					color:#000000;
				}
				.tableWrapper tr:last-child td{
					border-width:0px 0px 0px 0px;
				}
				.tableWrapper tr td:last-child{
					border-width:0px 0px 1px 0px;
				}
				.tableWrapper tr:last-child td:last-child{
					border-width:0px 0px 0px 0px;
				}
				.tableWrapper tr:first-child td{
					background-color:#005fbf;
					border:0px solid #000000;
					text-align:center;
					border-width:0px 0px 1px 0px;
					font-size:14px;
					font-family:Arial;
					font-weight:bold;
					color:#ffffff;
				}
				.tableWrapper tr:first-child td:first-child{
					border-width:0px 0px 1px 0px;
				}
				.tableWrapper tr:first-child td:last-child{
					border-width:0px 0px 1px 0px;
				}
				.tableWrapper th{
					border:0px solid #000000;
					border-width:0px 0px 1px 0px;
				}
				.tableWrapper th:first-child{
					padding: 7px;
					border-width:0px 0px 1px 0px;
				}

				#left-div{
					float: left;
				}
				#right-div{
					float: right;
				}
				#slip-wrap{
					clear: both;
				}
				img{
					height: 180px;
					width: auto;
				}
				#sliptable img {
					width: auto;
					height: auto;
					max-width: 48px;
					max-height: 48px;
					vertical-align: middle;
				}
			</style>

			<style type="text/css" media="print">
				#button-wrap{
					display: none;
				}
			</style>
		</head>
		<body>
			<div id="button-wrap">
				<button onclick="window.print();return false;"><?php echo __('Print','woocommerce-dropshippers'); ?></button>
				&nbsp;
				<button onclick="window.close();return false;"><?php echo __('Close','woocommerce-dropshippers'); ?></button>
				<hr>
			</div>
			<img alt="" src="<?php
				if(isset($options['company_logo'])){
					if(strrpos($options['company_logo'], 'http', -strlen($options['company_logo'])) !== false){
						echo $options['company_logo'];
					}
					else{
						echo home_url(substr($options['company_logo'],strlen($_SERVER['DOCUMENT_ROOT'])));
					}
				}
			?>" />
			<div id="head-wrap">
				<br>
				<div id="left-div">
					<h3 style="margin-bottom: 3px"><?php echo __('From','woocommerce-dropshippers'); ?></h3>
					<?php echo (isset($options['billing_address'])?str_replace("\n","<br>",$options['billing_address']):''); ?>
				</div>
				<div id="right-div">
					<h3 style="margin-bottom: 3px"><?php echo __('Shipped to','woocommerce-dropshippers'); ?></h3>
					<?php echo $order->get_formatted_shipping_address(); ?>
					<br><br>
				</div>
			</div>
			<div id="slip-wrap">
				<h3 style="margin-bottom: 3px"><?php __('Package content','woocommerce-dropshippers'); ?></h3>
				<div class="tableWrapper">
					<table id="sliptable">
						<tr><th><?php echo __('Item','woocommerce-dropshippers'); ?></th><th><?php echo __('Quantity','woocommerce-dropshippers'); ?></th></tr>
					<?php
					foreach ($order->get_items() as $item){
						if(get_post_meta( $item["product_id"], 'woo_dropshipper', true) == $current_user){
							$tmpproduct = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
							$meta = new WC_Order_Item_Meta( $item['item_meta'] );	
							$my_meta = $meta->display( true, true );
							if(!empty($my_meta)){
								$my_meta = '<br>'.$my_meta;
							}
							else{
								$my_meta = '';
							}
							$product_image_string = '';
							if(!empty($options['slip_images']) && $options['slip_images'] == 'Yes'){
								$product_image_string = $tmpproduct->get_image() .' ';
							}
							echo '<tr><td>'. $product_image_string . $item['name']. ' (#'.$tmpproduct->get_sku().')'.$my_meta.'</td><td>'.$item['qty'].'</td></tr>';
						}
					}
					?>
					</table>
				</div>
				<br>
				<?php echo apply_filters( 'woocoommerce_dropshippers_packing_slip_after_order_details', '' ); ?>
				<center><?php echo (isset($options['slip_footer'])?str_replace("\n","<br>",$options['slip_footer']):''); ?></center>
			</div>
		</body>
		</html>
		<?php
	}
	exit;
}

?>