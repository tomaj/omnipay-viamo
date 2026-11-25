<h1>
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="docs/public/logo-dark.svg">
    <img alt="" src="docs/public/logo.svg" width="48" height="48" align="absmiddle">
  </picture>
  Omnipay Viamo
</h1>

**Viamo mobile payments gateway for the [Omnipay](https://omnipay.thephpleague.com/) payment processing library**

**Documentation: https://tomaj.github.io/omnipay-viamo/**

[Getting started](https://tomaj.github.io/omnipay-viamo/guide/getting-started) · [Payment flow](https://tomaj.github.io/omnipay-viamo/guide/payment-flow) · [Examples](https://tomaj.github.io/omnipay-viamo/examples/) · [API reference](https://tomaj.github.io/omnipay-viamo/api/reference) · [Changelog](https://tomaj.github.io/omnipay-viamo/changelog)

[![Latest Stable Version](https://img.shields.io/packagist/v/tomaj/omnipay-viamo.svg)](https://packagist.org/packages/tomaj/omnipay-viamo)
[![CI](https://github.com/tomaj/omnipay-viamo/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/tomaj/omnipay-viamo/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/tomaj/omnipay-viamo/badge.svg?branch=master)](https://coveralls.io/github/tomaj/omnipay-viamo?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/tomaj/omnipay-viamo/badge)](https://scorecard.dev/viewer/?uri=github.com/tomaj/omnipay-viamo)
[![Documentation](https://img.shields.io/badge/docs-5.x-0f9f55.svg)](https://tomaj.github.io/omnipay-viamo/)
[![License](https://img.shields.io/packagist/l/tomaj/omnipay-viamo.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/dependency-v/tomaj/omnipay-viamo/php.svg)](https://packagist.org/packages/tomaj/omnipay-viamo)

## What is omnipay-viamo?

[Viamo](https://www.viamo.sk/) is a Slovak mobile payment app: the customer scans a QR code (or opens a link on the phone) and confirms the payment in the app. This package is an Omnipay gateway that:

- **builds the signed payment request** and returns it as a QR code (base64 PNG), a URL of Viamo's QR API, and a `viamo://` app link (`purchase()`);
- **verifies the result** Viamo appends to your return URL (`completePurchase()`);
- **verifies the webhook notification** Viamo sends to your server (`webhook()`).

Signatures are AES-128 over a SHA-256 hash with your merchant keys and are compared in constant time.

## Installation

This library requires PHP 8.4 or later with `ext-openssl`, `ext-hash` and `ext-gd`.

```bash
composer require tomaj/omnipay-viamo
```

## Usage

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('Viamo');
$gateway->initialize([
    'bid' => 'YOUR_BID',
    'key1' => getenv('VIAMO_KEY1'), // 32 hex characters: signs the request, verifies the return URL
    'key2' => getenv('VIAMO_KEY2'), // 32 hex characters: verifies the webhook
    'testMode' => false,
]);

// 1. Show the payment to the customer
$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '2026000123',
    'rurl' => 'https://shop.example/viamo/return',
])->send();

echo '<img src="data:image/png;base64,' . $response->getQrData() . '">';
echo '<a href="' . htmlspecialchars((string) $response->getAppLink()) . '">Pay in the Viamo app</a>';

// 2. On the return URL (?responseString=...)
$result = $gateway->completePurchase()->send();
if ($result->isSuccessful()) {
    markOrderPaid($result->getVs());
}

// 3. On the webhook endpoint (JSON body)
$json = json_decode((string) file_get_contents('php://input'), true);
$notification = $gateway->webhook(['json' => is_array($json) ? $json : []])->send();
if ($notification->isSuccessful()) {
    markOrderPaid($notification->getVs());
}
```

Always look up the order by its variable symbol and compare the amount with your records before you mark it paid. The [documentation](https://tomaj.github.io/omnipay-viamo/) covers every parameter, the payment flow, test mode, the signature algorithm and the API reference.

## Examples

[`examples/`](examples/) has complete recipes that run offline and are tested in CI against their expected output: a [QR checkout](https://tomaj.github.io/omnipay-viamo/examples/qr-checkout), a [return URL](https://tomaj.github.io/omnipay-viamo/examples/return-url) and a [webhook](https://tomaj.github.io/omnipay-viamo/examples/webhook) endpoint, a [whole shop paid by a fake Viamo app](https://tomaj.github.io/omnipay-viamo/examples/shop), and [signatures checked with openssl](https://tomaj.github.io/omnipay-viamo/examples/signatures).

## Upgrading

5.0 requires PHP 8.4 and adds native types; see [UPGRADE-5.0.md](UPGRADE-5.0.md).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) and the [Code of Conduct](CODE_OF_CONDUCT.md).

## Security

Do not report security vulnerabilities in public issues. See [SECURITY.md](SECURITY.md) for private reporting.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
