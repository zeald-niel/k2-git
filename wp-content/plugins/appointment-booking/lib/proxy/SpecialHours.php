<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class SpecialHours
 * Invoke local methods from Special Hours add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static string preparePrice( string $price, int $staff_id, int $service_id, $start_time )
 * @see \BooklySpecialHours\Lib\ProxyProviders\Local::preparePrice()
 */
class SpecialHours extends Base\ProxyInvoker
{

}