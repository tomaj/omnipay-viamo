# Upgrading from 4.x to 5.0

## PHP 8.4

5.0 requires PHP 8.4 or newer and the `gd` extension (the QR code has always been rendered with GD; 5.0 declares it).

## Native types

Every method has native parameter and return types, and the gateway, requests, responses and `ViamoSign` are `final`.

- Parameter setters accept `?string` and return `static`. Passing parameters through `initialize()`, `purchase([...])`, `completePurchase([...])` or `webhook([...])` still converts integers to strings. Calling a setter directly with an `int` from a file with `declare(strict_types=1)` now throws a `TypeError`; `setRpt()` accepts `int|string|null`.

  <!-- snippet: skip -->
  ```php
  // 4.x: a TypeError in 5.0 under strict_types
  $request->setVs(2026000123);
  ```

  ```php
  // 5.0
  $request->setVs('2026000123');
  ```

- Getters return `?string` (`getBid()`, `getKey1()`, `getKey2()`, `getVs()`, `getCs()`, `getSs()`, `getMsg()`, `getRpt()`, `getRurl()`, `getDt()`). `WebhookRequest::getJson()` returns `?array`.
- `ViamoSign::sign(string $input, string $secret): string`.
- Classes can no longer be extended. Wrap them (composition) instead of subclassing.

## Response getters return `null` instead of `false`

`PurchaseResponse::getVs()`, `WebhookResponse::getVs()`, `getPaymentId()` and `getNotificationId()` returned `false` when the value was missing; they return `null` now. Values are returned as strings (a numeric payment id from the webhook JSON was returned as `int`).

<!-- snippet: skip -->
```php
// 4.x
if ($response->getPaymentId() !== false) {
    // ...
}
```

```php
// 5.0
if ($response->getPaymentId() !== null) {
    // ...
}
```

## Invalid keys throw

`key1` and `key2` must be 128-bit keys written as 32 hexadecimal characters. 4.x signed silently with a wrong (zero-padded or truncated) key; 5.0 throws `Omnipay\Viamo\Exception\InvalidKeyException` (an Omnipay `InvalidRequestException` that implements `Omnipay\Viamo\Exception\ViamoException`).

```php
try {
    $response = $gateway->purchase($parameters)->send();
} catch (\Omnipay\Viamo\Exception\ViamoException $e) {
    // misconfigured key
}
```

## The return URL is read from the gateway's HTTP request

`completePurchase()` reads `responseString` from the Symfony `Request` the gateway was created with (by default `Request::createFromGlobals()`, so `$_GET` as before) instead of `$_GET` directly. If you create the gateway with your own request object, it must contain the query string of the return URL.

```php
$gateway = new \Omnipay\Viamo\Gateway(null, $symfonyRequest);
$result = $gateway->completePurchase()->send();
```

## `completePurchase()` data uses `VS`

The variable symbol in `CompletePurchaseResponse::getData()` is under the key `VS` (was `vs`), the same key `getVs()` reads; `getVs()` returned `null` in 4.x. Use the getter:

<!-- snippet: skip -->
```php
// 4.x
$vs = $result->getData()['vs'];
```

```php
// 5.0
$vs = $result->getVs();
```

A missing or malformed `responseString` gives a failed response (`getRes() === 'ERROR'`, `getMessage()` says why) instead of PHP warnings.
