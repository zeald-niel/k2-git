<?php
namespace Bookly\Lib\Entities;

use Bookly\Lib;

/**
 * Class StatsSteps
 * @package Bookly\Lib\Entities
 */
class StatsSteps extends Lib\Base\Entity
{
    protected static $table = 'ab_stats_steps';

    protected static $cache = array();

    protected static $schema = array(
        'step'  => array( 'format' => '%s' ),
        'count' => array( 'format' => '%d' ),
    );
}