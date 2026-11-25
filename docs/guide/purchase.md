---
description: 'purchase() signs the Viamo payment request and returns it as a QR code PNG, a QR API URL and a viamo:// app link.'
---

# purchase(): QR code and app link

`purchase()` builds the Viamo payment request string, signs it with `key1`, and renders it three ways. Nothing is sent to Viamo: you show the result to the customer and wait for the [webhook](./webhook) and the [return URL](./return-url).

<<< @/snippets/purchase.php

## Parameters

| Parameter | Request field | Required | Notes |
|---|---|---|---|
| `amount` | `AM` | yes | Formatted by Omnipay for the currency |
| `currency` | `CC` | no | ISO 4217 |
| `vs` | `VS` | in practice yes | Your order number; the only link between Viamo's answers and your order. Up to 10 digits |
| `cs`, `ss` | `CS`, `SS` | no | Constant and specific symbol. Up to 10 digits |
| `msg` | `MSG` | no | Shown to the payer. Must not contain `*` |
| `rpt` | `RPT` | no | Sent as an integer |
| `rurl` | `RURL` | for the return URL | Only in the app link |
| `dt` | | no | Stored only |

The [Configuration](./configuration#payment-parameters) page has the types, the formatting rules and the exceptions.

## The request string

The request string is a list of `KEY:value` fields joined with `*`, ending with the signature of everything before it:

```text
QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*SIG:5B0A9AE6F98801683C4D63687D2B3FD8
```

Fields appear in this order and only when set: `QP` (always `1.0`), `BID`, `AM`, `CC`, `VS`, `CS`, `SS`, `RPT`, `MSG`. `SIG` is the [signature](./signatures) of the part before `*SIG:` with `key1`. A value that contains `*` is refused with `InvalidRequestException` (only `rurl` is escaped), so a message cannot add fields such as a second `AM:`.

The app link is built from the same fields plus `RURL` (the return URL, with every `*` written as `%2A`), and is signed separately:

```text
QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*RURL:https://shop.example/viamo/return?order=2026000123*SIG:DF2A817C8B1A274AD04619AF4B6897B4
```

The QR API URL and the app link carry these strings URL-encoded (`rawurlencode()`), so spaces, `&`, `#` or `%` in `msg` or `rurl` reach Viamo unchanged; decoding the query parameter gives back exactly the signed string:

```text
viamo://?requestString=QP%3A1.0%2ABID%3A42%2AAM%3A24.90%2ACC%3AEUR%2AVS%3A2026000123%2AMSG%3AOrder%202026000123%2ARURL%3Ahttps%3A%2F%2Fshop.example%2Fviamo%2Freturn%3Forder%3D2026000123%2ASIG%3ADF2A817C8B1A274AD04619AF4B6897B4
```

All three lines come from the [QR checkout example](../examples/qr-checkout), whose CI run recomputes the two signatures with `openssl`.

## The response

`PurchaseResponse` is not a redirect: `isRedirect()` is `false` and `isSuccessful()` is always `true` (it means "signed", not "paid").

| Getter | `getData()` key | Value |
|---|---|---|
| `getQrData()` | `qr_data` | The QR code of the request string as a base64-encoded PNG, 300 px plus a 10 px margin (320 × 320). Use it as `data:image/png;base64,…`. |
| `getQrUrl()` | `qr_url` | `https://qr.viamo.sk/api/?text=<URL-encoded request string>&template=300`: Viamo's QR API renders the same code. |
| `getAppLink()` | `app_link` | `viamo://?requestString=<URL-encoded request string with RURL>`, or `viamo-staging://` in [test mode](./test-mode). |
| `getVs()` | `vs` | The variable symbol of the request, or `null`. |
| | `template` | `300` (`PurchaseRequest::QR_TEMPLATE`) |

## Desktop or phone

On a computer, show the QR code: the customer scans it with the Viamo app. On a phone, a QR code on the same screen cannot be scanned, so show the app link as a button. Only the app link carries `rurl`: the QR code's request string has no return URL, so a payment started from the QR code reaches your shop through the [webhook](./webhook). Let the checkout page check the order state (reload or poll) to show the result.

A simple choice is to show both, as the [shop example](../examples/shop) does, or to pick one by the user agent.

## When the customer pays later

The QR code contains no expiry. If an order can be cancelled or its total can change, check the order state when the webhook arrives (the webhook carries the signed amount), not only when the QR code is shown.
