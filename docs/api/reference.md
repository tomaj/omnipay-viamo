---
description: 'Every public class and method of omnipay-viamo - Gateway, the requests and responses, ViamoSign and the exceptions.'
---

# API reference

All classes are `final`. Namespace `Omnipay\Viamo`. `Core\CredentialsParameters` and `Core\Str` are `@internal`: not part of the public API.

## Gateway

`Omnipay\Viamo\Gateway` extends `Omnipay\Common\AbstractGateway`. Create it with `Omnipay::create('Viamo')` or `new Gateway(?ClientInterface $httpClient = null, ?Request $httpRequest = null)`.

| Method | Description |
|---|---|
| `getName(): string` | `'Viamo Gateway'` |
| `getDefaultParameters(): array` | `['bid' => '', 'key1' => '', 'key2' => '', 'testMode' => false]` |
| `getBid(): ?string`, `setBid(?string): static` | Merchant identifier |
| `getKey1(): ?string`, `setKey1(?string): static` | Key for the payment request and the return URL |
| `getKey2(): ?string`, `setKey2(?string): static` | Key for the webhook |
| `getTestMode(): bool`, `setTestMode(bool)` | Inherited; see [Test mode](../guide/test-mode) |
| `purchase(array $parameters = []): PurchaseRequest` | See [purchase()](../guide/purchase) |
| `completePurchase(array $parameters = []): CompletePurchaseRequest` | See [completePurchase()](../guide/return-url) |
| `webhook(array $parameters = []): WebhookRequest` | See [webhook()](../guide/webhook) |

Every request has the same `getBid()`/`setBid()`, `getKey1()`/`setKey1()`, `getKey2()`/`setKey2()` accessors and inherits the gateway's values.

## Message\PurchaseRequest

Extends `Omnipay\Common\Message\AbstractRequest` (so `getAmount()`/`setAmount()`, `getCurrency()`/`setCurrency()`, `getTestMode()` are inherited).

| Member | Description |
|---|---|
| `QR_ENDPOINT` | `'https://qr.viamo.sk/api/'` |
| `QR_TEMPLATE` | `300` |
| `getVs(): ?string`, `setVs(?string): static` | Variable symbol |
| `getCs(): ?string`, `setCs(?string): static` | Constant symbol |
| `getSs(): ?string`, `setSs(?string): static` | Specific symbol |
| `getMsg(): ?string`, `setMsg(?string): static` | Message for the payer |
| `getRpt(): ?string`, `setRpt(int\|string\|null): static` | `RPT` field, sent as an integer |
| `getRurl(): ?string`, `setRurl(?string): static` | Return URL (app link only) |
| `getDt(): ?string`, `setDt(?string): static` | Stored only; not sent |
| `getQrEndpoint(): string` | `QR_ENDPOINT` |
| `getData(): array` | Validates that `bid`, `key1` and `amount` are not `null` or empty and that `vs`, `cs`, `ss` have at most 10 digits; returns `[]`. Throws `InvalidRequestException` |
| `send(): PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): PurchaseResponse` | Builds and signs the request string, renders the QR code, returns the response (`$data` is ignored). Throws `InvalidRequestException` when a field value contains `*` |

## Message\PurchaseResponse

| Method | Description |
|---|---|
| `isSuccessful(): bool` | Always `true` |
| `isRedirect(): bool` | Always `false` |
| `getQrData(): ?string` | Base64 PNG of the QR code |
| `getQrUrl(): ?string` | Viamo QR API URL; the request string is URL-encoded in `text` |
| `getAppLink(): ?string` | `viamo://` or `viamo-staging://` link; the request string is URL-encoded in `requestString` |
| `getVs(): ?string` | Variable symbol of the request |
| `getData(): mixed` | `['vs', 'template', 'qr_url', 'qr_data', 'app_link']` |

## Message\CompletePurchaseRequest

| Method | Description |
|---|---|
| `getData(): array` | Parses and verifies `responseString` from the gateway's HTTP request query: `['RES' => string, 'VS' => string, 'message' => string]` (`VS`/`message` when present). No `validate()`; throws `InvalidKeyException` for an invalid `key1` once `BID` matches |
| `send(): CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): CompletePurchaseResponse` | Wraps `$data` in the response |

Also `getBid()`/`setBid()`, `getKey1()`/`setKey1()`, `getKey2()`/`setKey2()`.

## Message\CompletePurchaseResponse

| Method | Description |
|---|---|
| `isSuccessful(): bool` | `RES` is `OK` |
| `getRes(): ?string` | `OK`, `FAIL` or `ERROR` |
| `getVs(): ?string` | Variable symbol |
| `getMessage(): ?string` | Reason of an `ERROR` |
| `isRedirect(): bool` | `false` (inherited) |
| `getData(): mixed` | `['RES' => …, 'VS' => …, 'message' => …]` |

## Message\WebhookRequest

| Method | Description |
|---|---|
| `getJson(): ?array`, `setJson(?array): static` | The decoded notification body |
| `getData(): array` | Validates that `bid`, `key2`, `json` are not `null`; returns `[]` |
| `send(): WebhookResponse` | `sendData(getData())` |
| `sendData(mixed $data): WebhookResponse` | Verifies the notification (`$data` is ignored); see [webhook()](../guide/webhook#what-is-checked-in-order) |

Also `getBid()`/`setBid()`, `getKey1()`/`setKey1()`, `getKey2()`/`setKey2()`.

## Message\WebhookResponse

| Method | Description |
|---|---|
| `isSuccessful(): bool` | Correctly signed and `result` is `OK` |
| `isRedirect(): bool` | Always `false` |
| `getVs(): ?string` | Variable symbol |
| `getPaymentId(): ?string` | Viamo payment id |
| `getNotificationId(): ?string` | Notification id |
| `errorMessage(): ?string`, `getMessage(): ?string` | Reason of a rejection |
| `getAmount(): ?string` | Signed `payment.amount` as the text that was signed (e.g. `24.90`); `null` unless the signature is valid |
| `getData(): mixed` | `['success' => bool, 'vs', 'payment_id', 'notification_id', 'amount', 'error']` |

## Core\ViamoSign

| Method | Description |
|---|---|
| `sign(string $input, string $secret): string` | Signature as 32 uppercase hex characters; throws `InvalidKeyException` for a key that is not 32 hex characters |
| `verify(string $input, string $secret, mixed $signature): bool` | Constant-time comparison; case-insensitive; `false` for a non-string signature |

See [Signatures](../guide/signatures).

## Exceptions

| Class | Description |
|---|---|
| `Exception\ViamoException` | Interface implemented by every exception of this package |
| `Exception\InvalidKeyException` | `key1`/`key2` is not 32 hexadecimal characters. Extends Omnipay's `InvalidRequestException`. |

Omnipay's `InvalidRequestException` is thrown for a required parameter that is `null` or empty, a payment symbol that is not up to 10 digits, a field value containing `*`, a negative amount, or an amount with too many decimals; `Money\Exception\UnknownCurrencyException` for an unknown currency. See [Error handling](../guide/errors).

## Inherited from Omnipay

The gateway inherits `initialize(array $parameters = [])`, `getParameters()`, `getShortName()`, `getTestMode()`/`setTestMode()`, `getCurrency()`/`setCurrency()` from `AbstractGateway`. The requests inherit `initialize()`, `getParameters()`, `getAmount()`/`setAmount()`, `getCurrency()`/`setCurrency()`, `getTestMode()`/`setTestMode()`, `getResponse()` and `validate()` from `AbstractRequest`. The responses inherit `getRequest()`, `isCancelled()`, `getCode()` and `getTransactionReference()` (both `null`) from `AbstractResponse`. See the [Omnipay documentation](https://omnipay.thephpleague.com/).
