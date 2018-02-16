<?php

// WP 3.0+
//add_action('add_meta_boxes', 'subpages_add_custom_box');
add_action('admin_init', 'subpages_add_custom_box', 1);
add_action('save_post', 'subpages_save_postdata');

function subpages_add_custom_box() {
    add_meta_box( 'subpages_menu_label', 'Subpages Extended Menu Label', 
                'subpages_inner_custom_box', 'page', 'side', 'low' );
}

/* Prints the box content */
function subpages_inner_custom_box() {
	global $post;
	$post_id = $post;
	if (is_object($post_id)) $post_id = $post_id->ID;
	
	wp_nonce_field( plugin_basename(__FILE__), 'subpages_page_title_metabox' );
  
	$value = htmlspecialchars(stripcslashes(get_post_meta($post_id, '_subpages_menu_label', true)));
  
	echo '<label for="subpages_menu_label">' . __("Menu Label", 'subpages_textdomain' ) . '</label> ';
	echo '<input type="text" id= "subpages_menu_label" name="subpages_menu_label" value="'.$value.'" size="10" />';
}

function subpages_save_postdata( $post_id ) {
  if ( !isset($_POST['subpages_page_title_metabox']) || isset($_POST['subpages_page_title_metabox']) && !wp_verify_nonce( $_POST['subpages_page_title_metabox'], plugin_basename(__FILE__) )) {
    return $post_id;
  }
  
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
    return $post_id;
	
  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ) )
      return $post_id;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ) )
      return $post_id;
  }
  
	$menu_label = $_POST['subpages_menu_label'];
	delete_post_meta($post_id, '_subpages_menu_label'); add_post_meta($post_id, '_subpages_menu_label', $menu_label);
	
   return $menu_label;
}
?>