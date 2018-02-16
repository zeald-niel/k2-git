<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Admin/Meta-Boxes
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Abstract Meta Box for Memberships
 *
 * Serves as a base meta box class for different meta boxes. One of the goals
 * is to keep meta box classes as self-contained as possible, removing any
 * external setup or configuration.
 *
 * @since 1.0.0
 */
abstract class WC_Memberships_Meta_Box {


	/** @var string meta box id **/
	protected $id;

	/** @var string meta box context **/
	protected $context = 'normal';

	/** @var string meta box priority **/
	protected $priority = 'default';

	/** @var array list of supported screen IDs **/
	protected $screens = array();

	/** @var array list of additional postbox classes for this meta box **/
	protected $postbox_classes = array( 'wc-memberships', 'woocommerce' );

	/** @var \WP_Post current post where the meta box appears */
	protected $post;

	/** @var \WC_Product current product where the meta box appears */
	protected $product;

	/** @var \WC_Order order object related to a User Membership */
	protected $order;

	/** @var \WP_User user object an User Membership belongs to */
	protected $user;

	/** @var \WC_Memberships_User_Membership current membership where the meta box appears */
	protected $user_membership;

	/** @var \WC_Memberships_Membership_Plan current plan instance where the meta box appears */
	protected $membership_plan;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// load the HTML view abstract
		require_once( wc_memberships()->get_plugin_path() . '/includes/admin/meta-boxes/views/abstract-wc-memberships-meta-box-view.php' );

		// add/edit screen hooks
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

		// enqueue meta box scripts and styles,
		// but only if the meta box has scripts or styles
		if ( method_exists( $this, 'enqueue_scripts_and_styles' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_scripts_and_styles' ) );
		}

		// update meta box data when saving post,
		// but only if the meta box supports data updates
		if ( method_exists( $this, 'update_data' ) ) {
			add_action( 'save_post', array( $this, 'save_post' ), 5, 2 );
		}
	}


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract public function get_title();


	/**
	 * Get the meta box ID
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Get the meta box ID, with underscores instead of dashes
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_id_underscored() {
		return str_replace( '-', '_', $this->id );
	}


	/**
	 * Get the nonce name for this meta box
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_nonce_name() {
		return '_' . $this->get_id_underscored() . '_nonce';
	}


	/**
	 * Get the nonce action for this meta box
	 *
	 * @since 1.0.0
	 * @return string
	 */
	protected function get_nonce_action() {
		return 'update-' . $this->id;
	}


	/**
	 * Get the post object
	 *
	 * @since 1.7.0
	 * @return \WP_Post
	 */
	public function get_post() {
		return $this->post;
	}


	/**
	 * Get the membership plan object
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan
	 */
	public function get_membership_plan() {
		return $this->membership_plan;
	}


	/**
	 * Get the user membership object
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_User_Membership
	 */
	public function get_user_membership() {
		return $this->user_membership;
	}


	/**
	 * Get the product object
	 *
	 * @since 1.7.0
	 * @return \WC_Product
	 */
	public function get_product() {
		return $this->product;
	}


	/**
	 * Get the order object
	 *
	 * @since 1.7.0
	 * @return \WC_Order
	 */
	public function get_order() {
		return $this->order;
	}


	/**
	 * Get the user object
	 *
	 * @since 1.7.0
	 * @return \WP_User
	 */
	public function get_user() {
		return $this->user;
	}


	/**
	 * Get access period options
	 *
	 * @since 1.7.0
	 * @return array Associative array of option keys and labels
	 */
	public function get_access_period_options() {
		return wc_memberships()->get_plans_instance()->get_membership_plans_access_length_periods( true );
	}


	/**
	 * Get access schedule period options
	 *
	 * @since 1.7.0
	 * @return array Associative array of option keys and labels
	 */
	public function get_access_schedule_period_options() {

		return array(
			'immediate' => __( 'immediately', 'woocommerce-memberships' ),
			'specific'  => __( 'specify a time', 'woocommerce-memberships' ),
		);
	}


	/**
	 * Get products discount type options
	 *
	 * @since 1.7.0
	 * @return array Associative array of option keys and labels
	 */
	public function get_discount_type_options() {

		return array(
			'percentage' => '%',
			'amount'     => get_woocommerce_currency_symbol(),
		);
	}


	/**
	 * Get Product restriction access type options
	 *
	 * @since 1.7.0
	 * @return array Associative array of option keys and labels
	 */
	public function get_product_restriction_access_type_options() {

		return array(
			'view'     => __( 'view', 'woocommerce-memberships' ),
			'purchase' => __( 'purchase', 'woocommerce-memberships' ),
		);
	}


	/**
	 * Get product restriction content type options
	 *
	 * @since 1.7.0
	 * @return array
	 */
	public function get_product_restriction_content_type_options() {

		$product_restriction_content_type_options = array(
			'post_types' => array(
				'post_type|product' => get_post_type_object( 'product' ),
			),
			'taxonomies' => array(),
		);

		foreach ( wc_memberships()->get_admin_instance()->get_valid_taxonomies_for_product_restriction() as $taxonomy_name => $taxonomy ) {
			$product_restriction_content_type_options['taxonomies'][ 'taxonomy|' . $taxonomy_name ] = $taxonomy;
		}

		return $product_restriction_content_type_options;
	}


	/**
	 * Get purchasing discount content type options
	 *
	 * @since 1.7.0
	 * @return array
	 */
	public function get_purchasing_discount_content_type_options() {

		$purchasing_discount_content_type_options = array(
			'post_types' => array(
				'post_type|product' => get_post_type_object( 'product' ),
			),
			'taxonomies' => array(),
		);

		foreach ( wc_memberships()->get_admin_instance()->get_valid_taxonomies_for_purchasing_discounts() as $taxonomy_name => $taxonomy ) {
			$purchasing_discount_content_type_options['taxonomies'][ 'taxonomy|' . $taxonomy_name ] = $taxonomy;
		}

		return $purchasing_discount_content_type_options;
	}


	/**
	 * Get available membership plans
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan[] Membership Plan objects
	 */
	public function get_available_membership_plans() {

		return wc_memberships_get_membership_plans( array(
			'post_status' => array( 'publish', 'private', 'future', 'draft', 'pending' )
		) );
	}


	/**
	 * Get Membership Plan options
	 *
	 * @since 1.7.0
	 * @return array Associative array of option keys and labels
	 */
	public function get_membership_plan_options() {

		$membership_plan_options = array();

		$membership_plans = $this->get_available_membership_plans();

		if ( ! empty( $membership_plans ) ) {

			foreach ( $membership_plans as $membership_plan ) {

				$membership_plan_name = $membership_plan->get_name();

				if ( 'publish' !== $membership_plan->post->post_status ) {
					/* translators: Placeholder: Membership plan name for a membership that is inactive */
					$membership_plan_name = sprintf( __( '%s (inactive)', 'woocommerce-memberships' ), $membership_plan_name );
				}

				$membership_plan_options[ $membership_plan->get_id() ] = $membership_plan_name;
			}
		}

		return $membership_plan_options;
	}


	/**
	 * Get user membership's user id
	 *
	 * @since 1.7.0
	 * @param null|\WC_Memberships_User_Membership $user_membership
	 * @return null|\WP_User
	 */
	public function get_membership_user( $user_membership = null ) {
		global $pagenow;

		$user    = null;
		$user_id = null;

		if ( 'post.php' === $pagenow && $user_membership ) {
			$user_id = $user_membership->get_user_id();
		} elseif ( isset( $_GET['user'] ) ) {
			$user_id = $_GET['user'];
		}

		if ( is_numeric( $user_id ) ) {
			$user = get_user_by( 'id', (int) $user_id );
		}

		return $user;
	}


	/**
	 * Get a blank rule to be used as template in meta box views to add new ones
	 *
	 * @since 1.7.0
	 * @param string $type Rule type: 'content_restriction', 'product_restriction' or 'purchasing_discount'
	 * @return \null|\WC_Memberships_Membership_Plan_Rule
	 */
	private function get_plan_rule_template( $type = '' ) {

		if ( ! in_array( $type, array( 'content_restriction', 'product_restriction', 'purchasing_discount' ), true ) ) {
			return null;
		}

		// rule args
		$args = array(
			'rule_type'          => $type,
			'id'                 => '',
			'content_type'       => '',
			'content_type_name'  => '',
			'object_ids'         => array(),
		);

		// determine the rule object by context
		if ( $this->membership_plan instanceof WC_Memberships_Membership_Plan ) {
			$args['membership_plan_id'] = $this->membership_plan->get_id();
		} elseif ( $this->post instanceof WP_Post ) {
			$args['object_id'] = (int) $this->post->ID;
		} else {
			return null;
		}

		if ( 'purchasing_discount' !== $type ) {
			// restriction properties
			$args = array_merge( $args, array(
				'access_type'                   => '',
				'access_schedule'               => 'immediate',
				'access_schedule_exclude_trial' => 'no',
			) );
		} else {
			// discount properties
			$args = array_merge( $args, array(
				'discount_type'      => '',
				'discount_amount'    => '',
				'active'             => '',
			) );
		}

		return new WC_Memberships_Membership_Plan_Rule( $args );
	}


	/**
	 * Get content restriction rules
	 *
	 * This stub method can be overridden by individual meta boxes
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] array of rule objects
	 */
	public function get_content_restriction_rules() {

		$content_restriction_rules = array();

		if ( $this->post ) {
			$content_restriction_rules['__INDEX__'] = $this->get_plan_rule_template( 'content_restriction' );
		}

		return $content_restriction_rules;
	}


	/**
	 * Get product restriction rules
	 *
	 * This stub method can be overridden by individual meta boxes
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] array of rule objects
	 */
	public function get_product_restriction_rules() {

		$product_restriction_rules = array();

		if ( $this->post ) {
			$product_restriction_rules['__INDEX__'] = $this->get_plan_rule_template( 'product_restriction' );
		}

		return $product_restriction_rules;
	}


	/**
	 * Get purchasing discount rules
	 *
	 * This stub method can be overridden by individual meta boxes
	 *
	 * @since 1.7.0
	 * @return \WC_Memberships_Membership_Plan_Rule[] array of object rules
	 */
	public function get_purchasing_discount_rules() {

		$purchasing_discount_rules = array();

		if ( $this->post ) {
			$purchasing_discount_rules['__INDEX__'] = $this->get_plan_rule_template( 'purchasing_discount' );
		}

		return $purchasing_discount_rules;
	}


	/**
	 * Enqueue scripts & styles for this meta box, if conditions are met
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function maybe_enqueue_scripts_and_styles() {

		$screen = get_current_screen();

		if ( ! in_array( $screen->id, $this->screens, true ) ) {
			return;
		}

		$this->enqueue_scripts_and_styles();
	}


	/**
	 * Enqueue scripts and styles for this meta box
	 *
	 * Default implementation is a no-op.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		// no-op, implement in subclass
	}


	/**
	 * Add meta box to the supported screen(s)
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		global $post;

		// sanity check
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! in_array( $screen->id, $this->screens, true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce_membership_plans' ) ) {
			return;
		}

		add_meta_box(
			$this->id,
			$this->get_title(),
			array( $this, 'do_output' ),
			$screen->id,
			$this->context,
			$this->priority
		);

		add_filter( "postbox_classes_{$screen->id}_{$this->id}", array( $this, 'postbox_classes' ) );
	}


	/**
	 * Add wc-memberships class to meta box
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $classes
	 * @return array
	 */
	public function postbox_classes( $classes ) {
		return array_merge( $classes, $this->postbox_classes );
	}


	/**
	 * Output basic meta box contents
	 *
	 * @since 1.0.0
	 */
	public function do_output() {
		global $post;

		// add a nonce field
		if ( method_exists( $this, 'update_data' ) ) {
			wp_nonce_field( $this->get_nonce_action(), $this->get_nonce_name() );
		}

		// output implementation-specific HTML
		$this->output( $post );
	}


	/**
	 * Output meta box contents
	 *
	 * @param \WP_Post $post
	 * @since 1.0.0
	 */
	abstract public function output( WP_Post $post );


	/**
	 * Process and save meta box data
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, WP_Post $post ) {

		// check nonce
		if ( ! isset( $_POST[ $this->get_nonce_name() ] ) || ! wp_verify_nonce( $_POST[ $this->get_nonce_name() ], $this->get_nonce_action() ) ) {
			return;
		}

		// if this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// bail out if not a supported post type
		if ( ! in_array( $post->post_type, $this->screens, true ) ) {
			return;
		}

		// check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		if ( ! current_user_can( 'manage_woocommerce_membership_plans' ) ) {
			return;
		}

		// implementation-specific meta box data update
		if ( method_exists( $this, 'update_data' ) ) {
			$this->update_data( $post_id, $post );
		}

		/**
		 * Save meta box
		 *
		 * @since 1.5.3
		 * @param array $_POST The Post data
		 * @param string $meta_box_id The meta box id
		 * @param int $post_id WP_Post id
		 * @param \WP_Post $post WP_Post object
		 */
		do_action( 'wc_memberships_save_meta_box', $_POST, $this->id, $post_id, $post );
	}


}
