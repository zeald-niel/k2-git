<?php
namespace Bookly\Backend\Modules\Staff\Forms;

use Bookly\Lib;

/**
 * Class StaffServices
 * @package Bookly\Backend\Modules\Staff\Forms
 */
class StaffServices extends Lib\Base\Form
{
    protected static $entity_class = 'StaffService';

    /**
     * @var Lib\Entities\Category[]
     */
    private $categories = array();

    /**
     * @var array
     */
    private $services_data = array();

    /**
     * @var array
     */
    private $uncategorized_services = array();

    public function configure()
    {
        $this->setFields( array( 'price', 'deposit', 'service', 'staff_id', 'capacity' ) );
    }

    public function load( $staff_id )
    {
        $data = Lib\Entities\Category::query( 'c' )
            ->select( 'c.name AS category_name, s.*' )
            ->innerJoin( 'Service', 's', 's.category_id = c.id' )
            ->sortBy( 'c.position, s.position' )
            ->where( 's.type', Lib\Entities\Service::TYPE_SIMPLE )
            ->fetchArray();
        if ( !$data ) {
            $data = array();
        }

        $this->uncategorized_services = Lib\Entities\Service::query( 's' )->where( 's.category_id', null )->where( 's.type', Lib\Entities\Service::TYPE_SIMPLE )->fetchArray();

        $staff_services = Lib\Entities\StaffService::query( 'ss' )
            ->select( 'ss.service_id, ss.price, ss.deposit, ss.capacity' )
            ->where( 'ss.staff_id', $staff_id )
            ->fetchArray();
        if ( $staff_services ) {
            foreach ( $staff_services as $staff_service ) {
                $this->services_data[ $staff_service['service_id'] ] = array( 'price' => $staff_service['price'], 'deposit' => $staff_service['deposit'], 'capacity' => $staff_service['capacity'] );
            }
        }

        foreach ( $data as $row ) {
            if ( ! isset( $this->categories[ $row['category_id'] ] ) ) {
                $category = new Lib\Entities\Category( array( 'id' => $row['category_id'], 'name' => $row['category_name'] ) );
                $this->categories[ $row['category_id'] ] = $category;
            }
            unset( $row['category_name'] );

            $service = new Lib\Entities\Service( $row );
            $this->categories[ $row['category_id'] ]->addService( $service );
        }

    }

    public function save()
    {
        $staff_id = $this->data['staff_id'];
        if ( $staff_id ) {
            Lib\Entities\StaffService::query()->delete()->where( 'staff_id', $staff_id )->execute();
            if ( isset( $this->data['service'] ) ) {
                foreach ( $this->data['service'] as $service_id ) {
                    $staff_service = new Lib\Entities\StaffService();
                    $staff_service->set( 'capacity', $this->data['capacity'][ $service_id ] )
                        ->set( 'deposit',    empty( $this->data['deposit'] ) ? '100%' : $this->data['deposit'][ $service_id ] )
                        ->set( 'price',      $this->data['price'][ $service_id ] )
                        ->set( 'service_id', $service_id )
                        ->set( 'staff_id',   $staff_id )
                        ->save();
                }
            }
        }
    }

    /**
     * @return Lib\Entities\Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return array
     */
    public function getServicesData()
    {
        return $this->services_data;
    }

    /**
     * @return array
     */
    public function getUncategorizedServices()
    {
        return $this->uncategorized_services;
    }

}