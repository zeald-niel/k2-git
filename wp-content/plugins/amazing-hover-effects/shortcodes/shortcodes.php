<?php

function ahe_circle_shortcode($atts, $content = null){
    extract( shortcode_atts( array(
    
        'id' => '',
        
    ), $atts) );
    
    
    $q = new WP_Query(
        array('posts_per_page' => -1, 'post_type' => 'hover_effect', 'p' => $id)
    );

    while($q->have_posts()) : $q->the_post();
    $idd = get_the_ID();

   

    $options = get_post_meta( $idd, 'hover_effects_options', true );
    
    if ( $options['style'] == 'circle') {
       require ('circle.php'); 
    }
                
    endwhile;
    wp_reset_query();
    return $output;
    
}
add_shortcode('hover', 'ahe_circle_shortcode'); 






