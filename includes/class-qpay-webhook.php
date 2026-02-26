<?php

class WC_QPay_Webhook
{
    public function register(): void
    {
        add_action('woocommerce_api_qpay_webhook', [$this, 'handle']);
    }

    public function handle(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        $invoice_id = sanitize_text_field($body['invoice_id'] ?? '');

        if (empty($invoice_id)) {
            wp_send_json_error('Missing invoice_id', 400);
        }

        $orders = wc_get_orders([
            'meta_key' => '_qpay_invoice_id',
            'meta_value' => $invoice_id,
            'limit' => 1,
        ]);

        if (empty($orders)) {
            wp_send_json_error('Order not found', 404);
        }

        $order = $orders[0];
        $gateway = new WC_QPay_Gateway();
        $api = new WC_QPay_API($gateway->get_option('username'), $gateway->get_option('password'), $gateway->get_option('base_url'));

        $result = $api->check_payment($invoice_id);
        if ($result && ! empty($result['rows'])) {
            $order->payment_complete();
            $order->add_order_note('QPay payment confirmed. Invoice: ' . $invoice_id);
            wp_send_json_success(['status' => 'paid']);
        } else {
            wp_send_json_success(['status' => 'unpaid']);
        }
    }
}
