<?php

// ====================== Footer Editor ======================

function ds_footer_links_editor($wp_customize) {
	
    $wp_customize->add_panel( 'footer_links_option', array(
        'priority'       => 30,
        'capability'     => 'edit_theme_options',
        'title'          => __('Edit Footer Links', footer_links_title),
        'description'    => __('Customize the login of your website.', footer_links_title),
    ));
	
    $wp_customize->add_section('ds_footer_links_section', array(
        'priority' => 5,
        'title' => __('Footer Links Editor', footer_links_title),
        'panel'  => 'footer_links_option',
    ));
    // Before Link One
    $wp_customize->add_setting('ds_footer_links_before_link_one', array(
        'default' => 'Designed By',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_footer_links_before_link_one', array(
        'label' => __('Text Before First Link', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 5,
        'settings' => 'ds_footer_links_before_link_one'
    ));
    // Link One	
    $wp_customize->add_setting('ds_footer_links_link_one', array(
        'default' => 'Elegant Themes',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_footer_links_link_one', array(
        'label' => __('First Link Text', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 10,
        'settings' => 'ds_footer_links_link_one'
    ));
    // Link One URL	
    $wp_customize->add_setting('ds_footer_link_one_url', array(
        'default' => '#',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_footer_link_one_url', array(
        'label' => __('First Link URL', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 15,
        'settings' => 'ds_footer_link_one_url'
    ));
// Before Link Two
    $wp_customize->add_setting('ds_footer_links_before_link_two', array(
        'default' => 'Powered By',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_footer_links_before_link_two', array(
        'label' => __('Text Before Second Link', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 20,
        'settings' => 'ds_footer_links_before_link_two'
    ));
    // Link Two
    $wp_customize->add_setting('ds_footer_links_link_two', array(
        'default' => 'WordPress',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));

    $wp_customize->add_control('ds_footer_links_link_two', array(
        'label' => __('Second Link Text', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 25,
        'settings' => 'ds_footer_links_link_two'
    ));
    // Link Two URL	
    $wp_customize->add_setting('ds_footer_link_two_url', array(
        'default' => '###',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));
	
    $wp_customize->add_control('ds_footer_link_two_url', array(
        'label' => __('Second Link URL', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 30,
        'settings' => 'ds_footer_link_two_url'
    ));
    // Footer Divider	
    $wp_customize->add_setting('ds_footer_link_divider', array(
        'default' => '|',
		'type' => 'option',
        'capability' => 'edit_theme_options',
    ));
	
    $wp_customize->add_control('ds_footer_link_divider', array(
        'label' => __('Footer Link Divider', footer_links_title),
        'section' => 'ds_footer_links_section',
		'type' => 'option',
        'priority' => 35,
        'settings' => 'ds_footer_link_divider'
    ));
}

add_action('customize_register', 'ds_footer_links_editor');

function ds_new_bottom_footer() {
	
$footer_one = get_option('ds_footer_links_before_link_one','Designed By');
$footer_two = get_option('ds_footer_links_link_one','Elegant Themes');
$footer_link_one = get_option('ds_footer_link_one_url','http://www.elegantthemes.com/');
$footer_three = get_option('ds_footer_links_before_link_two','Powered By');
$footer_four = get_option('ds_footer_links_link_two', 'WordPress');
$footer_link_two = get_option('ds_footer_link_two_url', 'https://wordpress.org/');
$footer_divider = get_option('ds_footer_link_divider','|');
	
?>

<script type="text/javascript">
jQuery(document).ready(function(){
jQuery("#footer-info").text(' ');
jQuery('<p id="footer-info"><?php if( !empty($footer_one)) : ?><?php echo $footer_one; ?><?php endif; ?> <a href="<?php if( !empty($footer_link_one)) : ?><?php echo $footer_link_one; ?><?php endif; ?>"><?php if( !empty($footer_two)) : ?><?php echo $footer_two; ?><?php endif; ?></a> <?php if( !empty($footer_divider)) : ?><?php echo $footer_divider; ?><?php endif; ?> <?php if( !empty($footer_three)) : ?><?php echo $footer_three; ?><?php endif; ?> <a href="<?php if( !empty($footer_link_two)) : ?><?php echo $footer_link_two; ?><?php endif; ?>"><?php if( !empty($footer_four)) : ?><?php echo $footer_four; ?><?php endif; ?></a></p>').insertAfter("#footer-info");
});
</script>

<?php
}

add_action( 'wp_head', 'ds_new_bottom_footer' );
?>