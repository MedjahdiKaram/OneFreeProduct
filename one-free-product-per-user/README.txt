=== One Free product per user ===
Contributors: medjahdikaram
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Tags: woocommerce, free product, restriction
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WooCommerce extension that allows only one free product per user or IP address. The plugin prevents additional zero priced items in the cart and ensures the quantity of a free product is limited to 1. If a user or IP address has already obtained a free product, an error message is displayed during checkout.

== Description ==
This plugin restricts customers from purchasing more than one product with a price of 0. It checks orders by user account and IP address. If a free product is already ordered, any attempt to get another will be blocked.

== Installation ==
1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. No additional settings are required.

== Frequently Asked Questions ==
= Can I reset the free product history? =
Currently, you must manually remove the `onefp_free_purchased` user meta or clear the `onefp_free_ips` option via the database.

== Changelog ==
= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.0.0 =
First stable version.
