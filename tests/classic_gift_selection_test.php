<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

if (!class_exists('WooCommerce')) {
    class WooCommerce {}
}

if (!function_exists('absint')) {
    function absint($value) {
        return abs(intval($value));
    }
}

if (!function_exists('add_action')) {
    function add_action(...$args) {
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(...$args) {
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        return $default;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null) {
        return $text;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($id) {
        return 'Gift #' . intval($id);
    }
}

final class GGW_Test_Product {
    public function is_purchasable() { return true; }
    public function is_in_stock() { return true; }
    public function is_type($type) { return $type === 'simple'; }
}

if (!function_exists('wc_get_product')) {
    function wc_get_product($id) {
        return new GGW_Test_Product();
    }
}

if (!function_exists('WC')) {
    function WC() {
        static $instance;
        if (!$instance) {
            $instance = new stdClass();
            $instance->cart = null;
        }
        return $instance;
    }
}

require_once dirname(__DIR__) . '/Main';

$ref = new ReflectionClass('GGW_Free_Gift_Campaigns');

$requiredClassic = $ref->getMethod('required_classic_selection_count');
$requiredClassic->setAccessible(true);
$collectClassic = $ref->getMethod('collect_classic_selected_gifts_from_request');
$collectClassic->setAccessible(true);

$campaign = [
    'id' => 'test-campaign',
    'min_qty' => 1,
    'gifts_to_choose' => 2,
    'gift_products' => [101, 102],
    'gift_product_limits' => [],
];

$assertions = 0;
$failures = [];

$assert = function (bool $condition, string $message) use (&$assertions, &$failures): void {
    $assertions++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$requiredQty1 = $requiredClassic->invoke(null, $campaign, 1);
$assert($requiredQty1 === 2, 'quantity 1 should require base 2 gifts');

$requiredQty2 = $requiredClassic->invoke(null, $campaign, 2);
$assert($requiredQty2 === 4, 'quantity 2 should require entitled 4 gifts');

$_POST = [
    'ggw_gift_qty' => [101 => 2, 102 => 2],
    'ggw_gift_variation_id' => [],
];
$error = '';
$selected = $collectClassic->invokeArgs(null, [$campaign, null, &$error, 2]);
$assert(is_array($selected), 'exact required gift selection should pass');
$assert(count($selected) === 4, 'exact required gift selection should include 4 gifts');
$assert($error === '', 'exact required gift selection should not set an error');

$_POST = [
    'ggw_gift_qty' => [101 => 1, 102 => 1],
    'ggw_gift_variation_id' => [],
];
$error = '';
$selected = $collectClassic->invokeArgs(null, [$campaign, null, &$error, 2]);
$assert($selected === null, 'too few gifts should fail validation');
$assert(strpos($error, 'nøyaktig 4 gave') !== false, 'too few gifts should mention required count in error');

if (!empty($failures)) {
    fwrite(STDERR, "Failed assertions:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Assertions: {$assertions}\n";
