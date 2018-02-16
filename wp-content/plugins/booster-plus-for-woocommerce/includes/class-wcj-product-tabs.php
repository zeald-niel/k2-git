<?php
/**
 * Booster for WooCommerce - Module - Product Tabs
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Tabs' ) ) :

class WCJ_Product_Tabs extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @todo    code refactoring and clean-up
	 */
	function __construct() {

		$this->id         = 'product_tabs';
		$this->short_desc = __( 'Product Tabs', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add custom product tabs - globally or per product. Customize or completely remove WooCommerce default product tabs.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-product-tabs';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'wp_head', array( $this, 'maybe_add_js_links' ) );
			add_filter( 'woocommerce_product_tabs', array( $this, 'customize_product_tabs' ), 98 );
			if ( 'yes' === get_option( 'wcj_custom_product_tabs_local_enabled', 'yes' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_custom_tabs_meta_box' ) );
				add_action( 'save_post_product', array( $this, 'save_custom_tabs_meta_box' ), 100, 2 );
			}
		}
	}

	/**
	 * Customize the product tabs.
	 *
	 * @version 2.8.0
	 */
	function customize_product_tabs( $tabs ) {

		// DEFAULT TABS
		// Unset
		if ( get_option( 'wcj_product_info_product_tabs_description_disable' ) === 'yes' ) {
			unset( $tabs['description'] );
		}
		if ( get_option( 'wcj_product_info_product_tabs_reviews_disable' ) === 'yes' ) {
			unset( $tabs['reviews'] );
		}
		if ( get_option( 'wcj_product_info_product_tabs_additional_information_disable' ) === 'yes' ) {
			unset( $tabs['additional_information'] );
		}

		// Priority and Title
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['priority'] = apply_filters( 'booster_get_option', 10, get_option( 'wcj_product_info_product_tabs_description_priority' ) );
			if ( get_option( 'wcj_product_info_product_tabs_description_title' ) !== '' ) {
				$tabs['description']['title'] = get_option( 'wcj_product_info_product_tabs_description_title' );
			}
		}
		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['priority'] = apply_filters( 'booster_get_option', 20, get_option( 'wcj_product_info_product_tabs_reviews_priority' ) );
			if ( get_option( 'wcj_product_info_product_tabs_reviews_title' ) !== '' ) {
				$tabs['reviews']['title'] = get_option( 'wcj_product_info_product_tabs_reviews_title' );
			}
		}
		if ( isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information']['priority'] = apply_filters( 'booster_get_option', 30, get_option( 'wcj_product_info_product_tabs_additional_information_priority' ) );
			if ( get_option( 'wcj_product_info_product_tabs_additional_information_title' ) !== '' ) {
				$tabs['additional_information']['title'] = get_option( 'wcj_product_info_product_tabs_additional_information_title' );
			}
		}

		// CUSTOM TABS
		// Add New
		$current_post_id = get_the_ID();
		// Add New - Global
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) ); $i++ ) {
			$key = 'global_' . $i;
			if ( '' != get_option( 'wcj_custom_product_tabs_title_' . $key, '' ) &&
				( '' != get_option( 'wcj_custom_product_tabs_content_' . $key, '' ) || '' != get_option( 'wcj_custom_product_tabs_link_' . $key, '' ) )
			) {
				if ( ! $this->is_global_tab_visible( $i, $current_post_id ) ) {
					continue;
				}
				// Adding the tab
				$tabs[ $key ] = array(
					'title'    => do_shortcode( get_option( 'wcj_custom_product_tabs_title_' . $key ) ),
					'priority' => get_option( 'wcj_custom_product_tabs_priority_' . $key, 40 ),
					'callback' => array( $this, 'create_new_custom_product_tab_global' ),
				);
			}
		}
		// Add New - Local
		$option_name = 'wcj_custom_product_tabs_local_total_number';
		if ( ! ( $total_custom_tabs = get_post_meta( $current_post_id, '_' . $option_name, true ) ) ) {
			$total_custom_tabs = apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		}
		if ( 'yes' !== get_option( 'wcj_custom_product_tabs_local_enabled', 'yes' ) ) {
			$total_custom_tabs = 0;
		}
		for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
			if ( $this->is_local_tab_visible( $i, $current_post_id ) ) {
				$key = 'local_' . $i;
				$tab_priority = get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_priority_' . $key, true );
				if ( ! $tab_priority ) {
					$tab_priority = (50 + $i - 1);
				}
				if ( '' != get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_title_' . $key, true ) &&
					(
						'' != get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_content_' . $key, true ) ||
						'' != get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_link_' . $key, true )
					)
				) {
					$tabs[ $key ] = array(
						'title'    => do_shortcode( get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_title_' . $key, true ) ),
						'priority' => $tab_priority,
						'callback' => array( $this, 'create_new_custom_product_tab_local' ),
					);
				}
			}
		}

		return $tabs;
	}

	/**
	 * is_global_tab_visible.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function is_global_tab_visible( $i, $_product_id ) {

		// Exclude by product id
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_global_hide_in_products_' . $i );
		if ( '' == $array_to_exclude || empty( $array_to_exclude ) ) {
			$list_to_exclude = get_option( 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i );
			if ( '' != $list_to_exclude ) {
				$array_to_exclude = explode( ',', $list_to_exclude );
			}
		}
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			if ( $array_to_exclude && in_array( $_product_id, $array_to_exclude ) ) {
				return false;
			}
		}

		// Exclude by product category
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_global_hide_in_cats_' . $i );
		if ( '' == $array_to_exclude || empty( $array_to_exclude ) ) {
			$list_to_exclude = get_option( 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i );
			if ( '' != $list_to_exclude ) {
				$array_to_exclude = explode( ',', $list_to_exclude );
			}
		}
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			$do_exclude = false;
			$product_categories_objects = get_the_terms( $_product_id, 'product_cat' );
			if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
				foreach ( $product_categories_objects as $product_categories_object ) {
					if ( $array_to_exclude && in_array( $product_categories_object->term_id, $array_to_exclude ) ) {
						$do_exclude = true;
						break;
					}
				}
			}
			if ( $do_exclude ) {
				return false;
			}
		}

		// Exclude by product tag
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_global_hide_in_tags_' . $i );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			$do_exclude = false;
			$product_tags_objects = get_the_terms( $_product_id, 'product_tag' );
			if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
				foreach ( $product_tags_objects as $product_tags_object ) {
					if ( $array_to_exclude && in_array( $product_tags_object->term_id, $array_to_exclude ) ) {
						$do_exclude = true;
						break;
					}
				}
			}
			if ( $do_exclude ) {
				return false;
			}
		}

		// Include by product id
		$array_to_include = get_option( 'wcj_custom_product_tabs_global_show_in_products_' . $i );
		if ( '' == $array_to_include || empty( $array_to_include ) ) {
			$list_to_include = get_option( 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i );
			if ( '' != $list_to_include ) {
				$array_to_include = explode( ',', $list_to_include );
			}
		}
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			// If NOT in array then hide this tab for this product
			if ( $array_to_include && ! in_array( $_product_id, $array_to_include ) ) {
				return false;
			}
		}

		// Include by product category
		$array_to_include = get_option( 'wcj_custom_product_tabs_global_show_in_cats_' . $i );
		if ( '' == $array_to_include || empty( $array_to_include ) ) {
			$list_to_include = get_option( 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i );
			if ( '' != $list_to_include ) {
				$array_to_include = explode( ',', $list_to_include );
			}
		}
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			$do_include = false;
			$product_categories_objects = get_the_terms( $_product_id, 'product_cat' );
			if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
				foreach ( $product_categories_objects as $product_categories_object ) {
					if ( $array_to_include && in_array( $product_categories_object->term_id, $array_to_include ) ) {
						$do_include = true;
						break;
					}
				}
			}
			if ( ! $do_include ) {
				return false;
			}
		}

		// Include by product tag
		$array_to_include = get_option( 'wcj_custom_product_tabs_global_show_in_tags_' . $i );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			$do_include = false;
			$product_tags_objects = get_the_terms( $_product_id, 'product_tag' );
			if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
				foreach ( $product_tags_objects as $product_tags_object ) {
					if ( $array_to_include && in_array( $product_tags_object->term_id, $array_to_include ) ) {
						$do_include = true;
						break;
					}
				}
			}
			if ( ! $do_include ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * get_js_link_script.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function get_js_link_script( $link, $key, $do_open_in_new_window ) {
		$link = do_shortcode( $link );
		$command = ( 'yes' === $do_open_in_new_window ) ?
			'window.open("' . $link . '","_blank");' :
			'window.location = "' . $link . '";';
		return '<script>' .
			'jQuery(document).ready(function() {
				jQuery("li.' . $key . '_tab").click(function(){
					' . $command . '
					return false;
				});
			});' .
		'</script>';
	}

	/**
	 * maybe_add_js_links.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function maybe_add_js_links() {
		$current_post_id = get_the_ID();
		// Global tabs
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) ); $i++ ) {
			if ( $this->is_global_tab_visible( $i, $current_post_id ) ) {
				$key = 'global_' . $i;
				if ( '' != get_option( 'wcj_custom_product_tabs_title_' . $key, '' ) && '' != ( $link = get_option( 'wcj_custom_product_tabs_link_' . $key, '' ) ) ) {
					echo $this->get_js_link_script( $link, $key, get_option( 'wcj_custom_product_tabs_link_new_tab_' . $key, true ) );
				}
			}
		}
		// Local tabs
		if ( 'yes' !== get_option( 'wcj_custom_product_tabs_local_enabled', 'yes' ) ) {
			return;
		}
		if ( ! ( $total_custom_tabs = get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_local_total_number', true ) ) ) {
			$total_custom_tabs = apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		}
		for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
			if ( $this->is_local_tab_visible( $i, $current_post_id ) ) {
				$key = 'local_' . $i;
				if ( '' != get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_title_' . $key, true ) ) {
					if ( '' != ( $link = get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_link_' . $key, true ) ) ) {
						echo $this->get_js_link_script( $link, $key, get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_link_new_tab_' . $key, true ) );
					}
				}
			}
		}
	}

	/**
	 * create_new_custom_product_tab_local.
	 */
	function create_new_custom_product_tab_local( $key, $tab ) {
		$current_post_id = get_the_ID();
		echo apply_filters( 'the_content', get_post_meta( $current_post_id, '_' . 'wcj_custom_product_tabs_content_' . $key, true ) );
	}

	/**
	 * create_new_custom_product_tab_global.
	 */
	function create_new_custom_product_tab_global( $key, $tab ) {
		echo apply_filters( 'the_content', get_option( 'wcj_custom_product_tabs_content_' . $key ) );
	}

	/**
	 * save_custom_tabs_meta_box.
	 *
	 * @version 2.8.0
	 * @todo    rewrite as standard `WCJ_Module` function
	 */
	function save_custom_tabs_meta_box( $post_id, $post ) {

		// Check that we are saving with custom tab metabox displayed.
		if ( ! isset( $_POST['woojetpack_custom_tabs_save_post'] ) )
			return;

		// Save: title, priority, content
		$option_names = array(
			'wcj_custom_product_tabs_title_local_',
			'wcj_custom_product_tabs_priority_local_',
			'wcj_custom_product_tabs_content_local_',
			'wcj_custom_product_tabs_link_local_',
			'wcj_custom_product_tabs_link_new_tab_local_',
		);
		$default_total_custom_tabs = apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		$total_custom_tabs_before_saving = get_post_meta( $post_id, '_' . 'wcj_custom_product_tabs_local_total_number', true );
		$total_custom_tabs_before_saving = ( '' != $total_custom_tabs_before_saving ) ? $total_custom_tabs_before_saving : $default_total_custom_tabs;
		for ( $i = 1; $i <= $total_custom_tabs_before_saving; $i++ ) {
			if ( $this->is_local_tab_visible( $i, $post_id ) ) {
				foreach ( $option_names as $option_name ) {
					update_post_meta( $post_id, '_' . $option_name . $i, $_POST[ $option_name . $i ] );
				}
			}
		}

		// Save: total custom tabs number
		$option_name = 'wcj_custom_product_tabs_local_total_number';
		$total_custom_tabs = isset( $_POST[ $option_name ] ) ? $_POST[ $option_name ] : $default_total_custom_tabs;
		update_post_meta( $post_id, '_' . $option_name, $_POST[ $option_name ] );
	}

	/**
	 * add_custom_tabs_meta_box.
	 *
	 * @version 2.4.7
	 */
	function add_custom_tabs_meta_box() {
		add_meta_box(
			'wc-jetpack-custom-tabs',
			__( 'Booster: Custom Tabs', 'woocommerce-jetpack' ),
			array( $this, 'create_custom_tabs_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * is_local_tab_visible.
	 *
	 * @version 2.4.7
	 * @since   2.4.7
	 */
	function is_local_tab_visible( $custom_tab_index, $product_id ) {

		// Exclude by product id
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_local_hide_in_products_' . $custom_tab_index );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			if ( $array_to_exclude && in_array( $product_id, $array_to_exclude ) ) {
				return false;
			}
		}

		// Exclude by product category
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_local_hide_in_cats_' . $custom_tab_index );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			$product_categories_objects = get_the_terms( $product_id, 'product_cat' );
			if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
				foreach ( $product_categories_objects as $product_categories_object ) {
					if ( $array_to_exclude && in_array( $product_categories_object->term_id, $array_to_exclude ) ) {
						return false;
					}
				}
			}
		}

		// Exclude by product tag
		$array_to_exclude = get_option( 'wcj_custom_product_tabs_local_hide_in_tags_' . $custom_tab_index );
		if ( '' != $array_to_exclude && ! empty( $array_to_exclude ) ) {
			$product_tags_objects = get_the_terms( $product_id, 'product_tag' );
			if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
				foreach ( $product_tags_objects as $product_tags_object ) {
					if ( $array_to_exclude && in_array( $product_tags_object->term_id, $array_to_exclude ) ) {
						return false;
					}
				}
			}
		}

		// Include by product id
		$array_to_include = get_option( 'wcj_custom_product_tabs_local_show_in_products_' . $custom_tab_index );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			// If NOT in array then hide this tab for this product
			if ( $array_to_include && ! in_array( $product_id, $array_to_include ) ) {
				return false;
			}
		}

		// Include by product category
		$array_to_include = get_option( 'wcj_custom_product_tabs_local_show_in_cats_' . $custom_tab_index );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			$do_include = false;
			$product_categories_objects = get_the_terms( $product_id, 'product_cat' );
			if ( $product_categories_objects && ! empty( $product_categories_objects ) ) {
				foreach ( $product_categories_objects as $product_categories_object ) {
					if ( $array_to_include && in_array( $product_categories_object->term_id, $array_to_include ) ) {
						$do_include = true;
						break;
					}
				}
			}
			if ( ! $do_include ) {
				return false;
			}
		}

		// Include by product tag
		$array_to_include = get_option( 'wcj_custom_product_tabs_local_show_in_tags_' . $custom_tab_index );
		if ( '' != $array_to_include && ! empty( $array_to_include ) ) {
			$do_include = false;
			$product_tags_objects = get_the_terms( $product_id, 'product_tag' );
			if ( $product_tags_objects && ! empty( $product_tags_objects ) ) {
				foreach ( $product_tags_objects as $product_tags_object ) {
					if ( $array_to_include && in_array( $product_tags_object->term_id, $array_to_include ) ) {
						$do_include = true;
						break;
					}
				}
			}
			if ( ! $do_include ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * create_custom_tabs_meta_box.
	 *
	 * @version 2.8.0
	 */
	function create_custom_tabs_meta_box() {

		$current_post_id = get_the_ID();
		$option_name = 'wcj_custom_product_tabs_local_total_number';
		if ( ! ( $total_custom_tabs = get_post_meta( $current_post_id, '_' . $option_name, true ) ) )
			$total_custom_tabs = apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
		$html = '';

		$is_disabled = apply_filters( 'booster_get_message', '', 'readonly_string' );
		$is_disabled_message = apply_filters( 'booster_get_message', '', 'desc' );

		$html .= '<table>';
		$html .= '<tr>';
		$html .= '<th>';
		$html .= __( 'Total number of custom tabs', 'woocommerce-jetpack' );
		$html .= '</th>';
		$html .= '<td>';
		$html .= '<input type="number" min="0" id="' . $option_name . '" name="' . $option_name . '" value="' . $total_custom_tabs . '" ' . $is_disabled . '>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= __( 'Click "Update" product after you change this number.', 'woocommerce-jetpack' ) . '<br>' . $is_disabled_message;
		$html .= '</td>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		echo $html;

		$options = array(
			array(
				'id'       => 'wcj_custom_product_tabs_title_local_',
				'title'    => __( 'Title', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
			array(
				'id'       => 'wcj_custom_product_tabs_priority_local_',
				'title'    => __( 'Order', 'woocommerce-jetpack' ),
				'type'     => 'number',
			),
			array(
				'id'       => 'wcj_custom_product_tabs_content_local_',
				'title'    => __( 'Content', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
			),
			array(
				'id'       => 'wcj_custom_product_tabs_link_local_',
				'title'    => __( 'Link', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'desc_tip' => __( 'If you wish to forward tab to new link, enter it here. In this case content is ignored. Leave blank to show content.', 'woocommerce-jetpack' ),
			),
			array(
				'id'       => 'wcj_custom_product_tabs_link_new_tab_local_',
				'title'    => __( 'Link - Open in New Window', 'woocommerce-jetpack' ),
				'type'     => 'select',
				'options'  => array(
					'no'  => __( 'No', 'woocommerce-jetpack' ),
					'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				),
			),
		);
		$enable_wp_editor = ( 'yes' === get_option( 'wcj_custom_product_tabs_local_wp_editor_enabled', 'yes' ) );
		for ( $i = 1; $i <= $total_custom_tabs; $i++ ) {
			$is_local_tab_visible = $this->is_local_tab_visible( $i, $current_post_id );
			$readonly = ( $is_local_tab_visible ) ? '' : ' readonly'; // not really used
			$disabled = ( $is_local_tab_visible ) ? '' : ' - ' . __( 'Disabled', 'woocommerce-jetpack' );
			$data = array();
			$html = '<hr>';
			$html .= '<h4>' . __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i . $disabled . '</h4>';
			if ( $is_local_tab_visible ) {
				foreach ( $options as $option ) {
					$option_id = $option['id'] . $i;
					if ( ! ( $option_value = get_post_meta( $current_post_id, '_' . $option_id, true ) ) ) {
						$option_value = get_option( $option['id'] . 'default_' . $i, '' );
						if ( '' === $option_value && 'wcj_custom_product_tabs_priority_local_' === $option['id'] ) {
							$option_value = (50 + $i - 1);
						}
					}
					switch ( $option['type'] ) {
						case 'select':
							$the_field = '<select type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '"' . $readonly . '>';
							foreach ( $option['options'] as $select_option_id => $select_option_title ) {
								$the_field .= '<option value="' . $select_option_id . '" ' . selected( $option_value, $select_option_id, false ). '>' . $select_option_title . '</option>';
							}
							$the_field .= '</select>';
							break;
						case 'number':
							$the_field = '<input style="width:25%;min-width:100px;" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '"' . $readonly . '>';
							break;
						case 'text':
							$the_field = '<input style="width:50%;min-width:150px;" type="' . $option['type'] . '" id="' . $option_id . '" name="' . $option_id . '" value="' . $option_value . '"' . $readonly . '>';
							break;
						case 'textarea':
							if ( $enable_wp_editor ) {
								ob_start();
								wp_editor( $option_value, $option_id );
								$the_field = ob_get_clean();
							} else {
								$the_field = '<textarea style="width:100%;height:300px;" id="' . $option_id . '" name="' . $option_id . '"' . $readonly . '>' . $option_value . '</textarea>';
							}
							break;
					}
					if ( '' != $the_field ) {
						if ( isset( $option['desc_tip'] ) && '' != $option['desc_tip'] ) {
							$option['title'] .= '<span class="woocommerce-help-tip" data-tip="' . $option['desc_tip'] . '"></span>';
						}
						$data[] = array( $option['title'], $the_field );
					}
				}
				$html .= wcj_get_table_html( $data, array( 'table_class' => 'widefat', 'table_style' => 'margin-bottom:20px;', 'table_heading_type' => 'vertical', 'columns_styles' => array( 'width:10%;', ) ) );
			}
			echo $html;
		}
		$html = '<input type="hidden" name="woojetpack_custom_tabs_save_post" value="woojetpack_custom_tabs_save_post">';
		echo $html;
	}

}

endif;

return new WCJ_Product_Tabs();
