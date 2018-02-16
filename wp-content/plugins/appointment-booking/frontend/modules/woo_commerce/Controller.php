<?php
namespace Bookly\Frontend\Modules\WooCommerce;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\WooCommerce
 */
class Controller extends Lib\Base\Controller
{
    const VERSION = '1.0';

    private $product_id = 0;
    private $checkout_info = array();

    protected function getPermissions()
    {
        return array( '_this' => 'anonymous', );
    }

    public function __construct()
    {
        if ( get_option( 'bookly_wc_enabled' ) ) {
            $this->product_id = get_option( 'bookly_wc_product', 0 );

            add_action( 'woocommerce_add_order_item_meta',      array( $this, 'addOrderItemMeta' ), 10, 3 );
            add_action( 'woocommerce_after_order_itemmeta',     array( $this, 'orderItemMeta' ), 10, 1 );
            add_action( 'woocommerce_before_calculate_totals',  array( $this, 'beforeCalculateTotals' ), 10, 1 );
            add_action( 'woocommerce_before_cart_contents',     array( $this, 'checkAvailableTimeForCart' ), 10, 0 );
            add_action( 'woocommerce_order_item_meta_end',      array( $this, 'orderItemMeta' ), 10, 1 );
            add_action( 'woocommerce_order_status_cancelled',   array( $this, 'cancelOrder' ), 10, 1 );
            add_action( 'woocommerce_order_status_completed',   array( $this, 'paymentComplete' ), 10, 1 );
            add_action( 'woocommerce_order_status_on-hold',     array( $this, 'paymentComplete' ), 10, 1 );
            add_action( 'woocommerce_order_status_processing',  array( $this, 'paymentComplete' ), 10, 1 );
            add_action( 'woocommerce_order_status_refunded',    array( $this, 'cancelOrder' ), 10, 1 );

            add_filter( 'woocommerce_checkout_get_value',       array( $this, 'checkoutValue' ), 10, 2 );
            add_filter( 'woocommerce_get_item_data',            array( $this, 'getItemData' ), 10, 2 );
            add_filter( 'woocommerce_quantity_input_args',      array( $this, 'quantityArgs' ), 10, 2 );

            parent::__construct();
        }
    }

    /**
     * Verifies the availability of all appointments that are in the cart
     */
    public function checkAvailableTimeForCart()
    {
        $recalculate_totals = false;
        foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
            if ( array_key_exists( 'bookly', $wc_item ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) ) {
                    if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                if ( $wc_item['quantity'] > 1 ) {
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        // Equal appointments increase quantity
                        $cart_item->set( 'number_of_persons', $cart_item->get( 'number_of_persons' ) * $wc_item['quantity'] );
                    }
                }
                // Check if appointment's time is still available
                $failed_cart_key = $userData->cart->getFailedKey();
                if ( $failed_cart_key !== null ) {
                    $cart_item = $userData->cart->get( $failed_cart_key );
                    $slot = $cart_item->get( 'slots' );
                    $notice = strtr( __( 'Sorry, the time slot %date_time% for %service% has been already occupied.', 'bookly' ),
                        array(
                            '%service%'   => '<strong>' . $cart_item->getService()->getTitle() . '</strong>',
                            '%date_time%' => Lib\Utils\DateTime::formatDateTime( $slot[0][2] )
                    ) );
                    wc_print_notice( $notice, 'notice' );
                    WC()->cart->set_quantity( $wc_key, 0, false );
                    $recalculate_totals = true;
                }
            }
        }
        if ( $recalculate_totals ) {
            WC()->cart->calculate_totals();
        }
    }

    /**
     * Assign checkout value from appointment.
     *
     * @param $null
     * @param $field_name
     * @return string|null
     */
    public function checkoutValue( $null, $field_name )
    {
        if ( empty( $this->checkout_info ) ) {
            foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
                if ( array_key_exists( 'bookly', $wc_item ) ) {
                    if ( ! isset( $wc_item['bookly']['version'] ) || $wc_item['bookly']['version'] < self::VERSION ) {
                        if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                            // Removed item from cart.
                            continue;
                        }
                    }
                    $full_name = $wc_item['bookly']['name'];
                    $this->checkout_info = array(
                        'billing_first_name' => strtok( $full_name, ' ' ),
                        'billing_last_name'  => strtok( '' ),
                        'billing_email'      => $wc_item['bookly']['email'],
                        'billing_phone'      => $wc_item['bookly']['phone']
                    );
                    break;
                }
            }
        }
        if ( array_key_exists( $field_name, $this->checkout_info ) ) {
            return $this->checkout_info[ $field_name ];
        }

        return null;
    }

    /**
     * Do bookings after checkout.
     *
     * @param $order_id
     */
    public function paymentComplete( $order_id )
    {
        $order = new \WC_Order( $order_id );
        foreach ( $order->get_items() as $item_id => $order_item ) {
            $data = wc_get_order_item_meta( $item_id, 'bookly' );
            if ( $data && ! isset ( $data['processed'] ) ) {
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $data );
                $userData->cart->setItemsData( $data['items'] );
                if ( $order_item['qty'] > 1 ) {
                    foreach ( $userData->cart->getItems() as $cart_item ) {
                        $cart_item->set( 'number_of_persons', $cart_item->get( 'number_of_persons' ) * $order_item['qty'] );
                    }
                }
                list( $total, $deposit ) = $userData->cart->getInfo();
                $payment = new Lib\Entities\Payment();
                $payment->set( 'type', Lib\Entities\Payment::WOO_COMMERCE )
                    ->set( 'status',   Lib\Entities\Payment::STATUS_COMPLETED )
                    ->set( 'total',    $total )
                    ->set( 'paid',     $deposit )
                    ->set( 'created',  current_time( 'mysql' ) )
                    ->save();
                $ca_list = $userData->save( $payment->get( 'id' ) );
                $payment->setDetails( $ca_list )->save();
                // Mark item as processed.
                $data['processed'] = true;
                $data['ca_ids']    = array_keys( $ca_list );
                wc_update_order_item_meta( $item_id, 'bookly', $data );
                Lib\NotificationSender::sendFromCart( $ca_list );
            }
        }
    }

    /**
     * Cancel appointments on WC order cancelled.
     *
     * @param $order_id
     */
    public function cancelOrder( $order_id )
    {
        $order = new \WC_Order( $order_id );
        foreach ( $order->get_items() as $item_id => $order_item ) {
            $data = wc_get_order_item_meta( $item_id, 'bookly' );
            if ( isset ( $data['processed'], $data['ca_ids'] ) && $data['processed'] ) {
                /** @var Lib\Entities\CustomerAppointment[] $ca_list */
                $ca_list = Lib\Entities\CustomerAppointment::query()->whereIn( 'id', $data['ca_ids'] )->find();
                foreach ( $ca_list as $ca ) {
                    $ca->cancel();
                }
                $data['ca_ids'] = array();
                wc_update_order_item_meta( $item_id, 'bookly', $data );
            }
        }
    }

    /**
     * Change attr for WC quantity input
     *
     * @param array       $args
     * @param \WC_Product $product
     * @return mixed
     */
    public function quantityArgs( $args, $product )
    {
        if ( $product->get_id() == $this->product_id ) {
            $args['max_value'] = $args['input_value'];
            $args['min_value'] = $args['input_value'];
        }

        return $args;
    }

    /**
     * Change item price in cart.
     *
     * @param \WC_Cart $cart_object
     */
    public function beforeCalculateTotals( $cart_object )
    {
        foreach ( $cart_object->cart_contents as $wc_key => $wc_item ) {
            if ( isset ( $wc_item['bookly'] ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) || $wc_item['bookly']['version'] < self::VERSION ) {
                    if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                $userData = new Lib\UserBookingData( null );
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                list( $total, $deposit ) = $userData->cart->getInfo();
                /** @var \WC_Product $wc_item['data'] */
                $wc_item['data']->set_price( $deposit );
            }
        }
    }

    public function addOrderItemMeta( $item_id, $values, $wc_key )
    {
        if ( isset ( $values['bookly'] ) ) {
            wc_update_order_item_meta( $item_id, 'bookly', $values['bookly'] );
        }
    }

    /**
     * Get item data for cart.
     *
     * @param $other_data
     * @param $wc_item
     * @return array
     */
    public function getItemData( $other_data, $wc_item )
    {
        if ( isset ( $wc_item['bookly'] ) ) {
            $userData = new Lib\UserBookingData( null );
            $info = array();
            if ( isset( $wc_item['bookly']['version'] ) && $wc_item['bookly']['version'] == self::VERSION ) {
                $userData->fillData( $wc_item['bookly'] );
                $userData->cart->setItemsData( $wc_item['bookly']['items'] );
                list( , $deposit, $due ) = $userData->cart->getInfo();
                foreach ( $userData->cart->getItems() as $cart_item ) {
                    $slots = $cart_item->get( 'slots' );
                    $appointment_datetime = date( 'Y-m-d H:i:s', $slots[0][2] );
                    if ( Lib\Config::useClientTimeZone() ) {
                        $appointment_datetime = Lib\Utils\DateTime::applyTimeZoneOffset(
                            $appointment_datetime, $userData->get( 'time_zone_offset' )
                        );
                    }
                    $service = $cart_item->getService();
                    $staff = $cart_item->getStaff();
                    $codes = array(
                        '{appointment_date}'  => Lib\Utils\DateTime::formatDate( $appointment_datetime ),
                        '{appointment_time}'  => Lib\Utils\DateTime::formatTime( $appointment_datetime ),
                        '{category_name}'     => $service ? $service->getCategoryName() : '',
                        '{number_of_persons}' => $cart_item->get( 'number_of_persons' ),
                        '{service_info}'      => $service ? $service->getInfo() : '',
                        '{service_name}'      => $service ? $service->getTitle() : __( 'Service was not found', 'bookly' ),
                        '{service_price}'     => $service ? Lib\Utils\Common::formatPrice( $cart_item->getServicePrice() ) : '',
                        '{staff_info}'        => $staff ? $staff->getInfo() : '',
                        '{staff_name}'        => $staff ? $staff->getName() : '',
                        '{amount_to_pay}'     => Lib\Utils\Common::formatPrice( $deposit ),
                        '{amount_due}'        => Lib\Utils\Common::formatPrice( $due ),
                    );
                    $data  = Lib\Proxy\Shared::prepareCartItemInfoText( array(), $cart_item );
                    $codes = Lib\Proxy\Shared::prepareInfoTextCodes( $codes, $data );
                    // Support deprecated codes [[CODE]]
                    foreach ( array_keys( $codes ) as $code_key ) {
                        if ( $code_key{1} == '[' ) {
                            $codes[ '{' . strtolower( substr( $code_key, 2, -2 ) ) . '}' ] = $codes[ $code_key ];
                        } else {
                            $codes[ '[[' . strtoupper( substr( $code_key, 1, -1 ) ) . ']]' ] = $codes[ $code_key ];
                        }
                    }
                    $info[]  = strtr( Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_wc_cart_info_value' ), $codes );
                }
            }
            $other_data[] = array( 'name' => Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_wc_cart_info_name' ), 'value' => implode( PHP_EOL . PHP_EOL, $info ) );
        }

        return $other_data;
    }

    /**
     * Print appointment details inside order items in the backend.
     *
     * @param int $item_id
     */
    public function orderItemMeta( $item_id )
    {
        $data = wc_get_order_item_meta( $item_id, 'bookly' );
        if ( $data ) {
            $other_data = $this->getItemData( array(), array( 'bookly' => $data ) );
            echo '<br/>' . $other_data[0]['name'] . '<br/>' . nl2br( $other_data[0]['value'] );
        }
    }

    /**
     * Add product to cart
     *
     * return string JSON
     */
    public function executeAddToWoocommerceCart()
    {
        if ( ! get_option( 'bookly_wc_enabled' ) ) {
            exit;
        }
        $response = null;
        $userData = new Lib\UserBookingData( $this->getParameter( 'form_id' ) );

        if ( $userData->load() ) {
            $session = WC()->session;
            /** @var \WC_Session_Handler $session */
            if ( $session instanceof \WC_Session_Handler && $session->get_session_cookie() === false ) {
                $session->set_customer_session_cookie( true );
            }
            if ( $userData->cart->getFailedKey() === null ) {
                $cart_item = $this->getIntersectedItem( $userData->cart->getItems() );
                if ( $cart_item === null ) {
                    $bookly = array(
                        'version' => self::VERSION,
                        'email'   => $userData->get( 'email' ),
                        'items'   => $userData->cart->getItemsData(),
                        'name'    => $userData->get( 'name' ),
                        'phone'   => $userData->get( 'phone' ),
                        'time_zone_offset' => $userData->get( 'time_zone_offset' ),
                    );

                    // Qnt 1 product in $userData exist value with number_of_persons
                    WC()->cart->add_to_cart( $this->product_id, 1, '', array(), array( 'bookly' => $bookly ) );

                    $response = array( 'success' => true );
                } else {
                    $response = array( 'success' => false, 'error' => __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' ) );
                }
            } else {
                $response = array( 'success' => false, 'error' => __( 'The selected time is not available anymore. Please, choose another time slot.', 'bookly' ) );
            }
        } else {
            $response = array( 'success' => false, 'error' => __( 'Session error.', 'bookly' ) );
        }
        wp_send_json( $response );
    }

    /**
     * Find conflicted CartItem with items in WC Cart.
     *
     * Resolved:
     *  number of persons > staff.capacity
     *  Services for some Staff intersected
     *
     * @param Lib\CartItem[] $new_items
     * @return Lib\CartItem
     */
    private function getIntersectedItem( array $new_items )
    {
        /** @var Lib\CartItem[] $wc_items */
        $wc_items  = array();
        $cart_item = new Lib\CartItem();
        foreach ( WC()->cart->get_cart() as $wc_key => $wc_item ) {
            if ( array_key_exists( 'bookly', $wc_item ) ) {
                if ( ! isset( $wc_item['bookly']['version'] ) ) {
                    if ( $this->_migration( $wc_key, $wc_item ) === false ) {
                        // Removed item from cart.
                        continue;
                    }
                }
                foreach ( $wc_item['bookly']['items'] as $item_data ) {
                    $entity = clone $cart_item;
                    $entity->setData( $item_data );
                    if ( $wc_item['quantity'] > 1 ) {
                        $nop = $item_data['number_of_persons'] *= $wc_item['quantity'];
                        $entity->set( 'number_of_persons', $nop );
                    }
                    $wc_items[] = $entity;
                }
            }
        }
        $staff_service = array();
        foreach ( $new_items as $cart_key => $candidate_cart_item ) {
            foreach ( $wc_items as $wc_cart_item ) {
                $candidate_staff_id   = $candidate_cart_item->getStaff()->get( 'id' );
                $candidate_service_id = $candidate_cart_item->getService()->get( 'id' );
                $candidate_slots      = $candidate_cart_item->get( 'slots' );
                $wc_cart_slots        = $wc_cart_item->get( 'slots' );
                if ( $wc_cart_item->getStaff() && $candidate_cart_item->getService() ) {
                    if ( $candidate_staff_id == $wc_cart_item->getStaff()->get( 'id' ) ) {
                        // Equal Staff
                        $candidate_start = $candidate_slots[0][2];
                        $candidate_end   = $candidate_start + $candidate_cart_item->getService()->get( 'duration' ) + $candidate_cart_item->getExtrasDuration();
                        $wc_cart_start   = $wc_cart_slots[0][2];
                        $wc_cart_end     = $wc_cart_start + $wc_cart_item->getService()->get( 'duration' ) + $wc_cart_item->getExtrasDuration();
                        if ( ( $wc_cart_end > $candidate_start ) && ( $candidate_end > $wc_cart_start ) ) {
                            // Services intersected.
                            if ( $candidate_start == $wc_cart_start ) {
                                // Equal Staff/Service/Start
                                if ( ! isset( $staff_service[ $candidate_staff_id ][ $candidate_service_id ] ) ) {
                                    $staff_service[ $candidate_staff_id ][ $candidate_service_id ] = $this->getCapacity( $candidate_staff_id, $candidate_service_id );
                                }
                                $allow_capacity = $staff_service[ $candidate_staff_id ][ $candidate_service_id ];
                                $nop = $candidate_cart_item->get( 'number_of_persons' ) + $wc_cart_item->get( 'number_of_persons' );
                                if ( $nop > $allow_capacity ) {
                                    // Equal Staff/Service/Start and number_of_persons > capacity
                                    return $candidate_cart_item;
                                }
                            } else {
                                // Intersect Services for some Staff
                                return $candidate_cart_item;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param int $staff_id
     * @param int $service_id
     * @return int
     */
    private function getCapacity( $staff_id, $service_id )
    {
        $staff_service = Lib\Entities\StaffService::query()
            ->select( 'capacity' )
            ->where( 'staff_id',   $staff_id )
            ->where( 'service_id', $service_id )
            ->fetchRow();

        return $staff_service['capacity'];
    }

    /**
     * Migration deprecated cart items.
     *
     * @param $wc_key
     * @param $data
     * @return bool
     */
    private function _migration( $wc_key, $data )
    {
        // The current implementation only remove cart items with deprecated format.
        WC()->cart->set_quantity( $wc_key, 0, false );
        WC()->cart->calculate_totals();

        return false;
    }

    /**
     * Override parent method to register 'wp_ajax_nopriv_' actions too.
     *
     * @param bool $with_nopriv
     */
    protected function registerWpAjaxActions( $with_nopriv = false )
    {
        parent::registerWpAjaxActions( true );
    }
}