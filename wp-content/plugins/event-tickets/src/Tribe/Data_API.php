<?php


/**
 * Class to connect ticketing infomation requests with ticket provider
 *
 * Class Tribe__Tickets__Data_API
 */
class Tribe__Tickets__Data_API {

	protected $active_modules;
	protected $ticket_types = array();
	protected $ticket_class = array();

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->active_modules = Tribe__Tickets__Tickets::modules();
		$this->setup_data();
	}

	/**
	 * Setup activate ticket classes and field for data api
	 */
	protected function setup_data() {

		foreach ( Tribe__Tickets__Tickets::modules() as $module_class => $module_instance ) {
			$provider = call_user_func( array( $module_class, 'get_instance' ) );

			/**
			 * The usage of plain `$module_class::ATTENDEE_EVENT_KEY` will throw a `T_PAAMAYIM_NEKUDOTAYIM`
			 * when using PHP 5.2, which is a fatal.
			 *
			 * So we have to construct the constant name using a string and use the `constant` function.
			 */
			$types['order']   = constant( "$module_class::ORDER_OBJECT" );
			$types['product'] = $provider->ticket_object;
			$types['ticket']  = constant( "$module_class::ATTENDEE_OBJECT" );
			if ( 'Tribe__Tickets__RSVP' === $module_class ) {
				$types['ticket'] = $provider->ticket_object;
			}
			$types['attendee'] = constant( "$module_class::ATTENDEE_OBJECT" );

			$this->ticket_class[ $module_class ] = array();


			foreach ( $types as $key => $value ) {
				$this->ticket_types[ $key ][]                = $value;
				$this->ticket_class[ $module_class ][ $key ] = $value;
			}

			$this->ticket_class[ $module_class ]['tribe_for_event'] = $provider->event_key;
			$this->ticket_class[ $module_class ]['event_id_key'] = constant( "$module_class::ATTENDEE_EVENT_KEY" );
			$this->ticket_class[ $module_class ]['order_id_key'] = constant( "$module_class::ATTENDEE_ORDER_KEY" );
		}

		$this->ticket_types['events'][] = class_exists( 'Tribe__Events__Main' ) ? Tribe__Events__Main::POSTTYPE : '';

	}

	/**
	 * Detect what is available in the custom post type by the id passed to it
	 *
	 * @param null $post id or post object
	 *
	 * @return array|bool array includes infomation available and the tribe_tickets_tickets class to use
	 */
	public function detect_by_id( $post = null ) {

		// only the rsvp order key is non numeric
		if ( is_object( $post ) && ! empty( $post->ID ) ) {
			$post = (int) $post->ID;
			$cpt  = get_post_type( $post->ID );
		} elseif ( ! is_numeric( $post ) ) {
			$post = esc_attr( $post );
			$cpt  = $this->check_rsvp_order_key_exists( $post );
		} else {
			$post = absint( $post );
			$cpt  = get_post_type( $post );
		}

		// if no custom post type
		if ( ! $cpt ) {
			return false;
		}

		$cpt_arr              = array();
		$cpt_arr['post_type'] = $cpt;

		foreach ( $this->ticket_types as $type => $cpts ) {
			if ( in_array( $cpt, $cpts ) ) {
				$cpt_arr[] = $type;
			}
		}

		foreach ( $this->ticket_class as $classes => $cpts ) {
			if ( in_array( $cpt, $cpts ) ) {
				$cpt_arr['class'] = $classes;
			}
		}

		return $cpt_arr;
	}

	/**
	 * Return Array of Event IDs when passed an Order, Ticket, or Attendee ID
	 *
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_event_ids( $post_id ) {
		$services = $this->detect_by_id( $post_id );
		if ( ! is_array( $services ) ) {
			$services = array();
		}

		// if this id is not an order id or a ticket id return
		$is_ticket_related = array_intersect( array( 'order', 'ticket', 'attendee', 'product' ), $services );
		if ( ! $is_ticket_related ) {
			return array();
		}

		// if no post type or module class return
		if ( empty( $services['post_type'] ) || empty( $services['class'] ) ) {
			return array();
		}

		$module_class = $services['class'];

		/**
		 * if we have a rsvp order with a unique rsvp order key
		 * change $post_id to the first rsvp post's id
		 */
		if ( ! is_numeric( $post_id ) && 'Tribe__Tickets__RSVP' === $module_class ) {
			$post_id = $this->get_rsvp_post_id_from_order_key( $post_id );
		}
		$event_id_key = $this->ticket_class[ $module_class ]['event_id_key'];
		$event_ids    = array();

		$is_product = array_intersect( array( 'product' ), $services );
		if ( $is_product ) {
			$tribe_for_event = $this->ticket_class[ $module_class ]['tribe_for_event'];
			$event_ids[]     = (int) get_post_meta( $post_id, $tribe_for_event, true );

			return $event_ids;
		}


		// if rsvp or a ticket id get the connected id field
		$is_ticket_attendee = array_intersect( array( 'ticket', 'attendee' ), $services );
		if ( 'Tribe__Tickets__RSVP' === $module_class || $is_ticket_attendee ) {
			$event_ids[] = (int) get_post_meta( $post_id, $event_id_key, true );

			return $event_ids;
		}

		$ticket_cpt   = $this->ticket_class[ $module_class ]['ticket'];
		$order_id_key = $this->ticket_class[ $module_class ]['order_id_key'];

		if ( ! $order_id_key ) {
			return array();
		}

		$order_tickets = get_posts( array(
			'post_type'      => $ticket_cpt,
			'meta_key'       => $order_id_key,
			'meta_value'     => $post_id,
			'posts_per_page' => - 1,
		) );

		foreach ( $order_tickets as $ticket ) {

			$event_id = get_post_meta( $ticket->ID, $event_id_key, true );

			if ( ! in_array( $event_id, $event_ids ) ) {
				$event_ids[] = (int) $event_id;
			}
		}

		return $event_ids;
	}


	/**
	 * Return Ticket Provider by Order, Product, Attendee, or Ticket ID
	 *
	 * @param $post_id
	 *
	 * @return bool/object
	 */
	public function get_ticket_provider( $post_id ) {
		$services = $this->detect_by_id( $post_id );

		// if no module class return
		if ( empty( $services['class'] ) || ! class_exists( $services['class'] ) ) {
			return false;
		}

		return call_user_func( array( $services['class'], 'get_instance' ) );
	}

	/**
	 * Get attendee(s) by id
	 *
	 * @param      $post_id
	 * @param null $context
	 *
	 * @return mixed
	 */
	public function get_attendees_by_id( $post_id, $context = null ) {
		return $this->get_attendees( $post_id, $context );
	}

	/**
	 * Return if attendee(s) have meta fields with data
	 *
	 * @param      $post_id
	 * @param null $context
	 *
	 * @return bool
	 */
	public function attendees_has_meta_data( $post_id, $context = null ) {
		$attendees = $this->get_attendees( $post_id, $context );
		if ( ! is_array( $attendees ) ) {
			return false;
		}

		return $this->attendees_meta_check( false, $attendees );
	}

	/**
	 * Return if tickets have meta fields
	 *
	 * @param      $post_id
	 * @param null $context
	 *
	 * @return bool
	 */
	public function ticket_has_meta_fields( $post_id, $context = null ) {
		$services = $this->detect_by_id( $post_id );
		if ( ! is_array( $services ) ) {
			$services = array();
		}

		$has_meta_fields = false;
		$products        = '';

		// if no class then look for tickets by event/post id
		if ( ! isset( $services['class'] ) ) {
			$products = $this->get_product_ids_from_tickets( Tribe__Tickets__Tickets::get_all_event_tickets( $post_id ) );
		}

		// if no product ids and id is not ticket related return false
		$is_ticket_related = array_intersect( array( 'order', 'ticket', 'attendee', 'product' ), $services );
		if ( ! $products && ! $is_ticket_related ) {
			return false;
		}

		// if the id is a product add the id to the array
		$is_product = array_intersect( array( 'product' ), $services );
		if ( ! $products && $is_product ) {
			$products[] = absint( $post_id );
		}

		//elseif handle order id ticket&attendee
		$is_order_ticket_attendee = array_intersect( array( 'order', 'ticket', 'attendee' ), $services );
		if ( ! $products && $is_order_ticket_attendee ) {
			$products = $this->get_product_ids_from_attendees( $this->get_attendees( $post_id, $context, $services ) );
		}

		if ( is_array( $products ) ) {
			$has_meta_fields = $this->check_for_meta_fields_by_product_id( $products );
		}

		return $has_meta_fields;
	}

	/**
	 * Return an array of product ids from an array of ticket objects
	 *
	 * @param $tickets array an array of ticket objects
	 *
	 * @return array
	 */
	protected function get_product_ids_from_tickets( $tickets ) {
		$product_ids = array();
		foreach ( $tickets as $ticket ) {
			if ( isset( $ticket->ID ) && ! in_array( $ticket->ID, $product_ids ) ) {
				$product_ids[] = $ticket->ID;
			}
		}

		return $product_ids;
	}

	/**
	 * Return an array of product ids from an array of attendee(s)
	 *
	 * @param $attendees array an array of attendee(s)
	 *
	 * @return array
	 */
	protected function get_product_ids_from_attendees( $attendees ) {
		$product_ids = array();
		foreach ( $attendees as $attendee ) {
			if ( isset( $attendee['product_id'] ) && ! in_array( $attendee['product_id'], $product_ids ) ) {
				$product_ids[] = $attendee['product_id'];
			}
		}

		return $product_ids;
	}

	/**
	 * Return true if meta enabled and fields are
	 *
	 * @param $products array an array of product ids
	 *
	 * @return bool
	 */
	protected function check_for_meta_fields_by_product_id( $products ) {
		$has_meta_fields = false;
		foreach ( $products as $product_id ) {
			$meta_enabled = get_post_meta( $product_id, '_tribe_tickets_meta_enabled', true );
			$meta_fields  = get_post_meta( $product_id, '_tribe_tickets_meta', true );
			if ( $meta_enabled && $meta_fields ) {
				$has_meta_fields = true;
			}
		}

		return $has_meta_fields;
	}

	/**
	 * Get attendee(s) from any id
	 *
	 * @param $post_id
	 * @param $context
	 *
	 * @return mixed
	 */
	protected function get_attendees( $post_id, $context, $services = false ) {
		if ( ! $services ) {
			$services = $this->detect_by_id( $post_id );
		}

		/**
		 * if a post id is passed with rsvp order context
		 * get the order key to return all attendees by the key
		 */
		if ( 'rsvp_order' === $context && is_numeric( $post_id ) ) {
			$post_id = $this->get_rsvp_order_key( $post_id );
		}

		// if no provider class, use the passed id to return attendee(s)
		if ( ! isset( $services['class'] ) && is_numeric( $post_id ) ) {
			return Tribe__Tickets__Tickets::get_event_attendees( $post_id );
		} elseif ( ! isset( $services['class'] ) && ! is_numeric( $post_id ) ) {
			return array();
		}

		$provider = call_user_func( array( $services['class'], 'get_instance' ) );

		return $provider->get_attendees_by_id( $post_id, $services['post_type'] );
	}

	/**
	 * Check if attendee(s) have meta data
	 *
	 * @param $has_meta
	 * @param $attendees
	 * @param $has_meta_fields
	 *
	 * @return bool
	 */
	protected function attendees_meta_check( $has_meta, $attendees ) {
		foreach ( $attendees as $attendee ) {
			if ( isset( $attendee['attendee_meta'] ) && ! empty( $attendee['attendee_meta'] ) ) {
				$has_meta = true;
			}
		}

		return $has_meta;
	}

	/**
	 * Check if a order key passed exists and return attendee object name
	 *
	 * @param $order_key
	 *
	 * @return string
	 */
	protected function check_rsvp_order_key_exists( $order_key ) {

		$attendees_query = $this->query_by_rsvp_order_key( $order_key );
		if ( ! $attendees_query->have_posts() ) {
			return '';
		}

		return Tribe__Tickets__RSVP::ATTENDEE_OBJECT;
	}

	/**
	 * Get the rsvp order key from a post id
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	protected function get_rsvp_order_key( $post_id ) {
		$order_key = get_post_meta( $post_id, Tribe__Tickets__RSVP::get_instance()->order_key, true );
		if ( ! $order_key ) {
			return $post_id;
		}

		return $order_key;
	}

	/**
	 * Get a post id when passing a rsvp order key
	 * Since all rsvp orders will be from one post,
	 * we only need to return the first match
	 *
	 * @param $order_key
	 *
	 * @return false|int|string
	 */
	protected function get_rsvp_post_id_from_order_key( $order_key ) {
		$attendees_query = $this->query_by_rsvp_order_key( $order_key, 1 );
		if ( ! $attendees_query->have_posts() ) {
			return '';
		}

		$post_id = '';

		while ( $attendees_query->have_posts() ) {
			$attendees_query->the_post();
			$post_id = get_the_ID();
		}

		wp_reset_postdata();

		return $post_id;
	}


	/**
	 * Query RSVP Orders by the Order Key
	 *
	 * @param        $order_key
	 * @param string $post_per_page
	 *
	 * @return WP_Query
	 */
	protected function query_by_rsvp_order_key( $order_key, $post_per_page = '-1' ) {
		$attendees_query = new WP_Query( array(
			'posts_per_page' => $post_per_page,
			'post_type'      => Tribe__Tickets__RSVP::ATTENDEE_OBJECT,
			'meta_key'       => Tribe__Tickets__RSVP::get_instance()->order_key,
			'meta_value'     => esc_attr( $order_key ),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		return $attendees_query;
	}

}

