---
description: 'The three operations of the Viamo gateway, what each request returns and which key it uses.'
---

# Operations

The gateway has three operations. Each one returns an Omnipay request; `send()` returns its response. None of them makes an HTTP request.

| Operation | Request | Response | Key | Reads |
|---|---|---|---|---|
| [`purchase(array $parameters = [])`](./purchase) | `PurchaseRequest` | `PurchaseResponse` | `key1` | the [payment parameters](./configuration#payment-parameters) |
| [`completePurchase(array $parameters = [])`](./return-url) | `CompletePurchaseRequest` | `CompletePurchaseResponse` | `key1` | `responseString` from the HTTP request query |
| [`webhook(array $parameters = [])`](./webhook) | `WebhookRequest` | `WebhookResponse` | `key2` | the `json` parameter |

## What `isSuccessful()` means

| Response | `isSuccessful()` is `true` when | `isRedirect()` |
|---|---|---|
| `PurchaseResponse` | always: the request was signed (nothing has been paid yet) | `false` |
| `CompletePurchaseResponse` | the `responseString` is signed with `key1`, has your `BID`, and `RES` is `OK` | `false` (inherited) |
| `WebhookResponse` | the notification is signed with `key2` and `payment.result` is `OK` | `false` |

When it is `false`, `getMessage()` says why. The [Error handling](./errors) page lists every reason.

## Getters at a glance

| | `PurchaseResponse` | `CompletePurchaseResponse` | `WebhookResponse` |
|---|---|---|---|
| Variable symbol | `getVs()` | `getVs()` | `getVs()` |
| Result | | `getRes()`: `OK`, `FAIL`, `ERROR` | |
| Reason of a rejection | | `getMessage()` | `getMessage()`, `errorMessage()` |
| Payment | `getQrData()`, `getQrUrl()`, `getAppLink()` | | `getPaymentId()`, `getNotificationId()` |
| Raw data | `getData()` | `getData()` | `getData()` |
