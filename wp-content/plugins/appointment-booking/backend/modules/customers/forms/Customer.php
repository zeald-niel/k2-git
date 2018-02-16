<?php
namespace Bookly\Backend\Modules\Customers\Forms;

use Bookly\Lib;

/**
 * Class Customer
 * @package Bookly\Backend\Modules\Customers\Forms
 */
class Customer extends Lib\Base\Form
{
    protected static $entity_class = 'Customer';

    public function configure()
    {
        $this->setFields( array(
            'wp_user_id',
            'name',
            'phone',
            'email',
            'notes',
            'birthday',
        ) );
    }

}
