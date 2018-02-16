<?php

// Login Editor

function ds_register_login_editor($wp_customize) {
	
	// New Login Editor Panel
	
    $wp_customize->add_panel( 'ds_login_editor_panel', array(
        'priority'       => 30,
        'capability'     => 'edit_theme_options',
        'title'          => __('Login Editor', login_editor_title),
        'description'    => __('Customize the login of your website.', login_editor_title),
    ));
	
	// New Sections

    $wp_customize->add_section('ds_login_logo_section', array(
        'priority' => 5,
        'title' => __('Logo', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));

	$wp_customize->add_section('ds_login_text_section', array(
		'priority' => 10,
		'title' => __('Text Options', login_editor_title),
		'panel'  => 'ds_login_editor_panel',
	));

    $wp_customize->add_section('ds_login_background_section', array(
        'priority' => 15,
        'title' => __('Background', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));

    $wp_customize->add_section('ds_login_form_bg_section', array(
        'priority' => 20,
        'title' => __('Form Background', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));

    $wp_customize->add_section('ds_login_form_section', array(
        'priority' => 25,
        'title' => __('Form Style', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));

    $wp_customize->add_section('ds_login_field_section', array(
        'priority' => 30,
        'title' => __('Fields Style', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));

    $wp_customize->add_section('ds_login_button_section', array(
        'priority' => 35,
        'title' => __('Edit Button', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));

    $wp_customize->add_section('ds_login_advanced_section', array(
        'priority' => 40,
        'title' => __('Custom CSS', login_editor_title),
        'panel'  => 'ds_login_editor_panel',
    ));
    // LOGO OPTIONS
    $wp_customize->add_setting('ds_login_logo', array(
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'ds_login_logo', array(
        'label' => __('Login Logo', login_editor_title),
        'section' => 'ds_login_logo_section',
        'priority' => 5,
        'settings' => 'ds_login_logo'
    )));

    $wp_customize->add_setting('ds_login_logo_width', array(
        'default' => '84px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_logo_width', array(
        'label' => __('Logo Width', login_editor_title),
        'section' => 'ds_login_logo_section',
        'priority' => 10,
        'settings' => 'ds_login_logo_width'
    ));

    $wp_customize->add_setting('ds_login_logo_height', array(
        'default' => '84px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_logo_height', array(
        'label' => __('Logo Height', login_editor_title),
        'section' => 'ds_login_logo_section',
        'priority' => 15,
        'settings' => 'ds_login_logo_height'
    ));

    $wp_customize->add_setting('ds_login_logo_padding', array(
        'default' => '5px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_logo_padding', array(
        'label' => __('Padding Bottom', login_editor_title),
        'section' => 'ds_login_logo_section',
        'priority' => 20,
        'settings' => 'ds_login_logo_padding'
    ));

    $wp_customize->add_setting('ds_login_bg_image', array(
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));
    // BACKGROUND IMAGE EDITS
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'ds_login_bg_image', array(
        'label' => __('Background Image', login_editor_title),
        'section' => 'ds_login_background_section',
        'priority' => 5,
        'settings' => 'ds_login_bg_image'
    )));

    $wp_customize->add_setting('ds_login_bg_color', array(
        'default' => '#F1F1F1',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_bg_color', array(
        'label' => __('Background Color', login_editor_title),
        'section' => 'ds_login_background_section',
        'priority' => 10,
        'settings' => 'ds_login_bg_color'
    )));

    $wp_customize->add_setting('ds_login_bg_size', array(
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_bg_size', array(
        'label' => __('Background Size', login_editor_title),
        'section' => 'ds_login_background_section',
        'priority' => 15,
        'settings' => 'ds_login_bg_size'
    ));
    // FORM BACKGROUND EDITS
    $wp_customize->add_setting('ds_login_form_bg_image', array(
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'ds_login_form_bg_image', array(
        'label' => __('Background Image', login_editor_title),
        'section' => 'ds_login_form_bg_section',
        'priority' => 5,
        'settings' => 'ds_login_form_bg_image'
    )));

    $wp_customize->add_setting('ds_login_form_bg_color', array(
        'default' => '#FFF',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_form_bg_color', array(
        'label' => __('Background Color', login_editor_title),
        'section' => 'ds_login_form_bg_section',
        'priority' => 10,
        'settings' => 'ds_login_form_bg_color'
    )));

    $wp_customize->add_setting('ds_login_form_width', array(
        'default' => '320px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_form_width', array(
        'label' => __('Width', login_editor_title),
        'section' => 'ds_login_form_section',
        'priority' => 15,
        'settings' => 'ds_login_form_width'
    ));

    $wp_customize->add_setting('ds_login_form_height', array(
        'default' => '194px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_form_height', array(
        'label' => __('Height', login_editor_title),
        'section' => 'ds_login_form_section',
        'priority' => 20,
        'settings' => 'ds_login_form_height'
    ));

    $wp_customize->add_setting('ds_login_form_padding', array(
        'default' => '26px 24px 46px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_form_padding', array(
        'label' => __('Padding', login_editor_title),
        'section' => 'ds_login_form_section',
        'priority' => 25,
        'settings' => 'ds_login_form_padding'
    ));
    // FORM FIELDS
    $wp_customize->add_setting('ds_login_field_width', array(
        'default' => '100%',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_field_width', array(
        'label' => __('Input Field Width', login_editor_title),
        'section' => 'ds_login_field_section',
        'priority' => 5,
        'settings' => 'ds_login_field_width'
    ));

    $wp_customize->add_setting('ds_login_field_margin', array(
        'default' => '2px 6px 16px 0px',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_field_margin', array(
        'label' => __('Input Field Margin', login_editor_title),
        'section' => 'ds_login_field_section',
        'priority' => 10,
        'settings' => 'ds_login_field_margin'
    ));

    $wp_customize->add_setting('ds_login_field_bg', array(
        'default' => '#FFF',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_field_bg', array(
        'label' => __('Input Field Background', login_editor_title),
        'section' => 'ds_login_field_section',
        'priority' => 15,
        'settings' => 'ds_login_field_bg'
    )));

    $wp_customize->add_setting('ds_login_field_color', array(
        'default' => '#333',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_field_color', array(
        'label' => __('Input Field Color', login_editor_title),
        'section' => 'ds_login_field_section',
        'priority' => 20,
        'settings' => 'ds_login_field_color'
    )));

    $wp_customize->add_setting('ds_login_field_label', array(
        'default' => '#777',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_field_label', array(
        'label' => __('Label Color', login_editor_title),
        'section' => 'ds_login_field_section',
        'priority' => 25,
        'settings' => 'ds_login_field_label'
    )));
    // BUTTON STYLES
    $wp_customize->add_setting('ds_login_button_bg', array(
        'default' => '#2EA2CC',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_button_bg', array(
        'label' => __('Button Background', login_editor_title),
        'section' => 'ds_login_button_section',
        'priority' => 5,
        'settings' => 'ds_login_button_bg'
    )));

    $wp_customize->add_setting('ds_login_button_border', array(
        'default' => '#0074A2',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_button_border', array(
        'label' => __('Button Border', login_editor_title),
        'section' => 'ds_login_button_section',
        'priority' => 10,
        'settings' => 'ds_login_button_border'
    )));

    $wp_customize->add_setting('ds_login_button_hover_bg', array(
        'default' => '#1E8CBE',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_button_hover_bg', array(
        'label' => __('Button Background (Hover)', login_editor_title),
        'section' => 'ds_login_button_section',
        'priority' => 15,
        'settings' => 'ds_login_button_hover_bg'
    )));

    $wp_customize->add_setting('ds_login_button_hover_border', array(
        'default' => '#0074A2',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_button_hover_border', array(
        'label' => __('Button Border (Hover)', login_editor_title),
        'section' => 'ds_login_button_section',
        'priority' => 20,
        'settings' => 'ds_login_button_hover_border'
    )));

    $wp_customize->add_setting('ds_login_button_shadow', array(
        'default' => '#78C8E6',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_button_shadow', array(
        'label' => __('Button Box Shadow', login_editor_title),
        'section' => 'ds_login_button_section',
        'priority' => 25,
        'settings' => 'ds_login_button_shadow'
    )));

    $wp_customize->add_setting('ds_login_button_color', array(
        'default' => '#FFF',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_button_color', array(
        'label' => __('Button Color', login_editor_title),
        'section' => 'ds_login_button_section',
        'priority' => 30,
        'settings' => 'ds_login_button_color'
    )));
    // CUSTOM CSS
    $wp_customize->add_setting('ds_login_custom_css', array(
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_login_custom_css', array(
        'label' => __('Not found the option you wanted? Add your own CSS here...', login_editor_title),
        'type' => 'textarea',
        'section' => 'ds_login_advanced_section',
        'priority' => 5,
        'settings' => 'ds_login_custom_css'
    ));
	
    $wp_customize->add_setting('ds_login_text_color', array(
        'default' => '#999',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_text_color', array(
        'label' => __('Text Color', login_editor_title),
        'section' => 'ds_login_text_section',
        'priority' => 5,
        'settings' => 'ds_login_text_color'
    )));

    $wp_customize->add_setting('ds_login_text_color_hover', array(
        'default' => '#2EA2CC',
        'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ds_login_text_color_hover', array(
        'label' => __('Text Color (Hover)', login_editor_title),
        'section' => 'ds_login_text_section',
        'priority' => 10,
        'settings' => 'ds_login_text_color_hover'
    )));
	
    }
	
add_action('customize_register', 'ds_register_login_editor');

function ds_login_customizer() {
$logo_url = get_option('ds_login_logo');
$logo_width = get_option('ds_login_logo_width');
$logo_height = get_option('ds_login_logo_height');
$logo_padding = get_option('ds_login_logo_padding');
$bg_img = get_option('ds_login_bg_image');
$bg_color = get_option('ds_login_bg_color');
$bg_size = get_option('ds_login_bg_size');
$form_bg_image = get_option('ds_login_form_bg_image');
$form_bg_color = get_option('ds_login_form_bg_color');
$form_width = get_option('ds_login_form_width');
$form_height = get_option('ds_login_form_height');
$form_padding = get_option('ds_login_form_padding');
$field_width = get_option('ds_login_field_width');
$field_margin = get_option('ds_login_field_margin');
$field_bg = get_option('ds_login_field_bg');
$field_color = get_option('ds_login_field_color');
$field_label = get_option('ds_login_field_label');
$button_bg = get_option('ds_login_button_bg');
$button_border = get_option('ds_login_button_border');
$button_shadow = get_option('ds_login_button_shadow');
$button_color = get_option('ds_login_button_color');
$button_hover_bg = get_option('ds_login_button_hover_bg');
$button_hover_border = get_option('ds_login_button_hover_border');
$other_color = get_option('ds_login_text_color');
$other_color_hover = get_option('ds_login_text_color_hover');
$other_css = get_option('ds_login_custom_css');
?>

<style type="text/css">

body.login {
<?php if( !empty($bg_img)) : ?>
    background-image: url(<?php echo $bg_img; ?>) !important;
<?php endif; ?>
<?php if( !empty($bg_color)) : ?>
    background-color: <?php echo $bg_color; ?> !important;
<?php endif; ?>
<?php if( !empty($bg_url)) : ?>
    background-size: <?php echo $bg_size; ?> !important;
<?php endif; ?>
    }
	
body.login #login h1 a {
<?php if( !empty($logo_url)) : ?>
    background-image: url(<?php echo $logo_url; ?>) !important;
<?php endif; ?>
<?php if( !empty($logo_width)) : ?>
    width: <?php echo $logo_width; ?> !important;
<?php endif; ?>
<?php if( !empty($logo_height)) : ?>
    height: <?php echo $logo_height; ?> !important;
<?php endif; ?>
<?php if( !empty($logo_width) || !empty($logo_height)) : ?>
    background-size: <?php echo $logo_width; ?> <?php echo $logo_height; ?> !important;
<?php endif; ?>
<?php if( !empty($logo_padding)) : ?>
    padding-bottom: <?php echo $logo_padding; ?> !important;
<?php endif; ?>
}

#loginform {
<?php if( !empty($form_bg_image)) : ?>
    background-image: url(<?php echo $form_bg_image; ?>) !important;
<?php endif; ?>
<?php if( !empty($form_bg_color)) : ?>
    background-color: <?php echo $form_bg_color; ?> !important;
<?php endif; ?>
<?php if( !empty($form_height)) : ?>
    height: <?php echo $form_height; ?> !important;
<?php endif; ?>
<?php if( !empty($form_padding)) : ?>
    padding: <?php echo $form_padding; ?> !important;
<?php endif; ?>
<?php if( !empty($form_border)) : ?>
    border: <?php echo $form_border; ?> !important;
<?php endif; ?>
}

#login {
<?php if( !empty($form_width)) : ?>
    width: <?php echo $form_width; ?> !important;
<?php endif; ?>
}

.login form .input, .login input[type="text"] {
<?php if( !empty($field_width)) : ?>
    width: <?php echo $field_width; ?> !important;
<?php endif; ?>
<?php if( !empty($field_margin)) : ?>
    margin: <?php echo $field_margin; ?> !important;
<?php endif; ?>
<?php if( !empty($field_bg)) : ?>
    background: <?php echo $field_bg; ?> !important;
<?php endif; ?>
<?php if( !empty($field_color)) : ?>
    color: <?php echo $field_color; ?> !important;
<?php endif; ?>
}

.login label {
<?php if( !empty($field_label)) : ?>
    color: <?php echo $field_label; ?> !important;
<?php endif; ?>
}

.wp-core-ui .button-primary {
<?php if( !empty($button_bg)) : ?>
    background: <?php echo $button_bg; ?> !important;
<?php endif; ?>
<?php if( !empty($button_border)) : ?>
    border-color: <?php echo $button_border; ?> !important;
<?php endif; ?>
<?php if( !empty($button_shadow)) : ?>
    box-shadow: 0px 1px 0px <?php echo $button_shadow; ?> inset, 0px 1px 0px rgba(0, 0, 0, 0.15);
<?php endif; ?>
<?php if( !empty($button_color)) : ?>
    color: <?php echo $button_color; ?> !important;
<?php endif; ?>
}

.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary.hover, .wp-core-ui .button-primary:focus, .wp-core-ui .button-primary:hover {
<?php if( !empty($button_hover_bg)) : ?>
    background: <?php echo $button_hover_bg; ?> !important;
<?php endif; ?>
<?php if( !empty($button_hover_border)) : ?>
    border-color: <?php echo $button_hover_border; ?> !important;
<?php endif; ?>
}

.login #backtoblog a, .login #nav a {
<?php if( !empty($other_color)) : ?>
    color: <?php echo $other_color; ?> !important;
<?php endif; ?>
}

.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover {
<?php if( !empty($other_color_hover)) : ?>
    color: <?php echo $other_color_hover; ?> !important;
<?php endif; ?>
}

<?php if( !empty($other_css)) : ?>
    <?php echo $other_css; ?>
<?php endif; ?>

</style>

<?php
}

add_action( 'login_enqueue_scripts', 'ds_login_customizer' );

function ds_login_login_logo_url() { return get_bloginfo( 'url' ); }

add_filter( 'login_headerurl', 'ds_login_login_logo_url' );

function ds_login_login_logo_url_title() { $title = get_bloginfo( 'name', 'display' ); return $title; }

add_filter( 'login_headertitle', 'ds_login_login_logo_url_title' );

?>