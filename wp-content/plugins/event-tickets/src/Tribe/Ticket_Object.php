<?php
if ( ! class_exists( 'Tribe__Tickets__Ticket_Object' ) ) {
	/**
	 *    Generic object to hold information about a single ticket
	 */
	class Tribe__Tickets__Ticket_Object {
		/**
		 * This value - an empty string - should be used to populate the stock
		 * property in situations where no limit has been placed on stock
		 * levels.
		 */
		const UNLIMITED_STOCK = '';

		/**
		 * Unique identifier
		 * @var
		 */
		public $ID;
		/**
		 * Name of the ticket
		 * @var string
		 */
		public $name;

		/**
		 * Free text with a description of the ticket
		 * @var string
		 */
		public $description;

		/**
		 * Whether to show the description on the front end and in emails
		 *
		 * @since 4.6
		 *
		 * @var boolean
		 */
		public $show_description = true;

		/**
		 * Current sale price, without any sign. Just a float.
		 *
		 * @var float
		 */
		public $price;

		/**
		 * Ticket Capacity
		 *
		 * @since  4.6
		 *
		 * @var    int
		 */
		public $capacity;

		/**
		 * Regular price (if the ticket is not on a special sale this will be identical to
		 * $price).
		 *
		 * @var float
		 */
		public $regular_price;

		/**
		 * Indicates if the ticket is currently being offered at a reduced price as part
		 * of a special sale.
		 *
		 * @var bool
		 */
		public $on_sale;

		/**
		 * Link to the admin edit screen for this ticket in the provider system,
		 * or null if the provider doesn't have any way to edit the ticket.
		 * @var string
		 */
		public $admin_link;

		/**
		 * Link to the report screen for this ticket in the provider system,
		 * or null if the provider doesn't have any sales reports.
		 * @var string
		 */
		public $report_link;

		/**
		 * Link to the front end of this ticket, if the providers has single view
		 * for this ticket.
		 * @var string
		 */
		public $frontend_link;

		/**
		 * Class name of the provider handling this ticket
		 * @var
		 */
		public $provider_class;

		/**
		 * Holds the SKU for the ticket
		 *
		 * @var string
		 */
		public $sku;

		/**
		 * Holds the menu order for the ticket
		 *
		 * @since 4.6
		 *
		 * @var string
		 */
		public $menu_order;

		/**
		 * @var Tribe__Tickets__Tickets
		 */
		protected $provider;

		/**
		 * Amount of tickets of this kind in stock
		 * Use $this->stock( value ) to set manage and get the value
		 *
		 * @var mixed
		 */
		protected $stock;

		/**
		 * The mode of stock handling to be used for the ticket when global stock
		 * is enabled for the event.
		 *
		 * @var string
		 */
		protected $global_stock_mode = Tribe__Tickets__Global_Stock::OWN_STOCK_MODE;

		/**
		 * The maximum permitted number of sales for this ticket when global stock
		 * is enabled for the event and CAPPED_STOCK_MODE is in effect.
		 *
		 * @var int
		 */
		protected $global_stock_cap = 0;

		/**
		 * Amount of tickets of this kind sold
		 * Use $this->qty_sold( value ) to set manage and get the value
		 *
		 * @var int
		 */
		protected $qty_sold = 0;

		/**
		 * Number of tickets for which an order has been placed but not confirmed or "completed".
		 * Use $this->qty_pending( value ) to set manage and get the value
		 *
		 * @var int
		 */
		protected $qty_pending = 0;

		/**
		 * Number of tickets for which an order has been cancelled.
		 * Use $this->qty_cancelled( value ) to set manage and get the value
		 *
		 * @var int
		 */
		protected $qty_cancelled = 0;

		/**
		 * Holds whether or not stock is being managed
		 *
		 * @var boolean
		 */
		protected $manage_stock = false;

		/**
		 * Date the ticket should be put on sale
		 *
		 * @var string
		 */
		public $start_date;

		/**
		 * Time the ticket should be put on sale
		 *
		 * @since 4.6
		 *
		 * @var string
		 */
		public $start_time;

		/**
		 * Date the ticket should be stop being sold
		 * @var string
		 */
		public $end_date;

		/**
		 * Time the ticket should be stop being sold
		 *
		 * @since 4.6
		 *
		 * @var string
		 */
		public $end_time;

		/**
		 * Purchase limite for the ticket
		 *
		 * @var
		 */
		public $purchase_limit;

		/**
		 * Get the ticket's start date
		 *
		 * @since 4.2
		 *
		 * @return string
		 */
		public function start_date() {
			$start_date = null;
			if ( ! empty( $this->start_date ) ) {
				$start_date = $this->start_date;

				if ( ! empty( $this->start_time ) ) {
					$start_date .= ' ' . $this->start_time;
				}

				$start_date = strtotime( $start_date );
			}

			return $start_date;
		}

		/**
		 * Get the ticket's end date
		 *
		 * @since 4.2
		 *
		 * @return string
		 */
		public function end_date() {
			$end_date = null;

			if ( ! empty( $this->end_date ) ) {
				$end_date = $this->end_date;

				if ( ! empty( $this->end_time ) ) {
					$end_date .= ' ' . $this->end_time;
				}

				$end_date = strtotime( $end_date );
			}

			return $end_date;
		}

		/**
		 * Determines if the given date is within the ticket's start/end date range
		 *
		 * @param string $datetime The date/time that we want to determine if it falls within the start/end date range
		 *
		 * @return boolean Whether or not the provided date/time falls within the start/end date range
		 */
		public function date_in_range( $datetime ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} else {
				$timestamp = strtotime( $datetime );
			}

			$start_date = $this->start_date();
			$end_date   = $this->end_date();

			return ( empty( $start_date ) || $timestamp > $start_date ) && ( empty( $end_date ) || $timestamp < $end_date );
		}

		/**
		 * Determines if the given date is smaller than the ticket's start date
		 *
		 * @param string $datetime The date/time that we want to determine if it is smaller than the ticket's start date
		 *
		 * @return boolean Whether or not the provided date/time is smaller than the ticket's start date
		 */
		public function date_is_earlier( $datetime ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} else {
				$timestamp = strtotime( $datetime );
			}

			$start_date = $this->start_date();

			return empty( $start_date ) || $timestamp < $start_date;
		}

		/**
		 * Determines if the given date is greater than the ticket's end date
		 *
		 * @param string $datetime The date/time that we want to determine if it is smaller than the ticket's start date
		 *
		 * @return boolean Whether or not the provided date/time is greater than the ticket's end date
		 */
		public function date_is_later( $datetime ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} else {
				$timestamp = strtotime( $datetime );
			}

			$end_date = $this->end_date();

			return empty( $end_date ) || $timestamp > $end_date;
		}

		/**
		 * Returns ticket availability slug
		 *
		 * The availability slug is used for CSS class names and filter helper strings
		 *
		 * @since 4.2
		 *
		 * @return string
		 */
		public function availability_slug( $datetime = null ) {
			if ( is_numeric( $datetime ) ) {
				$timestamp = $datetime;
			} elseif ( $datetime ) {
				$timestamp = strtotime( $datetime );
			} else {
				$timestamp = current_time( 'timestamp' );
			}

			$slug = 'available';

			if ( $this->date_is_earlier( $timestamp ) ) {
				$slug = 'availability-future';
			} elseif ( $this->date_is_later( $timestamp ) ) {
				$slug = 'availability-past';
			}

			/**
			 * Filters the availability slug
			 *
			 * @param string Slug
			 * @param string Datetime string
			 */
			$slug = apply_filters( 'event_tickets_availability_slug', $slug, $datetime );

			return $slug;
		}

		/**
		 * Provides the quantity of original stock of tickets
		 *
		 * @deprecated 4.6
		 *
		 * @return int
		 */
		public function original_stock() {
			return $this->capacity();
		}

		/**
		 * Determines if there is any stock for purchasing
		 *
		 * @return boolean
		 */
		public function is_in_stock() {
			// if we aren't tracking stock, then always assume it is in stock
			if ( ! $this->managing_stock() ) {
				return true;
			}

			$remaining = $this->remaining();

			return false === $remaining || $remaining > 0;
		}

		/**
		 * Returns whether or not the ticket is managing stock
		 *
		 * @param boolean $manages_stock Boolean to set stock management state
		 * @return boolean
		 */
		public function manage_stock( $manages_stock = null ) {
			if ( null !== $manages_stock ) {
				$this->manage_stock = tribe_is_truthy( $manages_stock );
			}

			return $this->manage_stock;
		}

		/**
		 * Returns whether or not the ticket is managing stock. Alias method with a friendlier name for fetching state.
		 *
		 * @param boolean $manages_stock Boolean to set stock management state
		 * @return boolean
		 */
		public function managing_stock( $manages_stock = null ) {
			return $this->manage_stock( $manages_stock );
		}

		/**
		 * Provides the Inventory of the Ticket which should match the Commerce Stock
		 *
		 * @since  4.6
		 *
		 * @return int
		 */
		public function inventory() {
			// Fetch provider
			$provider = $this->get_provider();
			$capacity = $this->capacity();

			// If we dont have the provider we fetch from inventory
			if ( is_null( $provider ) || ! method_exists( $provider, 'get_attendees_by_id' ) ) {
				return $capacity - $this->qty_sold() - $this->qty_pending();
			}

			// if we aren't tracking stock, then always assume it is in stock or capacity is unlimited
			if ( ! $this->managing_stock() || -1 === $capacity ) {
				return -1;
			}

			// Fetch the Attendees
			$attendees = $this->provider->get_attendees_by_id( $this->ID );
			$attendees_count = 0;

			// Loop on All the attendees, allowing for some filtering of which will be removed or not
			foreach ( $attendees as $attendee ) {
				// Prevent RSVP with Not Going Status to decrease Inventory
				if ( 'rsvp' === $attendee['provider_slug'] && 'no' === $attendee['order_status'] ) {
					continue;
				}

				$attendees_count++;
			}

			// Do the math!
			$inventory[] = $capacity - $attendees_count;

			// Calculate and verify the Event Inventory
			if (
				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $this->global_stock_mode()
				|| Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $this->global_stock_mode()
			) {
				$event_attendees = $this->provider->get_attendees_by_id( $this->get_event()->ID );
				$event_attendees_count = 0;

				foreach ( $event_attendees as $attendee ) {
					$attendee_ticket_stock = new Tribe__Tickets__Global_Stock( $attendee['product_id'] );
					$attendee_ticket_stock_mode = get_post_meta( $this->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true );

					// On all cases of indy stock we don't add
					if (
						! $attendee_ticket_stock->is_enabled()
						|| empty( $attendee_ticket_stock_mode )
						|| Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $attendee_ticket_stock_mode
					) {
						continue;
					}

					// All the others we add to the count
					$event_attendees_count++;
				}

				$inventory[] = tribe_tickets_get_capacity( $this->get_event()->ID ) - $event_attendees_count;
			}

			$inventory = min( $inventory );

			// Prevents Negative
			return max( $inventory, 0 );
		}

		/**
		 * Provides the quantity of remaining tickets
		 *
		 * @deprecated   4.6  We are now using inventory as the new Remaining
		 *
		 * @return int
		 */
		public function remaining() {
			return $this->inventory();
		}

		/**
		 * Provides the quantity of Avaiable tickets based on the Attendees number
		 *
		 * @todo   Create a way to get the Available for an Event (currenty impossible)
		 *
		 * @since  4.6
		 *
		 * @return int
		 */
		public function available() {
			// if we aren't tracking stock, then always assume it is in stock or capacity is unlimited
			if ( ! $this->managing_stock() || -1 === $this->capacity() ) {
				return -1;
			}

			$stock_mode = $this->global_stock_mode();

			$values[] = $this->inventory();
			$values[] = $this->capacity();
			$values[] = $this->stock();

			// What ever is the lowest we use it
			$available = min( $values );

			// Prevents Negative
			return max( $available, 0 );
		}

		/**
		 * Gets the Capacity for the Ticket
		 *
		 * @since   4.6
		 *
		 * @return  int
		 */
		public function capacity() {
			if ( is_null( $this->capacity ) ) {
				$this->capacity = tribe_tickets_get_capacity( $this->ID );
			}

			$stock_mode = $this->global_stock_mode();

			// Unlimited is always unlimited
			if ( -1 === (int) $this->capacity ) {
				return (int) $this->capacity;
			}

			// If Capped or we used the local Capacity
			if (
				Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $stock_mode
				|| Tribe__Tickets__Global_Stock::OWN_STOCK_MODE === $stock_mode
			) {
				return (int) $this->capacity;
			}

			$event_capacity = tribe_tickets_get_capacity( $this->get_event() );

			return (int) $event_capacity;
		}

		/**
		 * Method to manage the protected `stock` property of the Object
		 * Prevents setting `stock` lower then zero.
		 *
		 * Returns the current ticket stock level: either an integer or an
		 * empty string (Tribe__Tickets__Ticket_Object::UNLIMITED_STOCK)
		 * if stock is unlimited.
		 *
		 * @param int|null $value This will overwrite the old value
		 *
		 * @return int|string
		 */
		public function stock( $value = null ) {
			if ( null === $value ) {
				$value = null === $this->stock
					? (int) get_post_meta( $this->ID, '_stock', true )
					: $this->stock;
			}

			// if we aren't tracking stock, then always assume it is in stock or capacity is unlimited
			if ( ! $this->managing_stock() || -1 === $this->capacity() ) {
				return -1;
			}

			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) || $value === self::UNLIMITED_STOCK ) {
				$this->stock = $value;
			}

			// if stock is negative, force it to 0
			$this->stock = 0 >= $this->stock ? 0 : $this->stock;

			$stock[] = $this->stock;

			if (
				Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $this->global_stock_mode()
				|| Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $this->global_stock_mode()
			) {
				$stock[] = (int) get_post_meta( $this->get_event()->ID, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL, true );
			}

			// return the new Stock
			return min( $stock );
		}

		/**
		 * Sets or gets the current global stock mode in effect for the ticket.
		 *
		 * Typically this is one of the constants provided by Tribe__Tickets__Global_Stock:
		 *
		 *     GLOBAL_STOCK_MODE if it should draw on the global stock
		 *     CAPPED_STOCK_MODE as above but with a limit on the total number of allowed sales
		 *     OWN_STOCK_MODE if it should behave as if global stock is not in effect
		 *
		 * @param string $mode
		 *
		 * @return string
		 */
		public function global_stock_mode( $mode = null ) {
			if ( ! is_null( $mode ) ) {
				$this->global_stock_mode = $mode;
			}

			if ( empty( $this->global_stock_mode ) ) {
				$this->global_stock_mode = get_post_meta( $this->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true );
			}

			return $this->global_stock_mode;
		}

		/**
		 * Sets or gets any cap on sales that might be in effect for this ticket when global stock
		 * mode is in effect.
		 *
		 * @param int $cap
		 *
		 * @return int
		 */
		public function global_stock_cap( $cap = null ) {
			if ( is_numeric( $cap ) ) {
				$this->global_stock_cap = (int) $cap;
			}

			return (int) $this->global_stock_cap;
		}

		/**
		 * Method to manage the protected `qty_sold` property of the Object
		 * Prevents setting `qty_sold` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_sold( $value = null ) {
			return $this->qty_getter_setter( $this->qty_sold, $value );
		}

		/**
		 * Method to manage the protected `qty_pending` property of the Object
		 * Prevents setting `qty_pending` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_pending( $value = null ) {
			return $this->qty_getter_setter( $this->qty_pending, $value );
		}

		/**
		 * Method to get/set protected quantity properties, disallowing illegal
		 * things such as setting a negative value.
		 *
		 * Callables are also supported, allowing properties to be lazily fetched
		 * or calculated on demand.
		 *
		 * @param int               &$property
		 * @param int|callable|null $value
		 *
		 * @return int|mixed
		 */
		protected function qty_getter_setter( &$property, $value = null ) {
			// Set to a positive numeric value
			if ( is_numeric( $value ) ) {
				$property = (int) $value;

				// Disallow negative values (and force to zero if one is passed)
				$property = max( (int) $property, 0 );
			}

			// Set to a callback
			if ( is_callable( $value ) ) {
				$property = $value;
			}

			// Return the callback's output if appropriate: but only when the
			// property is being set to avoid upfront costs
			if ( null === $value && is_callable( $property ) ) {
				return call_user_func( $property, $this->ID );
			}

			// Or else return the current property value
			return $property;
		}

		/**
		 * Magic getter to handle fetching protected properties
		 *
		 * @deprecated 4.0
		 * @todo Remove when event-tickets-* plugins are fully de-supported
		 */
		public function __get( $var ) {
			switch ( $var ) {
				case 'stock':
					return $this->stock();
					break;
				case 'qty_pending':
					return $this->qty_pending();
					break;
				case 'qty_sold':
					return $this->qty_sold();
					break;
				case 'qty_cancelled':
					return $this->qty_cancelled();
					break;
			}

			return null;
		}

		/**
		 * Magic setter to handle setting protected properties
		 *
		 * @deprecated 4.0
		 * @todo Remove when event-tickets-* plugins are fully de-supported
		 */
		public function __set( $var, $value ) {
			switch ( $var ) {
				case 'stock':
					return $this->stock( $value );
					break;
				case 'qty_pending':
					return $this->qty_pending( $value );
					break;
				case 'qty_sold':
					return $this->qty_sold( $value );
					break;
				case 'qty_cancelled':
					return $this->qty_cancelled( $value );
					break;
			}

			return null;
		}

		/**
		 * Method to manage the protected `qty_cancelled` property of the Object
		 * Prevents setting `qty_cancelled` lower then zero
		 *
		 * @param int|null $value This will overwrite the old value
		 * @return int
		 */
		public function qty_cancelled(  $value = null ) {
			// If the Value was passed as numeric value overwrite
			if ( is_numeric( $value ) ) {
				$this->qty_cancelled = $value;
			}

			// Prevents qty_cancelled from going negative
			$this->qty_cancelled = max( (int) $this->qty_cancelled, 0 );

			// return the new Qty Cancelled
			return $this->qty_cancelled;
		}

		/**
		 * Returns an instance of the provider class.
		 *
		 * @return Tribe__Tickets__Tickets|null
		 */
		public function get_provider() {
			if ( empty( $this->provider ) ) {
				if ( empty( $this->provider_class ) || ! class_exists( $this->provider_class ) ) {
					return null;
				}

				if ( method_exists( $this->provider_class, 'get_instance' ) ) {
					$this->provider = call_user_func( array( $this->provider_class, 'get_instance' ) );
				} else {
					$this->provider = new $this->provider_class;
				}
			}

			return $this->provider;
		}

		/**
		 * Returns the ID of the event post this ticket belongs to.
		 *
		 * @return WP_Post|null
		 */
		public function get_event() {
			$provider = $this->get_provider();

			if ( null !== $provider ) {
				return $provider->get_event_for_ticket( $this->ID );
			}

			return null;
		}

		/**
		 * Returns whether the ticket description should show on
		 * the front page and in emails. Defaults to true.
		 *
		 * @since 4.6
		 *
		 * @return boolean
		 */
		public function show_description() {
			$key = tribe( 'tickets.handler' )->key_show_description;

			$show = true;
			if ( metadata_exists( 'post', $this->ID, $key ) ) {
				$show = get_post_meta( $this->ID, $key, true );
			}

			/**
			 * Allows filtering of the value so we can for example, disable it for a theme/site
			 *
			 * @since 4.6
			 *
			 * @param boolean whether to show the description or not
			 * @param int ticket ID
			 */
			$show = apply_filters( 'tribe_tickets_show_description', $show, $this->ID );

			// Make sure we have the correct value
			return tribe_is_truthy( $show );
		}
	}
}
