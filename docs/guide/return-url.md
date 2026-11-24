---
description: 'completePurchase() verifies the signed responseString that the Viamo app appends to your return URL.'
---

# completePurchase(): return URL

After the payment the Viamo app sends the customer to the return URL (`rurl` of the [app link](./purchase)) with a `responseString` query parameter:

```text
https://shop.example/viamo/return?responseString=BID:42*VS:2026000123*RES:OK*SIG:88F0AC8F3D9B94BB1C4241EDC85D4E40
```

`completePurchase()` reads it from the gateway's HTTP request (`Request::createFromGlobals()` unless you passed your own Symfony `Request` to the `Gateway` constructor), and verifies it:

<<< @/snippets/complete-purchase.php

## What is checked, in order

1. `responseString` is present, a string and not empty, otherwise `Missing responseString`.
2. It contains `*SIG:`, otherwise `Missing signature`. The signature is everything after the **last** `*SIG:`; the signed part is everything before it.
3. The fields are parsed from `KEY:value` pairs separated by `*` (a value may contain `:`; parts without `:` are ignored). `BID` must be exactly your `bid`, otherwise `Wrong BID`.
4. The signature must match the signed part with `key1` (constant-time, case-insensitive), otherwise `Wrong sign`.
5. `RES` and `VS` must be present, otherwise `Missing RES` or `Missing VS`.

Only then is `RES` taken from the response: `OK` or `FAIL`.

## The response

| Getter | Value |
|---|---|
| `isSuccessful()` | `true` only for a verified response with `RES:OK` |
| `getRes()` | `OK` or `FAIL` from Viamo, or `ERROR` when the response could not be verified |
| `getVs()` | The variable symbol. On `ERROR` it is returned when present, but it is not verified: use it for logging only |
| `getMessage()` | Why the response was rejected (see above), `null` otherwise |
| `getData()` | `['RES' => …, 'VS' => …]`, plus `message` for `ERROR` |

`getRes()` tells the three cases apart:

| `getRes()` | Meaning | What to show |
|---|---|---|
| `OK` | Viamo says the payment went through | "Thank you, your order is paid" (mark it paid if the webhook has not yet) |
| `FAIL` | A correctly signed failure: declined or cancelled | "The payment did not go through, try again" |
| `ERROR` | Not verifiable: forged, truncated, another merchant, or no `responseString` at all | Neither paid nor failed: "We will update your order once the payment is confirmed", and log `getMessage()` |

The return URL carries no amount. It proves that Viamo reported `RES:OK` for that variable symbol; the amount was fixed by the signed request you created. The [webhook](./webhook) carries the signed amount.

The [return URL example](../examples/return-url) runs a shop on the PHP built-in server and sends it paid, declined, forged and incomplete responses; its output shows the status, the page and the logged reason of each.

## Your own request object

In a framework that already has a request object, pass a Symfony `Request` with the return URL's query to the gateway:

```php
$gateway = new \Omnipay\Viamo\Gateway(null, $symfonyRequest);
```

With PSR-7, build it with `symfony/psr-http-message-bridge`, or `new Request($psrRequest->getQueryParams())`.
