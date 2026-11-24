---
description: 'webhook() verifies the signed JSON notification Viamo POSTs to your server, and how to check the amount and stay idempotent.'
---

# webhook(): notification

Viamo POSTs a JSON notification to your webhook URL for the payment. Decode the body and pass it as `json`:

<<< @/snippets/webhook.php

## The notification

```json
{
  "notificationId": "N-1001",
  "payment": {
    "rid": "R-77",
    "vs": "2026000123",
    "e2e": "E2E-2026000123",
    "result": "OK",
    "amount": "24.90",
    "id": 555001
  },
  "signature": { "sign": "A88917626B50E704C2870E09B510EB03" }
}
```

| Field | Required | Signed | Meaning |
|---|---|---|---|
| `payment.result` | yes | yes | `OK` for a successful payment |
| `payment.amount` | yes | yes | The amount paid |
| `payment.id` | yes | yes | Viamo's payment id |
| `payment.rid`, `payment.vs`, `payment.e2e` | no | yes, when not empty | Request id, variable symbol, end-to-end reference |
| `signature.sign` | yes | | The signature, 32 hexadecimal characters |
| `notificationId` | no | no | Id of this notification |

The signed text is `rid`, `vs` and `e2e` (each only when present and not empty), then `result`, `amount` and `id`, concatenated without separators, signed with `key2`. For the notification above that is `R-772026000123E2E-2026000123OK24.90555001`. See [Signatures](./signatures).

## What is checked, in order

`webhook()->send()` first validates that `bid`, `key2` and `json` are not `null` (`InvalidRequestException` otherwise). Then:

| Check | `getMessage()` when it fails |
|---|---|
| `json` has a `payment` key | `Missing payment data` |
| `payment` is an array with `result`, `amount` and `id` | `Missing payment parameter` |
| `signature.sign` is present | `Missing signature` |
| the signature matches (constant-time, case-insensitive) | `Wrong signature` |
| `payment.result` is `OK` | `Invalid result` |

`Invalid result` is the only rejection of a correctly signed notification: Viamo really reported a payment that did not succeed.

## The response

| Getter | Value |
|---|---|
| `isSuccessful()` | `true` only for a correctly signed notification with `result` `OK` |
| `getVs()` | `payment.vs` as a string, or `null` |
| `getPaymentId()` | `payment.id` as a string, or `null` |
| `getNotificationId()` | `notificationId` as a string, or `null` |
| `getMessage()`, `errorMessage()` | Why the notification was rejected, `null` otherwise |
| `getData()` | `['success' => bool, 'vs' => …, 'payment_id' => …, 'notification_id' => …]` plus `error`; the values keep their JSON types here |

`vs`, `payment_id` and `notification_id` are filled for `Wrong signature` and `Invalid result` too (they are not verified for `Wrong signature`).

## Check the amount

The amount is covered by the signature, but the response has no getter for it. Once `isSuccessful()` is `true`, read it from the JSON you passed in and compare it with your order:

<<< @/../examples/webhook/public/index.php#amount{php}

## Answer and stay idempotent

- Mark the order paid only if it is not paid yet; a second delivery of the same notification, or a return URL that came first, must change nothing.
- Answer quickly with a 2xx status once you have stored the result; do slow work (emails, invoices) afterwards. Check Viamo's merchant documentation for the response it expects and how it retries.
- Answer a rejected notification with a 4xx status and log `getMessage()`.

The [webhook example](../examples/webhook) runs an endpoint like this on the PHP built-in server and sends it a paid notification, the same one again, a failed payment, a notification with a changed amount, a correctly signed wrong amount, an unknown order, a missing signature and a body that is not JSON.
