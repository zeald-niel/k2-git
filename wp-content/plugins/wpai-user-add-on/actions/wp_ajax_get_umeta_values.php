<?php

function pmui_wp_ajax_get_umeta_values(){

	if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp_all_import_user_add_on'))) );
	}

	if ( ! current_user_can('manage_options') ){
		exit( json_encode(array('html' => __('Security check', 'wp_all_import_user_add_on'))) );
	}
	
	global $wpdb;
	
	ob_start();	

	$meta_key = $_POST['key'];

	$r = $wpdb->get_results("
		SELECT DISTINCT usermeta.meta_value
		FROM ".$wpdb->usermeta." as usermeta
		WHERE usermeta.meta_key='".$meta_key."'
	", ARRAY_A);		

	$html = '<div class="input ex_values">';		
	
	if (!empty($r)){
		$html .= '<select class="existing_umeta_values"><option value="">'.__('Existing Values...','wp_all_import_user_add_on').'</option>';
		foreach ($r as $key => $value) { if (empty($value['meta_value'])) continue;
			$html .= '<option value="'.esc_html($value['meta_value']).'">'.$value['meta_value'].'</option>';
		}
		$html .= '</select>';
	}				
	else $html .= '<p>' . __('No existing values were found for this field.','wp_all_import_user_add_on') . '</p>';

	$html .= '</div>';

	echo $html;

	exit(json_encode(array('html' => ob_get_clean()))); die;

}

?>