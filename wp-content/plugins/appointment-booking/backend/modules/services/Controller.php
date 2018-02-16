<?php
namespace Bookly\Backend\Modules\Services;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Services
 */
class Controller extends Lib\Base\Controller
{
    const page_slug = 'bookly-services';

    /**
     * Index page.
     */
    public function index()
    {
        wp_enqueue_media();
        $this->enqueueStyles( array(
            'wp'       => array( 'wp-color-picker' ),
            'frontend' => array( 'css/ladda.min.css' ),
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css' ),
        ) );

        $this->enqueueScripts( array(
            'wp'       => array( 'wp-color-picker' ),
            'backend'  => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/help.js'  => array( 'jquery' ),
                'js/alert.js' => array( 'jquery' ),
                'js/range_tools.js' => array( 'jquery' ),
            ),
            'module'   => array( 'js/service.js' => array( 'jquery-ui-sortable', 'jquery' ) ),
            'frontend' => array(
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'bookly-spin.min.js', 'jquery' ),
            )
        ) );

        wp_localize_script( 'bookly-service.js', 'BooklyL10n', array(
            'are_you_sure' => __( 'Are you sure?', 'bookly' ),
            'selector'     => array( 'nothing_selected' => __( 'No staff selected', 'bookly' ), ),
            'service_special_day' => Lib\Config::specialDaysEnabled() && Lib\Config::specialDaysEnabled()
        ) );

        // Allow add-ons to enqueue their assets.
        Lib\Proxy\Shared::enqueueAssetsForServices();

        $staff_collection    = $this->getStaffCollection();
        $category_collection = $this->getCategoryCollection();
        $service_collection  = $this->getServiceCollection();
        $this->render( 'index', compact( 'staff_collection', 'category_collection', 'service_collection' ) );
    }

    /**
     *
     */
    public function executeGetCategoryServices()
    {
        wp_send_json_success( $this->render( '_list', $this->getCaSeStCollections(), false ) );
    }

    /**
     *
     */
    public function executeAddCategory()
    {
        $html = '';
        if ( ! empty ( $_POST ) ) {
            $form = new Forms\Category();
            $form->bind( $this->getPostParameters() );
            if ( $category = $form->save() ) {
                $html = $this->render( '_category_item', array( 'category' => $category->getFields() ), false );
            }
        }
        wp_send_json_success( compact( 'html' ) );
    }

    /**
     * Update category.
     */
    public function executeUpdateCategory()
    {
        $form = new Forms\Category();
        $form->bind( $this->getPostParameters() );
        $form->save();
    }

    /**
     * Update category position.
     */
    public function executeUpdateCategoryPosition()
    {
        $category_sorts = $this->getParameter( 'position' );
        foreach ( $category_sorts as $position => $category_id ) {
            $category_sort = new Lib\Entities\Category();
            $category_sort->load( $category_id );
            $category_sort->set( 'position', $position );
            $category_sort->save();
        }
    }

    /**
     * Update services position.
     */
    public function executeUpdateServicesPosition()
    {
        $services_sorts = $this->getParameter( 'position' );
        foreach ( $services_sorts as $position => $service_ids ) {
            $services_sort = new Lib\Entities\Service();
            $services_sort->load( $service_ids );
            $services_sort->set( 'position', $position );
            $services_sort->save();
        }
    }

    /**
     * Delete category.
     */
    public function executeDeleteCategory()
    {
        $category = new Lib\Entities\Category();
        $category->set( 'id', $this->getParameter( 'id', 0 ) );
        $category->delete();
    }

    public function executeAddService()
    {
        $form = new Forms\Service();
        $form->bind( $this->getPostParameters() );
        $form->getObject()->set( 'duration', Lib\Config::getTimeSlotLength() );
        $service = $form->save();
        $data = $this->getCaSeStCollections( $service->get( 'category_id' ) );
        Lib\Proxy\Shared::serviceCreated( $service, $this->getPostParameters() );
        wp_send_json_success( array( 'html' => $this->render( '_list', $data, false ), 'service_id' => $service->get( 'id' ) ) );
    }

    public function executeRemoveServices()
    {
        $service_ids = $this->getParameter( 'service_ids', array() );
        if ( is_array( $service_ids ) && ! empty ( $service_ids ) ) {
            Lib\Entities\Service::query( 's' )->delete()->whereIn( 's.id', $service_ids )->execute();
        }
        wp_send_json_success();
    }

    /**
     * Update service parameters and assign staff
     */
    public function executeUpdateService()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $form = new Forms\Service();
        $form->bind( $this->getPostParameters() );
        $service = $form->save();

        $staff_ids = $this->getParameter( 'staff_ids', array() );
        if ( empty( $staff_ids ) ) {
            Lib\Entities\StaffService::query()->delete()->where( 'service_id', $service->get( 'id' ) )->execute();
        } else {
            Lib\Entities\StaffService::query()->delete()->where( 'service_id', $service->get( 'id' ) )->whereNotIn( 'staff_id', $staff_ids )->execute();
            if ( $this->getParameter( 'update_staff', false ) ) {
                $wpdb->update( Lib\Entities\StaffService::getTableName(), array( 'price' => $this->getParameter( 'price' ) ), array( 'service_id' => $this->getParameter( 'id' ) ) );
                $wpdb->update( Lib\Entities\StaffService::getTableName(), array( 'capacity' => $this->getParameter( 'capacity' ) ), array( 'service_id' => $this->getParameter( 'id' ) ) );
            }
            $service_staff_exists = Lib\Entities\StaffService::query()->select( 'staff_id' )->where( 'service_id', $service->get( 'id' ) )->fetchArray();
            $service_staff = array();
            foreach ( $service_staff_exists as $staff ) {
                $service_staff[] = $staff['staff_id'];
            }
            foreach ( $staff_ids as $staff_id ) {
                if ( ! in_array( $staff_id, $service_staff ) ) {
                    $staff_service = new Lib\Entities\StaffService();
                    $staff_service->set( 'staff_id',   $staff_id );
                    $staff_service->set( 'service_id', $service->get( 'id' ) );
                    $staff_service->set( 'price',      $service->get( 'price' ) );
                    $staff_service->set( 'capacity',   $service->get( 'capacity' ) );
                    $staff_service->save();
                }
            }
        }

        $alert = Lib\Proxy\Shared::updateService( array( 'success' => array( __( 'Settings saved.', 'bookly' ) ) ), $service, $this->getPostParameters() );

        $service_extras = (array) Lib\Proxy\ServiceExtras::findByServiceId( $service->get( 'id' ) );
        /** @var \BooklyServiceExtras\Lib\Entities\ServiceExtra[] $db_extras */
        $db_extras = array();
        foreach ( $service_extras as $extra ) {
            $db_extras[ $extra->get( 'id' ) ] = $extra;
        }
        $new_extras = array();
        // Find id for new extras.
        foreach ( $this->getParameter( 'extras', array() ) as $_post_id => $_post_extra ) {
            if ( isset( $db_extras[ $_post_id ] ) ) {
                unset( $db_extras[ $_post_id ] );
            } else {
                foreach ( $db_extras as $id => $extra ) {
                    if ( $extra->get( 'title' ) == $_post_extra['title']
                         && $extra->get( 'price' ) == $_post_extra['price']
                         && $extra->get( 'duration' ) == $_post_extra['duration']
                    ) {
                        $new_extras[ $_post_id ] = $id;
                        unset( $db_extras[ $id ] );
                        break;
                    }
                }
            }
        }

        $price = Lib\Utils\Common::formatPrice( $service->get( 'price' ) );
        if ( $service->get( 'type' ) == Lib\Entities\Service::TYPE_SIMPLE ) {
            $nice_duration = Lib\Utils\DateTime::secondsToInterval( $service->get( 'duration' ) );
        } else {
            $nice_duration = sprintf( _n( '%d service', '%d services', count( json_decode( $service->get( 'sub_services' ), true ) ), 'bookly' ), count( json_decode( $service->get( 'sub_services' ), true ) ) );
        }

        wp_send_json_success( array( 'title' => $service->get( 'title' ), 'price' => $price, 'color' => $service->get( 'color' ), 'nice_duration' => $nice_duration, 'new_extras' => $new_extras, 'alert' => $alert ) );
    }

    /**
     * Array for rendering service list.
     *
     * @param int $category_id
     * @return array
     */
    private function getCaSeStCollections( $category_id = 0 )
    {
        if ( ! $category_id ) {
            $category_id = $this->getParameter( 'category_id', 0 );
        }

        return array(
            'service_collection'  => $this->getServiceCollection( $category_id ),
            'staff_collection'    => $this->getStaffCollection(),
            'category_collection' => $this->getCategoryCollection(),
        );
    }

    /**
     * @return array
     */
    private function getCategoryCollection()
    {
        return Lib\Entities\Category::query()->sortBy( 'position' )->fetchArray();
    }

    /**
     * @return array
     */
    private function getStaffCollection()
    {
        return Lib\Entities\Staff::query()->fetchArray();
    }

    /**
     * @param int $id
     * @return array
     */
    private function getServiceCollection( $id = 0 )
    {
        $services = Lib\Entities\Service::query( 's' )
            ->select( 's.*, COUNT(staff.id) AS total_staff, GROUP_CONCAT(DISTINCT staff.id) AS staff_ids' )
            ->leftJoin( 'StaffService', 'ss', 'ss.service_id = s.id' )
            ->leftJoin( 'Staff', 'staff', 'staff.id = ss.staff_id' )
            ->whereRaw( 's.category_id = %d OR !%d', array( $id, $id ) )
            ->groupBy( 's.id' )
            ->indexBy( 'id' )
            ->sortBy( 's.position' );

        return $services->fetchArray();
    }

    public function executeUpdateExtraPosition()
    {
        Lib\Proxy\ServiceExtras::reorder( $this->getParameter( 'position' ) );
        wp_send_json_success();
    }
}