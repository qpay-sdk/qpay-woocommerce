<?php

class WC_QPay_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'qpay';
        $this->method_title = 'QPay';
        $this->method_description = __('QPay V2 payment gateway', 'qpay-woocommerce');
        $this->has_fields = false;
        $this->icon = QPAY_WC_URL . 'assets/css/qpay-icon.png';

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', 'QPay');
        $this->description = $this->get_option('description', 'QPay-ээр төлбөр төлөх');
        $this->enabled = $this->get_option('enabled');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'qpay-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable QPay Payment', 'qpay-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', 'qpay-woocommerce'),
                'type' => 'text',
                'default' => 'QPay',
            ],
            'description' => [
                'title' => __('Description', 'qpay-woocommerce'),
                'type' => 'textarea',
                'default' => 'QPay-ээр төлбөр төлөх',
            ],
            'base_url' => [
                'title' => __('API Base URL', 'qpay-woocommerce'),
                'type' => 'text',
                'default' => 'https://merchant.qpay.mn',
            ],
            'username' => [
                'title' => __('Username', 'qpay-woocommerce'),
                'type' => 'text',
            ],
            'password' => [
                'title' => __('Password', 'qpay-woocommerce'),
                'type' => 'password',
            ],
            'invoice_code' => [
                'title' => __('Invoice Code', 'qpay-woocommerce'),
                'type' => 'text',
            ],
            'callback_url' => [
                'title' => __('Callback URL', 'qpay-woocommerce'),
                'type' => 'text',
                'description' => site_url('?wc-api=qpay_webhook'),
                'default' => site_url('?wc-api=qpay_webhook'),
            ],
        ];
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $api = new WC_QPay_API($this->get_option('username'), $this->get_option('password'), $this->get_option('base_url'));

        $invoice = $api->create_invoice([
            'invoice_code' => $this->get_option('invoice_code'),
            'sender_invoice_no' => (string) $order_id,
            'invoice_receiver_code' => $order->get_billing_email(),
            'invoice_description' => sprintf('WooCommerce Order #%s', $order_id),
            'amount' => (float) $order->get_total(),
            'callback_url' => $this->get_option('callback_url'),
        ]);

        if (! $invoice || empty($invoice['invoice_id'])) {
            wc_add_notice(__('QPay payment error. Please try again.', 'qpay-woocommerce'), 'error');
            return ['result' => 'fail'];
        }

        $order->update_meta_data('_qpay_invoice_id', $invoice['invoice_id']);
        $order->update_meta_data('_qpay_qr_image', $invoice['qr_image'] ?? '');
        $order->update_meta_data('_qpay_qr_text', $invoice['qr_text'] ?? '');
        $order->update_meta_data('_qpay_urls', wp_json_encode($invoice['urls'] ?? []));
        $order->save();

        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}

// Payment page
add_action('woocommerce_receipt_qpay', function ($order_id) {
    $order = wc_get_order($order_id);
    $qr_image = $order->get_meta('_qpay_qr_image');
    $invoice_id = $order->get_meta('_qpay_invoice_id');
    $urls = json_decode($order->get_meta('_qpay_urls'), true) ?: [];

    wc_enqueue_js("
        var qpayPoll = setInterval(function(){
            jQuery.post('" . admin_url('admin-ajax.php') . "', {
                action: 'qpay_check_payment',
                invoice_id: '" . esc_js($invoice_id) . "'
            }, function(res){
                if(res.success && res.data.status === 'paid'){
                    clearInterval(qpayPoll);
                    window.location.href = '" . esc_js($order->get_checkout_order_received_url()) . "';
                }
            });
        }, 3000);
    ");

    echo '<div style="text-align:center;padding:20px;">';
    echo '<h3>QPay Төлбөр</h3>';
    if ($qr_image) {
        echo '<img src="data:image/png;base64,' . esc_attr($qr_image) . '" width="256" height="256" alt="QR Code">';
    }
    echo '<p style="margin-top:16px;">Банкны аппликейшнээр төлөх:</p>';
    echo '<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">';
    foreach ($urls as $link) {
        $logo = isset($link['logo']) ? '<img src="' . esc_url($link['logo']) . '" width="24" height="24"> ' : '';
        echo '<a href="' . esc_url($link['link'] ?? '') . '" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#333;">' . $logo . esc_html($link['name'] ?? '') . '</a>';
    }
    echo '</div>';
    echo '<p style="margin-top:16px;color:#666;">Төлбөр баталгаажихыг хүлээж байна...</p>';
    echo '</div>';
});
