<?php
/**
 * KYC-Free Crypto Payment Gateway Class
 *
 * @package KYC_Free_Crypto_Gateway
 */

defined('ABSPATH') || exit;

class WC_Gateway_KYCFree extends WC_Payment_Gateway {

    /**
     * Gateway Host URL
     *
     * @var string
     */
    public $gateway_url;

    /**
     * Merchant Identifier
     *
     * @var string
     */
    public $merchant_identifier;

    /**
     * Allowed methods array
     *
     * @var array
     */
    public $allowed_methods;

    /**
     * Completed vs Processing order status
     *
     * @var string
     */
    public $order_status;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'kyc_free_crypto';
        $this->icon               = apply_filters('woocommerce_kycfree_icon', '');
        $this->has_fields         = false;
        $this->method_title       = __('KYC-Free Crypto Gateway (USDT / USDC)', 'kyc-free-crypto-gateway');
        $this->method_description = __('Accept decentralized, non-custodial crypto payments (USDT & USDC on Tron, Polygon, BSC, and Ethereum) without KYC.', 'kyc-free-crypto-gateway');
        $this->supports           = array('products');

        // Load configuration
        $this->init_form_fields();
        $this->init_settings();

        // Bind properties from saved options
        $this->title               = $this->get_option('title', __('Crypto Payment (USDT / USDC)', 'kyc-free-crypto-gateway'));
        $this->description         = $this->get_option('description', __('Pay securely with USDT or USDC via Tron, Polygon, BSC, or Ethereum. Instant, private, non-custodial checkout.', 'kyc-free-crypto-gateway'));
        $this->gateway_url         = rtrim($this->get_option('gateway_url', 'https://0xgate.org'), '/');
        $this->merchant_identifier = trim($this->get_option('merchant_identifier', ''));
        $this->allowed_methods     = (array) $this->get_option('allowed_methods', array(
            'TRC20_USDT',
            'POLYGON_USDT',
            'BSC_USDT',
            'ERC20_USDT',
            'POLYGON_USDC',
            'BSC_USDC',
            'ERC20_USDC'
        ));
        $this->order_status        = $this->get_option('order_status', 'processing');

        // Admin option save handler
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Webhook IPN Listener
        add_action('woocommerce_api_kyc_free_crypto', array($this, 'handle_webhook'));
    }

    /**
     * Form fields in WooCommerce Admin Settings
     */
    public function init_form_fields() {
        $portal_url = !empty($this->gateway_url) ? esc_url($this->gateway_url) : 'https://0xgate.org';

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable / Disable', 'kyc-free-crypto-gateway'),
                'label'       => __('Enable 0xGate KYC-Free Crypto Payment Gateway', 'kyc-free-crypto-gateway'),
                'type'        => 'checkbox',
                'default'     => 'no',
                'description' => __('Tick this box to enable cryptocurrency checkout in your store.', 'kyc-free-crypto-gateway'),
            ),
            'title' => array(
                'title'       => __('Title at Checkout', 'kyc-free-crypto-gateway'),
                'type'        => 'text',
                'description' => __('The payment method title shown to customers during checkout.', 'kyc-free-crypto-gateway'),
                'default'     => __('Crypto Payment (USDT / USDC - No KYC)', 'kyc-free-crypto-gateway'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Customer Instructions', 'kyc-free-crypto-gateway'),
                'type'        => 'textarea',
                'description' => __('Payment description shown to customers when selecting this payment option.', 'kyc-free-crypto-gateway'),
                'default'     => __('Pay securely with USDT or USDC via Tron, Polygon, BSC, or Ethereum. Instant, private, non-custodial checkout.', 'kyc-free-crypto-gateway'),
            ),
            'gateway_url' => array(
                'title'       => __('Gateway Host URL', 'kyc-free-crypto-gateway'),
                'type'        => 'text',
                'description' => __('The URL of your 0xGate payment gateway instance (defaults to https://0xgate.org, or enter your own Sovereign VPS URL).', 'kyc-free-crypto-gateway'),
                'default'     => 'https://0xgate.org',
                'desc_tip'    => true,
            ),
            'merchant_identifier' => array(
                'title'       => __('Merchant Identifier', 'kyc-free-crypto-gateway'),
                'type'        => 'text',
                'description' => sprintf(
                    __('Enter your registered username or identifier. <strong>Don\'t have an account yet?</strong> <a href="%s" target="_blank" style="color:#2563eb; font-weight:600; text-decoration:underline;">Click here to open 0xGate.org &amp; Register</a> in 10 seconds (zero KYC required).', 'kyc-free-crypto-gateway'),
                    $portal_url
                ),
                'default'     => '',
                'desc_tip'    => false,
            ),
            'allowed_methods' => array(
                'title'       => __('Allowed Payment Methods', 'kyc-free-crypto-gateway'),
                'type'        => 'multiselect',
                'description' => __('Select which cryptocurrency chains and tokens you wish to accept.', 'kyc-free-crypto-gateway'),
                'default'     => array(
                    'TRC20_USDT',
                    'POLYGON_USDT',
                    'BSC_USDT',
                    'ERC20_USDT',
                    'POLYGON_USDC',
                    'BSC_USDC',
                    'ERC20_USDC'
                ),
                'options'     => array(
                    'TRC20_USDT'   => 'USDT (Tron TRC20)',
                    'POLYGON_USDT' => 'USDT (Polygon)',
                    'BSC_USDT'     => 'USDT (Binance Smart Chain BEP20)',
                    'ERC20_USDT'   => 'USDT (Ethereum ERC20)',
                    'POLYGON_USDC' => 'USDC (Polygon)',
                    'BSC_USDC'     => 'USDC (Binance Smart Chain BEP20)',
                    'ERC20_USDC'   => 'USDC (Ethereum ERC20)',
                ),
                'css'         => 'height: 140px;',
            ),
            'order_status' => array(
                'title'       => __('Order Status on Successful Payment', 'kyc-free-crypto-gateway'),
                'type'        => 'select',
                'description' => __('Select what status orders should transition to once on-chain payment is confirmed.', 'kyc-free-crypto-gateway'),
                'default'     => 'processing',
                'options'     => array(
                    'processing' => __('Processing (Recommended for physical goods)', 'kyc-free-crypto-gateway'),
                    'completed'  => __('Completed (Recommended for digital downloads / instant fulfillment)', 'kyc-free-crypto-gateway'),
                ),
            ),
        );
    }

    /**
     * Get webhook callback URL safely
     */
    public function get_webhook_url() {
        if (function_exists('WC') && WC() && method_exists(WC(), 'api_request_url')) {
            return WC()->api_request_url('kyc_free_crypto');
        }
        return home_url('/?wc-api=kyc_free_crypto');
    }

    /**
     * Render the admin options and custom Webhook URL callout
     */
    public function admin_options() {
        $webhook_url = $this->get_webhook_url();
        $portal_url  = !empty($this->gateway_url) ? esc_url($this->gateway_url) : 'https://0xgate.org';
        ?>
        <h2><?php echo esc_html($this->method_title); ?></h2>
        <p><?php echo esc_html($this->method_description); ?></p>

        <!-- Quick-Start Onboarding Banner -->
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 16px;">
                <div>
                    <h3 style="margin: 0; color: #0f172a; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <span>🚀</span> <?php esc_html_e('Quick Setup & Merchant Registration', 'kyc-free-crypto-gateway'); ?>
                    </h3>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
                        <?php esc_html_e('Follow these 3 simple steps to connect your WooCommerce store to the payment gateway.', 'kyc-free-crypto-gateway'); ?>
                    </p>
                </div>
                <a href="<?php echo $portal_url; ?>" target="_blank" class="button button-primary" style="background: #2563eb; border-color: #1d4ed8; padding: 4px 14px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <?php esc_html_e('Open Merchant Portal / Register ↗', 'kyc-free-crypto-gateway'); ?>
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <!-- Step 1 -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px;">
                    <div style="font-size: 11px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        <?php esc_html_e('Step 1: Create Account', 'kyc-free-crypto-gateway'); ?>
                    </div>
                    <div style="font-size: 12px; color: #334155; line-height: 1.4;">
                        <?php esc_html_e('Open the Merchant Portal, click "Register Remotely" and set your username and payout crypto wallet address. No KYC required.', 'kyc-free-crypto-gateway'); ?>
                    </div>
                </div>

                <!-- Step 2 -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px;">
                    <div style="font-size: 11px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        <?php esc_html_e('Step 2: Enter Identifier', 'kyc-free-crypto-gateway'); ?>
                    </div>
                    <div style="font-size: 12px; color: #334155; line-height: 1.4;">
                        <?php esc_html_e('Enter your registered username into the "Merchant Identifier" field below so payments are routed directly to your account.', 'kyc-free-crypto-gateway'); ?>
                    </div>
                </div>

                <!-- Step 3 -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px;">
                    <div style="font-size: 11px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        <?php esc_html_e('Step 3: Save Webhook URL', 'kyc-free-crypto-gateway'); ?>
                    </div>
                    <div style="font-size: 12px; color: #334155; line-height: 1.4;">
                        <?php esc_html_e('Copy your Store Webhook URL below and paste it into your Merchant Portal settings to enable instant order completion.', 'kyc-free-crypto-gateway'); ?>
                    </div>
                </div>
            </div>

            <!-- Webhook Callout Section -->
            <div style="margin-top: 16px; padding-top: 14px; border-top: 1px dashed #cbd5e1;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                    📋 <?php esc_html_e('Your Store Webhook Callback URL:', 'kyc-free-crypto-gateway'); ?>
                </label>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <input type="text" id="wc-kycfree-webhook-url" readonly value="<?php echo esc_url($webhook_url); ?>" style="flex: 1; min-width: 280px; max-width: 600px; font-family: monospace; font-size: 12px; background: #f1f5f9; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px;" onclick="this.select();">
                    <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('wc-kycfree-webhook-url').value); alert('Webhook URL copied to clipboard!');">
                        <?php esc_html_e('Copy URL', 'kyc-free-crypto-gateway'); ?>
                    </button>
                </div>
            </div>
        </div>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    /**
     * Process checkout payment and redirect customer to payment invoice desk
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice(__('Order not found.', 'kyc-free-crypto-gateway'), 'error');
            return array('result' => 'fail');
        }

        if (empty($this->merchant_identifier)) {
            wc_add_notice(__('Gateway error: Store owner has not configured their Merchant Identifier.', 'kyc-free-crypto-gateway'), 'error');
            return array('result' => 'fail');
        }

        // Validate store currency (USDT/USDC gateway expects USD-pegged order totals)
        $order_currency = strtoupper((string) $order->get_currency());
        $allowed_fiat   = apply_filters('woocommerce_kycfree_allowed_currencies', array('USD', 'USDT', 'USDC'));
        if (!in_array($order_currency, $allowed_fiat, true)) {
            wc_add_notice(
                sprintf(
                    __('Crypto checkout requires USD pricing (current currency: %s). Please switch store currency to USD or configure a currency conversion filter.', 'kyc-free-crypto-gateway'),
                    esc_html($order_currency)
                ),
                'error'
            );
            return array('result' => 'fail');
        }

        if ($order->is_paid()) {
            return array('result' => 'success', 'redirect' => $this->get_return_url($order));
        }

        // An order keeps one unpredictable reference and one invoice. Store the
        // reference before the request so a timeout/retry cannot create a new one.
        $bound_invoice = (int) $order->get_meta('_0xgate_invoice_id', true);
        $bound_host = (string) $order->get_meta('_0xgate_gateway_url', true);
        if ($bound_invoice > 0) {
            if ($bound_host !== $this->gateway_url) {
                wc_add_notice(__('This order is bound to a different gateway. Contact the store owner.', 'kyc-free-crypto-gateway'), 'error');
                return array('result' => 'fail');
            }
            return array('result' => 'success', 'redirect' => $bound_host . '/pay/' . $bound_invoice);
        }

        // Amount formatted to 2 decimal places in USD
        $amount_usd = number_format((float) $order->get_total(), 2, '.', '');
        $website_label = get_bloginfo('name');
        $order_ref = (string) $order->get_meta('_0xgate_reference', true);
        if ($order_ref === '') {
            $order_ref = 'wc-' . $order->get_id() . '-' . wp_generate_uuid4();
            $order->update_meta_data('_0xgate_reference', $order_ref);
            $order->save();
        }

        // Allowed methods CSV
        $methods_csv = !empty($this->allowed_methods) ? implode(',', (array) $this->allowed_methods) : '';

        // Build redirect checkout URL
        $query_args = array(
            'merchant' => $this->merchant_identifier,
            'amount'   => $amount_usd,
            'website'  => rawurlencode($website_label),
            'ref'      => $order_ref,
        );

        if (!empty($methods_csv)) {
            $query_args['methods'] = $methods_csv;
        }

        $checkout_url = add_query_arg($query_args, $this->gateway_url . '/pay/new');

        // Create the invoice on the server, then pin its ID before sending the
        // buyer to checkout. Never let a notification choose the order binding.
        $created = wp_remote_get($checkout_url, array('timeout' => 15, 'redirection' => 0));
        $location = is_wp_error($created) ? '' : (string) wp_remote_retrieve_header($created, 'location');
        if (is_wp_error($created) || wp_remote_retrieve_response_code($created) !== 302 ||
            !preg_match('~^/pay/([1-9][0-9]*)$~D', $location, $invoice_match)) {
            wc_add_notice(__('Could not create the crypto invoice. Please retry checkout.', 'kyc-free-crypto-gateway'), 'error');
            return array('result' => 'fail');
        }
        $bound_invoice = (int) $invoice_match[1];
        $order->update_meta_data('_0xgate_invoice_id', $bound_invoice);
        $order->update_meta_data('_0xgate_gateway_url', $this->gateway_url);
        $order->update_meta_data('_0xgate_merchant_identifier', $this->merchant_identifier);
        $order->save();
        $checkout_url = $this->gateway_url . '/pay/' . $bound_invoice;

        // Update order status to Pending Payment with note
        $order->update_status('pending', sprintf(
            __('Awaiting crypto payment via KYC-Free Gateway. Amount: $%s USD. Ref: %s', 'kyc-free-crypto-gateway'),
            $amount_usd,
            $order_ref
        ));

        // Empty cart safely
        if (function_exists('WC') && WC() && WC()->cart) {
            WC()->cart->empty_cart();
        }

        return array(
            'result'   => 'success',
            'redirect' => $checkout_url,
        );
    }

    /**
     * Handle incoming webhook callbacks from the payment gateway
     * Endpoint: https://yourdomain.com/?wc-api=kyc_free_crypto
     */
    public function handle_webhook() {
        // Read raw JSON body
        $raw_input = file_get_contents('php://input');

        if (empty($raw_input)) {
            status_header(400);
            wp_send_json_error(array('message' => 'Empty request body'));
            exit;
        }

        $payload = json_decode($raw_input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($payload['event']) || !isset($payload['data'])) {
            status_header(400);
            wp_send_json_error(array('message' => 'Invalid JSON payload structure'));
            exit;
        }

        // Verify event type
        if ($payload['event'] !== 'payment.status_updated') {
            wp_send_json_success(array('message' => 'Event ignored'));
            exit;
        }

        $data      = $payload['data'];
        $payment_id = isset($data['id']) ? intval($data['id']) : 0;
        $order_ref  = isset($data['merchant_ref']) ? sanitize_text_field($data['merchant_ref']) : '';

        if ($payment_id <= 0 || empty($order_ref)) {
            status_header(400);
            wp_send_json_error(array('message' => 'Missing payment id or merchant_ref in payload'));
            exit;
        }

        // Server-to-Server Verification: Fetch canonical invoice state directly from the configured 0xGate host
        // Prevents unauthenticated third-party webhook spoofing
        $verify_url = rtrim($this->gateway_url, '/') . '/api/public/pay/' . $payment_id;
        $verify_res = wp_remote_get($verify_url, array('timeout' => 10, 'redirection' => 0));

        if (is_wp_error($verify_res) || wp_remote_retrieve_response_code($verify_res) !== 200) {
            status_header(502);
            wp_send_json_error(array('message' => 'Could not verify payment status against gateway API'));
            exit;
        }

        $verified_invoice = json_decode(wp_remote_retrieve_body($verify_res), true);
        if (!is_array($verified_invoice) || empty($verified_invoice['id']) || intval($verified_invoice['id']) !== $payment_id) {
            status_header(400);
            wp_send_json_error(array('message' => 'Gateway verification response mismatch'));
            exit;
        }

        $canonical_ref = isset($verified_invoice['merchant_ref']) ? (string) $verified_invoice['merchant_ref'] : '';
        if ($canonical_ref === '' || !hash_equals($canonical_ref, $order_ref)) {
            status_header(400);
            wp_send_json_error(array('message' => 'Invoice order reference mismatch'));
            exit;
        }

        // Extract canonical fields from verified gateway response
        $status          = isset($verified_invoice['status']) ? sanitize_text_field($verified_invoice['status']) : '';
        $tx_hash         = isset($verified_invoice['tx_hash']) ? sanitize_text_field($verified_invoice['tx_hash']) : '';
        $network         = isset($verified_invoice['network']) ? sanitize_text_field($verified_invoice['network']) : '';
        $crypto_currency = isset($verified_invoice['crypto_currency']) ? sanitize_text_field($verified_invoice['crypto_currency']) : '';
        $paid_amount     = isset($verified_invoice['paid_amount']) ? floatval($verified_invoice['paid_amount']) : 0.0;
        $crypto_amount   = $paid_amount > 0 ? (string) $paid_amount : (isset($verified_invoice['crypto_amount']) ? sanitize_text_field($verified_invoice['crypto_amount']) : '');
        $amount_usd      = isset($verified_invoice['amount_usd']) ? floatval($verified_invoice['amount_usd']) : 0.0;
        $derived_address = sanitize_text_field($verified_invoice['derived_address'] ?? '');
        $merchant_ident  = isset($verified_invoice['merchant_identifier'])
            ? sanitize_text_field($verified_invoice['merchant_identifier'])
            : (isset($verified_invoice['merchant_name']) ? sanitize_text_field($verified_invoice['merchant_name']) : '');

        // Verify that this invoice belongs to our configured merchant identifier
        if (empty($this->merchant_identifier) || empty($merchant_ident) || strtolower($merchant_ident) !== strtolower($this->merchant_identifier)) {
            wp_send_json_success(array('message' => 'Webhook received for different identifier. Ignored.'));
            exit;
        }

        // Use the internal ID from the canonical reference only to locate the
        // order, then require the entire saved random reference and invoice ID.
        // WC order APIs work with both legacy storage and HPOS.
        $order = preg_match('~^wc-([1-9][0-9]*)-~', $canonical_ref, $ref_match)
            ? wc_get_order((int) $ref_match[1]) : null;

        if (!$order) {
            status_header(404);
            wp_send_json_error(array('message' => 'Order not found: ' . $order_ref));
            exit;
        }

        if ($order->get_payment_method() !== $this->id ||
            !hash_equals((string) $order->get_meta('_0xgate_reference', true), $canonical_ref) ||
            (int) $order->get_meta('_0xgate_invoice_id', true) !== $payment_id ||
            (string) $order->get_meta('_0xgate_gateway_url', true) !== $this->gateway_url ||
            strtolower((string) $order->get_meta('_0xgate_merchant_identifier', true)) !== strtolower($merchant_ident)) {
            status_header(400);
            wp_send_json_error(array('message' => 'Invoice is not bound to this order'));
            exit;
        }
        if ($order->is_paid()) {
            wp_send_json_success(array('message' => 'Payment already recorded'));
            exit;
        }

        // Process status transitions using canonical gateway verification
        if ($status === 'PAID') {
            if (!$order->is_paid()) {
                $expected_total = floatval($order->get_total());
                $effective_paid = $paid_amount > 0 ? $paid_amount : $amount_usd;
                $ratio          = $expected_total > 0 ? ($effective_paid / $expected_total) : 1.0;
                $shortfall      = $expected_total - $effective_paid;

                // Ensure the verified gateway invoice actually covers the WooCommerce order total (within standard 1.5% / $1.00 exchange fee tolerance)
                $covers_order_total = ($ratio >= 0.985) || ($expected_total >= 20.0 && $shortfall <= 1.0);

                if (!$covers_order_total) {
                    $hold_note = sprintf(
                        __("⚠️ Gateway Invoice #%d marked PAID ($%0.2f USD), but is less than WooCommerce Order Total ($%0.2f USD). Placed on-hold for manual review.", 'kyc-free-crypto-gateway'),
                        $payment_id,
                        $effective_paid,
                        $expected_total
                    );
                    $order->add_order_note($hold_note);
                    $order->update_status('on-hold', __('Amount mismatch on crypto invoice.', 'kyc-free-crypto-gateway'));
                    wp_send_json_success(array('message' => 'Invoice amount below order total; placed on-hold.'));
                    exit;
                }

                $order->set_transaction_id($tx_hash);

                $note = sprintf(
                    __("✅ Crypto Payment Confirmed (Verified Invoice #%d)!\n• Amount Received: %s %s\n• Invoice Value: $%0.2f USD\n• Network: %s\n• Deposit Address: %s\n• Blockchain Tx Hash: %s", 'kyc-free-crypto-gateway'),
                    $payment_id,
                    $crypto_amount,
                    $crypto_currency,
                    $amount_usd,
                    $network,
                    $derived_address,
                    $tx_hash
                );
                $order->add_order_note($note);

                // Mark payment complete (reduces stock, handles downloads)
                $order->payment_complete($tx_hash);

                // If store opted for instant Completed status (e.g. for digital goods)
                if ($this->order_status === 'completed') {
                    $order->update_status('completed', __('Order auto-completed via KYC-Free Gateway.', 'kyc-free-crypto-gateway'));
                }
            }

            wp_send_json_success(array('message' => 'Order payment recorded successfully'));
            exit;
        } elseif ($status === 'UNDERPAID') {
            $note = sprintf(
                __("⚠️ WARNING: Underpayment Detected (Invoice #%d)!\n• Received: %s %s\n• Expected USD Value: $%0.2f\n• Network: %s\n• Tx Hash: %s\nOrder placed on hold until merchant accepts underpayment or customer tops up.", 'kyc-free-crypto-gateway'),
                $payment_id,
                $crypto_amount,
                $crypto_currency,
                $amount_usd,
                $network,
                $tx_hash
            );
            $order->add_order_note($note);
            $order->update_status('on-hold', __('Underpaid crypto transaction.', 'kyc-free-crypto-gateway'));

            wp_send_json_success(array('message' => 'Underpayment registered. Order set to on-hold.'));
            exit;
        }

        // Other statuses (PENDING, EXPIRED)
        wp_send_json_success(array('message' => 'Status update acknowledged: ' . $status));
        exit;
    }
}
