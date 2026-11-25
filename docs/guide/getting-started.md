---
description: 'Install omnipay-viamo, create the gateway, show a Viamo QR code, and verify the return URL and the webhook.'
---

# Getting started

omnipay-viamo is an [Omnipay](https://omnipay.thephpleague.com/) gateway for [Viamo](https://www.viamo.sk/), a Slovak mobile payment app. Your shop signs the payment and shows it as a QR code (or a link on a phone), the customer confirms it in the Viamo app, and Viamo reports the result twice: to your **webhook** and on your **return URL**. This package signs the request and verifies both answers. It makes no HTTP requests itself.

## Requirements

- PHP 8.4 or newer
- `ext-openssl` and `ext-hash` (signatures), `ext-gd` (the QR code PNG)
- A Viamo merchant account: the merchant identifier (`bid`) and two 128-bit keys (`key1`, `key2`), each written as 32 hexadecimal characters

Composer installs the rest: `omnipay/common`, `endroid/qr-code` for the QR code, and `symfony/http-client` with `nyholm/psr7` as the HTTP client every Omnipay gateway is constructed with.

## Installation

```bash
composer require tomaj/omnipay-viamo
```

## A payment from start to end

### 1. Create the gateway

Create it once, with the credentials from Viamo (every option is on the [Configuration](./configuration) page):

<<< @/snippets/gateway.php

### 2. Show the payment

`purchase()` signs the payment request with `key1` and returns it as a QR code and an app link. Show the QR code on a computer and the link on a phone:

<<< @/snippets/purchase.php

The [QR checkout example](../examples/qr-checkout) runs exactly this and prints what it produced; the request string inside the QR code looks like this:

```text
QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*SIG:5B0A9AE6F98801683C4D63687D2B3FD8
```

### 3. Handle the webhook

When the customer has paid, Viamo POSTs a signed JSON notification to your webhook URL. It arrives even when the customer never comes back to your shop, so treat it as the source of truth:

<<< @/snippets/webhook.php

### 4. Handle the return URL

The Viamo app sends the customer back to `rurl` with a signed `responseString` in the query. `completePurchase()` verifies it:

<<< @/snippets/complete-purchase.php

Run against a signed return URL, the snippet prints:

```text
Payment for order 777 received.
```

and for a tampered one (the signature no longer matches):

```text
Payment failed: Wrong sign
```

::: warning Always verify, then compare with your order
A payment is paid only when `isSuccessful()` returns `true`. The signature proves the message comes from Viamo; it does not know your order. Look the order up by its variable symbol (`getVs()`), compare the amount and state with your own records, and make the handling idempotent: the return URL and the webhook can both arrive, in any order, and webhooks can repeat. The [shop example](../examples/shop) does all of this.
:::

## Next steps

- [Payment flow](./payment-flow): who sends what, signed with which key
- [Configuration](./configuration): every gateway and payment parameter
- [purchase()](./purchase), [completePurchase()](./return-url), [webhook()](./webhook): each operation in detail
- [Examples](../examples/): five complete recipes that run offline
- [Testing your integration](./testing) without the Viamo app
