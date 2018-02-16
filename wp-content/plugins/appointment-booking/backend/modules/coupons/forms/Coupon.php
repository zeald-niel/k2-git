<?php
namespace Bookly\Backend\Modules\Coupons\Forms;

use Bookly\Lib;

/**
 * Class Coupon
 * @package Bookly\Backend\Modules\Coupons\Forms
 */
class Coupon extends Lib\Base\Form
{
    protected static $entity_class = 'Coupon';

    public function configure()
    {
        $this->setFields( array( 'id', 'code', 'discount', 'deduction', 'usage_limit' ) );
    }

}