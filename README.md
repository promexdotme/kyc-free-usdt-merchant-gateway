# 0xGate (`https://0xgate.org`) — Zero-KYC Multi-Chain USDT & USDC Payment Gateway (Merchant Kit, WooCommerce Plugin & AI Agent MCP)

[![Official Website](https://img.shields.io/badge/Live_Gateway-0xgate.org-10b981?style=for-the-badge)](https://0xgate.org)
[![Live Demo](https://img.shields.io/badge/Try_Live_Demo-$25_Checkout-3b82f6?style=for-the-badge)](https://0xgate.org/pay/demo?amount=25.00)
[![Discord](https://img.shields.io/badge/Discord-Join_Support-5865F2?style=for-the-badge)](https://discord.gg/FEKpzP6TFy)
[![Engineered by PROMEX](https://img.shields.io/badge/Engineered_by-PROMEX-111827?style=for-the-badge)](https://promex.me)

**0xGate (`https://0xgate.org`)** is the permissionless, 100% Zero-KYC, Zero-Rejection stablecoin payment processor for **WooCommerce**, **SaaS**, **High-Risk Merchants** (IPTV, peptides, digital goods, gaming, VPNs), and **Autonomous AI Agents**.

Accept **USDT** and **USDC** across **Tron (TRC20)**, **BNB Smart Chain (BSC / BEP20)**, **Polygon**, and **Ethereum (ERC20)** in 10 seconds—with **no passport uploads, no KYB compliance freezes, and no rolling reserves**.

---

## Why Merchants Switch to 0xGate

1. **True Zero KYC Forever**: Unlike custodial "bait-and-switch" gateways (NOWPayments, Cryptomus, CoinGate, Coinbase Commerce) that let you sign up and then freeze your withdrawals demanding corporate KYB documents, 0xGate has **zero identity fields** in its database.
2. **Cross-Chain EVM Mistake Auto-Recovery**: Every invoice derives a fresh single-use BIP44 child address (`xpub`). Because Ethereum, BSC, and Polygon share the same `0x...` address derivation, if your buyer selects BSC at checkout and accidentally sends USDT or USDC on Polygon or Ethereum, **0xGate automatically detects and credits the payment**.
3. **Exchange Withdrawal Fee Slippage Tolerance**: When buyers withdraw from Binance or Coinbase and a ~$0.80 fee is deducted, 0xGate's smart slippage tolerance auto-approves the order—plus supports live partial-payment top-ups on the exact same address.
4. **Simple Two-Option Pricing**:
   * **Instant Cloud Gateway (`2.99%` flat)**: Register in 10 seconds at [`https://0xgate.org`](https://0xgate.org) with $0 monthly fees. Merchant withdrawals are executed **once daily** directly to your wallet.
   * **Sovereign Self-Hosted VPS Edition (`$999` one-time)**: Want **0% commission forever**? We build and deliver your own private 0xGate server (including a **FREE Dedicated VPS** or deployed on your own server) within **24–48 hours**, connected directly to your own hardware wallet `xpub`.

---

## What's Inside This Repository

| Directory / File | Description |
| :--- | :--- |
| [`merchant-portal.html`](merchant-portal.html) | Standalone single-file Remote Merchant Portal. Run it locally or host it anywhere to manage profiles, webhooks, checkout links, and daily payouts on `https://0xgate.org` (or your own self-hosted VPS). |
| [`woocommerce/`](woocommerce/) | Turnkey **WordPress / WooCommerce Payment Gateway Plugin** (and ready-to-upload [`kyc-free-crypto-gateway.zip`](woocommerce/kyc-free-crypto-gateway.zip)). |
| [`mcp-server/`](mcp-server/) | **Model Context Protocol (MCP) Server** for **Claude Desktop**, **Cursor**, and autonomous AI agents to register accounts, generate USDT/USDC payment links, and verify on-chain settlement. |
| [`openapi.json`](openapi.json) | **OpenAPI 3.1 Specification** for 1-click import into **OpenAI Custom GPT Actions**, LangChain, Dify, and Relevance AI. |

---

## 1. Instant Checkout Integration (Zero SDK Required)

### Option A: 1-Line Redirect Link (`GET`)
Redirect any customer or bot user directly to the checkout route:

```text
https://0xgate.org/pay/new?merchant=YOUR_USERNAME&amount=49.99&website=MyStore&ref=ORDER_123&methods=TRC20_USDT,BSC_USDT,POLYGON_USDT
```

*(Backward compatible with legacy `https://xto.377.live/pay/new` integrations).*

#### Query Parameters:
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `merchant` | String | Yes | Your registered 0xGate username or sub-profile identifier (e.g. `shop_2`). |
| `amount` | Decimal | Yes | Order amount in USD (e.g. `49.99`). |
| `website` | String | Yes | Store label displayed on the checkout header (also scopes `ref` so multi-store order IDs never collide). |
| `ref` | String | No | Unique order reference ID from your store database (e.g. `ORDER_10293`). |
| `methods` | CSV String | No | Allowed networks. If omitted, all supported networks are offered (`TRC20_USDT`, `POLYGON_USDT`, `BSC_USDT`, `ERC20_USDT`, `TRC20_USDC`, `POLYGON_USDC`, `BSC_USDC`, `ERC20_USDC`). |

---

### Option B: Programmatic API Invoice Creation (`POST`)

**Endpoint:** `POST https://0xgate.org/api/merchant/payment-links`  
**Headers:**
* `Content-Type: application/json`
* `Authorization: Bearer YOUR_MERCHANT_JWT_TOKEN`

```json
{
  "amount_usd": 49.99,
  "website_name": "MyStore",
  "merchant_ref": "ORDER_123",
  "allowed_methods": ["TRC20_USDT", "POLYGON_USDT", "BSC_USDT"]
}
```

---

## 2. Webhook Delivery

When an order is confirmed on-chain (`PAID` or `UNDERPAID`), 0xGate dispatches an `HTTP POST` webhook to your configured callback URL:

```json
{
  "event": "payment.status_updated",
  "data": {
    "id": 42,
    "website_name": "MyStore",
    "merchant_identifier": "my_store",
    "amount_usd": 49.99,
    "crypto_amount": 49.99,
    "paid_amount": 49.99,
    "crypto_currency": "USDT",
    "network": "BSC",
    "derived_address": "0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359",
    "address_index": 5,
    "status": "PAID",
    "tx_hash": "0x4b7f83b...",
    "merchant_ref": "ORDER_123",
    "created_at": "2026-10-18T00:00:00.000Z"
  }
}
```

---

## 3. WooCommerce Plugin (`/woocommerce`)

**Version 1.2.1 upgrade:** update the gateway backend first so its invoice API
returns `merchant_ref`, then install the plugin. Existing unpaid orders without
the new saved invoice binding need manual payment reconciliation; check their
previous invoices before asking customers to pay again. See the
[security upgrade notes](woocommerce/README.md#security-update-121).

1. Download [`woocommerce/kyc-free-crypto-gateway.zip`](woocommerce/kyc-free-crypto-gateway.zip).
2. In WordPress Admin, go to **Plugins > Add New > Upload Plugin** and activate it.
3. In **WooCommerce > Settings > Payments > KYC-Free Crypto Gateway**, enter your **Merchant Identifier** and copy your **Store Webhook Callback URL** into your 0xGate Dashboard.

See the [WooCommerce Plugin Documentation](woocommerce/README.md) or [https://0xgate.org/woocommerce-usdt-crypto-plugin.html](https://0xgate.org/woocommerce-usdt-crypto-plugin.html) for full details.

---

## 4. AI Agents, Claude MCP & OpenAI Custom GPT Actions

AI agents cannot upload a driver's license or pass Stripe KYB checks.
* **OpenAI Custom GPTs / LangChain**: Import `https://0xgate.org/openapi.json` (or local [`openapi.json`](openapi.json)).
* **Claude Desktop / Cursor MCP**: Use [`mcp-server/index.js`](mcp-server/index.js) to give your AI agent native tools (`oxgate_register_merchant`, `oxgate_create_payment_link`, `oxgate_check_payment_status`).
* **LLM Documentation**: [`https://0xgate.org/llms.txt`](https://0xgate.org/llms.txt)

---

## Support, Custom VPS Builds & Contact

* **Official Gateway & Instant Signup**: [https://0xgate.org](https://0xgate.org)
* **Support Email**: [cs@0xgate.org](mailto:cs@0xgate.org)
* **Official Discord**: [https://discord.gg/FEKpzP6TFy](https://discord.gg/FEKpzP6TFy)
* **Engineered by PROMEX**: [https://promex.me](https://promex.me) • [@promexdotme](https://github.com/promexdotme)
