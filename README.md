# bKash WordPress Payment Gateway (Unofficial)

⚠️ Disclaimer  
This is an unofficial community-driven integration for bKash Tokenized Checkout.  
This project is not affiliated with or endorsed by bKash or Bangladesh Bank.

> **Developer:** [Tamim Iqbal](https://tamimiqbal.com) — IT Manager & AI Developer

A WooCommerce payment gateway plugin for **bKash Tokenized Checkout** (Bangladesh). Supports Classic and Blocks checkout.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Laravel Integration](#laravel-integration)
- [API Reference](#api-reference)
  - [Authentication](#authentication)
  - [Initiate Payment](#initiate-payment)
  - [Handle Callback](#handle-callback)
  - [Check Transaction Status](#check-transaction-status)
  - [Utilities](#utilities)
- [Payment Flow](#payment-flow)
- [Status Codes](#status-codes)
- [Gotchas & Troubleshooting](#gotchas--troubleshooting)
- [Security Notes](#security-notes)
- [Testing](#testing)
- [License](#license)

## Features

- Unofficial bKash gateway for WooCommerce
- Classic Checkout and WooCommerce Blocks checkout supported
- Secure API communication with bKash Tokenized Checkout
- Redirect-based payment flow with callback handling
- Compatible with the latest WordPress and WooCommerce

## Requirements

- WordPress 6.0+ (recommended)
- WooCommerce 7.0+ (recommended)
- PHP 7.4+ with cURL enabled

## Installation

1. Upload the plugin folder to `/wp-content/plugins/bkash-wordpress-integration`.
2. Activate via WordPress **Plugins** menu.
3. Go to WooCommerce → Settings → Payments and enable **bKash**.
4. Enter your bKash credentials:
   - Base API URL
   - Username
   - Password
   - App Key
   - App Secret

## Quick Start

- Add products to cart and proceed to checkout.
- Select **bKash** and place order.
- Customer is redirected to bKash for payment.
- On return, the order is marked paid/cancelled/failed based on callback status.

## Laravel Integration

If you are looking for a Laravel/PHP API integration (not this plugin), you can reuse the API flow from `instructorium.com/api/bkash` and adapt it for Laravel.

## API Reference

### Authentication
The plugin authenticates using bKash Tokenized Checkout credentials.

### Initiate Payment
Creates a payment and redirects the customer to bKash.

### Handle Callback
The callback endpoint is:

`https://your-site.com/?wc-api=bkash_callback`

### Check Transaction Status
Handled by execute payment during callback.

### Utilities
Includes invoice generation and payment metadata saved per order.

## Payment Flow

1. Customer checks out → bKash selected.
2. Plugin authenticates and creates payment.
3. Customer pays on bKash webview.
4. bKash redirects to callback.
5. Order status updated in WooCommerce.

## Status Codes

- `Completed` = Success

## Gotchas & Troubleshooting

- Store currency should be **BDT**.
- Ensure credentials are correct (test vs production).
- Ensure SSL is enabled for callback URL.

## Security Notes

- Do not hardcode credentials in files.
- Use HTTPS for all callbacks and checkout pages.

## Testing

- Use bKash sandbox credentials where available.
- Test Classic and Blocks checkout flows.

## License

MIT License. See LICENSE file for details.
