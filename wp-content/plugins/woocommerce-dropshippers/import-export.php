<?php

function woocommerce_dropshippers_csv_to_array($filename='', $delimiter=',', $enclosure='"')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;
	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE){
		while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE){
			if(!$header){
				$header = $row;
			}
			else{
				if(count($header) == count($row)){
					$data[] = array_combine($header, $row);
				}
			}
		}
		fclose($handle);
	}
	return $data;
}

function woocommerce_dropshippers_plugins_loaded(){
	global $pagenow;
	if( ($pagenow == 'admin.php') && isset($_POST['option_page']) && ($_POST['option_page'] == 'dropshippers_importexport') ){
		if(!current_user_can('manage_woocommerce')){
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		if(isset($_POST['action'])){
			if(wp_verify_nonce( $_POST['_wpnonce'], 'dropshippers_importexport_nonce' )){
				switch ($_POST['action']) {
					case 'update':
						update_option('dropshippers_importexport', array(
							'delimiter' => (isset($_POST['opt-delimiter']) ? stripslashes($_POST['opt-delimiter']) : ','),
							'enclosure' => (isset($_POST['opt-enclosure']) ? stripslashes($_POST['opt-enclosure']) : '"'),
						));
						$pageurl = admin_url( 'admin.php?page=dropshippers_importexport');
						wp_redirect(''. $pageurl . '&wdpstatus=true');
						exit;
						break;

					case 'import':
						$options = get_option('dropshippers_importexport', array(
							'delimiter' => ',',
							'enclosure' => '"',
						));
						try {
							// Undefined | Multiple Files | $_FILES Corruption Attack
							// If this request falls under any of them, treat it invalid.
							if ( !isset($_FILES['import-file']['error']) || is_array($_FILES['import-file']['error']) ) {
								wp_redirect(''. $pageurl . '&error=' . urlencode('Invalid parameters.') );
								exit;
							}
							// Check $_FILES['import-file']['error'] value.
							$pageurl = admin_url( 'admin.php?page=dropshippers_importexport');
							switch ($_FILES['import-file']['error']) {
								case UPLOAD_ERR_OK:
								break;
								case UPLOAD_ERR_NO_FILE:
									wp_redirect(''. $pageurl . '&error=' . urlencode('No file sent.') );
									exit;
								case UPLOAD_ERR_INI_SIZE:
								case UPLOAD_ERR_FORM_SIZE:
									wp_redirect(''. $pageurl . '&error=' . urlencode('Exceeded filesize limit.') );
									exit;
								default:
									wp_redirect(''. $pageurl . '&error=' . urlencode('Unknown errors.') );
									exit;
							}
							// You should also check filesize here.
							if ($_FILES['import-file']['size'] > 1000000) {
								wp_redirect(''. $pageurl . '&error=' . urlencode('Exceeded filesize limit.') );
								exit;
							}
							// DO NOT TRUST $_FILES['import-file']['mime'] VALUE !!
							// Check MIME Type by yourself.
							$finfo = new finfo(FILEINFO_MIME_TYPE);
							
							if (false === $ext = array_search(
								$finfo->file($_FILES['import-file']['tmp_name']),
								array(
									'text/csv',
									'text/html',
									'text/plain',
								),
								true
							)) {
								wp_redirect(''. $pageurl . '&error=' . urlencode('Invalid file format.') );
								exit;
							}
							$csv = woocommerce_dropshippers_csv_to_array($_FILES['import-file']['tmp_name'],$options['delimiter'],$options['enclosure']);
						} catch (RuntimeException $e) {
							wp_redirect(''. $pageurl . '&error=' . urlencode($e->getMessage()) );
							exit;
						}
						if($csv && !empty($csv)){
							if(!empty($csv)){
								foreach ($csv as $rowkey => $row) {
									if(isset($row['id']) && isset($row['dropshipper']) && isset($row['stock'])){
										$product = new WC_Product($row['id']);
										$product->set_stock($row['stock']);
										update_post_meta( $row['id'], 'woo_dropshipper', $row['dropshipper']);
									}
								}
							}
							wp_redirect(''. $pageurl . '&wdpprod=true');
							exit;
						}
						break;

					case 'export':
						$options = get_option('dropshippers_importexport', array(
							'delimiter' => ',',
							'enclosure' => '"',
						));
						header("Content-type: text/csv");
						header("Content-Disposition: attachment; filename=DropshippersExport.csv");
						header("Pragma: no-cache");
						header("Expires: 0");

						$args = array(
							'posts_per_page' => -1,
							'post_type' => 'product',
						);
						$the_query = new WP_Query( $args );
						echo 'id'.$options['delimiter'].'dropshipper'.$options['delimiter'].'stock'.$options['delimiter'].'sku'.$options['delimiter'].'prod_name'. "\n";
						// The Loop
						while ( $the_query->have_posts() ) {
							$the_query->the_post();
							$id = get_the_ID();
							$product = new WC_Product_Variable($id);
							$stock = $product->get_stock_quantity();
							$sku = $product->get_sku();
							$name = $product->get_title();
							$dropshipper = get_post_meta( $id, 'woo_dropshipper', true);
							$variations = $product->get_available_variations();
							if(!$dropshipper){
								$dropshipper = '';
							}
							echo $options['enclosure'] . $id . $options['enclosure'] . $options['delimiter'];
							echo $options['enclosure'] . $dropshipper . $options['enclosure'] . $options['delimiter'];
							echo $options['enclosure'] . $stock . $options['enclosure'] . $options['delimiter'];
							echo $options['enclosure'] . $sku . $options['enclosure'] . $options['delimiter'];
							echo $options['enclosure'] . $name . $options['enclosure'] . $options['delimiter'] . "\n";
							foreach ($variations as $key => $variation) {
								$tmp_id = $variation['variation_id'];
								$tmp_product = new WC_Product_Variation($tmp_id);
								$tmp_stock = $tmp_product->get_stock_quantity();
								$tmp_sku = $tmp_product->get_sku();
								$tmp_name = $tmp_product->get_title();
								echo $options['enclosure'] . $tmp_id . $options['enclosure'] . $options['delimiter'];
								echo $options['enclosure'] . $options['enclosure'] . $options['delimiter'];
								echo $options['enclosure'] . $tmp_stock . $options['enclosure'] . $options['delimiter'];
								echo $options['enclosure'] . $tmp_sku . $options['enclosure'] . $options['delimiter'];
								echo $options['enclosure'] . $tmp_name . $options['enclosure'] . $options['delimiter'] . "\n";
							}
						}
						wp_reset_postdata();

						exit();
						break;
					
					default:
						wp_die(__('You do not have sufficient permissions to access this page.'));
						break;
				}
			}
			else{
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
		}
	}
}
add_action('wp_loaded', 'woocommerce_dropshippers_plugins_loaded');


function dropshippers_importexport_page(){
	if(!current_user_can('manage_woocommerce')){
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
	$options = get_option('dropshippers_importexport', array(
		'delimiter' => ',',
		'enclosure' => '"',
	));
	?>
	<div class="dropshippers-header" style="margin:0; padding:0; width:100%; height:100px; background: url('<?php echo plugins_url( 'images/headerbg.png', __FILE__ ) ?>'); background-repeat: repeat-x;">
		<img src="<?php echo plugins_url( 'images/woocommerce-dropshippers-header.png', __FILE__ ) ?>" style="margin:0; padding:0; width:auto; height:100px;">
	</div>
	<div class="wrap">
		<?php screen_icon(); ?>
		<?php
			if(isset($_GET['error'])){
				echo '<div id="wdp-error" class="error"><p>'. htmlspecialchars($_GET['error']) .'</p></div>';
			}
			elseif(isset($_GET['wdpstatus'])){
				echo '<div id="wdp-updated" class="updated"><p>'. __('Settings updated.') .'</p></div>';
			}
			elseif(isset($_GET['wdpprod'])){
				echo '<div id="wdp-updated" class="updated"><p>'. __('Products updated.') .'</p></div>';
			}
		?>
		<h2>Dropshippers Stock Update</h2>
		<hr>
		<h3>General CSV Options</h3>
		<form method="post" action="<?php echo admin_url( 'admin.php?page=dropshippers_importexport'); ?>">
			<?php
				$nonce = wp_create_nonce( 'dropshippers_importexport_nonce');
			?>
			<input type='hidden' name='option_page' value='dropshippers_importexport' />
			<input type="hidden" name="action" value="update" />
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo htmlspecialchars($nonce); ?>" />
			<input type="hidden" name="_wp_http_referer" value="<?php echo admin_url( 'admin.php?page=dropshippers_importexport'); ?>" />

			<table class="form-table">
				<tr><th scope="row">Delimiter</th>
				<td>
					<input id="opt-delimiter" name="opt-delimiter" type="text" value="<?php echo htmlspecialchars($options['delimiter']); ?>">
				</td>
				</tr>
				<tr><th scope="row">Enclosure</th>
				<td>
					<input id="opt-enclosure" name="opt-enclosure" type="text" value="<?php echo htmlspecialchars($options['enclosure']); ?>">
				</td>
				</tr>
			</table>
			<input id="saveoptionsbtn" type="submit" class="button-primary" value="Save settings">
		</form>
		<br>

		<hr>

		<h3>Import</h3>
		<form method="post" action="<?php echo admin_url( 'admin.php?page=dropshippers_importexport'); ?>" enctype="multipart/form-data">
			<input type='hidden' name='option_page' value='dropshippers_importexport' />
			<input type="hidden" name="action" value="import" />
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo htmlspecialchars($nonce); ?>" />
			<input type="hidden" name="_wp_http_referer" value="<?php echo admin_url( 'admin.php?page=dropshippers_importexport'); ?>" />
			<table class="form-table">
				<tr><th scope="row">Select File</th>
				<td>
					<input id="import-file" name="import-file" type="file" accept="text/csv">
				</td>
				</tr>
			</table>
			<input id="importbtn" type="submit" class="button-primary" value="Import Stock CSV">
		</form>
		<br>

		<hr>

		<h3>Export</h3>
		<form method="post" action="<?php echo admin_url( 'admin.php?page=dropshippers_importexport'); ?>">
			<input type='hidden' name='option_page' value='dropshippers_importexport' />
			<input type="hidden" name="action" value="export" />
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo htmlspecialchars($nonce); ?>" />
			<input type="hidden" name="_wp_http_referer" value="<?php echo admin_url( 'admin.php?page=dropshippers_importexport'); ?>" />
			<input id="exportbtn" type="submit" class="button-primary" value="Export Current Stock Status CSV">
		</form>

	</div>
	<?php
} // END public function bulk_assign_page()

?>