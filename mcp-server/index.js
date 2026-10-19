#!/usr/bin/env node
/**
 * 0xGate Model Context Protocol (MCP) Server
 * Zero-dependency stdio JSON-RPC 2.0 MCP Server for Claude Desktop, Cursor, and Autonomous AI Agents.
 * Connects directly to https://0xgate.org (or custom OXGATE_URL).
 */

const readline = require('readline');

const OXGATE_URL = (process.env.OXGATE_URL || 'https://0xgate.org').replace(/\/$/, '');

const TOOLS = [
  {
    name: 'register_zero_kyc_merchant',
    description: 'Register an anonymous Zero-KYC merchant account on 0xGate in 1 API call. Requires only username, password, network (TRC20/BSC/POLYGON/ERC20), and payout wallet address.',
    inputSchema: {
      type: 'object',
      required: ['username', 'password', 'payout_network', 'payout_address'],
      properties: {
        username: { type: 'string', description: 'Unique merchant identifier (e.g. my_ai_store)' },
        password: { type: 'string', description: 'Merchant password (min 8 characters)' },
        payout_network: { type: 'string', enum: ['TRC20', 'BSC', 'POLYGON', 'ERC20'] },
        payout_address: { type: 'string', description: 'Destination wallet address (0x... or T...)' },
        webhook_url: { type: 'string', description: 'Optional webhook URL for PAID notifications' }
      }
    }
  },
  {
    name: 'create_crypto_payment_link',
    description: 'Generate a live multi-chain USDT/USDC checkout URL and single-use BIP44 deposit address for a customer or AI agent transaction.',
    inputSchema: {
      type: 'object',
      required: ['merchant', 'amount'],
      properties: {
        merchant: { type: 'string', description: 'Merchant username or profile identifier' },
        amount: { type: 'number', description: 'Amount in USD (e.g. 25.00)' },
        website: { type: 'string', description: 'Store or AI service name shown on checkout' },
        ref: { type: 'string', description: 'Optional unique order reference ID' },
        network: { type: 'string', enum: ['TRC20', 'BSC', 'POLYGON', 'ERC20'], description: 'Optional chain to lock immediately' },
        currency: { type: 'string', enum: ['USDT', 'USDC'], description: 'Optional stablecoin to lock immediately' }
      }
    }
  },
  {
    name: 'check_crypto_invoice_status',
    description: 'Check real-time on-chain payment status (PENDING, PAID, EXPIRED, UNDERPAID), deposit address, and transaction hash for a 0xGate invoice ID.',
    inputSchema: {
      type: 'object',
      required: ['invoice_id'],
      properties: {
        invoice_id: { type: 'number', description: 'Numeric invoice ID (e.g. 64)' }
      }
    }
  }
];

async function handleToolCall(name, args) {
  if (name === 'register_zero_kyc_merchant') {
    const res = await fetch(`${OXGATE_URL}/api/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username: args.username,
        password: args.password,
        payout_type: 'TOKEN',
        payout_network: args.payout_network,
        payout_address: args.payout_address,
        webhook_url: args.webhook_url || undefined
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Registration failed');
    return {
      status: 'registered',
      merchant_username: args.username,
      checkout_url_template: `${OXGATE_URL}/pay/new?merchant=${encodeURIComponent(args.username)}&amount=25.00`,
      details: data
    };
  }

  if (name === 'create_crypto_payment_link') {
    const params = new URLSearchParams({
      merchant: args.merchant,
      amount: String(args.amount)
    });
    if (args.website) params.set('website', args.website);
    if (args.ref) params.set('ref', args.ref);
    if (args.network) params.set('network', args.network);
    if (args.currency) params.set('currency', args.currency);

    const createUrl = `${OXGATE_URL}/pay/new?${params.toString()}`;
    const res = await fetch(createUrl, { redirect: 'manual' });
    const location = res.headers.get('location');

    if (location && location.includes('/pay/')) {
      const invoiceId = location.split('/pay/').pop();
      const statusRes = await fetch(`${OXGATE_URL}/api/public/pay/${invoiceId}`);
      const invoiceDetails = await statusRes.json();
      return {
        invoice_id: Number(invoiceId),
        checkout_url: `${OXGATE_URL}/pay/${invoiceId}`,
        direct_redirect_url: createUrl,
        invoice: invoiceDetails
      };
    }
    return { direct_redirect_url: createUrl };
  }

  if (name === 'check_crypto_invoice_status') {
    const res = await fetch(`${OXGATE_URL}/api/public/pay/${encodeURIComponent(args.invoice_id)}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Invoice not found');
    return data;
  }

  throw new Error(`Unknown tool: ${name}`);
}

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
  terminal: false
});

function sendResponse(id, result) {
  process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id, result }) + '\n');
}

function sendError(id, code, message) {
  process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id, error: { code, message } }) + '\n');
}

rl.on('line', async (line) => {
  if (!line.trim()) return;
  let msg;
  try {
    msg = JSON.parse(line);
  } catch (e) {
    return;
  }

  const { id, method, params } = msg;

  try {
    if (method === 'initialize') {
      sendResponse(id, {
        protocolVersion: '2024-11-05',
        capabilities: { tools: {} },
        serverInfo: { name: '0xgate-mcp-server', version: '1.0.0' }
      });
    } else if (method === 'notifications/initialized') {
      // No response required for notifications
    } else if (method === 'tools/list') {
      sendResponse(id, { tools: TOOLS });
    } else if (method === 'tools/call') {
      const output = await handleToolCall(params.name, params.arguments || {});
      sendResponse(id, {
        content: [{ type: 'text', text: JSON.stringify(output, null, 2) }]
      });
    } else if (id !== undefined) {
      sendError(id, -32601, `Method not found: ${method}`);
    }
  } catch (err) {
    if (id !== undefined) {
      sendResponse(id, {
        isError: true,
        content: [{ type: 'text', text: `Error: ${err.message}` }]
      });
    }
  }
});
