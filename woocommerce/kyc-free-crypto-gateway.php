<?php
/**
 * Plugin Name: 0xGate KYC-Free Crypto Payment Gateway for WooCommerce
 * Plugin URI: https://0xgate.org/woocommerce-usdt-crypto-plugin.html
 * Description: Accept decentralized, non-custodial crypto payments (USDT & USDC on Tron TRC20, Polygon, BSC BEP20, and Ethereum ERC20) with zero KYC, automatic wrong-chain EVM recovery, and instant webhook fulfillment.
 * Version: 1.2.1
 * Author: 0xGate by PROMEX
 * Author URI: https://0xgate.org
 * License: MIT
 * Text Domain: kyc-free-crypto-gateway
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 */

defined('ABSPATH') || exit;

define('WC_KYCFREE_VERSION', '1.2.1');
define('WC_KYCFREE_PLUGIN_FILE', __FILE__);
define('WC_KYCFREE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_KYCFREE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Declare WooCommerce HPOS and Cart/Checkout Blocks Compatibility
 */
add_action('before_woocommerce_init', 'wc_kycfree_declare_features_compatibility');
function wc_kycfree_declare_features_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // High-Performance Order Storage (HPOS)
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        // Gutenberg Cart & Checkout Blocks
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

/**
 * Initialize gateway once plugins are loaded
 */
add_action('plugins_loaded', 'wc_kycfree_init_gateway', 11);
function wc_kycfree_init_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Load class file with fallback support
    $class_file = WC_KYCFREE_PLUGIN_PATH . 'includes/class-wc-gateway-kycfree.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    } elseif (file_exists(WC_KYCFREE_PLUGIN_PATH . 'class-wc-gateway-kycfree.php')) {
        require_once WC_KYCFREE_PLUGIN_PATH . 'class-wc-gateway-kycfree.php';
    }

    if (class_exists('WC_Gateway_KYCFree')) {
        add_filter('woocommerce_payment_gateways', 'wc_kycfree_register_gateway');
    }
}

/**
 * Register the gateway with WooCommerce
 */
function wc_kycfree_register_gateway($gateways) {
    $gateways[] = 'WC_Gateway_KYCFree';
    return $gateways;
}

/**
 * Register WooCommerce Blocks Support
 */
add_action('woocommerce_blocks_loaded', 'wc_kycfree_register_blocks_support');
function wc_kycfree_register_blocks_support() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    $blocks_class_file = WC_KYCFREE_PLUGIN_PATH . 'includes/class-wc-gateway-kycfree-blocks.php';
    if (file_exists($blocks_class_file)) {
        require_once $blocks_class_file;
    }

    if (class_exists('WC_Gateway_KYCFree_Blocks_Support')) {
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new WC_Gateway_KYCFree_Blocks_Support());
            }
        );
    }
}

/**
 * Add settings action shortcut link on the WordPress Plugins list page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_kycfree_action_links');
function wc_kycfree_action_links($links) {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=kyc_free_crypto');
    $plugin_links = array(
        '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'kyc-free-crypto-gateway') . '</a>'
    );
    return array_merge($plugin_links, $links);
}

/**
 * Admin notice if WooCommerce is missing
 */
add_action('admin_notices', 'wc_kycfree_woocommerce_missing_notice');
function wc_kycfree_woocommerce_missing_notice() {
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('KYC-Free Crypto Payment Gateway requires WooCommerce to be installed and active.', 'kyc-free-crypto-gateway') . '</strong></p></div>';
    }
}
