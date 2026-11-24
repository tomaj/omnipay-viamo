---
layout: home
title: Omnipay Viamo

hero:
  name: Omnipay Viamo
  text: Viamo mobile payments for PHP
  tagline: Sign the payment as a QR code and an app link, then verify what comes back on the return URL and the webhook. An Omnipay gateway for PHP 8.4+.
  image:
    src: /illustrations/hero-light.svg
    alt: A shop shows a signed Viamo QR code, the customer pays in the Viamo app, and the shop verifies the return URL and the webhook
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Examples
      link: /examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-viamo

features:
  - icon:
      src: /icons/qr.svg
      alt: QR code
      width: 48px
      height: 48px
    title: QR code and app link
    details: purchase() signs the payment request with key1 and returns it as a base64 PNG QR code, a URL of Viamo's QR API, and a viamo:// link that opens the app on a phone.
    link: /guide/purchase
    linkText: purchase()
  - icon:
      src: /icons/verified.svg
      alt: Verified
      width: 48px
      height: 48px
    title: Verified return URL
    details: completePurchase() reads responseString from the return URL, checks that BID is yours and that the signature matches, and tells you OK, FAIL or ERROR with the reason.
    link: /guide/return-url
    linkText: completePurchase()
  - icon:
      src: /icons/webhook.svg
      alt: Webhook
      width: 48px
      height: 48px
    title: Verified webhook
    details: webhook() checks the key2 signature over rid, vs, e2e, result, amount and payment id, so a notification cannot be forged or its amount changed.
    link: /guide/webhook
    linkText: webhook()
  - icon:
      src: /icons/key.svg
      alt: Key
      width: 48px
      height: 48px
    title: Signatures you can check
    details: AES-128 over a SHA-256 hash, compared with hash_equals(). The tests and the examples recompute every signature with the openssl CLI.
    link: /guide/signatures
    linkText: How signing works
  - icon:
      src: /icons/omnipay.svg
      alt: Plug
      width: 48px
      height: 48px
    title: A plain Omnipay gateway
    details: Omnipay::create('Viamo'), configure bid, key1 and key2 once, and use the familiar request/response API. No HTTP calls to Viamo are made by the gateway.
    link: /guide/configuration
    linkText: Configuration
  - icon:
      src: /icons/offline.svg
      alt: Flask
      width: 48px
      height: 48px
    title: Examples that run offline
    details: Five complete recipes, from a QR checkout to a shop paid by a fake Viamo app on the PHP built-in server. CI runs each one and compares its output.
    link: /examples/
    linkText: Browse the examples
---

<div class="home-more">

## What it looks like

Create the gateway once, then sign a payment and show it to the customer:

<<< @/../examples/qr-checkout/run.php#purchase{php}

The QR code encodes a signed request string, and the app link carries the same fields plus your return URL. This is the real output of the [QR checkout example](/examples/qr-checkout):

```text
request string: QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*SIG:5B0A9AE6F98801683C4D63687D2B3FD8
app link: viamo://?requestString=QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*RURL:https://shop.example/viamo/return?order=2026000123*SIG:DF2A817C8B1A274AD04619AF4B6897B4
```

When the customer has paid, Viamo calls your webhook and the app returns the customer to your shop. Both carry a signature, and both are verified in one line:

```php
$paid = $gateway->webhook(['json' => $json])->send()->isSuccessful();
$paid = $gateway->completePurchase()->send()->isSuccessful();
```

## Where to go next

- [Getting started](/guide/getting-started) installs the package and takes one payment from start to end.
- [Payment flow](/guide/payment-flow) shows who talks to whom, and what each message is signed with.
- [Examples](/examples/) are complete, tested recipes: checkout, return URL, webhook, a whole shop, signatures.
- [Configuration](/guide/configuration) lists every gateway and payment parameter.
- [API reference](/api/reference) documents every public class and method.

</div>
