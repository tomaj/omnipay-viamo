# Operations

The gateway has three operations. Each returns a request; `send()` returns its response.

| Operation | Request | Response | Signed with |
|---|---|---|---|
| `purchase(array $parameters)` | `PurchaseRequest` | `PurchaseResponse` | `key1` |
| `completePurchase(array $parameters = [])` | `CompletePurchaseRequest` | `CompletePurchaseResponse` | `key1` |
| `webhook(array $parameters)` | `WebhookRequest` | `WebhookResponse` | `key2` |

## purchase()

Builds the Viamo payment request string, signs it, and renders it:

<<< @/snippets/purchase.php

The request string is a list of `KEY:value` fields separated by `*`, ending with the signature of everything before it:

```text
QP:1.0*BID:42*AM:10.00*CC:EUR*VS:777*MSG:Order 777*SIG:4EBE348B1F40E5598DBF0C109174E6A0
```

Fields appear in this order and only when set: `QP` (always `1.0`), `BID`, `AM`, `CC`, `VS`, `CS`, `SS`, `RPT`, `MSG`. The app link adds `RURL` before `SIG` and is signed separately.

`PurchaseResponse` is not a redirect (`isRedirect()` is `false`, `isSuccessful()` is `true`): show the QR code or the link, then wait for the return URL or the webhook.

| Getter | `getData()` key | Value |
|---|---|---|
| `getQrData()` | `qr_data` | The QR code of the request string as a base64-encoded PNG (300 px plus margin). |
| `getQrUrl()` | `qr_url` | `https://qr.viamo.sk/api/?text=<request string>&template=300`: Viamo's QR API renders the same QR code. |
| `getAppLink()` | `app_link` | `viamo://?requestString=<request string with RURL>`; `viamo-staging://` in test mode. |
| `getVs()` | `vs` | The variable symbol of the request, or `null`. |
| — | `template` | `300` |

## completePurchase()

Viamo returns the customer to `rurl` with a `responseString` query parameter:

```text
https://shop.example/viamo/return?responseString=BID:42*VS:777*RES:OK*SIG:04B15D742A42AD489F05F2174C32DBBD
```

`completePurchase()` reads it from the gateway's HTTP request (`Request::createFromGlobals()` unless you pass your own Symfony `Request` to the gateway constructor), checks that `BID` is yours and that `SIG` is the signature of everything before the last `*SIG:` with `key1`:

<<< @/snippets/complete-purchase.php

| Getter | Value |
|---|---|
| `isSuccessful()` | `true` only for a verified response with `RES:OK` |
| `getRes()` | `OK` or `FAIL` from Viamo, or `ERROR` when the response could not be verified |
| `getVs()` | The variable symbol from the response (also on errors, when present; do not trust it unless `isSuccessful()`) |
| `getMessage()` | Why the response was rejected, `null` otherwise |

`getData()` is `['RES' => …, 'VS' => …]`, plus `message` for `ERROR`. The messages:

| `getMessage()` | Cause |
|---|---|
| `Missing responseString` | No (or an empty, or a non-string) `responseString` in the query |
| `Missing signature` | No `*SIG:` field |
| `Wrong BID` | `BID` missing or not the configured `bid` |
| `Wrong sign` | The signature does not match (tampered, truncated, other key) |
| `Missing RES` / `Missing VS` | A correctly signed response without the field |

## webhook()

Viamo posts a JSON notification to your webhook URL. Pass the decoded body as `json`:

<<< @/snippets/webhook.php

The notification looks like this (`rid`, `vs` and `e2e` are optional):

```json
{
  "notificationId": "N1",
  "payment": { "rid": "R1", "vs": "777", "e2e": "E2E", "result": "OK", "amount": "10.00", "id": 555 },
  "signature": { "sign": "D07E3492E35D34AC385D161F31B8100D" }
}
```

The signature covers `rid`, `vs`, `e2e` (each only when not empty), `result`, `amount` and `id`, concatenated without separators, signed with `key2`. See [Signatures](./signatures).

| Getter | Value |
|---|---|
| `isSuccessful()` | `true` only for a correctly signed notification with `result` `OK` |
| `getVs()` | Variable symbol, or `null` |
| `getPaymentId()` | Viamo payment id as a string, or `null` |
| `getNotificationId()` | `notificationId` as a string, or `null` |
| `errorMessage()`, `getMessage()` | Why the notification was rejected, `null` otherwise |

`getData()` is `['success' => bool, 'vs' => …, 'payment_id' => …, 'notification_id' => …]` plus `error`. The errors: `Missing payment data`, `Missing payment parameter` (`result`, `amount` or `id`), `Missing signature`, `Wrong signature`, `Invalid result` (correctly signed, but `result` is not `OK`).
