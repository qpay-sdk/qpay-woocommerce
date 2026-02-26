<?php

class WC_QPay_API
{
    private string $username;
    private string $password;
    private string $base_url;

    public function __construct(string $username, string $password, string $base_url = 'https://merchant.qpay.mn')
    {
        $this->username = $username;
        $this->password = $password;
        $this->base_url = rtrim($base_url, '/');
    }

    private function get_token(): ?string
    {
        $cached = get_transient('qpay_access_token');
        if ($cached) return $cached;

        $response = wp_remote_post($this->base_url . '/v2/auth/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) return null;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (! empty($body['access_token'])) {
            $expires = ($body['expires_in'] ?? 3600) - 60;
            set_transient('qpay_access_token', $body['access_token'], $expires);
            if (! empty($body['refresh_token'])) {
                set_transient('qpay_refresh_token', $body['refresh_token'], ($body['refresh_expires_in'] ?? 7200) - 60);
            }
            return $body['access_token'];
        }

        return null;
    }

    private function request(string $method, string $endpoint, array $body = []): ?array
    {
        $token = $this->get_token();
        if (! $token) return null;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (! empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($this->base_url . $endpoint, $args);
        if (is_wp_error($response)) return null;

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function create_invoice(array $data): ?array
    {
        return $this->request('POST', '/v2/invoice', $data);
    }

    public function check_payment(string $invoice_id): ?array
    {
        return $this->request('POST', '/v2/payment/check', [
            'object_type' => 'INVOICE',
            'object_id' => $invoice_id,
        ]);
    }
}
