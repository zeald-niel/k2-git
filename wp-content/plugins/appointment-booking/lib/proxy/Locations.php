<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class Locations
 * Invoke local methods from Locations add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static void renderAppearance() Render Locations in Appearance
 * @see \BooklyLocations\Lib\ProxyProviders\Local::renderAppearance()
 *
 * @method static \Booklylocations\Lib\Entities\Location|false findById( int $location_id ) Return Location entity.
 * @see \BooklyLocations\Lib\ProxyProviders\Local::findById()
 */
class Locations extends Base\ProxyInvoker
{

}