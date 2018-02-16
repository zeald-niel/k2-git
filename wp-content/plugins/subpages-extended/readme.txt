=== Subpages Extended ===
Contributors: mattsay
Donate link: http://metinsaylan.com/donate
Tags: page list, pages, list pages, subpages, widget, shortcode, navigation, menu, auto, index, list, children, wp_list_pages
Requires at least: 2.5
Tested up to: 4.7.2
Stable tag: 1.4

List subpages of your wordpress pages easily! It also comes with a `[subpages]` shortcode.

== Description ==

This widget displays subpages of a page easily. You can automatically display subpages list on empty pages. Though it's main power is the `[subpages]` shortcode. Using this shortcode on a page you can create subpage indexes. You can view live demo on my wordpress page. It automatically generates subpage indexes. You can also list subpages of another page using the childof attribute of shortcode. It supports all wp_list_pages functionality via shortcode. See the examples below:

Here are subpages of my wordpress page with a depth level of 1:
`[subpages depth="1" childof="286"]`

Outputs:

* Plugins
* Themes

If the page doesn’t have any subpages it will display the following error for you to fix it:
`[subpages depth="1" childof="257"]`

Outputs: 

"Services" doesn't have any sub pages.

== Installation ==

1. Download the widget and upload it to your server through `WP Admin -> Plugins -> Add New -> Upload`
1. After the upload is complete activate the plugin.
1. Go to Appearance -> Widgets page, drag and drop the widget to your sidebar.
1. Fill in the blanks as needed, and done!

== Frequently Asked Questions ==

= Any questions? =

You can ask your questions [here](http://metinsaylan.com/contact)

== Screenshots ==

1. A snapshot of the widget form.

== Changelog ==

= 1.4 = 
* Updated plugin support links.
* Removed unused tweetable script and css.

= 1.3.6 = 
* Fixed: "_get_post_ancestors is deprecated" error. Thanks to Troy Templeman.

= 1.3.5 = 
* Tested up to WP 4.0

= 1.3.4 = 
* Added: Rel option for shortcode & widget.
* Removed: Use menu labels options from widget & shortcode. Plugin uses menu label if it exists.

= 1.3.3 = 
* Fixed: Subpages widget is not visible on home page if a certain page is selected.

= 1.3.2 =
* Minor fix. Removed walker option from shortcode attributes. It was giving errors since one can't supply object in a shortcode. Use use_menu_labels option to switch current walker.

= 1.3.1 =
* Fixed `\n\t` output on empty pages with no children.
* Added some styling.

= 1.3 =
* Added use link on title option.
* Added `*Full Branch*` option to Parent which allows listing subpages until topmost page.
* Added `Use Menu Labels` option and added Menu Label meta box to allow using shorter menu labels for pages that have a long name. This box is seen on right side on the edit page screen.

= 1.2.1 =
* Fixed top pages show all pages when `*Parent of current page*` selected in the widget. Thanks to Wouter Bruijning for pointing out this error.

= 1.2 =
* Added sort options to the widget (Great thanks to Arkantos for the idea.)
* Added all wp_list_pages options to the shortcode. Now shortcode is more powerful than before. 
* Be sure to check metinsaylan.com for great demo and examples.

= 1.1 = 
* Added option to use Current page's title as widget title. (Great thanks to Thoschi for the idea.)
* Added shortcode option title="*current*" to display current page's title.
* Added pages dropdown selector to the widget for ease of use.
* Added shortcode option for childof="parent".

= 1.0.2 = 
* Added option for automatically displaying subpages on empty pages.

= 1.0.1 =
* Fixed version.

= 1.0 =
* First release.