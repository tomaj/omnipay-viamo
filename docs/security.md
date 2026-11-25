---
title: 'Security'
description: 'How to report a vulnerability in omnipay-viamo, supported versions, and what the package does to keep payments safe.'
---

# Security

## What the package does

- **Signs every payment request** with `key1`, so a changed amount, variable symbol or return URL is refused by Viamo.
- **Verifies every answer**: the return URL with `key1` and your `BID`, the webhook with `key2`. Signatures are compared with `hash_equals()` in constant time, never with `==`.
- **Refuses wrong keys**: a key that is not a 128-bit key in hexadecimal throws `InvalidKeyException` instead of signing with a padded or truncated key.
- **Treats Viamo's answers as untrusted input**: a malformed `responseString` or webhook body gives a rejected response, never a PHP warning or an exception.
- **Is tested against independent values**: the test suite and the examples recompute signatures with the `openssl` CLI.

## What your shop must do

- Keep `key1` and `key2` secret, out of the repository, and separate for test and production.
- Mark an order paid only when `isSuccessful()` is `true`, after finding it by `getVs()` and comparing the webhook's signed amount with the order.
- Make the payment handling idempotent: the return URL and the webhook can both arrive, and a webhook can repeat.
- Put only values your shop controls into `vs`, `cs`, `ss` and `msg`: `*` separates the fields of the request string.
- Serve the return URL and the webhook over HTTPS.

<!--@include: ../SECURITY.md{3,}-->
