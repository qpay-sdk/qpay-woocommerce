<?php
/**
 * QPay WooCommerce Uninstall
 *
 * Removes all QPay data when the plugin is deleted through WordPress admin.
 *
 * @package QPay_WooCommerce
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove QPay transients
delete_transient('qpay_access_token');
delete_transient('qpay_refresh_token');

// Remove QPay options from WooCommerce settings
delete_option('woocommerce_qpay_settings');
