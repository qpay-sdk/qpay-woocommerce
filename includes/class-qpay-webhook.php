<?php

class WC_QPay_Webhook
{
    public function register(): void
    {
        add_action('woocommerce_api_qpay_webhook', [$this, 'handle']);
    }

    public function handle(): void
    {
        // QPay sends GET request with ?qpay_payment_id=<id>
        $payment_id = sanitize_text_field($_GET['qpay_payment_id'] ?? '');

        if (empty($payment_id)) {
            status_header(400);
            echo 'Missing qpay_payment_id';
            exit;
        }

        $gateway = new WC_QPay_Gateway();
        $api = new WC_QPay_API($gateway->get_option('username'), $gateway->get_option('password'), $gateway->get_option('base_url'));

        // Get payment details to find the invoice_id (object_id)
        $payment = $api->get_payment($payment_id);
        if (! $payment || empty($payment['object_id'])) {
            status_header(400);
            echo 'Payment not found';
            exit;
        }

        $invoice_id = $payment['object_id'];

        $orders = wc_get_orders([
            'meta_key' => '_qpay_invoice_id',
            'meta_value' => $invoice_id,
            'limit' => 1,
        ]);

        if (empty($orders)) {
            status_header(404);
            echo 'Order not found';
            exit;
        }

        $order = $orders[0];

        // Double-check payment via check_payment endpoint
        $result = $api->check_payment($invoice_id);
        if ($result && ! empty($result['rows'])) {
            $order->payment_complete();
            $order->add_order_note('QPay payment confirmed. Payment ID: ' . $payment_id . ', Invoice: ' . $invoice_id);
        }

        status_header(200);
        echo 'SUCCESS';
        exit;
    }
}
