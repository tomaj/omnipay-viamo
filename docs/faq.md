---
description: 'Frequently asked questions about omnipay-viamo - marking orders paid, return URL vs webhook, amounts, keys, test mode, PHP versions.'
---

# FAQ

## Is `isSuccessful()` enough to mark an order paid?

It proves that Viamo sent a successful result for your merchant. Before you mark the order paid, find it by `getVs()`, check that it is not paid yet and, for the webhook, that the signed amount matches the order total. See [Check the amount](./guide/webhook#check-the-amount).

## Do I need both the return URL and the webhook?

Use the webhook as the source of truth: it arrives even when the customer closes the app before returning to your shop, and it carries the signed amount. The return URL is for showing the result to the customer. Handle both idempotently; they can arrive in any order. The [shop example](./examples/shop) does exactly this.

## Why does the return URL not tell me the amount?

Viamo's `responseString` only has `BID`, `VS` and `RES`. The amount was fixed by the payment request your shop signed, and the webhook carries the signed amount.

## Why does a payment started from the QR code not come back to my return URL?

The return URL (`RURL`) is part of the app link only; the QR code's request string has no return URL. Rely on the webhook, and let the checkout page check the order state.

## How do I get the amount from the webhook?

Call `getAmount()` after `isSuccessful()` is `true`. It returns `payment.amount` exactly as Viamo signed it (for example `24.90`), and `null` when the signature is not valid.

## What do `key1` and `key2` look like?

Each is a 128-bit AES key written as 32 hexadecimal characters, for example `000102030405060708090a0b0c0d0e0f` (that one is a test key; never use it). `key1` signs the payment request and verifies the return URL; `key2` verifies the webhook. Anything else throws `InvalidKeyException`.

## I get `Wrong sign` or `Wrong signature` for every payment

Usually the keys are swapped (`key1` vs `key2`), or test keys are used against production (or the other way round), or `bid` is not the one the keys belong to. For the return URL, check also that `responseString` reaches PHP unchanged (log `$_GET['responseString']`).

## `getVs()` of `completePurchase()` returned `null` in 4.x

That was a bug ([#20](https://github.com/tomaj/omnipay-viamo/issues/20)); 5.0 returns the variable symbol. `getData()` now has it under `VS`, not `vs`. See [Upgrading from 4.x](./upgrade).

## Does the gateway call Viamo?

No. `purchase()` signs and renders locally, `completePurchase()` and `webhook()` verify what they are given. Only the QR API URL (`getQrUrl()`) points at Viamo, if you choose to use it instead of the PNG from `getQrData()`.

## Why does the gateway need an HTTP client then?

Every Omnipay gateway is created with one. The package requires `symfony/http-client` and `nyholm/psr7` so `Omnipay::create('Viamo')` works without further setup. You can pass your own `Omnipay\Common\Http\ClientInterface` to the `Gateway` constructor.

## How do I test my integration without the Viamo app?

Sign a `responseString` or a webhook body with `ViamoSign` and your test key, then call your endpoint with it. See [Testing your integration](./guide/testing) and the [examples](./examples/).

## Is there a Viamo simulator?

Not from Viamo in this package. The [shop example](./examples/shop) contains a small fake Viamo app that checks the request signature, sends the webhook and the return URL, which is enough for automated tests. [payment-simulator](https://github.com/tomaj/payment-simulator) covers the Slovak bank gateways, not Viamo.

## Which PHP versions are supported?

PHP 8.4, 8.5 and 8.6, tested in CI (also with the lowest allowed dependencies). PHP 8.7 is tested as a nightly build.

## Is this an official Viamo package?

No. It is an open-source Omnipay gateway maintained by Tomas Majer, not affiliated with Viamo. For your merchant account, keys and Viamo's own documentation, contact Viamo.
