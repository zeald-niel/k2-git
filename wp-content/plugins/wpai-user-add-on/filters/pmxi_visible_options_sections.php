<?php
function pmui_pmxi_visible_options_sections( $sections, $post_type ){

	if ( 'import_users' == $post_type ) return array('settings');

	return $sections;

}