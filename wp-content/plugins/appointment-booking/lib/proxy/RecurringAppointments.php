<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class RecurringAppointments
 * Invoke local methods from Recurring Appointments add-on.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static string getStepHtml( \Bookly\Lib\UserBookingData $userData, bool $show_cart_btn, string $info_text, string $progress_tracker ) Render Repeat step
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::getStepHtml()
 *
 * @method static bool couldBeRepeated( bool $default, \Bookly\Lib\UserBookingData $userData ) Check current appointment ca be repeatable, (Appointment from repeat appointment can't be repeated).
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::couldBeRepeated()
 *
 * @method static bool hideChildAppointments( bool $default, \Bookly\Lib\CartItem $cart_item ) When need pay only first appointment in series, we hide next appointments.
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::hideChildAppointments()
 *
 * @method static void cancelPayment( int $payment_id ) Cancel payment for whole series
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::cancelPayment()
 *
 * @method static array buildSchedule( \Bookly\Lib\UserBookingData $userData, string $start_time, string $end_time, string $repeat, array $params, int[] $slots ) Build schedule with passed slots
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::buildSchedule()
 *
 * @method static void renderRecurringSubForm() Render recurring sub form in appointment dialog
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::renderRecurringSubForm()
 *
 * @method static void renderSchedule() Render recurring schedule in appointment dialog
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::renderSchedule()
 *
 * @method static void renderAppearance( string $progress_tracker ) Render recurring sub form in appearance.
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::renderAppearance()
 *
 * @method static void renderAppearanceEditableInfoMessage() Render editable message.
 * @see \BooklyRecurringAppointments\Lib\ProxyProviders\Local::renderAppearanceEditableInfoMessage()
 */
class RecurringAppointments extends Base\ProxyInvoker
{

}