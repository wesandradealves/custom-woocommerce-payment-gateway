=== BDM Digital Payment Gateway ===
Tags: payments, pix, qr code, woocommerce, bdm  
Requires at least: 6.0  
Tested up to: 6.8  
Requires PHP: 7.4  
Stable tag: 1.2.3  
Contributors: bdmmercantil, bdmdigital  
Version: 1.2.3
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Accept BDM Digital payments in WooCommerce with QR Code, automatic validation, and easy integration.

== Description ==

This plugin allows you to accept payments with the BDM digital currency in your WooCommerce store. It generates QR codes for payments, performs automatic validations, and provides instant confirmations to the customer.

Main features:
* Automatic QR Code generation
* Real-time payment validation
* Compatible with BDM digital wallets
* Direct integration with WooCommerce
* Custom checkout page

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory or install directly through the WordPress dashboard.
2. Activate the plugin via the "Plugins" menu in WordPress.
3. Make sure WooCommerce is installed and activated.
4. The plugin will automatically create a checkout page (`BDM Checkout`).
5. Configure your integration details under **Settings > BDM Gateway**.

== External services ==

This plugin communicates with the payment gateway API to process and check the status of transactions. All payment requests are sent to:

https://partner.dourado.cash

The following data is transmitted: order amount, order ID, and callback URLs. No personal customer information is sent unless required by the payment process.

== Source JS/CSS ==

jquery.toast.min.js: https://github.com/kamranahmedse/jquery-toast-plugin  
bootstrap.min.css: https://getbootstrap.com/  

== Frequently Asked Questions ==

= Is WooCommerce required? =
Yes. This plugin depends on WooCommerce to function properly.

= Where can I find my BDM integration details? =
You can obtain the integration details from the BDM wallet service or the Dourado Cash platform.

== Screenshots ==

1. Payment screen with QR Code
2. Custom checkout page
3. Settings in the admin panel

= 1.2.3 =
First official release of the plugin.

== License ==

This plugin is free software and is licensed under GPLv2 or later.
