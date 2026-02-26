=== QPay Payment Gateway for WooCommerce ===
Contributors: qpaysdk
Tags: payment, woocommerce, qpay, mongolia, payment gateway, qr code
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

QPay V2 payment gateway integration for WooCommerce. Accept payments via QR code and bank app deeplinks in Mongolia.

== Description ==

QPay Payment Gateway for WooCommerce enables Mongolian merchants to accept payments through the QPay V2 payment system directly in their WooCommerce stores.

**Features:**

* QR code display at checkout for easy mobile payments
* Bank app deeplinks for all major Mongolian banks
* Automatic payment status polling (3 second interval)
* Order status auto-update when payment is confirmed
* Webhook callback support for reliable payment confirmation
* Admin configuration panel in WooCommerce settings

**How It Works:**

1. Customer selects QPay at checkout
2. A QR code and bank app buttons are displayed
3. Customer scans QR or taps their bank app
4. Payment is confirmed automatically and order status updates

**Requirements:**

* WordPress 6.0 or higher
* WooCommerce 8.0 or higher
* PHP 8.1 or higher
* QPay merchant account (https://merchant.qpay.mn)

== Installation ==

1. Upload the `qpay-woocommerce` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to WooCommerce > Settings > Payments > QPay.
4. Enter your QPay API credentials:
   * API Base URL (default: https://merchant.qpay.mn)
   * Username
   * Password
   * Invoice Code
5. Enable the payment method and save.

== Frequently Asked Questions ==

= Where do I get QPay API credentials? =

You need a QPay merchant account. Visit https://merchant.qpay.mn to register and obtain your API credentials.

= What currencies are supported? =

QPay supports Mongolian Tugrik (MNT) payments.

= How does the callback URL work? =

The callback URL is automatically generated as `yoursite.com/?wc-api=qpay_webhook`. QPay sends payment confirmations to this URL.

= Is this plugin compatible with WooCommerce block checkout? =

The plugin currently supports the classic WooCommerce checkout. Block checkout support is planned for a future release.

== Screenshots ==

1. QPay payment settings in WooCommerce admin
2. QR code and bank app buttons on checkout page
3. Payment confirmation screen

== Changelog ==

= 1.0.0 =
* Initial release
* QPay V2 API integration
* QR code checkout display
* Bank app deeplinks
* Auto payment polling
* Webhook callback support
* Admin configuration panel

== Upgrade Notice ==

= 1.0.0 =
Initial release of QPay Payment Gateway for WooCommerce.
