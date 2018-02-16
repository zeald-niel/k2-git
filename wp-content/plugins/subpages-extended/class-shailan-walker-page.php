<?php 

class Shailan_Walker_Page extends Walker {

	var $tree_type = 'page';
	var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');
	var $_rel = ''; 

	function set_rel($rel){
		global $_rel;
		$_rel = $rel;
	}
	
	function start_lvl(&$output, $depth) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class='children'>\n";
	}

	function end_lvl(&$output, $depth) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $page, $depth, $args, $current_page) {
		global $_rel;
	
		if ( $depth )
			$indent = str_repeat("\t", $depth);
		else
			$indent = '';

		extract($args, EXTR_SKIP);
		$css_class = array('page_item', 'page-item-'.$page->ID);
		if ( !empty($current_page) ) {
			$_current_page = get_page( $current_page );
			get_post_ancestors($_current_page);
			if ( isset($_current_page->ancestors) && in_array($page->ID, (array) $_current_page->ancestors) )
				$css_class[] = 'current_page_ancestor';
			if ( $page->ID == $current_page )
				$css_class[] = 'current_page_item';
			elseif ( $_current_page && $page->ID == $_current_page->post_parent )
				$css_class[] = 'current_page_parent';
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current_page_parent';
		}

		$css_class = implode(' ', apply_filters('page_css_class', $css_class, $page));

		$output .= $indent . '<li class="' . $css_class . '"><a href="' . get_permalink($page->ID) . '" title="' . esc_attr( wp_strip_all_tags( apply_filters( 'the_title', $page->post_title, $page->ID ) ) ) . '" rel="'. $_rel .'">' . $link_before . apply_filters( 'walker_page_title', $page->post_title, $page->ID ) . $link_after . '</a>';

		if ( !empty($show_date) ) {
			if ( 'modified' == $show_date )
				$time = $page->post_modified;
			else
				$time = $page->post_date;

			$output .= " " . mysql2date($date_format, $time);
		}
	}

	function end_el(&$output, $page, $depth) {
		$output .= "</li>\n";
	}

}