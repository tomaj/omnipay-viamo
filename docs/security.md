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

## Keys in error reports

`key1` and `key2` are marked `#[\SensitiveParameter]` wherever they are a function argument: `setKey1()`, `setKey2()`
and the `$secret` of `ViamoSign::sign()` and `verify()`. When an exception is thrown while one of these calls is on the
stack (for example the `InvalidKeyException` for a key with a trailing newline), PHP shows
`Object(SensitiveParameterValue)` instead of the key in `getTraceAsString()` and in error reports (Tracy, Sentry,
Monolog).

The attribute does not cover values passed inside an array: `initialize(['key1' => ...])` or `purchase([...])` appear
in a trace as `Array`, and tools that dump arguments in full show them. Set `zend.exception_ignore_args = On` in
production (the default of `php.ini-production`): traces then contain no arguments at all.

<!--@include: ../SECURITY.md{3,}-->
