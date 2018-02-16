<?php
/*
* Plugin Name: Before After Image Slider WP
* Plugin URI: http://swadeshswain.com
* Description: This Responsive WordPress slider plugin will allow you to drag two images left and right that is before and after.
* Version: 2.2
* Author: swadeshswain
* Author URI: http://swadeshswain.com
*License: GPLv3 or later
*License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
function bais_min_jquery() {
    if (!is_admin()) {
       wp_enqueue_script('jquery');
   }
}
add_action('init', 'bais_min_jquery');
add_action( 'wp_footer', 'bais_enqueue_script' );
add_action( 'wp_footer', 'bais_enqueue_style' );
function bais_enqueue_script() {
  
  wp_enqueue_script('before-after.min.js', plugins_url('/js/before-after.min.js',__FILE__), array( 'jquery' ), false );
  ?>
  <script type="text/javascript">
  jQuery(document).ready(function($){
  $('.ba-slider').beforeAfter();
  });
  </script>

  <?php
}  
function bais_enqueue_style() {
wp_enqueue_style( 'before-after.min.css',  plugins_url('/css/before-after.min.css',__FILE__) , false ); 	
}
function bais_shortcode( $atts )
{
ob_start();
$atts = shortcode_atts(
		array(
			'before_image' => '',
			'after_image' => '',
		),
		$atts
	);
	$output .= '<div class="ba-slider">';
	$output .= '<img src="'.$atts['after_image'].'" /><div class=" resize">';
	$output .= '<img src="'.$atts['before_image'].'" /></div>';
	$output .= '<span class="handle"></span></div>';
ob_clean();
	return $output;
}
add_shortcode('bais_before_after', 'bais_shortcode');

// init process for registering tinymce button
 add_action('init', 'bais_shortcode_button_init');
 function bais_shortcode_button_init() {

      //Abort early if the user will never see TinyMCE
      if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
           return;

      //Add a callback to regiser the tinymce plugin   
      add_filter("mce_external_plugins", "bais_register_tinymce_plugin"); 

      // Add a callback to add the button to the TinyMCE toolbar
      add_filter('mce_buttons', 'bais_add_tinymce_button');
}

//This callback registers the plug-in
function bais_register_tinymce_plugin($plugin_array) {
    $plugin_array['bais_button'] = plugins_url( '/js/shortcode.js' , __FILE__ );
    return $plugin_array;
}

//This callback adds the button to the toolbar
function bais_add_tinymce_button($buttons) {
            //Add the button ID to the $button array
    $buttons[] = "bais_button";
    return $buttons;
}

?>