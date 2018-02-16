<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access pages directly.
// ===============================================================================================
// -----------------------------------------------------------------------------------------------
// METABOX OPTIONS
// -----------------------------------------------------------------------------------------------
// ===============================================================================================

$options      = array();

// -----------------------------------------
// Page Metabox Options                    -
// -----------------------------------------
$options[]    = array(
  'id'        => 'hover_effects_options',
  'title'     => 'Hover Effects Options',
  'post_type' => 'hover_effect',
  'context'   => 'normal',
  'priority'  => 'default',
  'sections'  => array(




    // begin: a section
    array(
      'name'  => 'circle_section',
      //'title' => 'Circle Style',
      'icon'  => 'fa fa-cog',



      // begin: fields
      'fields' => array(
      
      
        array(
          'id'           => 'style',
          'type'         => 'image_select',
          'title'        => 'Select Style',
          'options'      => array(
            'circle'    => plugins_url( 'img/circle.png' , __FILE__ ),
            'square'    => plugins_url( 'img/square.png' , __FILE__ ),
            'square2'    => plugins_url( 'img/square2.png' , __FILE__ ),
            'caption'    => plugins_url( 'img/caption.png' , __FILE__ ),
          ),
          'default'      => 'circle'
        ), 
      
      
      
      

            array(
              'id'              => 'circle_option',
              'type'            => 'group',
              'title'           => '',
              'dependency'   => array( 'style_circle', '==', 'true' ),
              'button_title'    => 'Add New Hover Item',
              'accordion_title' => 'Hover Item',
              'fields'          => array(
              
                array(
                  'id'    => 'circle_image',
                  'type'  => 'image',
                  'title' => 'Hover Image',
                ),
                array(
                  'id'    => 'circle_title',
                  'type'  => 'text',
                  'title' => 'Title',
                  'default' => 'Heading Here',
                ),
                array(
                  'id'    => 'circle_desc',
                  'type'  => 'textarea',
                  'title' => 'Description',
                  'default' => 'description goes here',
                ),
                array(
                  'id'    => 'circle_link',
                  'type'  => 'text',
                  'title' => 'Image Link <br /><span style="color: #d63434">Pro Only</span>',
                  //'default' => '#',
                ),                                
                
              ),           
              
            ),


            array(
              'type'    => 'notice',
              'class'   => 'danger',
              'content' => '<h3 align="center">To get all features working, please buy the pro version here <a target="_blank" href="https://themebon.com/item/amazing-hover-effects-pro/">Amazing Hover Effects Pro</a> for only $11</h3>',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),


        // begin: settings field
                 
            array(
              'id'       => 'circle_effect',
              'type'     => 'select',
              'title'    => 'Select Effect',
              'dependency'   => array( 'style_circle', '==', 'true' ),
              'options'  => array(
                'effect1'  => 'Effect 1',
                'effect2'   => 'Effect 2',
                'effect3' => 'Effect 3',
                'effect4' => 'Effect 4',
                'effect5' => 'Effect 5',
                'effect6' => 'Effect 6',
                'effect7' => 'Effect 7',
                'effect8' => 'Effect 8',
                'effect9' => 'Effect 9',
                'effect10' => 'Effect 10',
                'effect11' => 'Effect 11',
                'effect12' => 'Effect 12',
                'effect13' => 'Effect 13',
                'effect14' => 'Effect 14',
                'effect15' => 'Effect 15',
                'effect16' => 'Effect 16',
                'effect17' => 'Effect 17',
                'effect18' => 'Effect 18',
                'effect19' => 'Effect 19',
                'effect20' => 'Effect 20',
              ),
              'default'  => 'effect1',
            ),


            array(
              'id'       => 'circle_animation',
              'type'     => 'select',
              'title'    => 'Animation Direction',
              'dependency'   => array( 'style_circle', '==', 'true' ),
              //'dependency'   => array( 'circle_effect', 'any', 'effect2' ),
              'options'  => array(
                'left_to_right'  => 'Left To Right',
                'right_to_left'   => 'Right To Left',
                'top_to_bottom' => 'Top To Bottom',
                'bottom_to_top' => 'Bottom To Top',
              ),
              'default'  => 'left_to_right',
            ),


            array(
              'id'      => 'circle_color',
              'type'    => 'color_picker',
              'title'   => 'Background Color <br /><span style="color: #d63434">Pro Only</span>',
              //'default' => 'rgba(33,71,224,0.5)',
              'rgba'    => true,
              'desc'    => 'use rgba color for transperant bg',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
            
            array(
              'id'      => 'circle_image_width',
              'type'    => 'number',
              'title'   => 'Image Size <br /><span style="color: #d63434">Pro Only</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'default value is 216px',
              'default'  => '216',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
            
            array(
              'id'      => 'circle_column_space',
              'type'    => 'number',
              'title'   => 'Column Space</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'space between image column. default value is 15px',
              'default'  => '15',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),

            array(
              'id'      => 'circle_row_space',
              'type'    => 'number',
              'title'   => 'Row Space',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'space between image row. default value is 26px',
              'default'  => '26',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
            
            array(
              'id'    => 'circle_border',
              'type'  => 'checkbox',
              'title' => 'Remove Border <br /><span style="color: #d63434">Pro Only</span>',
              //'label' => 'Remove Border'
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
            

            array(
              'id'    => 'circle_underline',
              'type'  => 'checkbox',
              'title' => 'Remove Heading Underline <br /><span style="color: #d63434">Pro Only</span>',
              'options'    => array(
                'none'      => '',
              ),
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
            
            array(
              'id'         => 'circle_link_open',
              'type'       => 'checkbox',
              'title'      => 'Open link in new Tab? <br /><span style="color: #d63434">Pro Only</span>',
              'options'    => array(
                '_blank'      => '',
              ),
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),

            array(
              'id'    => 'circle_position',
              'type'  => 'checkbox',
              'title' => 'Position <br /><span style="color: #d63434">Pro Only</span>',
              'label' => 'Center',             
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
        
            array(
              'id'        => 'circle_font',
              'type'      => 'typography',
              'title'     => 'Custom Font <br /><span style="color: #d63434">Pro Only</span>',
              'default'   => array(
                'family'  => 'Open Sans',
                'font'    => 'google', // this is helper for output ( google, websafe, custom )
                //'variant' => '800',
              ),
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
        
            array(
              'id'      => 'circle_heading_font_size',
              'type'    => 'number',
              'title'   => 'Heading Font Size <br /><span style="color: #d63434">Pro Only</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'default value is 16px',
              'default'  => '16',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
                    
            array(
              'id'      => 'circle_heading_color',
              'type'    => 'color_picker',
              'title'   => 'Heading Color <br /><span style="color: #d63434">Pro Only</span>',
              'default' => '#fff',
              'desc'    => 'default color is #fff',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),  
                  
            array(
              'id'      => 'circle_desc_font_size',
              'type'    => 'number',
              'title'   => 'Description Font Size <br /><span style="color: #d63434">Pro Only</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'default value is 12px',
              'default'  => '12',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),
            
            array(
              'id'      => 'circle_desc_color',
              'type'    => 'color_picker',
              'title'   => 'Description Color <br /><span style="color: #d63434">Pro Only</span>',
              'default' => '#fff',
              'desc'    => 'default color is #fff',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ), 
            
            array(
              'id'      => 'circle_move_top',
              'type'    => 'number',
              'title'   => 'Move Top <br /><span style="color: #d63434">Pro Only</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'Moving description texts to top by decreasing value. default value is 110px',
              'default'  => '110',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),            
            
            array(
              'id'    => 'circle_responsive',
              'type'  => 'checkbox',
              'title' => 'Responsive Options <br /><span style="color: #d63434">Pro Only</span>',
              'label' => 'enable',             
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),            
            
             array(
              'type'    => 'notice',
              'class'   => 'danger',
              'content' => '<h3 align="center">To get all features working, please buy the pro version here <a target="_blank" href="http://themebon.com/item/amazing-hover-effects-pro/">Amazing Hover Effects Pro</a> for only $11</h3>',
              'dependency'   => array( 'style_circle', '==', 'true' ),
            ),           
            
            /*
            array(
              'type'    => 'notice',
              'class'   => 'info',
              'content' => 'Mobile Responsive',
              'dependency'   => array( 'style_circle', '==', 'true' ),
              'dependency'   => array( 'circle_responsive', '==', 'true' ),
            ),
            */
            
            array(
              'id'      => 'circle_mobile_image_width',
              'type'    => 'number',
              'title'   => 'Mobile Image Size <br /><span style="color: #d63434">Pro Only</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'default value is 240px',
              'default'  => '240',
              'dependency'   => array( 'style_circle', '==', 'true' ),
              'dependency'   => array( 'circle_responsive', '==', 'true' ),
            ),

            array(
              'id'      => 'circle_ipad_image_width',
              'type'    => 'number',
              'title'   => 'iPad Image Size <br /><span style="color: #d63434">Pro Only</span>',
              'after'   => '<i class="cs-text-muted">(px)</i>',
              'desc'    => 'default value is 300px',
              'default'  => '300',
              'dependency'   => array( 'style_circle', '==', 'true' ),
              'dependency'   => array( 'circle_responsive', '==', 'true' ),
            ),


        // Square Feilds


            array(
              'type'    => 'notice',
              'class'   => 'info',
              'content' => '<h3 align="center">Square Style for Pro Version</h3>',
              'dependency'   => array( 'style_square', '==', 'true' ),
            ),

            array(
              'type'    => 'notice',
              'class'   => 'danger',
              'content' => '<h3 align="center">Please buy the pro version here <a target="_blank" href="http://themebon.com/item/amazing-hover-effects-pro/">Amazing Hover Effects Pro</a> for only $11</h3>',
              'dependency'   => array( 'style_square', '==', 'true' ),
            ),





            array(
              'type'    => 'notice',
              'class'   => 'info',
              'content' => '<h3 align="center">Square 2 Style for Pro Version</h3>',
              'dependency'   => array( 'style_square2', '==', 'true' ),
            ),

            array(
              'type'    => 'notice',
              'class'   => 'danger',
              'content' => '<h3 align="center">Please buy the pro version here <a target="_blank" href="http://themebon.com/item/amazing-hover-effects-pro/">Amazing Hover Effects Pro</a> for only $11</h3>',
              'dependency'   => array( 'style_square2', '==', 'true' ),
            ),





            array(
              'type'    => 'notice',
              'class'   => 'info',
              'content' => '<h3 align="center">Caption Style for Pro Version</h3>',
              'dependency'   => array( 'style_caption', '==', 'true' ),
            ),

            array(
              'type'    => 'notice',
              'class'   => 'danger',
              'content' => '<h3 align="center">Please buy the pro version here <a target="_blank" href="http://themebon.com/item/amazing-hover-effects-pro/">Amazing Hover Effects Pro</a> for only $11</h3>',
              'dependency'   => array( 'style_caption', '==', 'true' ),
            ),



        // begin: settings field

                                                    
        
      ), // end: fields
      
), // end: a section










    // end: a section

  ),
);




CSFramework_Metabox::instance( $options );
