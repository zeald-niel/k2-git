<?php
namespace Bookly\Lib;

/**
 * Class CartItem
 * @package Bookly\Lib
 */
class CartItem
{
    private $data = array(
        // Step service
        'location_id'       => null,
        'service_id'        => null,
        'staff_ids'         => null,
        'number_of_persons' => null,
        'date_from'         => null,
        'days'              => null,
        'time_from'         => null,
        'time_to'           => null,
        // Step extras
        'extras'            => array(),
        // Step time
        'slots'             => null,
        // Step details
        'custom_fields'     => array(),
        'series_uniq_id'    => 0,
        'first_in_series'   => false,
    );

    /**
     * Constructor.
     */
    public function __construct() { }

    /**
     * Get data parameter.
     *
     * @param string $name
     * @return string
     */
    public function get( $name )
    {
        if ( array_key_exists( $name, $this->data ) ) {
            return $this->data[ $name ];
        }

        return false;
    }

    /**
     * Set data parameter.
     *
     * @param string $name
     * @param string $value
     */
    public function set( $name, $value )
    {
        $this->data[ $name ] = $value;
    }

    /**
     * Get data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data.
     *
     * @param array $data
     */
    public function setData( array $data )
    {
        foreach ( $data as $name => $value ) {
            $this->set( $name, $value );
        }
    }

    /**
     * Get service.
     *
     * @return Entities\Service
     */
    public function getService()
    {
        return Entities\Service::find( $this->data['service_id'] );
    }

    /**
     * Get service price.
     *
     * @return double
     */
    public function getServicePrice()
    {
        static $service_prices_cache = array();

        $service = $this->getService();
        $slots   = $this->get( 'slots' );
        list ( $service_id, $staff_id ) = $slots[0];

        $special_hours_enabled = Config::specialHoursEnabled();

        if ( $special_hours_enabled ) {
            $service_start = date( 'H:i:s', $slots[0][2] );
        } else {
            $service_start = 'unused'; //the price is the same for all services in day
        }

        if ( isset ( $service_prices_cache[ $staff_id ][ $service_id ][ $service_start ] ) ) {
            $service_price = $service_prices_cache[ $staff_id ][ $service_id ][ $service_start ];
        } else {//if record absentee in cache
            if ( $service->get( 'type' ) == Entities\Service::TYPE_COMPOUND ) {
                $service_price = $service->get( 'price' );
            } else {
                $staff_service = new Entities\StaffService();
                $staff_service->loadBy( compact( 'staff_id', 'service_id' ) );
                $service_price = $staff_service->get( 'price' );
            }
            $service_price = Proxy\SpecialHours::preparePrice( $service_price, $staff_id, $service_id, $service_start );
            $service_prices_cache[ $staff_id ][ $service_id ][ $service_start ] = $service_price;
        }

        return $service_price + $this->getExtrasAmount();
    }

    /**
     * Get service deposit.
     *
     * @return string
     */
    public function getDeposit()
    {
        $slots = $this->get( 'slots' );
        list ( $service_id, $staff_id ) = $slots[0];
        $staff_service = new Entities\StaffService();
        $staff_service->loadBy( array(
            'staff_id'   => $staff_id,
            'service_id' => $service_id,
        ) );

        return $staff_service->get( 'deposit' );
    }

    /**
     * Get service deposit price.
     *
     * @return double
     */
    public function getDepositPrice()
    {
        $nop = $this->get( 'number_of_persons' );

        return Proxy\DepositPayments::prepareAmount( $nop * $this->getServicePrice(), $this->getDeposit(), $nop );
    }

    /**
     * Get service deposit price formatted.
     *
     * @return double
     */
    public function getAmountDue()
    {
        $price   = $this->getServicePrice();
        $deposit = $this->getDepositPrice();

        return $price - $deposit;
    }

    /**
     * Get staff.
     *
     * @return Entities\Staff
     */
    public function getStaff()
    {
        $slots    = $this->get( 'slots' );
        $staff_id = $slots[0][1];

        return Entities\Staff::find( $staff_id );
    }

    /**
     * Get summary price of service's extras.
     *
     * @return double
     */
    public function getExtrasAmount()
    {
        $amount  = 0.0;
        $_extras = $this->get( 'extras' );
        $extras  = (array) Proxy\ServiceExtras::findByIds( array_keys( $_extras ) );
        foreach ( $extras as $extra ) {
            $amount += $extra->get( 'price' ) * $_extras[ $extra->get( 'id' ) ];
        }

        return $amount;
    }

    /**
     * Get duration of service's extras.
     *
     * @return int
     */
    public function getExtrasDuration()
    {
        return (int) Proxy\ServiceExtras::getTotalDuration( $this->get( 'extras' ) );
    }

    /**
     * @param int $service_id
     * @return bool
     */
    public function isFirstSubService( $service_id )
    {
        return $this->data['slots'][0][0] == $service_id;
    }

}