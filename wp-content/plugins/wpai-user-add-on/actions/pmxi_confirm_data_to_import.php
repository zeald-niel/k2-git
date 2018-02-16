<?php 
function pmui_pmxi_confirm_data_to_import( $isWizard, $post )
{
    if ( $post['custom_type'] == 'import_users'):

        $pmui_controller = new PMUI_Admin_Import();

        $pmui_controller->confirm( $isWizard, $post );

    endif;
}
