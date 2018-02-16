<?php
namespace Bookly\Lib\Proxy;

use Bookly\Lib\Base;

/**
 * Class Shared
 * Invoke shared methods.
 *
 * @package Bookly\Lib\Proxy
 *
 * @method static \Bookly\Lib\NotificationCodes prepareNotificationCodes( \Bookly\Lib\NotificationCodes $codes, \Bookly\Lib\Entities\CustomerAppointment $ca ) Prepare codes for replacing in notifications
 * @method static \Bookly\Lib\NotificationCodes prepareTestNotificationCodes( \Bookly\Lib\NotificationCodes $codes ) Prepare codes for testing email templates
 * @method static array  adjustMinAndMaxTimes( array $times ) Prepare time_from & time_to for UserBookingData.
 * @method static array  handleRequestAction( string $bookly_action ) Handle requests with given action.
 * @method static array  prepareAppearanceCodes( array $codes ) Alter array of codes to be displayed in Bookly Appearance.
 * @method static array  prepareAppearanceOptions( array $options_to_save, array $options ) Alter array of options to be saved in Bookly Appearance.
 * @method static array  prepareCartItemInfoText( array $data, \Bookly\Lib\CartItem $cart_item ) Prepare array for replacing in Cart items
 * @method static array  prepareCartNotificationShortCodes( array $codes ) Alter array of codes to be displayed in Cart settings.
 * @method static array  prepareCaSeSt( array $result ) Prepare Categories Services Staff data
 * @method static array  prepareChainItemInfoText( array $data, \Bookly\Lib\ChainItem $chain_item ) Prepare array for replacing in Chain items
 * @method static array  prepareInfoTextCodes( array $info_text_codes, array $data ) Prepare array for replacing on booking steps
 * @method static array  prepareNotificationShortCodes( array $codes ) Alter array of codes to be displayed in Bookly Notifications.
 * @method static array  prepareNotificationTitles( array $type_list ) Prepare notification titles.
 * @method static array  prepareNotificationTypes( array $notification_types ) Prepare notification types.
 * @method static array  preparePaymentOptions( array $options ) Alter payment option names before saving in Bookly Settings.
 * @method static array  preparePaymentOptionsData( array $data ) Alter and apply payment options data before saving in Bookly Settings.
 * @method static array  prepareReplaceCodes( array $codes, \Bookly\Lib\NotificationCodes $notification_codes, $format ) Replacing on booking steps
 * @method static array  prepareServiceSchedule( $schedule, $service_id ) Prepare service schedule (working time & breaks ...).
 * @method static array  prepareWooCommerceShortCodes( array $codes ) Alter array of codes to be displayed in WooCommerce (Order,Cart,Checkout etc.).
 * @method static array  saveSettings( array $alert, string $tab, $_post ) Save add-on settings
 * @method static array  serviceCreated( \Bookly\Lib\Entities\Service $service, array $_post ) Service created
 * @method static array  updateService( array $alert, \Bookly\Lib\Entities\Service $service, array $_post ) Update service settings in add-ons
 * @method static string prepareCalendarAppointmentDescription( string $description, \Bookly\Lib\Entities\CustomerAppointment | array $appointment_data ) Prepare description for calendar event (appointment)
 * @method static string prepareInfoMessage( string $default, \Bookly\Lib\UserBookingData $userData, int $step ) Prepare info message.
 * @method static string prepareStaffServiceInputClass( string $class_name ) Change css class name for inputs.
 * @method static string prepareStaffServiceLabelClass( string $class_name ) Change css class name for labels.
 * @method static void   enqueueAssetsForServices() Enqueue assets for page Services
 * @method static void   enqueueAssetsForStaffProfile() Enqueue assets for page Staff
 * @method static void   renderAfterServiceList( array $service_collection ) Render content after services forms
 * @method static void   renderAppearanceStepServiceSettings() Render checkbox settings
 * @method static void   renderAppointmentDialogCustomerList() Render content in AppointmentForm for customers
 * @method static void   renderAppointmentDialogFooter() Render buttons in appointments dialog footer.
 * @method static void   renderBooklyMenuAfterAppointments() Render menu in WP admin menu
 * @method static void   renderChainItemHead() Render head for chain in step service
 * @method static void   renderChainItemTail() Render tail for chain in step service
 * @method static void   renderComponentAppointments() Render content in appointments
 * @method static void   renderComponentCalendar() Render content in calendar page
 * @method static void   renderCustomerDetailsDialog() Render controls in Customer details dialog (Edit booking details)
 * @method static void   renderEmailNotifications( \Bookly\Backend\Modules\Notifications\Forms\Notifications $form ) Render email notification(s)
 * @method static void   renderPopUpShortCodeBooklyForm() Render controls in popup for bookly-form (build shortcode)
 * @method static void   renderPopUpShortCodeBooklyFormHead() Render controls in header popup for bookly-form (build shortcode)
 * @method static void   renderServiceForm( array $service ) Render content in service form
 * @method static void   renderServiceFormHead( array $service ) Render top content in service form
 * @method static void   renderSettingsForm() Render add-on settings form
 * @method static void   renderSettingsMenu() Render tab in settings page
 * @method static void   renderSmsNotifications( \Bookly\Backend\Modules\Notifications\Forms\Notifications $form ) Render SMS notification(s)
 * @method static void   renderStaffForm( \Bookly\Lib\Entities\Staff $staff ) Render Staff form tab details
 * @method static void   renderStaffService( int $staff_id, int $service_id, array $services_data ) Render controls for Staff on tab services.
 * @method static void   renderStaffServiceTail( int $staff_id, int $service_id, $attributes = array() ) Render controls for Staff on tab services.
 * @method static void   renderStaffServices( int $staff_id ) Render Components for staff profile
 * @method static void   renderStaffTab( \Bookly\Lib\Entities\Staff $staff )
 * @method static void   updateStaff( array $_post ) Update staff settings in add-ons
 * @method static void   renderMediaButtons( string $version ) Add buttons to WordPress editor.
 * @method static void   renderTinyMceComponent() Render PopUp windows for WordPress editor.
 */
abstract class Shared extends Base\ProxyInvoker
{

}
