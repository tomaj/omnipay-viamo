---
description: 'How a Viamo payment travels between the customer, your shop, the Viamo app and Viamo, and which key signs each message.'
---

# Payment flow

A Viamo payment is not a redirect to a bank page. Your shop shows a signed payment request; the customer pays in the Viamo app on their phone; Viamo then reports the result to your shop in two independent ways.

<div class="diagram">

![Sequence: the customer checks out, the shop's purchase() signs the request with key1 and returns a QR code or app link, the customer scans it with the Viamo app, Viamo checks the signature and the customer confirms, Viamo POSTs a webhook signed with key2 that the shop verifies with webhook(), and the app sends the customer to the return URL with a responseString signed with key1 that the shop verifies with completePurchase().](/diagrams/payment-flow-light.svg){.only-light width=940 height=690}
![The same payment flow sequence in dark colours.](/diagrams/payment-flow-dark.svg){.only-dark width=940 height=690}

</div>

## The steps

| # | Who | What | Signed with | In this package |
|---|---|---|---|---|
| 1 | Shop | Builds the request string `QP:1.0*BID:…*AM:…*…` and signs it | `key1` | [`purchase()`](./purchase) |
| 2 | Shop → customer | QR code (computer) or `viamo://` app link (phone); the app link also carries the return URL `RURL` | `key1` | `getQrData()`, `getQrUrl()`, `getAppLink()` |
| 3 | Customer → Viamo | The app reads the request; Viamo checks its signature, so a changed amount is refused; the customer confirms | | |
| 4 | Viamo → shop | `POST` of a JSON notification to your webhook URL | `key2` | [`webhook()`](./webhook) |
| 5 | Viamo app → shop | The customer returns to `RURL` with `?responseString=BID:…*VS:…*RES:…*SIG:…` | `key1` | [`completePurchase()`](./return-url) |

Steps 4 and 5 are independent. Either can come first, either can be missing (the customer closes the app; your server was down when the webhook came), and a webhook can be delivered more than once. Viamo's merchant documentation describes how and when it retries notifications; this package only verifies them.

## Which key does what

| Key | Signs | Verifies |
|---|---|---|
| `key1` | The payment request (QR code and app link) | The return URL `responseString` |
| `key2` | | The webhook notification |

Both are 128-bit AES keys written as 32 hexadecimal characters. The [Signatures](./signatures) page shows the algorithm and how to compute any signature with the `openssl` CLI.

## What your shop has to do

1. **Store the order before you show the QR code.** Use its number as the variable symbol (`vs`): it is the only field that links Viamo's answers back to your order.
2. **Verify every answer.** Call `webhook()` or `completePurchase()` and trust nothing unless `isSuccessful()` is `true`.
3. **Compare with your order.** Find it by `getVs()`. The webhook's signed `amount` must match the order total (the return URL carries no amount).
4. **Mark it paid once.** Whichever answer comes first marks the order paid; the second one finds it paid and changes nothing.
5. **Show the customer the result** on the return URL. If it could not be verified, say that the order updates when the payment is confirmed, rather than "failed".

The [shop example](../examples/shop) implements all five steps against a fake Viamo app, and its output shows every step.
