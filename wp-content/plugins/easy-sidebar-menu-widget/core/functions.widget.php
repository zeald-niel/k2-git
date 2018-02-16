<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create new widget named Easy Sidebar Menu
 * run on __construct function
 */
if( !class_exists( 'CREATE_EASY_SIDEBAR_MENU_WIDGET' ) ){
	class CREATE_EASY_SIDEBAR_MENU_WIDGET extends WP_Widget {
		function __construct(){
			parent::__construct(
				'easy_sidebar_menu_widget',
				__( 'Easy Sidebar Menu', 'easy-sidebar-menu-widget' ),
				array( 'description' => __( 'Display menus on the sidebar section with hover action to show child menus.', 'easy-sidebar-menu-widget' )
				)
			);
		}

		/**
		 * Outputs the content of the widget
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			// Get menu
			$nav_menu = ! empty( $instance['nav_menu'] ) ? wp_get_nav_menu_object( $instance['nav_menu'] ) : false;

			if ( !$nav_menu )
				return;

			/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
			$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

			echo $args['before_widget'];

			if ( !empty($instance['title']) )
				echo $args['before_title'] . $instance['title'] . $args['after_title'];

			$nav_menu_args = array(
				'fallback_cb' => '',
				'menu'        => $nav_menu,
				'walker'          => new Easy_Sidebar_Menu_Walker
			);

			/**
			 * Filters the arguments for the Custom Menu widget.
			 *
			 * @since 4.2.0
			 * @since 4.4.0 Added the `$instance` parameter.
			 *
			 * @param array    $nav_menu_args {
			 *     An array of arguments passed to wp_nav_menu() to retrieve a custom menu.
			 *
			 *     @type callable|bool $fallback_cb Callback to fire if the menu doesn't exist. Default empty.
			 *     @type mixed         $menu        Menu ID, slug, or name.
			 * }
			 * @param stdClass $nav_menu      Nav menu object for the current menu.
			 * @param array    $args          Display arguments for the current widget.
			 * @param array    $instance      Array of settings for the current widget.
			 */
			wp_nav_menu( apply_filters( 'easy_sidebar_menu_nav_menu_args', $nav_menu_args, $nav_menu, $args, $instance ) );

			echo $args['after_widget'];
		}

		/**
		 * Ouputs the options form on admin
		 *
		 * @param array $instance The widget options
		 */
		public function form( $instance ) {
			global $wp_customize;
			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$nav_menu = isset( $instance['nav_menu'] ) ? $instance['nav_menu'] : '';

			// Get menus
			$menus = wp_get_nav_menus();
			?>
			<p class="nav-menu-widget-no-menus-message" <?php if ( ! empty( $menus ) ) { echo ' style="display:none" '; } ?>>
				<?php
				if ( $wp_customize instanceof WP_Customize_Manager ) {
					$url = 'javascript: wp.customize.panel( "nav_menus" ).focus();';
				} else {
					$url = admin_url( 'nav-menus.php' );
				}
				?>
				<?php echo sprintf( __( 'No menus have been created yet. <a href="%s">Create some</a>.', 'easy-sidebar-menu-widget' ), esc_attr( $url ) ); ?>
			</p>
			<div class="easy-sidebar-menu-widget--form" <?php if ( empty( $menus ) ) { echo ' style="display:none" '; } ?>>
				<p>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
					<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'nav_menu' ); ?>"><?php _e( 'Select Menu:', 'easy-sidebar-menu-widget' ); ?></label>
					<select id="<?php echo $this->get_field_id( 'nav_menu' ); ?>" name="<?php echo $this->get_field_name( 'nav_menu' ); ?>">
						<option value="0"><?php _e( '&mdash; Select &mdash;' ); ?></option>
						<?php foreach ( $menus as $menu ) : ?>
							<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $nav_menu, $menu->term_id ); ?>>
								<?php echo esc_html( $menu->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<?php if( !class_exists('PHPBITS_extendedWidgetsDisplay') ):?>
					<div class="easy-sidebar-menu-widget--after">
						<a href="http://widget-options.com?utm_source=easy-menu-widget" target="_blank" ><?php _e( '<strong>Manage your widgets</strong> visibility, styling, alignment, columns, restrictions and more. Click here to learn more. ', 'easy-sidebar-menu-widget' );?></a>
					</div>
				<?php endif;?>
			</div>
			<?php
		}
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			if ( ! empty( $new_instance['title'] ) ) {
				$instance['title'] = sanitize_text_field( $new_instance['title'] );
			}
			if ( ! empty( $new_instance['nav_menu'] ) ) {
				$instance['nav_menu'] = (int) $new_instance['nav_menu'];
			}
			return $instance;
		}
	}

	// register widget
	function register_easy_sidebar_menu_widget() {
	    register_widget( 'CREATE_EASY_SIDEBAR_MENU_WIDGET' );
	}
	add_action( 'widgets_init', 'register_easy_sidebar_menu_widget' );
}
?>
