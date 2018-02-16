<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class ServiceExtras
 * Invoke local methods from Service Extras add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static string getStepHtml( \Bookly\Lib\UserBookingData $userData, bool $show_cart_btn, string $info_text, string $progress_tracker ) Render step Repeat
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::getStepHtml()
 *
 * @method static void renderAppearance( string $progress_tracker ) Render extras in appearance.
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::renderAppearance()
 *
 * @method static \BooklyServiceExtras\Lib\Entities\ServiceExtra[] findByIds( int $extras_ids ) Return extras entities.
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::findByIds()
 *
 * @method static \BooklyServiceExtras\Lib\Entities\ServiceExtra[] findByServiceId( int $service_id ) Return extras entities.
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::findByServiceId()
 *
 * @method static \BooklyServiceExtras\Lib\Entities\ServiceExtra[] findAll() Return all extras entities.
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::findAll()
 *
 * @method static array getInfo( string $extras_json, bool $translate )
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::getInfo()
 *
 * @method static int getTotalDuration( array $extras )
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::getTotalDuration()
 *
 * @method static int reorder( array $order )
 * @see \BooklyServiceExtras\Lib\ProxyProviders\Local::reorder()
 *
 */
class ServiceExtras extends Base\ProxyInvoker
{

}