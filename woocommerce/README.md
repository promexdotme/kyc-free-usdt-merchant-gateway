# 0xGate — KYC-Free Crypto Payment Gateway for WooCommerce

## Security update 1.2.1

Deploy the gateway API update that returns `merchant_ref` from `/api/public/pay/:id`
before installing this plugin update. New checkouts create their invoice on the
server and save the invoice ID, random reference, merchant and gateway URL on the
WooCommerce order before redirecting the buyer. Notifications must match those
saved values; reusing an invoice for another order is rejected.

Existing unpaid orders without this binding require manual payment reconciliation.
Do not ask a customer to pay again before checking their old invoice. The plugin
deliberately does not automatically trust legacy callbacks. Repeated notifications
for an already paid, correctly bound order are harmless.

Webhook URLs must be public HTTP(S) endpoints that respond directly with a 2xx
status; redirects, private networks and environment-proxy routing are disabled
by the gateway delivery code. Update redirected callback URLs to their final URL.

A lightweight, non-custodial cryptocurrency payment gateway extension for WooCommerce powered by **[0xGate (`https://0xgate.org`)](https://0xgate.org)** and engineered by **[PROMEX (`promex.me`)](https://promex.me)**.

Accept **USDT** and **USDC** payments directly on **Tron (TRC20)**, **Polygon**, **BNB Smart Chain (BEP20)**, and **Ethereum (ERC20)** with **zero KYC**, zero compliance freezes, automatic wrong-chain EVM recovery, and automated webhook order fulfillment.

---

## Features

* **True Zero KYC Forever**: Register your merchant identifier at [`https://0xgate.org`](https://0xgate.org) in 10 seconds with only a username and your payout wallet address. No passports, no KYB docs, no rolling reserves.
* **Multi-Chain Stablecoin Support**:
  * **USDT** (`TRC20`, `BSC BEP20`, `Polygon`, `Ethereum ERC20`)
  * **USDC** (`TRC20`, `BSC BEP20`, `Polygon`, `Ethereum ERC20`)
* **Zero-Rejection On-Chain Engine**:
  * **Cross-Chain EVM Mistake Recovery**: If a buyer selects BSC at checkout and accidentally sends USDT or USDC on Polygon or Ethereum, 0xGate automatically detects the deposit on the same derived `0x...` address and marks the order `PAID`.
  * **Exchange Withdrawal Fee Tolerance**: Automatically approves orders when centralized exchanges (like Binance or Coinbase) deduct a ~$0.80 network fee.
  * **1-Click Web3 Wallet Checkout**: Supports MetaMask, TrustWallet, Rabby, and Coinbase Wallet alongside QR codes.
* **Multi-Store Safe (`website` + `ref`)**: Connect unlimited WooCommerce stores to a single 0xGate account without order ID collisions.
* **Two Ways to Run**:
  * **Instant Cloud Gateway (`2.99%` flat)**: $0/month, instant setup at `https://0xgate.org`. Withdrawals are settled **once daily** after you request a payout.
  * **Sovereign Self-Hosted VPS Edition (`$999` one-time)**: 0% commission forever. Includes a **FREE Dedicated VPS** (or deploy on your own server), delivered turnkey in **24–48 hours**.

---

## Installation

### Method 1: Upload via WordPress Admin (Recommended)
1. Download [`kyc-free-crypto-gateway.zip`](kyc-free-crypto-gateway.zip) (or compress the `woocommerce` directory into a `.zip` file).
2. Log in to your WordPress Admin Dashboard.
3. Go to **Plugins > Add New > Upload Plugin**.
4. Choose `kyc-free-crypto-gateway.zip` and click **Install Now**.
5. Click **Activate Plugin**.

### Method 2: Manual FTP / SFTP Installation
1. Upload the `woocommerce` folder to your WordPress installation directory at:
   `/wp-content/plugins/kyc-free-crypto-gateway/`
2. Go to **Plugins > Installed Plugins** in WordPress and click **Activate**.

---

## Configuration (Takes 60 Seconds)

1. In WordPress Admin, navigate to **WooCommerce > Settings > Payments**.
2. Locate **KYC-Free Crypto Gateway (USDT / USDC)** and click **Manage**.
3. Configure your settings:
   * **Enable**: Check the box to activate the gateway on checkout.
   * **Title at Checkout**: e.g., `Crypto Payment (USDT / USDC - No KYC)`.
   * **Gateway Host URL**: Defaults to `https://0xgate.org` (backward compatible with `https://xto.377.live` or your own self-hosted Sovereign VPS URL).
   * **Merchant Identifier**: Enter your merchant username or custom profile identifier registered on [`https://0xgate.org`](https://0xgate.org).
   * **Allowed Payment Methods**: Select which cryptocurrency chains/tokens you want to accept.
   * **Order Status on Successful Payment**: Choose `Processing` (recommended for physical goods) or `Completed` (recommended for digital goods / IPTV / SaaS).
4. Click **Save changes**.

---

## Webhook Setup (Automatic Order Completion)

1. On the gateway settings page in WooCommerce, locate the callout box displaying:
   `📋 Your Store Webhook Callback URL`
   *(Format: `https://yourdomain.com/?wc-api=kyc_free_crypto`)*
2. Click **Copy URL**.
3. Open your **0xGate Merchant Dashboard** at [`https://0xgate.org`](https://0xgate.org) (or [`merchant-portal.html`](../merchant-portal.html)).
4. Paste this URL into the **Webhook URL** field of your main profile or custom store profile identifier and click **Save Settings**.

---

## Support & Community

* **Official Website**: [https://0xgate.org](https://0xgate.org)
* **Support Email**: [cs@0xgate.org](mailto:cs@0xgate.org)
* **Discord Community**: [https://discord.gg/FEKpzP6TFy](https://discord.gg/FEKpzP6TFy)
* **Engineered by**: [PROMEX (promex.me)](https://promex.me)
