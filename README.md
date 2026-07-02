# KYC-Free USDT Merchant Gateway

A standalone, zero-installation checkout portal and developer integration kit to connect with a self-hosted `kyc-free-usdt-provider-gateway` instance.

---

## 1. Remote Merchant Portal (`merchant-portal.html`)

The portal is a single-file application designed to run locally on your machine or be hosted directly on your website's domain. It operates over secure HTTPS connections directly to the universal gateway backend.

### How to use:
1. Open [merchant-portal.html](merchant-portal.html) in any modern browser.
2. At the top configuration banner, make sure the **Gateway Host URL** points to your active gateway backend (defaults to `https://xto.377.live`).
3. Click **Register Remotely** to create a merchant profile by entering your username, password, payout network, and payout address.
4. Once logged in, you can manage custom site identifiers, request payouts, generate checkout links, copy API tokens, and monitor payments in real-time.

---

## 2. Checkout Integration Methods

You can route customers to the payment desk using two integration options:

### Option A: Direct Redirect Link (GET)
Redirect the client's web browser directly to the checkout route with query parameters:

```
https://xto.377.live/pay/new?merchant=test&amount=10.00&website=MyStore&ref=ORDER_123&methods=TRC20_USDT,POLYGON_USDT
```

#### GET Parameters:
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `merchant` | String | Yes | Your main username or custom profile identifier (e.g., `shop_2`). |
| `amount` | Decimal | Yes | Price of the checkout order in USD (e.g., `19.99`). |
| `website` | String | Yes | Label printed on checkout desk header (e.g., `MyStore`). |
| `ref` | String | Yes | Unique reference identifier from your ecommerce database (e.g. `ORDER_10293`). |
| `methods` | CSV String | No | Allowed crypto networks. If left empty, all active networks are offered. Options: `TRC20_USDT`, `POLYGON_USDT`, `BSC_USDT`, `ERC20_USDT`, `POLYGON_USDC`, `BSC_USDC`, `ERC20_USDC`. |

---

### Option B: Programmatic API Creation (POST)
For deep backend integrations, generate checkout invoices dynamically using your JWT merchant token.

**Request Endpoint:** `POST https://xto.377.live/api/merchant/payment-links`  
**Headers:**
* `Content-Type: application/json`
* `Authorization: Bearer YOUR_MERCHANT_JWT_TOKEN`

**Request Body:**
```json
{
  "amount_usd": 10.00,
  "website_name": "MyStore",
  "merchant_ref": "ORDER_123",
  "allowed_methods": [
    "TRC20_USDT",
    "POLYGON_USDT",
    "BSC_USDT"
  ]
}
```

**Response Payload (`201 Created`):**
```json
{
  "success": true,
  "payment_link_id": 42,
  "checkout_url": "https://xto.377.live/checkout/42",
  "amount_usd": 10.00,
  "merchant_ref": "ORDER_123"
}
```

---

## 3. Webhook Delivery

When a customer pays, the gateway backend dispatches an HTTP POST payload to the webhook endpoint configured in your dashboard or custom identifier settings.

**Request Payload:**
```json
{
  "event": "payment.success",
  "payment_link_id": 42,
  "merchant_identifier": "test",
  "merchant_ref": "ORDER_123",
  "amount_usd": 10.00,
  "crypto_amount": "10.0000",
  "crypto_currency": "USDT",
  "network": "POLYGON",
  "tx_hash": "0x4b7f83b...",
  "status": "PAID"
}
```

> [!NOTE]
> Webhooks will retry up to 3 times with exponential backoff if your server responds with a status code outside the 2xx range. Your endpoint must return a `200 OK` or `204 No Content` to acknowledge successful receipt.
