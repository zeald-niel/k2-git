<?php

/*
 * Plugin Name: Divi Advanced Text Module
 * Plugin URI:  http://www.sean-barton.co.uk
 * Description: A simple plugin to provide a wysiwyg but with some extra controls
 * Author:      Sean Barton - Tortoise IT
 * Version:     1.2
 * Author URI:  http://www.sean-barton.co.uk
 */


    add_action('plugins_loaded', 'sb_divi_atm_init');
    
    function sb_divi_atm_init() {
        add_action('init', 'sb_divi_atm_theme_setup', 9999);
        add_action('admin_head', 'sb_divi_atm_admin_head', 9999);
    }
    
    function sb_divi_atm_admin_head() {
	
	if ($_SERVER['PHP_SELF'] != '/wp-admin/index.php') {
	    return; //clear the module cache for this plugin when the dashboard is visited
	}
	
	$prop_to_remove = array(
	    'et_pb_templates_et_pb_divi_atm'
	);
	
	$js_prop_to_remove = 'var sb_ls_remove = ["' . implode('","', $prop_to_remove) . '"];';

	echo '<script>
	
	' . $js_prop_to_remove . '
	
	for (var prop in localStorage) {
	    if (sb_ls_remove.indexOf(prop) != -1) {
		//console.log("found "+prop);
		console.log(localStorage.removeItem(prop));
	    }
	}
	
	</script>';
    }
    
    function sb_divi_atm_theme_setup() {
    
        if ( class_exists('ET_Builder_Module')) {
				
		class et_pb_divi_atm extends ET_Builder_Module {
			function init() {
				$this->name = esc_html__( 'Advanced Text', 'et_builder' );
				$this->slug = 'et_pb_divi_atm';
		
				$this->whitelisted_fields = array(
					'background_layout',
					'text_orientation',
					'content_new',
					'bullet_image',
					'bullet_width',
					'bullet_height',
					'admin_label',
					'module_id',
					'module_class',
					'max_width',
					'max_width_tablet',
					'max_width_phone',
				);
		
				$this->fields_defaults = array(
					'background_layout' => array( 'light' ),
					'text_orientation'  => array( 'left' ),
					'bullet_width'  => array( '40' ),
					'bullet_height'  => array( '40' ),
				);
		
				$this->main_css_element = '%%order_class%%';
				$this->advanced_options = array(
					'fonts' => array(
						'text'   => array(
							'label'    => esc_html__( 'Text', 'et_builder' ),
							'css'      => array(
								'line_height' => "{$this->main_css_element} p",
							),
						),
					),
					'background' => array(
						'settings' => array(
							'color' => 'alpha',
						),
					),
					'border' => array(),
					'custom_margin_padding' => array(
						'css' => array(
							'important' => 'all',
						),
					),
				);
			}
		
			function get_fields() {
				$fields = array(
					'background_layout' => array(
						'label'             => esc_html__( 'Text Color', 'et_builder' ),
						'type'              => 'select',
						'option_category'   => 'configuration',
						'options'           => array(
							'light' => esc_html__( 'Dark', 'et_builder' ),
							'dark'  => esc_html__( 'Light', 'et_builder' ),
						),
						'description'       => esc_html__( 'Here you can choose the value of your text. If you are working with a dark background, then your text should be set to light. If you are working with a light background, then your text should be dark.', 'et_builder' ),
					),
					'text_orientation' => array(
						'label'             => esc_html__( 'Text Orientation', 'et_builder' ),
						'type'              => 'select',
						'option_category'   => 'layout',
						'options'           => et_builder_get_text_orientation_options(),
						'description'       => esc_html__( 'This controls the how your text is aligned within the module.', 'et_builder' ),
					),
					'content_new' => array(
						'label'           => esc_html__( 'Content', 'et_builder' ),
						'type'            => 'tiny_mce',
						'option_category' => 'basic_option',
						'description'     => esc_html__( 'Here you can create the content that will be used within the module.', 'et_builder' ),
					),
					'max_width' => array(
						'label'           => esc_html__( 'Max Width', 'et_builder' ),
						'type'            => 'text',
						'option_category' => 'layout',
						'mobile_options'  => true,
						'tab_slug'        => 'advanced',
						'validate_unit'   => true,
					),
					'max_width_tablet' => array(
						'type' => 'skip',
					),
					'max_width_phone' => array(
						'type' => 'skip',
					),
					'disabled_on' => array(
						'label'           => esc_html__( 'Disable on', 'et_builder' ),
						'type'            => 'multiple_checkboxes',
						'options'         => array(
							'phone'   => esc_html__( 'Phone', 'et_builder' ),
							'tablet'  => esc_html__( 'Tablet', 'et_builder' ),
							'desktop' => esc_html__( 'Desktop', 'et_builder' ),
						),
						'additional_att'  => 'disable_on',
						'option_category' => 'configuration',
						'description'     => esc_html__( 'This will disable the module on selected devices', 'et_builder' ),
					),
					'bullet_image' => array(
						'label'              => esc_html__( 'Bullet Image', 'et_builder' ),
						'type'               => 'upload',
						'option_category'    => 'basic_option',
						'upload_button_text' => esc_attr__( 'Upload a bullet image', 'et_builder' ),
						'choose_text'        => esc_attr__( 'Choose a bullet Image', 'et_builder' ),
						'update_text'        => esc_attr__( 'Set As Bullet Image', 'et_builder' ),
						'description'        => esc_html__( 'If defined, this image will be used as the bullet image for all lists within this module', 'et_builder' ),
					),
					'bullet_width' => array(
						'label'       => esc_html__( 'Bullet Width', 'et_builder' ),
						'type'        => 'text',
						'description' => esc_html__( 'Optionally set the width (in px) of your bullet images..', 'et_builder' ),
					),
					'bullet_height' => array(
						'label'       => esc_html__( 'Bullet Height', 'et_builder' ),
						'type'        => 'text',
						'description' => esc_html__( 'Optionally set the height (in px) of your bullet images.', 'et_builder' ),
					),
					'admin_label' => array(
						'label'       => esc_html__( 'Admin Label', 'et_builder' ),
						'type'        => 'text',
						'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
					),
					'module_id' => array(
						'label'           => esc_html__( 'CSS ID', 'et_builder' ),
						'type'            => 'text',
						'option_category' => 'configuration',
						'tab_slug'        => 'custom_css',
						'option_class'    => 'et_pb_custom_css_regular',
					),
					'module_class' => array(
						'label'           => esc_html__( 'CSS Class', 'et_builder' ),
						'type'            => 'text',
						'option_category' => 'configuration',
						'tab_slug'        => 'custom_css',
						'option_class'    => 'et_pb_custom_css_regular',
					),
				);
				return $fields;
			}
		
			function shortcode_callback( $atts, $content = null, $function_name ) {
				$module_id            = $this->shortcode_atts['module_id'];
				$module_class         = $this->shortcode_atts['module_class'];
				$background_layout    = $this->shortcode_atts['background_layout'];
				$text_orientation     = $this->shortcode_atts['text_orientation'];
				$max_width            = $this->shortcode_atts['max_width'];
				$max_width_tablet     = $this->shortcode_atts['max_width_tablet'];
				$max_width_phone      = $this->shortcode_atts['max_width_phone'];
				
				$bullet_image      = $this->shortcode_atts['bullet_image'];
				$bullet_width      = $this->shortcode_atts['bullet_width'];
				$bullet_height      = $this->shortcode_atts['bullet_height'];
		
				$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );
		
				$this->shortcode_content = et_builder_replace_code_content_entities( $this->shortcode_content );
		
				if ( '' !== $max_width_tablet || '' !== $max_width_phone || '' !== $max_width ) {
					$max_width_values = array(
						'desktop' => $max_width,
						'tablet'  => $max_width_tablet,
						'phone'   => $max_width_phone,
					);
		
					//et_pb_generate_responsive_css( $max_width_values, '%%order_class%%', 'max-width', $function_name );
				}
				
				if ( '' !== $bullet_image ) {
					ET_Builder_Element::set_style( $function_name, array(
						'selector'    => '%%order_class%% ul li:before',
						'declaration' => 'content: \'\'; width: ' . $bullet_width . 'px; height: ' . $bullet_height . 'px; float: left; background-image: url(' . $bullet_image . '); padding-right: 15px; background-repeat: no-repeat; background-size: contain;'
						)
					);
					
					ET_Builder_Element::set_style( $function_name, array(
						'selector'    => '%%order_class%% ul li',
						'declaration' => 'line-height: ' . $bullet_height . 'px; clear: both; margin: 10px 0;'
						)
					);
					
					ET_Builder_Element::set_style( $function_name, array(
						'selector'    => '%%order_class%% ul li *',
						'declaration' => 'line-height: ' . $bullet_height . 'px !important; margin: 0; padding: 0;'
						)
					);
					
					ET_Builder_Element::set_style( $function_name, array(
						'selector'    => '%%order_class%% ul',
						'declaration' => 'list-style-type: none; list-style: none !important; margin-left: 0; padding-left: 0;'
						)
					);
				}
		
				if ( is_rtl() && 'left' === $text_orientation ) {
					$text_orientation = 'right';
				}
		
				$class = " et_pb_module et_pb_bg_layout_{$background_layout} et_pb_text_align_{$text_orientation}";
		
				$output = sprintf(
					'<div%3$s class="et_pb_text%2$s%4$s">
						%1$s
					</div> <!-- .et_pb_text -->',
					$this->shortcode_content,
					esc_attr( $class ),
					( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
					( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
				);
		
				return $output;
			}
		}
		new et_pb_divi_atm;
	}
}
?>