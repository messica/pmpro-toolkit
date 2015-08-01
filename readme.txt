=== Paid Memberships Pro - Developer's Toolkit Add On ===
Contributors: strangerstudios, jessica o
Tags: paid memberships pro, pmpro, debug, developer, toolkit
Requires at least: 3.8
Tested up to: 4.2.3
Stable tag: .4

Adds various tools and settings to aid in the development of Paid Memberships Pro enabled websites.

== Description ==

Features:

* Redirect all PMPro emails to a specific email address.
* Define payment gateway debug constants easily in one place.
* Enable a Checkout Debug Email every time the Checkout Page is hit.
* Disable PMPro's scheduled cron jobs.
* Enable a "View as" feature allowing admins to view any page as a specific membership level or levels.

== Installation ==

1. Upload the `pmpro-toolkit` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-toolkit/issues

== Changelog ==
= .4 =
* Added script to change members from one level to another.
* Added script to cancel all members of one level.
* Added script to copy page restrictions from one level to another.

= .3 =
* Added scripts to delete data from the database. (Use at your own risk!)

= .2.1 =
* Fixed issue where pmprodev_view_as_has_membership_level() was making members seem like they don't have a membership level.

= .2 =
* Added "Scheduled Cron Job Debugging" section to disable cron jobs.
* Now only have one Gateway Callback Email setting "ipn_debug" that is used for all gateways.
* Moved sections around a bit.

= .1.2 =
* Removed some warnings/notices.
* Added settings page.
* "View as" feature now filtering pmpro_hasMembershipLevel() function as well.

= .1.1 =
* Added "View as" access filter. Lets admins view any page as a specific membership level. Add "?pmprodev_view_as=3-2-1" to the query string.

= .1 =
* This is the initial version of the plugin.