<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class SpecialDays
 * Invoke local methods from Special Days add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static array getDataForAvailableTime( array $staff_ids, \DateTime $start_date_time = null, \DateTime $end_date_time = null )
 * @see \BooklySpecialDays\Lib\ProxyProviders\Local::getDataForAvailableTime()
 *
 * @method static array adjustConfigDaysAndTimes( array $data )
 * @see \BooklySpecialDays\Lib\ProxyProviders\Local::adjustConfigDaysAndTimes()
 *
 */
class SpecialDays extends Base\ProxyInvoker
{

}