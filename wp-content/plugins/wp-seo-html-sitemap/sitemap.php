<?php
/*
Plugin Name: WP SEO HTML Sitemap
Version: 0.9.6
Plugin URI: http://www.magnatechnology.com
Description: A responsive HTML sitemap that uses all of the settings for your XML sitemap in the WordPress SEO by Yoast Plugin.
Author: Magna Technology, LLC
Author URI: http://www.magnatechnology.com
*/

// add actions
add_action( 'admin_init', 'register_wpseo_sitemap_settings' );
add_action( 'admin_menu', 'wpseo_sitemap_create_menu' );
add_action( 'wp_enqueue_scripts', 'wpseo_sitemap_css' );

// add filters
add_filter( 'plugin_action_links', 'wpseo_sitemap_create_settings_link',10,2 );

// add the conditional css to the header
function wpseo_sitemap_css(){
	$options = get_option('wpseosms');
	if( $options['css-disable'] !== 'yes' ){
		wp_register_style( 'wpseo-html-sitemap',  plugin_dir_url( __FILE__ ) . 'style.css' );
		if( is_page( $options['pageID'] ) ){
			 wp_enqueue_style('wpseo-html-sitemap');
		 }
	}
}

// plugin activation
function wpseo_sitemap_activate() {
	register_wpseo_sitemap_settings();
	$options = get_option('wpseosms');
	if($options['columns'] == ''){
		$options['columns'] = '3';
		$options['css-disable'] = 'no';
		$options['location'] = 'after';
		$options['xml-link'] = 'no';
		$options['credits-link'] = 'no';
		update_option('wpseosms', $options);
	} 

}
register_activation_hook( __FILE__, 'wpseo_sitemap_activate' );



// add to settings menu
function wpseo_sitemap_create_menu() {
	add_submenu_page('options-general.php','SEO HTML Sitemap Settings', 'SEO HTML Sitemap', 'administrator', 'wpseo-html-sitemap', 'wpseo_sitemap_settings_page');
	add_action( 'admin_init', 'register_wpseo_sitemap_settings' );
}

// add settings link to plugins page
function wpseo_sitemap_create_settings_link($action_links,$plugin_file){
	if($plugin_file==plugin_basename(__FILE__)){
		$wcu_settings_link = '<a href="options-general.php?page=wpseo-html-sitemap">' . __("HTML Sitemap Settings") . '</a>';
		array_unshift($action_links,$wcu_settings_link);
	}
	return $action_links;
}

// register the settings
function register_wpseo_sitemap_settings() {
	register_setting( 'wpseo_html_sitemap_settings', 'wpseosms', 'wpseosms_validate');
}

// the admin settings page
function wpseo_sitemap_settings_page() {
	$options = get_option('wpseosms');
?>
<style type="text/css">
#wpseoO{background:transparent url('//ps.w.org/wp-seo-html-sitemap/assets/icon-256x256.png') top right no-repeat;}
#wpseoO .topSection{min-height:170px;}
#wpseoO .updated,#wpseoO h1.topTitle,#wpseoO p.desc{padding-right:300px;}
#wpseoO .shortcode{display:inline-block; text-align:center; width:auto;}
#wpseoO .inputs{width:33%;}
@media screen and (max-width:720px){
	#wpseoO{background-image:none;}
	#wpseoO .updated,#wpseoO h1.topTitle,#wpseoO p.desc{padding-right:0;}
	#wpseoO .inputs{width:100%;}
}
</style>

<div class="wrap" id="wpseoO">
<div class="topSection">
	<h1 class="topTitle">WP SEO HTML Sitemap Plugin</h1>
	<p class="desc">All settings used inside of <em>WordPress SEO by Yoast</em> (settings for sitemap XML and noindex) will also be applied to this HTML sitemap.<br />To fully utilize this plugin it is recomended you <a href="https://wordpress.org/plugins/wordpress-seo/" target="_blank">download</a> and activate Yoast's plugin as well.</p>
<?php if( $options['location'] == 'shortcode' ){ ?>
	<table class="form-table">
		<tr valign="top">
		<th scope="row">Use This Shortcode:</th>
		<td><input class="shortcode" type="text" onclick="this.focus();this.select();" readonly="readonly" value="[wpseo_html_sitemap]" /></td>
		</tr>
	</table>
<?php } ?>	
</div>

<h2>HTML Sitemap Settings:</h2>
<form method="post" action="options.php">
	<?php settings_fields('wpseo_html_sitemap_settings'); ?>
    <table class="form-table" style="width:100%;">
		<tr valign="top">
		<th scope="row">Sitemap Page:</th>
		<td><select name="wpseosms[pageID]" class="inputs">
			<?php
			if( $pages = get_pages() ){
				if ($options['wpseo-sitemap-pageID'] == ''){
					echo '<option value="">- Choose Your Sitemap Page -</option>';
				}
				foreach( $pages as $page ){
					echo '<option value="' . $page->ID . '" ' . selected( $page->ID, $options['pageID'] ) . '>' . $page->post_title . '</option>';
				}
			}
			?>
		</select>
		<br /><br /><small>What Page is your Sitemap placed on?<?php if($options['pageID'] !== ''){ echo ' <a href="'.get_permalink( $options['pageID'] ).'">Link to your sitemap page</a>'; } ?></small>
		</td>
		</tr>
        <tr valign="top">
        <th scope="row">Location On The Page</th>
        <td><select name="wpseosms[location]" class="inputs">
			<?php
			$location_option_labels = array("Replace the Page's Content","Before Page's Content","After Page's Content","Use Shortcode for Custom Placement");
			$location_option_values = array("replace","before","after","shortcode");
			$location_optionNum = 0;
			foreach( $location_option_values as $option_v ){
				echo '<option value="' . $option_v . '" ' . selected( $option_v, $options['location'] ) . '>' . $location_option_labels[$location_optionNum] . '</option>';
				$location_optionNum++;
			}
			?>
		</select>
		<br /><br /><small>Where should the sitemap be inside of the page? Before, Inside of with the shortcode [wpseo_html_sitemap], or affer the page's content?</small>
		</td>
        </tr>
        <tr valign="top">
        <th scope="row">Number of Columns:</th>
        <td><select name="wpseosms[columns]" class="inputs">
			<?php
			for ($columnNum = 1; $columnNum <= 4; $columnNum++){
				echo '<option value="' . $columnNum . '" ' . selected( $columnNum, $options['columns'] ) . '>' . $columnNum . ' Column Layout</option>';
			}
			?>
		</select>
		<br /><br /><small>Define how many columns you want to have in your html sitemap? This defines the layout.</small>
		</td>
        </tr>
        <tr valign="top">
        <th scope="row">Disable Plugin's CSS?</th>
        <td><select name="wpseosms[css-disable]" class="inputs">
			<?php
			$css_option_labels = array("Enable Plugin CSS Styles (Default)","Disable the CSS Styles");
			$css_option_values = array("no","yes");
			$css_optionNum = 0;
			foreach( $css_option_values as $option_v ){
				echo '<option value="' . $option_v . '" ' . selected( $option_v, $options['css-disable'] ) . '>' . $css_option_labels[$css_optionNum] . '</option>';
				$css_optionNum++;
			}
			?>
		</select>
		<br /><br /><small>If you don't want the plugin to load any CSS.</small>
		</td>
        </tr>
        <tr valign="top">
        <th scope="row">Link To XML Sitemap:</th>
        <td><select name="wpseosms[xml-link]" class="inputs">
			<?php
			$xml_option_labels = array("Yes, Add A Link To The Sitemap XML", "No, Don't Link to the Sitemap XML");
			$xml_option_values = array("yes","no");
			$xml_optionNum = 0;
			foreach( $xml_option_values as $option_v ){
				echo '<option value="' . $option_v . '" ' . selected( $option_v, $options['xml-link'] ) . '>' . $xml_option_labels[$xml_optionNum] . '</option>';
				$xml_optionNum++;
			}
			?>
		</select>
		<br /><br /><small>Why not add a link to your XML sitemap as well? ("/sitemap_index.xml"). <em>Yoast Plugin Required for this.</em></small>
		</td>
        </tr>
        <tr valign="top">
        <th scope="row">Credit Link:</th>
        <td><select name="wpseosms[credits-link]" class="inputs">
			<?php
			$link_option_labels = array("Add a Link to Plugin Author's Website", "Don't Add a Link to the Author's Website");
			$link_option_values = array("yes","no");
			$link_optionNum = 0;
			foreach( $link_option_values as $option_v ){
				echo '<option value="' . $option_v . '" ' . selected( $option_v, $options['credits-link'] ) . '>' . $link_option_labels[$link_optionNum] . '</option>';
				$link_optionNum++;
			}
			?>
		</select>
		<br /><br /><small>Enjoy this Plugin? Add a link to plugin author's website.</small>
		</td>
        </tr>
        <tr valign="top">
        <th scope="row">&nbsp;</th>
        <td class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></td>
        </tr>
    </table>

</form>
<p style="text-align:right;">Plugin by <a href="http://www.magnatechnology.com" target="_blank">Magna Technology, LLC</a><br /><small><em>This plugin is not affiliated with WordPress SEO by Yoast.</em></small></p>
</div>
<?php }

// validate data
function wpseosms_validate($input) {
	return $input;
}

// the plugin core
if ( !function_exists( 'wpseo_sitemap_shortcode' ) ) {
	function wpseo_sitemap_shortcode() {

	// ==============================================================================
	// General Variables
	$options = get_option('wpseosms');
	$checkOptions = get_option('wpseo_xml');
	$goHtm = '';

	//Hard Coded Styles
	if( $options['css-disable'] == '' ){
		echo '<link rel="stylesheet" type="text/css" href="'.plugin_dir_url( __FILE__ ) . 'style.css" media="screen" />';
	}
	
	$goHtm .= '<!-- WP SEO HTML Sitemap Plugin Start --><div id="wpseo_sitemap" class="columns_'.$options['columns'].'">';

	// ==============================================================================
	// Authors
	if ($checkOptions['disable_author_sitemap'] !== true ){
		$goHtm .= '<div id="sitemap_authors"><h3>'. __('Authors') .'</h3>
		<ul>';

		$authEx = implode (", ", get_users( 'orderby=nicename&meta_key=wpseo_excludeauthorsitemap&meta_value=on') );
		$goHtm .= wp_list_authors(array( 'exclude_admin' => false, 'exclude' => $authEx, 'echo' => false ) );
		$goHtm .= '</ul></div>';
	} 

	// ==============================================================================
	// Pages
	$pageCheck = get_pages(array( 'exclude' => $options['pageID'] ));
	if ( ( !empty( $pageCheck ) ) && $checkOptions['post_types-page-not_in_sitemap'] !== true ){
		$pageTitle = get_post_type_object( 'page' );
		$pageTitle = $pageTitle->label;
		$goHtm .= '<div id="sitemap_pages"><h3>'.$pageTitle.'</h3>
		<ul>';
		$pageInc = '';
		$getPages = get_all_page_ids();
		foreach( $getPages as $pageID ) {
			if ( $pageID !== $options['pageID'] ){
				if ( ( get_post_meta( $pageID, '_yoast_wpseo_meta-robots-noindex', true ) === '1' && get_post_meta( $pageID, '_yoast_wpseo_sitemap-include', true ) !== 'always' ) || ( get_post_meta( $pageID, '_yoast_wpseo_sitemap-include', true ) === 'never' ) ||  ( get_post_meta( $pageID, '_yoast_wpseo_redirect', true ) !== '' ) ) {
					continue;
				}
				if( $pageInc == ''){
					$pageInc = $pageID;
					continue;
				}
				$pageInc .= ', '.$pageID;
			}
		}
		$goHtm .= wp_list_pages( array( 'include' => $pageInc, 'title_li' => '', 'sort_column'  => 'post_title', 'sort_order' => 'ASC', 'echo' => false ) );

		$goHtm .= '</ul></div>';
	}
	

	// ==============================================================================
	// Posts
	$postsTest = get_posts();
	if ( ( !empty( $postsTest ) ) && $checkOptions['post_types-post-not_in_sitemap'] !== true ){
		$postTitle = get_post_type_object( 'post' );
		$postTitle = $postTitle->label;
		if( get_option( 'show_on_front' ) == 'page' ){
			$postsURL = get_permalink( get_option('page_for_posts') );
			$postTitle = get_the_title( get_option('page_for_posts') );
		}else{
			$postsURL = get_bloginfo('url');
		}
		$goHtm .= '<div id="sitemap_posts"><h3>';
		if ( $postsURL !== '' && $postsURL !== get_permalink($options['pageID']) ){
			$goHtm .= '<a href="'.  $postsURL .'">'. $postTitle .'</a>';
		}else{
			$goHtm .= $postTitle;
		}	
		$goHtm .= '</h3><ul>';
		//Categories
		$cateEx = '';
		$getCate = get_option('wpseo_taxonomy_meta');
		if( !empty($getCate['category']) ){
			foreach( $getCate['category'] as $cateID => $item ) {
				if( ( $item['wpseo_noindex'] == 'noindex' ) || ( $item['wpseo_sitemap_include'] == 'never' ) ){
					if( $cateEx == '' ) {
						$cateEx = $cateID;
					}
					else{
						$cateEx .= ', '.$cateID;
					}
				}
			}
		}
		$cats = get_categories('exclude='.$cateEx);
		foreach ($cats as $cat) {
			$goHtm .= "<li style='margin-top:10px;'><h4><a href='".esc_url( get_term_link( $cat ) )."'>".$cat->cat_name."</a></h4>";
			$goHtm .= "<ul>";
			query_posts('posts_per_page=-1&cat='.$cat->cat_ID);
			while(have_posts()) {
				the_post();
				if ( ( get_post_meta( get_the_ID(), '_yoast_wpseo_meta-robots-noindex', true ) === '1' && get_post_meta( get_the_ID(), '_yoast_wpseo_sitemap-include', true ) !== 'always' ) || ( get_post_meta( get_the_ID(), '_yoast_wpseo_sitemap-include', true ) === 'never' ) ||  ( get_post_meta( get_the_ID(), '_yoast_wpseo_redirect', true ) !== '' ) ) {
					continue;
				}

				$category = get_the_category();
				// Only display a post link once, even if it's in multiple categories
				if ( $category[0]->cat_ID == $cat->cat_ID ) {
					$goHtm .= '<li><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
				}
			}
			wp_reset_query();
			$goHtm .= "</ul>";
			$goHtm .= "</li>";
		}
		$goHtm .= '</ul></div>';
	}

	// ==============================================================================
	// Custom Post Types

	
	foreach( get_post_types( array('public' => true) ) as $post_type ) {
		$postsTest = get_posts('post_type='.$post_type);
		if  ( !empty( $postsTest ) ){
			$checkSitemap = 'post_types-'.$post_type.'-not_in_sitemap';
			if ( ( in_array( $post_type, array('post','page','attachment') ) ) || ( $checkOptions[$checkSitemap] === true ) ){
				continue;
			}
			$postType = get_post_type_object( $post_type );
			$postTypeLink = get_post_type_archive_link($postType->name);
			$goHtm .= '<div id="sitemap_'.str_replace( ' ', '', strtolower( $postType->labels->name ) ).'">';
			if( !empty( $postTypeLink ) ){
				$goHtm .= '<h3><a href="'. $postTypeLink .'">'. $postType->labels->name .'</a></h3>';
			}
			else{
				$goHtm .= '<h3>'. $postType->labels->name .'</h3>';
			}
			$goHtm .= '<ul>';
				query_posts('post_type='.$post_type.'&posts_per_page=-1&orderby=title&order=ASC');
			while( have_posts() ) {
				the_post();
				if ( ( get_post_meta( get_the_ID(), '_yoast_wpseo_meta-robots-noindex', true ) === '1' && get_post_meta( get_the_ID(), '_yoast_wpseo_sitemap-include', true ) !== 'always' ) || ( get_post_meta( get_the_ID(), '_yoast_wpseo_sitemap-include', true ) === 'never' ) ||  ( get_post_meta( get_the_ID(), '_yoast_wpseo_redirect', true ) !== '' ) ) {
					continue;
				}
				$goHtm .= '<li><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
			}
			wp_reset_query();
				$goHtm .= '</ul></div>';
		}
	}

	// ==============================================================================
	// Link To Sitemap
	if($options['xml-link'] == 'yes'){
		$goHtm .= '<div id="sitemap_xml"><h3><a rel="alternate" href="'.home_url().'/sitemap_index.xml" target="_blank">Sitemap XML</a></h3></div>';
	}

	// ==============================================================================
	// Add Credits Link
	if($options['credits-link'] == 'yes'){
		$goHtm .= '<div id="credits_link">Sitemap by <a href="http://www.magnatechnology.com/" target="_blank">Magna Technology, LLC</a></div>';
	}
	
	$goHtm .= '</div><div class="wpseo_clearRow"></div><!-- WP SEO HTML Sitemap Plugin END -->';

	return $goHtm;
	}
}
//End of Shortcode Function
add_shortcode('wpseo_html_sitemap', 'wpseo_sitemap_shortcode');

// add the sitemap to the page
function wpseo_html_sitemap_content($content) {
	$options = get_option('wpseosms');
	if( ( !empty( $options['pageID'] ) ) && ( is_page( $options['pageID'] ) ) &&  ( $options['location'] !== 'shortcode' ) ){
		$sitemap = '[wpseo_html_sitemap]';
		if ($options['location'] == 'before'){
			$content = $sitemap . $content;
		}else if ($options['location'] == 'after'){
			$content .= $sitemap;
		}else{
			$content = $sitemap;
		}
	}	
	return $content;
}
add_filter('the_content', 'wpseo_html_sitemap_content');

?>