<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-qpay-api.php';
require_once dirname(__DIR__) . '/includes/class-qpay-gateway.php';

class QPayGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_transients'] = [];
        $GLOBALS['wp_remote_calls'] = [];
        $GLOBALS['wp_remote_responses'] = [];
        $GLOBALS['wp_actions'] = [];
    }

    private function createGateway(): WC_QPay_Gateway
    {
        return new WC_QPay_Gateway();
    }

    // --- Initialization tests ---

    public function test_gateway_id_is_qpay(): void
    {
        $gateway = $this->createGateway();
        $this->assertEquals('qpay', $gateway->id);
    }

    public function test_gateway_method_title(): void
    {
        $gateway = $this->createGateway();
        $this->assertEquals('QPay', $gateway->method_title);
    }

    public function test_gateway_method_description(): void
    {
        $gateway = $this->createGateway();
        $this->assertEquals('QPay V2 payment gateway', $gateway->method_description);
    }

    public function test_gateway_has_no_fields(): void
    {
        $gateway = $this->createGateway();
        $this->assertFalse($gateway->has_fields);
    }

    public function test_gateway_default_title(): void
    {
        $gateway = $this->createGateway();
        $this->assertEquals('QPay', $gateway->title);
    }

    public function test_gateway_default_description(): void
    {
        $gateway = $this->createGateway();
        $this->assertStringContainsString('QPay', $gateway->description);
    }

    // --- Form fields tests ---

    public function test_form_fields_has_enabled(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('enabled', $gateway->form_fields);
        $this->assertEquals('checkbox', $gateway->form_fields['enabled']['type']);
    }

    public function test_form_fields_has_title(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('title', $gateway->form_fields);
        $this->assertEquals('text', $gateway->form_fields['title']['type']);
    }

    public function test_form_fields_has_base_url(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('base_url', $gateway->form_fields);
        $this->assertEquals('https://merchant.qpay.mn', $gateway->form_fields['base_url']['default']);
    }

    public function test_form_fields_has_username(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('username', $gateway->form_fields);
        $this->assertEquals('text', $gateway->form_fields['username']['type']);
    }

    public function test_form_fields_has_password(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('password', $gateway->form_fields);
        $this->assertEquals('password', $gateway->form_fields['password']['type']);
    }

    public function test_form_fields_has_invoice_code(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('invoice_code', $gateway->form_fields);
    }

    public function test_form_fields_has_callback_url(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('callback_url', $gateway->form_fields);
    }

    public function test_form_fields_has_description_field(): void
    {
        $gateway = $this->createGateway();
        $this->assertArrayHasKey('description', $gateway->form_fields);
        $this->assertEquals('textarea', $gateway->form_fields['description']['type']);
    }

    // --- Settings tests ---

    public function test_enabled_defaults_to_no(): void
    {
        $gateway = $this->createGateway();
        $this->assertEquals('no', $gateway->form_fields['enabled']['default']);
    }

    public function test_get_option_returns_default_when_not_set(): void
    {
        $gateway = $this->createGateway();
        $this->assertEquals('fallback_value', $gateway->get_option('nonexistent_key', 'fallback_value'));
    }

    // --- Admin action hook test ---

    public function test_admin_options_action_is_registered(): void
    {
        $this->createGateway();

        $hookName = 'woocommerce_update_options_payment_gateways_qpay';
        $this->assertArrayHasKey($hookName, $GLOBALS['wp_actions']);
    }

    // --- Icon test ---

    public function test_gateway_icon_path(): void
    {
        $gateway = $this->createGateway();
        $this->assertStringContainsString('qpay-icon.png', $gateway->icon);
    }

    // --- All required form fields present ---

    public function test_all_required_form_fields_are_present(): void
    {
        $gateway = $this->createGateway();
        $requiredFields = ['enabled', 'title', 'description', 'base_url', 'username', 'password', 'invoice_code', 'callback_url'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $gateway->form_fields, "Missing form field: {$field}");
        }
    }
}
