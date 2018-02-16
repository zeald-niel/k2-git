<?php
function pmui_pmxi_visible_template_sections( $sections, $post_type ){

	if ( 'import_users' == $post_type ) return array('main', 'cf');

	return $sections;

}