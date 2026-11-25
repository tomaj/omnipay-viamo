---
description: 'Test your Viamo integration without the Viamo app: sign return URLs and webhooks yourself with ViamoSign and your test key.'
---

# Testing your integration

The return URL and the webhook need no connection to Viamo: Viamo's answers are plain signed strings. Sign them yourself with `Omnipay\Viamo\Core\ViamoSign` and your **test** keys, then call your endpoints with them.

## Sign a return URL

<<< @/snippets/testing-return-url.php

## Sign a webhook

The signed text is `rid` + `vs` + `e2e` (each only when not empty) + `result` + `amount` + `id`:

<<< @/snippets/testing-webhook.php

## What to test

| Case | Expected |
|---|---|
| Return URL with `RES:OK` | The order is paid; reloading the page changes nothing |
| Return URL with `RES:FAIL` | The order stays unpaid or is marked failed |
| A field changed after signing | Rejected (`Wrong sign`), the order is unchanged |
| `BID` of another merchant | Rejected (`Wrong BID`) |
| No `responseString` | Rejected, no PHP warnings |
| Webhook with `result` `OK` | The order is paid once; the same notification again is answered without a second side effect |
| Webhook with `result` `FAIL` | Recorded as failed (`Invalid result`) |
| Webhook amount changed after signing | Rejected (`Wrong signature`) |
| Correctly signed webhook with another amount than the order | Rejected by your amount check |
| Webhook for an unknown variable symbol | Rejected by your order lookup |
| Return URL and webhook in either order | The order is paid once |

The [return URL](../examples/return-url), [webhook](../examples/webhook) and [shop](../examples/shop) examples are exactly such tests: each starts the shop on the PHP built-in server, plays Viamo against it, and compares the output with a committed file. Copy one and point it at your own endpoints.

## In PHPUnit

Create the gateway with a Symfony `Request` holding the query, instead of relying on `$_GET`:

<<< @/snippets/testing-phpunit.php

## Against Viamo

Use the test credentials Viamo gives you with [test mode](./test-mode): the app link opens Viamo's staging app (`viamo-staging://`). Run one real payment end to end there before you switch to production keys.

[payment-simulator](https://github.com/tomaj/payment-simulator) simulates the Slovak bank gateways (Tatra banka, VÚB, Slovenská sporiteľňa); it does not simulate Viamo.
