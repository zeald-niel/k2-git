<?php 
function pmui_pmxi_options_tab( $isWizard, $post ){		

	if ( $post['custom_type'] == 'import_users'):		

		$pmui_controller = new PMUI_Admin_Import();										
		
		$pmui_controller->options( $isWizard, $post );

	endif;

}
