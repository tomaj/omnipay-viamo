# Getting started

omnipay-viamo is an [Omnipay](https://omnipay.thephpleague.com/) gateway for [Viamo](https://www.viamo.sk/), a Slovak mobile payment app. The customer scans a QR code (or taps a link on the phone), confirms the payment in the Viamo app, and Viamo reports the result to your return URL and to your webhook.

## Requirements

- PHP 8.4 or newer
- `ext-openssl` and `ext-hash` (signatures), `ext-gd` (the QR code PNG)
- A Viamo merchant account: the merchant identifier (`bid`) and two 128-bit keys (`key1`, `key2`) as 32 hexadecimal characters

Composer installs the rest: `omnipay/common`, `endroid/qr-code` for the QR code, and `symfony/http-client` with `nyholm/psr7` as the HTTP client Omnipay's gateways are created with (this gateway itself makes no HTTP requests).

## Installation

```bash
composer require tomaj/omnipay-viamo
```

## A payment from start to end

**1. Create the gateway** once, with the credentials from Viamo (see [Configuration](./configuration)):

<<< @/snippets/gateway.php

**2. Show the payment.** `purchase()` signs the payment request and returns it as a QR code and an app link:

<<< @/snippets/purchase.php

**3. Handle the return URL.** After the payment, the Viamo app sends the customer to `rurl` with a signed `responseString` in the query. `completePurchase()` verifies it:

<<< @/snippets/complete-purchase.php

**4. Handle the webhook.** Viamo also notifies your server with a signed JSON body; it arrives even when the customer never comes back to the return URL:

<<< @/snippets/webhook.php

::: warning Always verify, then compare with your order
A payment is paid only when `isSuccessful()` returns `true`. The signature proves the message comes from Viamo; it does not know your order. Look the order up by its variable symbol (`getVs()`), compare the amount and state with your own records, and make the handling idempotent: the return URL and the webhook can both arrive, and webhooks can repeat.
:::

## Next steps

- [Configuration](./configuration): every gateway and payment parameter
- [Operations](./operations): what each operation returns
- [Test mode](./test-mode) and [Signatures](./signatures)
- [API reference](../api/reference)
