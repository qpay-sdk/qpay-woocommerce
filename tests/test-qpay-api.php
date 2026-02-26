<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-qpay-api.php';

class QPayApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_transients'] = [];
        $GLOBALS['wp_remote_calls'] = [];
        $GLOBALS['wp_remote_responses'] = [];
    }

    private function createApi(): WC_QPay_API
    {
        return new WC_QPay_API('test_user', 'test_pass', 'https://merchant.qpay.mn');
    }

    private function queueAuthResponse(string $token = 'test_token_123', int $expiresIn = 3600): void
    {
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'access_token' => $token,
                'refresh_token' => 'refresh_token_456',
                'expires_in' => $expiresIn,
                'refresh_expires_in' => 7200,
            ]),
        ];
    }

    // --- Authentication tests ---

    public function test_get_token_sends_basic_auth(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode(['invoice_id' => 'inv_1']),
        ];

        $api = $this->createApi();
        $api->create_invoice(['amount' => 1000]);

        $authCall = $GLOBALS['wp_remote_calls'][0];
        $this->assertEquals('POST', $authCall['method']);
        $this->assertEquals('https://merchant.qpay.mn/v2/auth/token', $authCall['url']);

        $expectedAuth = 'Basic ' . base64_encode('test_user:test_pass');
        $this->assertEquals($expectedAuth, $authCall['args']['headers']['Authorization']);
    }

    public function test_get_token_caches_access_token(): void
    {
        $this->queueAuthResponse('cached_token');
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode(['invoice_id' => 'inv_1']),
        ];

        $api = $this->createApi();
        $api->create_invoice(['amount' => 1000]);

        // Token should be cached now
        $this->assertEquals('cached_token', get_transient('qpay_access_token'));
    }

    public function test_get_token_uses_cached_token(): void
    {
        // Pre-cache a token
        set_transient('qpay_access_token', 'precached_token', 3600);

        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode(['invoice_id' => 'inv_1']),
        ];

        $api = $this->createApi();
        $api->create_invoice(['amount' => 1000]);

        // Should only have 1 call (the invoice request), not 2 (auth + invoice)
        $this->assertCount(1, $GLOBALS['wp_remote_calls']);
        $this->assertStringContainsString('/v2/invoice', $GLOBALS['wp_remote_calls'][0]['url']);
    }

    public function test_get_token_returns_null_on_wp_error(): void
    {
        $GLOBALS['wp_remote_responses'][] = new WP_Error('http_error', 'Connection failed');

        $api = $this->createApi();
        $result = $api->create_invoice(['amount' => 1000]);

        $this->assertNull($result);
    }

    public function test_get_token_returns_null_on_empty_response(): void
    {
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode([]),
        ];

        $api = $this->createApi();
        $result = $api->create_invoice(['amount' => 1000]);

        $this->assertNull($result);
    }

    public function test_get_token_stores_refresh_token(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode(['invoice_id' => 'inv_1']),
        ];

        $api = $this->createApi();
        $api->create_invoice(['amount' => 1000]);

        $this->assertEquals('refresh_token_456', get_transient('qpay_refresh_token'));
    }

    // --- create_invoice tests ---

    public function test_create_invoice_sends_correct_request(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'invoice_id' => 'inv_123',
                'qr_text' => 'qr_text_data',
                'qr_image' => 'base64_image_data',
                'urls' => [],
            ]),
        ];

        $api = $this->createApi();
        $result = $api->create_invoice([
            'invoice_code' => 'TEST_INVOICE',
            'amount' => 5000,
            'callback_url' => 'https://example.com/callback',
        ]);

        $this->assertIsArray($result);
        $this->assertEquals('inv_123', $result['invoice_id']);

        // Verify the invoice request
        $invoiceCall = $GLOBALS['wp_remote_calls'][1];
        $this->assertEquals('POST', $invoiceCall['args']['method']);
        $this->assertEquals('https://merchant.qpay.mn/v2/invoice', $invoiceCall['url']);
        $this->assertStringContainsString('Bearer test_token_123', $invoiceCall['args']['headers']['Authorization']);

        $sentBody = json_decode($invoiceCall['args']['body'], true);
        $this->assertEquals('TEST_INVOICE', $sentBody['invoice_code']);
        $this->assertEquals(5000, $sentBody['amount']);
    }

    public function test_create_invoice_returns_null_on_auth_failure(): void
    {
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 401],
            'body' => json_encode(['message' => 'Unauthorized']),
        ];

        $api = $this->createApi();
        $result = $api->create_invoice(['amount' => 1000]);

        $this->assertNull($result);
    }

    public function test_create_invoice_returns_null_on_request_wp_error(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = new WP_Error('timeout', 'Request timed out');

        $api = $this->createApi();
        $result = $api->create_invoice(['amount' => 1000]);

        $this->assertNull($result);
    }

    // --- check_payment tests ---

    public function test_check_payment_sends_correct_request(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'count' => 1,
                'paid_amount' => 1000.0,
                'rows' => [
                    ['payment_id' => 'pay_1', 'payment_status' => 'PAID'],
                ],
            ]),
        ];

        $api = $this->createApi();
        $result = $api->check_payment('inv_123');

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['count']);
        $this->assertCount(1, $result['rows']);

        // Verify the check payment request
        $checkCall = $GLOBALS['wp_remote_calls'][1];
        $this->assertEquals('POST', $checkCall['args']['method']);
        $this->assertEquals('https://merchant.qpay.mn/v2/payment/check', $checkCall['url']);

        $sentBody = json_decode($checkCall['args']['body'], true);
        $this->assertEquals('INVOICE', $sentBody['object_type']);
        $this->assertEquals('inv_123', $sentBody['object_id']);
    }

    public function test_check_payment_returns_empty_rows_when_unpaid(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'count' => 0,
                'paid_amount' => 0,
                'rows' => [],
            ]),
        ];

        $api = $this->createApi();
        $result = $api->check_payment('inv_456');

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['count']);
        $this->assertEmpty($result['rows']);
    }

    public function test_check_payment_returns_null_on_failure(): void
    {
        $this->queueAuthResponse();
        $GLOBALS['wp_remote_responses'][] = new WP_Error('server_error', 'Internal server error');

        $api = $this->createApi();
        $result = $api->check_payment('inv_789');

        $this->assertNull($result);
    }

    // --- Constructor tests ---

    public function test_constructor_trims_trailing_slash_from_base_url(): void
    {
        set_transient('qpay_access_token', 'test_token', 3600);

        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode(['invoice_id' => 'inv_1']),
        ];

        $api = new WC_QPay_API('user', 'pass', 'https://merchant.qpay.mn/');
        $api->create_invoice(['amount' => 100]);

        // URL should not have double slashes
        $this->assertEquals('https://merchant.qpay.mn/v2/invoice', $GLOBALS['wp_remote_calls'][0]['url']);
    }

    public function test_constructor_uses_default_base_url(): void
    {
        set_transient('qpay_access_token', 'test_token', 3600);

        $GLOBALS['wp_remote_responses'][] = [
            'response' => ['code' => 200],
            'body' => json_encode(['invoice_id' => 'inv_1']),
        ];

        $api = new WC_QPay_API('user', 'pass');
        $api->create_invoice(['amount' => 100]);

        $this->assertStringStartsWith('https://merchant.qpay.mn/', $GLOBALS['wp_remote_calls'][0]['url']);
    }
}
