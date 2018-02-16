<?php

function pmui_init(){

	$labels = array(
	    'name' => __('Users', 'wp_all_import_user_add_on'),
	    'singular_name' => __('User', 'wp_all_import_user_add_on'),
  	);

	$args = array(
	    'labels' => $labels,
	    'public' => false,
	    'publicly_queryable' => true,
	    'show_ui' => true, 
	    'show_in_menu' => false, 
	    'query_var' => true,	    
	    'rewrite' => array( 'slug' => 'import_users' ),
	    'capability_type' => 'post',
	    'has_archive' => false, 
	    'hierarchical' => false,
	    'menu_position' => null,
	    'supports' => array( 'title', 'editor', 'custom-fields' ),
	    'taxonomies' => array()
	); 
	
	register_post_type('import_users', $args);

	//flush_rewrite_rules();
		
}

?>