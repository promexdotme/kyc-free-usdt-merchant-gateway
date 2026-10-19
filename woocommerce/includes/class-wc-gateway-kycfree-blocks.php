<?php
/**
 * WooCommerce Checkout Blocks Support for KYC-Free Crypto Gateway
 *
 * @package KYC_Free_Crypto_Gateway
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;

final class WC_Gateway_KYCFree_Blocks_Support extends AbstractPaymentMethodType {

    /**
     * Payment method name defined by payment methods extending this class.
     *
     * @var string
     */
    protected $name = 'kyc_free_crypto';

    /**
     * Settings from gateway option
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_kyc_free_crypto_settings', array());
    }

    /**
     * Returns if this payment method should be active.
     *
     * @return boolean
     */
    public function is_active() {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_handle = 'wc-kycfree-blocks-integration';
        $script_src    = WC_KYCFREE_PLUGIN_URL . 'assets/js/blocks.js';

        if (!wp_script_is($script_handle, 'registered')) {
            wp_register_script(
                $script_handle,
                $script_src,
                array(
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ),
                WC_KYCFREE_VERSION,
                true
            );
        }

        return array($script_handle);
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'       => $this->get_setting('title', __('Crypto Payment (USDT / USDC - No KYC)', 'kyc-free-crypto-gateway')),
            'description' => $this->get_setting('description', __('Pay securely with USDT or USDC via Tron, Polygon, BSC, or Ethereum. Instant, private, non-custodial checkout.', 'kyc-free-crypto-gateway')),
            'supports'    => array('products'),
        );
    }
}
