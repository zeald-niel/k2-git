<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create new widget named Easy Sidebar Menu
 * run on __construct function
 */
if( !class_exists( 'Easy_Sidebar_Menu_Walker' ) ){
	class Easy_Sidebar_Menu_Walker extends Walker_Nav_Menu {

		function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ){
	        $id_field = $this->db_fields['id'];
	        if ( is_object( $args[0] ) ) {
	            $args[0]->has_children = ! empty( $children_elements[$element->$id_field] );
	        }
	        return parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	    }
		/**
	     * Start the element output.
	     *
	     * @param  string $output Passed by reference. Used to append additional content.
	     * @param  object $item   Menu item data object.
	     * @param  int $depth     Depth of menu item. May be used for padding.
	     * @param  array|object $args    Additional strings. Actually always an
	                                     instance of stdClass. But this is WordPress.
	     * @return void
	     */
	    function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ){
	        $classes     = empty ( $item->classes ) ? array () : (array) $item->classes;

	        $class_names = join(
	            ' ',
				apply_filters(
	                'easy_sidebar_menu_widget_css_class',
					array_filter( $classes ),
					$item
	            )
	        );

	        ! empty ( $class_names )
	            and $class_names = ' class="'. esc_attr( $class_names ) . '"';

	        $output .= "<li id='menu-item-$item->ID' $class_names>";

	        $attributes  = '';

	        ! empty( $item->attr_title )
	            and $attributes .= ' title="'  . esc_attr( $item->attr_title ) .'"';
	        ! empty( $item->target )
	            and $attributes .= ' target="' . esc_attr( $item->target     ) .'"';
	        ! empty( $item->xfn )
	            and $attributes .= ' rel="'    . esc_attr( $item->xfn        ) .'"';
	        ! empty( $item->url )
	            and $attributes .= ' href="'   . esc_attr( $item->url        ) .'"';

			$attributes .= ' class="easy-sidebar-menu-widget-link"';

	        // insert description for top level elements only
	        // you may change this
	        $description = ( ! empty ( $item->description ) and 0 == $depth )
	            ? '<small class="nav_desc">' . esc_attr( $item->description ) . '</small>' : '';

			//insert toggler for submenu items
			$toggler = ( $args->has_children )
	            ? '<a href="#" class="easy-sidebar-menu-widget-toggler"><i></i></a>' : '';

	        $title = apply_filters( 'the_title', $item->title, $item->ID );

	        $item_output = $args->before
	            . "<span class='link__wrap'><a $attributes>"
	            . $args->link_before
	            . $title
				. $description
	            . '</a> '
				. $toggler
				. '</span>'
	            . $args->link_after
	            . $args->after;

	        // Since $output is called by reference we don't need to return anything.
	        $output .= apply_filters(
	            'walker_easy_sidebar_menu_widget_start_el',
				$item_output,
				$item,
				$depth,
				$args
	        );
	    }
    }
}
