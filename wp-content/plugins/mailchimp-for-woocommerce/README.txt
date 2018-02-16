=== MailChimp for WooCommerce ===
Contributors: ryanhungate, MailChimp
Tags: ecommerce,email,workflows,mailchimp
Donate link: https://mailchimp.com
Requires at least: 4.3
Tested up to: 4.8
Stable tag: 4.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your store to your MailChimp list to track sales, create targeted emails, send abandoned cart emails, and more.

== Description ==
Join the 16 million customers who use MailChimp, the world's largest marketing automation platform, to develop their e-commerce marketing strategy. With the official MailChimp for WooCommerce integration, your customers and their purchase data are automatically synced with your MailChimp account, making it easy to send targeted campaigns, automatically follow up with customers post-purchase, recommend products, recover abandoned carts, and measure the ROI of your marketing efforts. And it's completely free.

With MailChimp for WooCommerce, you’ll have the power to:

- Sync list and purchase data.
- Set up marketing automations to remind customers about items they left in their cart or viewed on your site, win back lapsed customers, and follow up post-purchase. (Now available for free accounts!)
- Showcase product recommendations.
- Track and segment customers based on purchase history and purchase frequency.
- View detailed data on your marketing performance in your MailChimp Dashboard.
- Grow your audience and sell more stuff with Facebook and Instagram Ad Campaigns in MailChimp.
- Automatically embed a pop-up form that converts your website visitors to subscribers.
- Add discount codes created in WooCommerce to your emails and automations with a Promo Code content block

###A note for current WooCommerce integration users
This plugin supports our most powerful API 3.0 features, and is intended for users who have not yet integrated their WooCommerce stores with MailChimp.

You can run this new integration at the same time as your current WooCommerce integration for MailChimp. However, data from the older integration will display separately in subscriber profiles, and can’t be used with e-commerce features that require API 3.0.

== Installation ==
###Before You Start
Here are some things to know before you begin this process.

- This plugin requires you to have the [WooCommerce plugin](https://woocommerce.com/) already installed and activated in WordPress.
- Your hosting environment must meet [WooCommerce's minimum requirements](https://docs.woocommerce.com/document/server-requirements), including PHP 7.0 or greater.
- We recommend you use this plugin in a staging environment before installing it on production servers. To learn more about staging environments, [check out these related Wordpress plugins](https://wordpress.org/plugins/search.php?q=staging).
- MailChimp for WooCommerce syncs the customer’s first name, last name, email address, and orders.
- WooCommerce customers who haven't signed up for marketing emails will appear in the **Transactional** portion of your list, and cannot be exported.

###Task Roadmap
You’ll need to do a few things to connect your WooCommerce store to MailChimp. 

- Download the plugin.
- Install the plugin on your WordPress Admin site.
- Connect the plugin with your MailChimp API Key.
- Configure your list settings to complete the data sync process.

For more information on settings and configuration, please visit our Knowledge Base: [http://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-woocommerce](http://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-woocommerce)

== Changelog ==

= 2.1.2 =
* Fix store deletion on plugin deactivation
* Correct shipping name is now used on order notifications.
* Admin orders are now handled appropriately.
* Skip incomplete or cancelled orders from being submitted when new.
* fix hidden or inactive products from being recommended.

= 2.1.1 =
* To address performance issues previously reported, we've changed the action hook of "woocommerce_cart_updated" to use a filter "woocommerce_update_cart_action_cart_updated"

= 2.1.0 =
* Added Promo Code support.

= 2.0.2 =
* Added new logs feature to help troubleshoot isolated sync and data feed issues.
* Fixed bug with setting customers as Transactional during checkout if they had already opted in previously.
* Fixed bug where abandoned cart automation still fired after a customer completed an order.

= 2.0.1 =
* Added support for "Connected Site" scripts.
* Made physical address a required field for store setup.
* Fixed order, cart timestamps to begin using UTC.

= 2.0 = 
* Support WooComerce 3.0 
* Support for manually uploaded WooCommerce
* Fix for sync issues 
* Fix for guest orders sync issue
* Remove MailChimp debug logger

= 1.1.1 = 
* Support for site url changes 
* Fix for WP Version 4.4 compatibility issues 

= 1.1.0 =
* Fix for persisting opt-in status
* Pass order URLs to MailChimp
* Pass partial refund status to MailChimp 

= 1.0.9 =
* billing and shipping address support for orders

= 1.0.8 =
* add landing_site, financial status and discount information for orders
* fix to support php 5.3

= 1.0.7 =
* add options to move, hide and change defaults for opt-in checkbox
* add ability to re-sync and display connection details
* support for subscriptions without orders
* additional small fixes and some internal logging removal

= 1.0.6 =
* fixed conflict with the plugin updater where the class could not be loaded correctly.
* fixed error validation for store name.
* fixed cross device abandoned cart url's

= 1.0.4 =
* fix for Abandoned Carts without cookies

= 1.0.3 =
* fixed cart posts on dollar amounts greater than 1000

= 1.0.2 =
* title correction for Product Variants
* added installation checks for WooCommerce and phone contact info
* support for free orders

= 1.0 =
* added is_synicng flag to prevent sends during backfill
* fix for conflicts with Gravity Forms Pro and installation issues
* skip all Amazon orders
* allow users to set opt-in for pre-existing customers during first sync
* add Plugin Updater

= 0.1.22 =
* flag quantity as 1 if the product does not manage inventory

= 0.1.21 =
* php version check to display warnings < 5.5

= 0.1.19 =
* fix campaign tracking on new orders

= 0.1.18 =
* check woocommerce dependency before activating the plugin

= 0.1.17 =
* fix php version syntax errors for array's

= 0.1.16 =
* fix namespace conflicts
* fix free order 0.00 issue
* fix product variant naming issue

= 0.1.15 =
* adding special MailChimp header to requests

= 0.1.14 =
* removing jquery dependencies

= 0.1.13 =
* fixing a number format issue on total_spent

= 0.1.12 =
* skipping orders placed through amazon due to seller agreements

= 0.1.11 =
* removed an extra debug log that was not needed

= 0.1.10 =
* altered debug logging and fixed store settings validation requirements

= 0.1.9 =
* using fallback to stream context during failed patch requests

= 0.1.8 =
* fixing http request header for larger patch requests

= 0.1.7 =
* fixing various bugs with the sync and product issues.

= 0.1.2 =
* fixed admin order update hook.
