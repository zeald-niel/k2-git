<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class CompoundServices
 * Invoke local methods from Compound Services add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static void cancelAppointment( \Bookly\Lib\Entities\CustomerAppointment $customer_appointment ) Cancel compound appointment
 * @see \BooklyCompoundServices\Lib\ProxyProviders\Local::cancelAppointment()
 *
 * @method static void renderSubServices( array $service, array $service_collection, $sub_services ) Render sub services for compound
 * @see \BooklyCompoundServices\Lib\ProxyProviders\Local::renderSubServices()
 */
class CompoundServices extends Base\ProxyInvoker
{

}