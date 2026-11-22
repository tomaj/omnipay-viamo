# FAQ

## Is `isSuccessful()` enough to mark an order paid?

It proves that Viamo sent a successful result for your merchant. Before you mark the order paid, find it by `getVs()`, check that the amount and currency match and that it is not paid yet. The webhook signature also covers the amount; the return URL does not carry it.

## Do I need both the return URL and the webhook?

Use the webhook as the source of truth: it arrives even when the customer closes the app before returning to your shop. The return URL is for showing the result to the customer. Handle both idempotently.

## `getVs()` of `completePurchase()` returned `null` in 4.x

That was a bug ([#20](https://github.com/tomaj/omnipay-viamo/issues/20)); 5.0 returns the variable symbol. `getData()` now has it under `VS`, not `vs`.

## Why does the gateway need an HTTP client?

It does not make HTTP requests, but every Omnipay gateway is created with one. The package requires `symfony/http-client` and `nyholm/psr7` so `Omnipay::create('Viamo')` works without further setup. You can pass your own `Omnipay\Common\Http\ClientInterface` to the `Gateway` constructor.

## How do I test my integration without the Viamo app?

Sign a `responseString` or a webhook body with `ViamoSign` and your test key, then call your endpoint with it. See [Test mode](./guide/test-mode#testing-locally).

## Which PHP versions are supported?

PHP 8.4, 8.5 and 8.6, tested in CI (also with the lowest allowed dependencies). PHP 8.7 is tested as a nightly build.
