<?php 
/*
Plugin Name: Subpages Extended
Plugin URI: http://metinsaylan.com/wordpress/plugins/subpages-widget
Description: A widget to list subpages of a page with an option to show subpages list on <strong>empty pages</strong>. It also comes with a <code>[subpages]</code> shortcode. You can read <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget#usage">how to use subpages</a> . You can find more widgets, plugins and themes at <a href="http://metinsaylan.com">metinsaylan.com</a>.
Version: 1.3.6
Author: Metin Saylan
Author URI: http://metinsaylan.com
*/

global $subpages_indexes;

include_once('class-shailan-walker-page.php');
include_once('subpages-menu-label-metabox.php');

function shailan_page_title_filter( $title, $id  ){

	$subpages_menu_label = htmlspecialchars( stripcslashes ( get_post_meta ( $id, '_subpages_menu_label', true ) ) );	
	$aiosp_menulabel = htmlspecialchars( stripcslashes ( get_post_meta ( $id, '_aioseop_menulabel', true ) ) );	
	
	if('' != $subpages_menu_label){
		return $subpages_menu_label;
	} elseif( '' != $aiosp_menulabel ) {
		return $aiosp_menulabel;
	} else {
		return $title;
	}
	
} add_filter( 'walker_page_title', 'shailan_page_title_filter', 10, 2 );

/**
 * Shailan Subpages Widget Class
 */
class shailan_SubpagesWidget extends WP_Widget {
    /** constructor */
    function shailan_SubpagesWidget() {
		$widget_ops = array('classname' => 'shailan_SubpagesWidget', 'description' => __( 'Subpages list', 'subpages-extended' ) );
		$this->WP_Widget('shailan-subpages-widget', __('Subpages Extended', 'subpages-extended'), $widget_ops);
		$this->alt_option_name = 'widget_shailan_subpages';
		
		if ( is_active_widget(false, false, $this->id_base) )
			add_action( 'wp_head', array(&$this, 'styles') );	

		$this->sort_options = array(
			'Post Title' => 'post_title',
			'Menu Order' => 'menu_order, post_title',
			'Date' => 'post_date',
			'Last Modified' => 'post_modified',
			'Page ID' => 'ID',
			'Page Author' => 'post_author',
			'Page Slug' => 'post_name'
		);
			
		$this->widget_defaults = array(
			'title' => '',
			'exclude' => '',
			'depth' => -1,
			'use_parent_title' => false,
			'exceptme' => false,
			'childof' => '',
			'sort_by' => 'menu_order, post_title',
			/* 'use_menu_labels' => false, */
			'link_on_title' => false,
			'rel' => ''
		);
    }
	
    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
		global $post;
		
		extract( $args );
		$widget_options = wp_parse_args( $instance, $this->widget_defaults );
		extract( $widget_options, EXTR_SKIP );
		
		$use_parent_title = (bool) $use_parent_title;
		/* $use_menu_labels = (bool) $use_menu_labels; */
		$link_on_title = (bool) $link_on_title;
		
		// echo "<pre>".print_r($instance, true)."</pre>";
		
		$is_visible = false;
		
		if( '-1' == $childof ) {  
			$childof = $post->ID;
		} elseif( '*parent*' == $childof ) {
			$childof = $post->post_parent;
			if($childof == 0){ $childof = $post->ID; } /* Top pages display sub pages only */
		} elseif( '*full-branch*' == $childof )	{
			if(!$post->post_parent){ 
				$childof = $post->ID; 
			} else {
				$parent = $post->post_parent;
				$p = get_post($parent);
				while($p->post_parent) {
					$p = get_post($p->post_parent);
				}
				
				$childof = $p->ID;
			}
		} else {
			$is_visible = true;
		}

		if( is_page() || $is_visible ){
			
			$parent = $childof;
			
			// Setup page walker
			$walker = new Shailan_Walker_Page;
			$title_filter = 'walker_page_title';
			$walker->set_rel($rel);
		
			// Use parent title
			if( $use_parent_title ){ $title = get_the_title($parent); }
			
			// Link parent title
			if( $use_parent_title && $link_on_title ){ 
				$title = '<a href="' . get_permalink($parent) . '" title="' . esc_attr( wp_strip_all_tags( apply_filters( 'the_title', $title, $parent) ) ) . '">' . apply_filters( $title_filter, $title, $parent ) . '</a>'; 
			} else {
				$title = apply_filters( $title_filter, $title, $parent );
			}
			
			if( !$use_parent_title ){ $title = apply_filters('widget_title', $title); }
			
			$children=wp_list_pages( 'echo=0&child_of=' . $parent . '&title_li=' );
			
			$subpage_args = array(
				'depth'        => $depth,
				'show_date'    => 0,
				'date_format'  => get_option('date_format'),
				'child_of'     => $parent,
				'exclude'      => $exclude,
				'include'      => '',
				'title_li'     => '',
				'echo'         => 1,
				'authors'      => '',
				'sort_column'  => $sort_by,
				'link_before'  => '',
				'link_after'   => '',
				'walker' => $walker );
		
			if ($children) {		
			?>
				  <?php echo $before_widget; ?>
					<?php if ( $title )
							echo $before_title . $title . $after_title;
					?>

				<div id="shailan-subpages-<?php echo $this->number; ?>">
					<ul class="subpages">
						<?php wp_list_pages($subpage_args); ?>
					</ul>
				</div> 			
				
				  <?php echo $after_widget; ?>
			<?php
			} else {
				echo "\n\t<!-- SUBPAGES : This page doesn't have any subpages. -->";
			};
		}
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
	
		$widget_options = wp_parse_args( $instance, $this->widget_defaults );
		extract( $widget_options, EXTR_SKIP );
		
        $title = esc_attr($title);
		$use_parent_title = (bool) $use_parent_title;
		$link_on_title = (bool) $link_on_title;
		/*$use_menu_labels = (bool) $use_menu_labels;*/
		
		//echo "<pre>".print_r($instance, true)."</pre>";
		
        ?>		
		<div class="shailan-widget">
		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('use_parent_title'); ?>" name="<?php echo $this->get_field_name('use_parent_title'); ?>"<?php checked( $use_parent_title ); ?> /> <label for="<?php echo $this->get_field_id('use_parent_title'); ?>"><?php _e( 'Use page title as widget title' , 'subpages-extended' ); ?></label>
		<a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#title">(?)</a>
		</p>
		
		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('link_on_title'); ?>" name="<?php echo $this->get_field_name('link_on_title'); ?>"<?php checked( $link_on_title ); ?> /> <label for="<?php echo $this->get_field_id('link_on_title'); ?>"><?php _e( 'Use link on title' , 'subpages-extended' ); ?></label>
		<a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#title-link">(?)</a>
		</p>
		
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title :'); ?> <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#title">(?)</a> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('childof'); ?>"><?php _e('Parent (Subpages of):'); ?> <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#parent">(?)</a> <?php 
			$args = array( 
				'selected' => $childof,
				'show_option_no_change' => '*Current page*', 
				'show_option_none' => '*Parent of current page*',
				'option_none_value' => '*parent*',
				'name' => $this->get_field_name('childof'), 
				'id' => $this->get_field_id('childof') 
			); shailan_subpages_dropdown_pages($args); ?></label></p>
			
		<p><label for="<?php echo $this->get_field_id('rel'); ?>"><?php _e('Rel :'); ?> <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#rel">(?)</a> <input class="widefat" id="<?php echo $this->get_field_id('rel'); ?>" name="<?php echo $this->get_field_name('rel'); ?>" type="text" value="<?php echo $rel; ?>" /></label></p>
		
			
		<p><label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e('Exclude:'); ?> <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#exclude">(?)</a> <input class="widefat" id="<?php echo $this->get_field_id('exclude'); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" type="text" value="<?php echo $exclude; ?>" /></label><br /> 
		<small>Page IDs, separated by commas.</small></p>
		
		<p><label for="<?php echo $this->get_field_id('sort_column'); ?>"><?php _e('Sort by :'); ?> <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#sort-by">(?)</a> <select name="<?php echo $this->get_field_name('sort_column'); ?>" id="<?php echo $this->get_field_id('sort_column'); ?>" ><?php 
  foreach ($this->sort_options as $value=>$key) {  
  	$option = '<option value="'. $key .'" '. ( $key == $sort_column ? ' selected="selected"' : '' ) .'>';
	$option .= $value;
	$option .= '</option>\n';
	echo $option;
  }
 ?>
</select></label></p>
		
		<p><label for="<?php echo $this->get_field_id('depth'); ?>"><?php _e('Depth:'); ?> <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#depth">(?)</a> <input class="widefat" id="<?php echo $this->get_field_id('depth'); ?>" name="<?php echo $this->get_field_name('depth'); ?>" type="text" value="<?php echo $depth; ?>" /></label><br /> 
		<small>Depth of menu.</small></p>
		
		<!-- <p><input type="checkbox" class="checkbox" id="<?php //echo $this->get_field_id('use_menu_labels'); ?>" name="<?php //echo $this->get_field_name('use_menu_labels'); ?>"<?php //checked( $use_menu_labels ); ?> /> <label for="<?php //echo $this->get_field_id('use_menu_labels'); ?>"><?php //_e( 'Use menu labels for page title.' , 'subpages-extended' ); ?></label>
		<a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help#using-menu-labels">(?)</a>
		</p> -->
		
		<div class="widget-control-actions">
			<p><small>Powered by <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget" title="Wordpress Tips and tricks, Freelancing, Web Design (opens in new window)" target="_blank">metinsaylan.com</a> | <a href="http://metinsaylan.com/wordpress/" title="Get more wordpress widgets and themes (opens in new window)" target="_blank">Get more..</a></small></p>
		</div>
		</div>
        <?php 
	}
	
	function styles($instance){
		// additional styles will be printed here.
	}

} // class shailan_SubpagesWidget

// register widget
add_action('widgets_init', create_function('', 'return register_widget("shailan_SubpagesWidget");'));

function subpages_widget_adminMenu(){

	if(is_admin()){ 
		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'subpages-extended', WP_PLUGIN_URL . '/subpages-extended/css/subpages.css' );
	};

	if ( @$_GET['page'] == 'subpages-extended' ) {
		if ( @$_REQUEST['action'] && 'save' == $_REQUEST['action'] ) {
			if(isset($_REQUEST['auto_insert'])) {
				update_option( 'subpages_extended_auto_insert', $_REQUEST['auto_insert'] );
			} else { update_option( 'subpages_extended_auto_insert', false ); }
		}
	}

	if (function_exists('add_options_page')) {
			$page = add_options_page(__('Subpages Extended Options', 'subpages-extended') , __('Subpages Extended', 'subpages-extended'), 'edit_themes', 'subpages-extended', 'subpages_widget_options_page');
	}
}
// add admin menu
add_action('admin_menu', 'subpages_widget_adminMenu');

function subpages_widget_options_page(){

	$title = "Subpages Extended Options";
	?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title ); ?></h2>

<div class="nav"><small><a href="http://metinsaylan.com/wordpress/plugins/subpages-widget">Plugin page</a> | <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/help">Usage</a> | <a href="http://metinsaylan.com/wordpress/plugins/subpages-widget/shortcode">Shortcode</a> | <a href="http://metinsaylan.com/donate">Donate</a> | <a href="http://metinsaylan.com/wordpress">Get more widgets..</a></small></div>

<div class="share">
	<div class="share-label">
		Like this plugin? 
	</div>

	<div class="share-button tweet">
		<a href="http://twitter.com/share" class="twitter-share-button" data-url="http://metinsaylan.com/wordpress/plugins/subpages-widget/" data-text="I am using subpages extended #widget on my #wordpress blog, Check this out!" data-count="horizontal" data-via="shailancom">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
	</div>

	<div class="share-button facebook">
		<script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>
		<fb:like href="http://metinsaylan.com/wordpress/plugins/subpages-widget/" ref="plugin_options" show_faces="false" width="400" font="segoe ui"></fb:like>
	</div>
	
	<div class="clear"></div>
</div>



<?php if ( isset($_GET['message']) && isset($messages[$_GET['message']]) ) { ?>
<div id="message" class="updated"><p><?php echo $messages[$_GET['message']]; ?></p></div>
<?php } ?>
<?php if ( isset($_GET['error']) && isset($errors[$_GET['error']]) ) { ?>
<div id="message" class="error"><p><?php echo $errors[$_GET['error']]; ?></p></div>
<?php } ?>

<form id="frmShailanDm" name="frmShailanDm" method="post" action="">

<table class="form-table"> 
<tr valign="top"> 
	<th scope="row"><label for="auto_insert"><?php _e('Auto-insert:'); ?></label></th> 
	<td>
		<?php if(get_option('subpages_extended_auto_insert')){ $checked = "checked=\"checked\""; }else{ $checked = "";} ?>
		<input type="checkbox" name="auto_insert" id="auto_insert" value="true" <?php echo $checked; ?> />
		<span class="description">Auto-insert subpages list on empty pages.</span>
	</td> 
</tr> 
</table>

<input type="hidden" name="action" value="save" />

<p class="submit"> 
<input type="submit" name="Submit" class="button-primary" value="Save Changes" /> 
</p> 
 
</form>

<div id="shailancom" style="background:#ededed; border-top:10px solid #ff9966; padding:15px;">
<h3>Latest headlines from MetinSaylan.com</h3>
		<?php	
			
			$rss_options = array(
				'link' => 'http://metinsaylan.com',
				'url' => 'http://metinsaylan.com/feed',
				'title' => 'MetinSaylan.com',
				'items' => 5,
				'show_summary' => 0,
				'show_author' => 0,
				'show_date' => 0,
				'before' => 'text'
			);

			wp_widget_rss_output( $rss_options ); ?>
</div>

<p>
<small><a href="http://metinsaylan.com/wordpress/plugins/subpages-widget" rel="external" target="_blank">Subpages Extended</a> by <a href="http://metinsaylan.com">Metin Saylan</a>.</small>
</p>

</div>

<?php
}

function shailan_subpages_shortcode($atts) {
	global $post, $subpages_indexes;
	
	extract(shortcode_atts(array(
		'depth'        => 3,
		'show_date'    => false,
		'date_format'  => get_option('date_format'),
		'child_of'     => -1,
		'exclude'      => '',
		'include'      => '',
		'title_li'     => '',
		'echo'         => 1,
		'authors'      => '',
		'sort_column'  => 'menu_order, post_title',
		'sort_order'   => 'ASC',
		'link_before'  => '',
		'link_after'   => '',
		/* 'walker' =>  '', Can not be used in shortcode really, Use use_menu_labels option to switch current walker */
		'exceptme' => false,
		'childof' => '',
		'title' => '',
		/*'use_menu_labels' => true, */
		'rel' => ''
		), $atts));
		

		$walker = new Shailan_Walker_Page;
		$walker->set_rel( $rel );
	
	if('parent' == $childof || 'parent' == $child_of) {  
		$parent = $post->post_parent;
		//if($parent == 0){ $parent = $post->ID; } /* Top pages display sub pages only */
	} else {
		$parent = $childof;
		if(-1 != $child_of) { $parent = $child_of; }
		if($parent==''){ $parent = $post->ID; }
	}
	
	if($exceptme) { $exclude .= ','.$post->ID; }
	if($title == '*current*'){ $title = '<h3>' . get_the_title($parent) . '</h3>'; }
	
	$subpages_indexes += 1;
	$shortcode_id = $subpages_indexes;

	$children = wp_list_pages( 'echo=0&child_of=' . $parent . '&title_li=' );
	
	$subpage_args = array(
		'depth'        => $depth,
		'show_date'    => $show_date,
		'date_format'  => $date_format,
		'child_of'     => $parent,
		'exclude'      => $exclude,
		'include'      => $include,
		'title_li'     => '',
		'echo'         => false,
		'authors'      => $authors,
		'sort_column'  => $sort_column,
		'sort_order' => $sort_order,
		'link_before'  => $link_before,
		'link_after'   => $link_after,
		'walker' =>  $walker );
	
	if ($children) {
		$subpages = '<div id="shailan-subpages-' . $post->ID . '-' .$shortcode_id.'">'.$title.'<ul class="subpages">';
		$subpages .= wp_list_pages( $subpage_args );
		$subpages .= '</ul></div>';
	} else {
		$subpages = '"' . get_the_title($parent) . '" doesn\'t have any sub pages.';
	}
		
	return $subpages;
}
add_shortcode('subpages', 'shailan_subpages_shortcode');

function shailan_subpages_filter($content){
	global $post;
	
	$autoinsert = ! (bool) get_option( 'subpages_extended_auto_insert');
	
	if( strlen($content) != 0 || $autoinsert )
		return $content;
	
	$parent = $post->ID;
	$children = wp_list_pages( 'echo=0&child_of=' . $parent . '&title_li=' ); 
	$depth = 4;
	$exclude = '';
	
	if ($children) { 
		ob_start();
		?>
		<div class="shailan-subpages-container">
				<ul class="subpages">
					<?php wp_list_pages('sort_column=menu_order,post_title&depth='.$depth.'&title_li=&child_of='.$post->ID.'&exclude='.$exclude); ?>
				</ul>
		</div> 
		<?php
		$subpages = ob_get_clean();
		return $subpages;
	} else {
		return $content . "\n\t<!-- SUBPAGES : This page doesn't have any subpages. -->";
	}
} add_filter('the_content', 'shailan_subpages_filter');

function shailan_subpages_dropdown_pages($args = '') {
	
	$defaults = array(
		'depth' => 0, 'child_of' => 0,
		'selected' => 0, 'echo' => 1,
		'name' => 'page_id', 'id' => '',
		'show_option_none' => '', 'show_option_no_change' => '',
		'option_none_value' => ''
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	$pages = get_pages($r);
	$output = '';
	$name = esc_attr($name);
	// Back-compat with old system where both id and name were based on $name argument
	if ( empty($id) )
		$id = $name;

	if ( ! empty($pages) ) {
		$output = "<select name=\"$name\" id=\"$id\">\n";
		if ( $show_option_no_change )
			$output .= "\t<option value=\"-1\" " . selected( $selected, '-1', false ) . ">$show_option_no_change</option>";
		if ( $show_option_none )
			$output .= "\t<option value=\"" . esc_attr($option_none_value) . "\" " . selected( $selected, $option_none_value, false ) . ">$show_option_none</option>\n";
		$output .= "\t<option value=\"*full-branch*\" " . selected( $selected, "*full-branch*", false ) . ">*Full branch*</option>\n";		
		$output .= walk_page_dropdown_tree($pages, $depth, $r);
		$output .= "</select>\n";
	}

	$output = apply_filters('wp_dropdown_pages', $output);

	if ( $echo )
		echo $output;

	return $output;
}

