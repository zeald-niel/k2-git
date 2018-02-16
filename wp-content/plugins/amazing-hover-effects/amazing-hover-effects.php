<?php
/*
Plugin Name: Amazing Hover Effects
Plugin URI: http://themebon.com/item/amazing-hover-effects-pro/
Description: Amazing Hover Effects is an impressive hover effects collection, powered by pure CSS3 and iHover, no dependency.
Author: Noor-E-Alam
Author URI: http://themebon.com/item/amazing-hover-effects-pro/
Version: 6.6.5
*/

//Loading CSS
function amazing_hover_effects_style() {

	wp_enqueue_style('ahew_stylesheet', plugins_url( 'css/ihover.css' , __FILE__ ) );
    wp_enqueue_style('ahew_stylesheet_custom', plugins_url( 'css/custom.css' , __FILE__ ) );
	
}
add_action( 'wp_enqueue_scripts', 'amazing_hover_effects_style' );


//admin css
function amazing_hover_effects_admin_style() {
	wp_enqueue_style('ahew_admin', plugins_url( 'css/admin.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'amazing_hover_effects_admin_style' );


add_filter('widget_text', 'do_shortcode');


// Lood framework
require ('framework/cs-framework.php');



// Registering Custom Post
add_action( 'init', 'amazing_hover_effects_custom_post' );
function amazing_hover_effects_custom_post() {
	register_post_type( 'hover_effect',
		array(
			'labels' => array(
				'name' => __( 'Hover Effects' ),
				'singular_name' => __( 'Hover Effect' ),
				'add_new_item' => __( 'Add New Hover Effect' )
			),
			'public' => true,
			'supports' => array('title'),
			'has_archive' => true,
			'rewrite' => array('slug' => 'hover-effects'),
			'menu_icon' => 'dashicons-image-filter',
			'menu_position' => 20,
		)
	);
	
}



//Calling Shortcodes
require_once ('shortcodes/shortcodes.php');




// Shortcode Generator
add_filter( 'manage_hover_effect_posts_columns', 'ahe_revealid_add_id_column', 10 );
add_action( 'manage_hover_effect_posts_custom_column', 'ahe_revealid_id_column_content', 10, 2 );


function ahe_revealid_add_id_column( $columns ) {
   $columns['hover_effect'] = 'Hover Shortcode';
   return $columns;
}

function ahe_revealid_id_column_content( $column, $id ) {
  if( 'hover_effect' == $column ) {
      
  
     $shortcode_render ='[hover id="'.$id.'"]';
     
    echo '<input style="min-width:210px" type=\'text\' onClick=\'this.setSelectionRange(0, this.value.length)\' value=\''.$shortcode_render.'\' />';
    
  }
}




// Gallery custom messages
add_filter( 'post_updated_messages', 'ahe_updated_messages' );
function ahe_updated_messages( $messages ){
        
    global $post;
    
    $post_ID = $post->ID;
    
 $messages['hover_effect'] = array(
            0 => '', 
            1 => sprintf( __('Hover Effects published. Shortcode is: %s'), '[hover id="'.$post_ID.'"]' ),
            6 => sprintf( __('Hover Effects published. Shortcode is: %s'), '[hover id="'.$post_ID.'"]' ),
        );

    
    return $messages;
        
}
