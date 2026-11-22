# Configuration

## Gateway parameters

Set them with `initialize()` (or the setters) once; every request created by the gateway inherits them, and a request may override any of them.

| Parameter | Setter | Description |
|---|---|---|
| `bid` | `setBid(?string)` | Merchant (beneficiary) identifier assigned by Viamo. `completePurchase()` rejects a response with another `BID`. |
| `key1` | `setKey1(?string)` | 128-bit key as 32 hexadecimal characters. Signs the payment request and verifies the return URL. |
| `key2` | `setKey2(?string)` | 128-bit key as 32 hexadecimal characters. Verifies the webhook. |
| `testMode` | `setTestMode(bool)` | `true` creates `viamo-staging://` app links for Viamo's staging app. Default `false`. See [Test mode](./test-mode). |

<<< @/snippets/gateway.php

`purchase()` and `completePurchase()` require `bid`, `key1` and `key2`; `webhook()` requires `bid`, `key2` and `json`. A missing parameter throws Omnipay's `InvalidRequestException`. A key that is not exactly 32 hexadecimal characters throws `Omnipay\Viamo\Exception\InvalidKeyException` when it is used:

<<< @/snippets/errors.php

Keep the keys out of the repository: read them from the environment or a secret store.

## Payment parameters

Passed to `purchase([...])`. Optional fields are sent only when they are not empty (`'0'` counts as empty).

| Parameter | Request field | Description |
|---|---|---|
| `amount` | `AM` | Amount as a decimal string, for example `'10.00'`. Formatted by Omnipay for the currency. |
| `currency` | `CC` | ISO 4217 code, for example `'EUR'`. Optional. |
| `vs` | `VS` | Variable symbol: identifies the order in the return URL and the webhook. |
| `cs` | `CS` | Constant symbol. Optional. |
| `ss` | `SS` | Specific symbol. Optional. |
| `msg` | `MSG` | Message for the payer. Optional. |
| `rpt` | `RPT` | Sent as an integer (`setRpt()` accepts `int` or `string`). Optional. |
| `rurl` | `RURL` | Return URL. Sent only in the app link (every `*` is escaped as `%2A`, because `*` separates the fields). |
| `dt` | — | Stored on the request (`getDt()`); the Viamo request string has no field for it. |

The return URL and webhook requests take no payment parameters: `completePurchase()` reads the query of the current HTTP request, `webhook()` takes the decoded JSON body as `json`.
