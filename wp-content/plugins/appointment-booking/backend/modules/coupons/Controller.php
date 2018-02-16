<?php
namespace Bookly\Backend\Modules\Coupons;

use Bookly\Lib;

/**
 * Class Controller
 * @package Bookly\Backend\Modules\Coupons
 */
class Controller extends Lib\Base\Controller
{
    const page_slug = 'bookly-coupons';

    /**
     * Default action
     */
    public function index()
    {
        $this->enqueueStyles( array(
            'backend'  => array( 'bootstrap/css/bootstrap-theme.min.css', ),
            'frontend' => array( 'css/ladda.min.css', ),
        ) );

        $this->enqueueScripts( array(
            'backend' => array(
                'bootstrap/js/bootstrap.min.js' => array( 'jquery' ),
                'js/datatables.min.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/spin.min.js'  => array( 'jquery' ),
                'js/ladda.min.js' => array( 'jquery' ),
            ),
            'module' => array( 'js/coupons.js' => array( 'jquery' ) )
        ) );

        wp_localize_script( 'bookly-coupons.js', 'BooklyL10n', array(
            'edit'         => __( 'Edit', 'bookly' ),
            'zeroRecords'  => __( 'No coupons found.', 'bookly' ),
            'processing'   => __( 'Processing...', 'bookly' ),
            'are_you_sure' => __( 'Are you sure?', 'bookly' ),
            'selector' => array(
                'all_selected'      => __( 'All services', 'bookly' ),
                'nothing_selected'  => __( 'No service selected', 'bookly' ),
                'collection' => Lib\Entities\Service::query()->select( 'id, title' )->indexBy( 'id' )->fetchArray(),
            )
        ) );

        $this->render( 'index' );
    }

    /**
     * Get coupons list
     */
    public function executeGetCoupons()
    {
        $coupons = Lib\Entities\Coupon::query( 'c' )->select( 'c.*, GROUP_CONCAT(DISTINCT s.id) AS service_ids' )
            ->leftJoin( 'CouponService', 'cs', 'cs.coupon_id = c.id' )
            ->leftJoin( 'Service', 's', 's.id = cs.service_id' )
            ->groupBy( 'c.id' )
            ->fetchArray();
        foreach( $coupons as &$coupon ) {
            $coupon['service_ids'] = $coupon['service_ids'] ? explode( ',', $coupon['service_ids'] ) : array();
        }

        wp_send_json_success( $coupons );
    }

    /**
     * Create/update coupon
     */
    public function executeSaveCoupon()
    {
        $form = new Forms\Coupon();
        $form->bind( $this->getPostParameters() );
        $data = $form->getData();

        if ( $data['discount'] < 0 || $data['discount'] > 100 ) {
            wp_send_json_error( array ( 'message' => __( 'Discount should be between 0 and 100.', 'bookly' ) ) );
        } elseif ( $data['deduction'] < 0 ) {
            wp_send_json_error( array ( 'message' => __( 'Deduction should be a positive number.', 'bookly' ) ) );
        } else {
            $fields = $form->save()->getFields();
            $service_ids = $this->getParameter( 'service_ids', array() );
            if ( empty( $service_ids ) ) {
                Lib\Entities\CouponService::query()
                    ->delete()
                    ->where( 'coupon_id', $fields['id'] )
                    ->execute();
            } else {
                Lib\Entities\CouponService::query()
                    ->delete()
                    ->where( 'coupon_id', $fields['id'] )
                    ->whereNotIn( 'service_id', $service_ids )
                    ->execute();
                $service_exists = Lib\Entities\CouponService::query()
                    ->select( 'service_id' )
                    ->where( 'coupon_id', $fields['id'] )
                    ->indexBy( 'service_id' )
                    ->fetchArray();
                foreach ( $service_ids as $service_id ) {
                    if ( ! isset( $service_exists[ $service_id ] ) ) {
                        $coupon_service = new Lib\Entities\CouponService();
                        $coupon_service
                            ->set( 'coupon_id', $fields['id'] )
                            ->set( 'service_id', $service_id )
                            ->save();
                    }
                }
            }

            $fields['service_ids'] = $service_ids;
            wp_send_json_success( $fields );
        }
    }

    /**
     * Delete coupons.
     */
    public function executeDeleteCoupons()
    {
        $coupon_ids = array_map( 'intval', $this->getParameter( 'data', array() ) );
        Lib\Entities\Coupon::query()->delete()->whereIn( 'id', $coupon_ids )->execute();
        wp_send_json_success();
    }
}