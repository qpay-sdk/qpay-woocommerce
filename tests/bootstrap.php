<?php

/**
 * PHPUnit bootstrap for QPay WooCommerce tests.
 *
 * Stubs WordPress and WooCommerce functions/classes so tests can run
 * without a full WordPress installation.
 */

// Stub WordPress constants
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
if (! defined('QPAY_WC_VERSION')) {
    define('QPAY_WC_VERSION', '1.0.0');
}
if (! defined('QPAY_WC_PATH')) {
    define('QPAY_WC_PATH', dirname(__DIR__) . '/');
}
if (! defined('QPAY_WC_URL')) {
    define('QPAY_WC_URL', 'https://example.com/wp-content/plugins/qpay-woocommerce/');
}

// --- WordPress function stubs ---

$GLOBALS['wp_transients'] = [];
$GLOBALS['wp_remote_calls'] = [];
$GLOBALS['wp_remote_responses'] = [];
$GLOBALS['wp_actions'] = [];

function get_transient(string $key)
{
    return $GLOBALS['wp_transients'][$key] ?? false;
}

function set_transient(string $key, $value, int $expiration = 0): bool
{
    $GLOBALS['wp_transients'][$key] = $value;
    return true;
}

function delete_transient(string $key): bool
{
    unset($GLOBALS['wp_transients'][$key]);
    return true;
}

function wp_remote_post(string $url, array $args = []): array
{
    $GLOBALS['wp_remote_calls'][] = ['method' => 'POST', 'url' => $url, 'args' => $args];

    if (! empty($GLOBALS['wp_remote_responses'])) {
        return array_shift($GLOBALS['wp_remote_responses']);
    }

    return ['response' => ['code' => 200], 'body' => '{}'];
}

function wp_remote_request(string $url, array $args = []): array
{
    $GLOBALS['wp_remote_calls'][] = ['method' => $args['method'] ?? 'GET', 'url' => $url, 'args' => $args];

    if (! empty($GLOBALS['wp_remote_responses'])) {
        return array_shift($GLOBALS['wp_remote_responses']);
    }

    return ['response' => ['code' => 200], 'body' => '{}'];
}

function wp_remote_retrieve_body($response): string
{
    return $response['body'] ?? '';
}

function wp_remote_retrieve_response_code($response): int
{
    return $response['response']['code'] ?? 200;
}

function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}

function wp_json_encode($data): string
{
    return json_encode($data);
}

function sanitize_text_field(string $str): string
{
    return trim(strip_tags($str));
}

function __($text, $domain = 'default'): string
{
    return $text;
}

function site_url(string $path = ''): string
{
    return 'https://example.com' . $path;
}

function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void
{
    $GLOBALS['wp_actions'][$hook][] = $callback;
}

function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void
{
    // no-op for tests
}

function wp_send_json_success($data = null, int $status = 200): void
{
    throw new \QPay\Tests\WpJsonException(json_encode(['success' => true, 'data' => $data]), $status);
}

function wp_send_json_error($data = null, int $status = 200): void
{
    throw new \QPay\Tests\WpJsonException(json_encode(['success' => false, 'data' => $data]), $status);
}

function admin_url(string $path = ''): string
{
    return 'https://example.com/wp-admin/' . $path;
}

function esc_js(string $text): string
{
    return addslashes($text);
}

function esc_url(string $url): string
{
    return $url;
}

function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES);
}

function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES);
}

function wc_enqueue_js(string $js): void
{
    // no-op for tests
}

function plugin_dir_path(string $file): string
{
    return dirname($file) . '/';
}

function plugin_dir_url(string $file): string
{
    return 'https://example.com/wp-content/plugins/qpay-woocommerce/';
}

// --- WP_Error stub ---

class WP_Error
{
    private string $code;
    private string $message;

    public function __construct(string $code = '', string $message = '')
    {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_error_message(): string
    {
        return $this->message;
    }

    public function get_error_code(): string
    {
        return $this->code;
    }
}

// --- WooCommerce stubs ---

class WC_Payment_Gateway
{
    public string $id = '';
    public string $method_title = '';
    public string $method_description = '';
    public bool $has_fields = false;
    public string $icon = '';
    public string $title = '';
    public string $description = '';
    public string $enabled = 'no';
    public array $form_fields = [];
    protected array $settings = [];

    public function init_form_fields(): void {}

    public function init_settings(): void
    {
        // Load settings from form_fields defaults
        foreach ($this->form_fields as $key => $field) {
            if (! isset($this->settings[$key])) {
                $this->settings[$key] = $field['default'] ?? '';
            }
        }
    }

    public function get_option(string $key, $default = '')
    {
        return $this->settings[$key] ?? $default;
    }

    public function process_admin_options(): void {}
}

// --- WpJsonException for capturing wp_send_json calls ---

namespace QPay\Tests;

class WpJsonException extends \RuntimeException
{
    public int $statusCode;
    public string $body;

    public function __construct(string $body, int $statusCode = 200)
    {
        parent::__construct('wp_send_json called');
        $this->body = $body;
        $this->statusCode = $statusCode;
    }

    public function decoded(): array
    {
        return json_decode($this->body, true);
    }
}
