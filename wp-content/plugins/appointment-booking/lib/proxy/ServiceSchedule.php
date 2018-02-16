<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class ServiceSchedule
 * Invoke local methods from Service Schedule add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static array getSchedule( int $service_id ) Get schedule for service
 * @see \BooklyServiceSchedule\Lib\ProxyProviders\Local::getSchedule()
 *
 */
class ServiceSchedule extends Base\ProxyInvoker
{

}