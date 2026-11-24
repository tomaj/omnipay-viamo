---
description: 'Every gateway parameter (bid, key1, key2, testMode) and every payment parameter of omnipay-viamo, with types, defaults and the request field each one becomes.'
---

# Configuration

## Gateway parameters

Set them once with `initialize()` (or the setters). Every request the gateway creates starts with the gateway's parameters, and the array you pass to `purchase([...])`, `completePurchase([...])` or `webhook([...])` overrides any of them for that request.

| Parameter | Type | Default | Setter | Meaning |
|---|---|---|---|---|
| `bid` | `?string` | `''` | `setBid()` | Merchant (beneficiary) identifier assigned by Viamo. Sent as `BID` in the payment request; `completePurchase()` rejects a response whose `BID` is not exactly this value. |
| `key1` | `?string` | `''` | `setKey1()` | 128-bit key as 32 hexadecimal characters (either case). Signs the payment request and verifies the return URL. |
| `key2` | `?string` | `''` | `setKey2()` | 128-bit key as 32 hexadecimal characters. Verifies the webhook. |
| `testMode` | `bool` | `false` | `setTestMode()` | `true` makes `purchase()` return a `viamo-staging://` app link for Viamo's staging app. See [Test mode](./test-mode). |

<<< @/snippets/gateway.php

Keep the keys out of the repository: read them from the environment or a secret store.

### Missing and invalid values

- A key that is not exactly 32 hexadecimal characters (including an empty one) throws `Omnipay\Viamo\Exception\InvalidKeyException` when it is used to sign or verify: in `purchase()->send()` for `key1`, in `completePurchase()->send()` for `key1` once the response has the right `BID`, and in `webhook()->send()` for `key2` once the notification has all fields.
- `purchase()` and `webhook()` call Omnipay's `validate()`, which throws `InvalidRequestException` ("The bid parameter is required") only for a parameter that is `null`. A gateway created with `Omnipay::create()` starts with `''` for `bid`, `key1` and `key2`, so through the gateway the check only fires for `webhook()` without `json`. Configure `bid` explicitly: `purchase()` does not reject an empty one.

<<< @/snippets/errors.php

The [Error handling](./errors) page lists every exception and every rejection reason.

## Payment parameters

Passed to `purchase([...])`. Every one also has a setter and a getter on `PurchaseRequest` (`setVs()`/`getVs()` and so on). Optional fields are sent only when they are not empty; the string `'0'` counts as empty, like PHP's truthiness.

| Parameter | Type | Request field | Meaning |
|---|---|---|---|
| `amount` | `string` (or a number) | `AM` | The amount, formatted by Omnipay for the currency: `'10'` becomes `10.00` for EUR and stays `5` for JPY. More decimals than the currency allows, or a negative amount, throws `InvalidRequestException`. |
| `currency` | `string` | `CC` | ISO 4217 code, for example `'EUR'`. Optional. An unknown code throws `Money\Exception\UnknownCurrencyException` from `moneyphp/money`. |
| `vs` | `?string` | `VS` | Variable symbol: identifies the order in the webhook and on the return URL. Use your order number. |
| `cs` | `?string` | `CS` | Constant symbol. Optional. |
| `ss` | `?string` | `SS` | Specific symbol. Optional. |
| `msg` | `?string` | `MSG` | Message for the payer, shown in the app. Optional. |
| `rpt` | `int\|string\|null` | `RPT` | Sent as an integer: `'5x'` becomes `5`, `'0'` is not sent. Optional. |
| `rurl` | `?string` | `RURL` | Return URL. Sent only in the app link, after the other fields; every `*` in it is written as `%2A`, because `*` separates the fields. |
| `dt` | `?string` | | Stored on the request (`getDt()`); the Viamo request string has no field for it. |

::: warning Field values must not contain `*`
The request string is `KEY:value` pairs joined with `*`. Only `rurl` is escaped. Keep `vs`, `cs`, `ss` and `msg` to values your shop controls (an order number, `'Order 2026000123'`), never text a customer typed.
:::

The fields appear in the request string in this order, each only when set: `QP` (always `1.0`), `BID`, `AM`, `CC`, `VS`, `CS`, `SS`, `RPT`, `MSG`, then `SIG`. The app link adds `RURL` before `SIG` and is signed separately.

## Parameters of the other operations

- `completePurchase()` takes no payment parameters. It reads `responseString` from the query of the HTTP request the gateway was created with: `Request::createFromGlobals()` when you use `Omnipay::create('Viamo')`, or the Symfony `Request` you pass as the second constructor argument (`new Gateway($httpClient, $request)`).
- `webhook()` takes `json`: the decoded JSON body of the notification, as an array (`setJson(?array)`). It is required.
