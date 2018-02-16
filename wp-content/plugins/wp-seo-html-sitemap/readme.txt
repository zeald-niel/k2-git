=== WP SEO HTML Sitemap ===
Contributors: magnatechnology
Tags: WPSEO, Yoast SEO, HTML Sitemap, Sitemap, Google Sitemap, Google Webmaster Tools, Google Search Console, sitemaps, nofollow, wordpress seo, wordpress seo by yoast, yoast, seo
Requires at least: 3.5
Tested up to: 4.4
Stable tag: 0.9.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A responsive HTML sitemap that uses all of the settings for your XML sitemap in the WordPress SEO by Yoast Plugin.

== Description ==

If you use **WordPress SEO by Yoast Plugin** as your main SEO plugin, you may have noticed they don't have a HTML sitemap feature. This plugin is the answer to that problem.

= Features Include =
* Automatically uses all sitemap xml settings from the popular Wordpress SEO by Yoast Plugin
* Choose how many columns you want to display
* Columns have a masonry effect and is compatible with all modern browsers
* Overwrite, prepend, append, and shortcode options for placement on your sitemap page
* Fully responsive HTML to all devices
* Output is multilingual friendly
* HTML code has passed W3C Markup Validation with 0 errors
* Ability to disable the plugin's CSS
* Optional link to your sitemap_index.xml file

[youtube https://www.youtube.com/watch?v=hi5DGOu1uA0]

= Matt Cutts on HTML Sitemaps =
When Matt Cutts (Head of Google's Webspam Team) was asked, what is more important: "A XML sitemap or an HTML sitemap?" [YouTube Webmaster Tools Video](https://www.youtube.com/watch?v=hi5DGOu1uA0) Matt answered a HTML sitemap. HTML sitemaps help both users and search engine crawlers. ["It is always useful to have a HTML sitemap..."](https://www.youtube.com/watch?v=t5LIlkhxl2s).

Want to see the plugin in action? [Live HTML Sitemap Example](https://riseofweb.com/sitemap/).

Note: The [WordPress SEO by Yoast plugin](https://wordpress.org/plugins/wordpress-seo/) is NOT required in order to use this plugin. But this plugin does take full advantage of all settings related to the XML sitemap settings.

= Known oversights: =
* Author Roles filtering, I do not have it setup to be able to filter out author roles.
* The posts are sorted by name and may not show if a specific Category is selected to not show in the sitemap XML settings in Yoast.

== Installation ==

1. Upload folder to '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Is the page responsive? =
Yes. The columns option can be set and change size automatically. For mobile phones the columns are automatically set to 1.

= Can I change the CSS? =
Yes, the CSS is prefixed by the div id "#wpseo_sitemap". If you want to override any of the CSS just use the ">" example "#wpseo_sitemap > div > h3{}

= Will this work with any SEO plugin? =
Yes, but it is optimized for Yoast's WordPress SEO.  This plugin uses all setting from Yoast's plugin related to robots, nofollow, and the sitemap xml.

== Screenshots ==

1. This is a screenshot of the HTML sitemap in action

2. This is a screenshot of the admin options page

== Changelog ==
= 0.9.6 =
* BUGS FIXED: Fixed an HTML/CSS error in the multi-column layout where three columns would be two, two would be one.  Fixed an error when linking to the sitemap_index.xml file which used site_url instead of home_url (Thanks princekj for finding this error). 

= 0.9.5 =
* BUGS FIXED: Error when checking Yoast Plugin for Categories to exclude
* NEW FEATURE: Changed plugin output to now be multilingual friendly

= 0.9.4 =
* BUGS FIXED: Fixed link appearing on top of sitemap html if no blog page is set. (Thanks to muradabuseta and Adam B. for finding the error)
* NEW FEATURE: Changed the heading text for the Posts to be the page name selected to show the Posts.

= 0.9.3 =
* BUGS FIXED: Fixed a broken link on the plugins page (Thanks to dimatrovski for finding the error). Fixed link to Posts going to sitemap page (Thanks to Rob Eitzen for finding the error).
* NEW FEATURE: Added the rel="alternate" to the sitemap XML link.

= 0.9.2 =
* BUGS FIXED: Fixed CSS errors related to responsive. Thanks to anpaostudio for finding the errors.

= 0.9 =
* Major Update: added admin user interface, added an external CSS file (for proper HTML page validation), added columns number setting, added link to sitemap XML option, fixed some errors relating to category checking.


= 0.5 =
* Initial Release