---
description: 'Every exception omnipay-viamo throws and every reason a return URL or webhook is rejected, with what to do about each.'
---

# Error handling

There are two kinds of failure: **exceptions** for a misconfigured gateway or invalid input you passed in, and **rejected responses** for anything that comes from outside (a return URL or a webhook that cannot be trusted). An answer from Viamo never throws because it is malformed; it gives a response whose `isSuccessful()` is `false`.

## Exceptions

| Exception | Thrown by | When |
|---|---|---|
| `Omnipay\Viamo\Exception\InvalidKeyException` | `purchase()->send()`, `completePurchase()->send()`, `webhook()->send()`, `ViamoSign::sign()`/`verify()` | `key1` or `key2` is not 32 hexadecimal characters (also when it is empty) |
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()`, `webhook()->send()` | A required parameter is `null`: `bid`, `key1`, `key2` for `purchase()`; `bid`, `key2`, `json` for `webhook()` |
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()` | `amount` is negative or has more decimals than the currency allows (from Omnipay's amount formatting) |
| `Money\Exception\UnknownCurrencyException` | `purchase()->send()` | `currency` is not an ISO 4217 code |
| `TypeError` | setters | A value of the wrong type, for example `setVs(123)` under `strict_types` (pass strings; arrays passed to `purchase([...])` are converted) |

`InvalidKeyException` extends Omnipay's `InvalidRequestException` and implements `Omnipay\Viamo\Exception\ViamoException`, the interface every exception of this package implements. Catch `ViamoException` for "this package", `InvalidRequestException` for "this package or Omnipay's validation":

<<< @/snippets/errors.php

All of these are configuration or programming errors: fix them, do not retry. The [signatures example](../examples/signatures) triggers `InvalidKeyException` and `InvalidRequestException` and prints them.

## Rejected return URLs

`completePurchase()->send()` returns `getRes() === 'ERROR'` and the reason in `getMessage()`:

| `getMessage()` | Cause |
|---|---|
| `Missing responseString` | No `responseString` in the query, an empty one, or not a string (`?responseString[]=x`) |
| `Missing signature` | No `*SIG:` in it |
| `Wrong BID` | `BID` missing, or not exactly your `bid` (strict comparison) |
| `Wrong sign` | The signature does not match: changed, truncated, or signed with another key |
| `Missing RES`, `Missing VS` | A correctly signed response without that field |

`RES:FAIL` is not an error: it is a correctly signed failed payment (`getRes() === 'FAIL'`, `getMessage()` is `null`).

## Rejected webhooks

`webhook()->send()` returns `isSuccessful() === false` and the reason in `getMessage()` (and `errorMessage()`):

| `getMessage()` | Cause |
|---|---|
| `Missing payment data` | No `payment` key in the JSON |
| `Missing payment parameter` | `payment` is not an object, or has no `result`, `amount` or `id` |
| `Missing signature` | No `signature.sign` |
| `Wrong signature` | The signature does not match |
| `Invalid result` | Correctly signed, but `payment.result` is not `OK` |

## What to do

- **Log every rejection** with `getMessage()`; a stream of `Wrong sign` or `Wrong signature` means someone is probing your endpoints, or your keys are mixed up (`key1` vs `key2`, test vs production).
- **Never mark an order paid** on anything but `isSuccessful()`.
- **Do not show "payment failed"** for `ERROR` on the return URL: the payment may well have succeeded and the webhook will tell you. Show that the order will update.
- **Answer webhooks with 4xx** for rejected notifications, and with 2xx for `Invalid result` once you recorded the failed payment.
