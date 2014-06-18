=== PMPro Developer's Toolkit ===
Contributors: strangerstudios, jessica o
Tags: pmpro, debug, developer, toolkit
Requires at least: 3.8
Tested up to: 3.9.1
Stable tag: .1.2

Adds various tools and settings to aid in the development of Paid Memberships Pro enabled websites.

== Description ==

Features:

* Define payment gateway debug constants easily in one place.
* Redirect all PMPro emails to a specific email address.
* Enable a Checkout Debug Email every time the Checkout Page is hit.
* Enable a "View as" feature allowing admins to view any page as a specific membership level or levels.

== Installation ==

1. Upload the `pmpro-toolkit` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-toolkit/issues

== Changelog ==
= .1.2 =
* Removed some warnings/notices.
* Added settings page.
* "View as" feature now filtering pmpro_hasMembershipLevel() function as well.

= .1.1 =
* Added "View as" access filter. Lets admins view any page as a specific membership level. Add "?pmprodev_view_as=3-2-1" to the query string.

= .1 =
* This is the initial version of the plugin.