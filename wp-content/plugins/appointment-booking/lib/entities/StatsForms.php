<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class StatsForms
 * @package Bookly\Lib\Entities
 */
class StatsForms extends Lib\Base\Entity
{
    protected static $table = 'ab_stats_forms';

    protected static $cache = array();

    protected static $schema = array(
        'url' => array( 'format' => '%s' ),
    );
}