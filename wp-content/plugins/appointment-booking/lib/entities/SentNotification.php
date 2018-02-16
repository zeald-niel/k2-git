<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class SentNotification
 * @package Bookly\Lib\Entities
 */
class SentNotification extends Lib\Base\Entity
{
    protected static $table = 'ab_sent_notifications';

    protected static $schema = array(
        'id'       => array( 'format' => '%d' ),
        'ref_id'   => array( 'format' => '%d' ),
        'gateway'  => array( 'format' => '%s', 'default' => 'email' ),
        'type'     => array( 'format' => '%s' ),
        'created'  => array( 'format' => '%s' ),
    );

    protected static $cache = array();

}