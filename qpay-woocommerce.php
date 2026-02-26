<?php
/**
 * Plugin Name: QPay Payment Gateway for WooCommerce
 * Description: QPay V2 payment gateway integration for WooCommerce
 * Version: 1.0.0
 * Author: QPay SDK
 * License: MIT
 * Requires Plugins: woocommerce
 */

if (! defined('ABSPATH')) exit;

define('QPAY_WC_VERSION', '1.0.0');
define('QPAY_WC_PATH', plugin_dir_path(__FILE__));
define('QPAY_WC_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'qpay_wc_init');

function qpay_wc_init()
{
    if (! class_exists('WC_Payment_Gateway')) return;

    require_once QPAY_WC_PATH . 'includes/class-qpay-api.php';
    require_once QPAY_WC_PATH . 'includes/class-qpay-gateway.php';
    require_once QPAY_WC_PATH . 'includes/class-qpay-webhook.php';

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_QPay_Gateway';
        return $gateways;
    });

    $webhook = new WC_QPay_Webhook();
    $webhook->register();
}

// AJAX handler for checking payment status
add_action('wp_ajax_qpay_check_payment', 'qpay_ajax_check_payment');
add_action('wp_ajax_nopriv_qpay_check_payment', 'qpay_ajax_check_payment');

function qpay_ajax_check_payment()
{
    $invoice_id = sanitize_text_field($_POST['invoice_id'] ?? '');
    if (empty($invoice_id)) {
        wp_send_json_error('Missing invoice_id');
    }

    $gateway = new WC_QPay_Gateway();
    $api = new WC_QPay_API($gateway->get_option('username'), $gateway->get_option('password'), $gateway->get_option('base_url'));

    $result = $api->check_payment($invoice_id);
    if ($result && ! empty($result['rows'])) {
        wp_send_json_success(['status' => 'paid']);
    } else {
        wp_send_json_success(['status' => 'unpaid']);
    }
}
