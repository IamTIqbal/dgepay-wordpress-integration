# DGePay WordPress Payment Gateway (Unofficial)

⚠️ Disclaimer  
This is an unofficial community-driven integration for DGePay.  
This project is not affiliated with or endorsed by DGePay or Bangladesh Bank.

> **Developer:** [Tamim Iqbal](https://tamimiqbal.com) — IT Manager & AI Developer

A WooCommerce payment gateway plugin for the **DGePay Payment Gateway API** (Bangladesh). Supports **bKash**, **Nagad**, and other MFS (Mobile Financial Service) providers through the DGePay payment aggregator.

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

- Unofficial DGePay gateway for WooCommerce
- Classic Checkout and WooCommerce Blocks checkout supported
- Secure API communication (signature + AES encryption)
- Redirect-based payment flow with callback handling
- Compatible with the latest WordPress and WooCommerce

## Requirements

- WordPress 6.0+ (recommended)
- WooCommerce 7.0+ (recommended)
- PHP 7.4+ with cURL and OpenSSL enabled

## Installation

1. Upload the plugin folder to `/wp-content/plugins/dgepay-wordpress-integration`.
2. Activate via WordPress **Plugins** menu.
3. Go to WooCommerce → Settings → Payments and enable **DGePay**.
4. Enter your DGePay credentials:
   - Client ID
   - Client Secret
   - Client API Key
   - Base API URL (default provided)

## Quick Start

- Add products to cart and proceed to checkout.
- Select **DGePay** and place order.
- Customer is redirected to DGePay for payment.
- On return, the order is marked paid/cancelled/failed based on callback status.

## Laravel Integration

If you are looking for the Laravel/PHP SDK (not this plugin), use:

- GitHub: `IamTIqbal/dgepay-php-client`
- Composer: `tamimiqbal/dgepay-php`

## API Reference

### Authentication
Handled automatically by the embedded DGePay SDK during payment initiation.

### Initiate Payment
The plugin creates a payment request and redirects the customer to DGePay.

### Handle Callback
The callback endpoint is:

`https://your-site.com/?wc-api=dgepay_callback`

### Check Transaction Status
Available via the SDK methods in `includes/class-dgepay-sdk.php`.

### Utilities
Includes transaction ID generation and helper methods via the SDK.

## Payment Flow

1. Customer checks out → DGePay selected.
2. Plugin authenticates and creates payment.
3. Customer pays on DGePay webview.
4. DGePay redirects to callback.
5. Order status updated in WooCommerce.

## Status Codes

- `3` = Success
- `8` = Cancelled

## Gotchas & Troubleshooting

- Make sure credentials are correct (test vs production).
- Ensure SSL is enabled for callback URL.
- Keep store currency consistent with DGePay account settings.

## Security Notes

- Do not hardcode credentials in files.
- Use HTTPS for all callbacks and checkout pages.

## Testing

- Use DGePay sandbox credentials where available.
- Test Classic and Blocks checkout flows.

## License

MIT License. See LICENSE file for details.
