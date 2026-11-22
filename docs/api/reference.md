# API reference

All classes are `final`. Namespace `Omnipay\Viamo`.

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
| `purchase(array $parameters = []): PurchaseRequest` | See [purchase()](../guide/operations#purchase) |
| `completePurchase(array $parameters = []): CompletePurchaseRequest` | See [completePurchase()](../guide/operations#completepurchase) |
| `webhook(array $parameters = []): WebhookRequest` | See [webhook()](../guide/operations#webhook) |

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
| `getData(): array` | Validates `bid`, `key1`, `key2`; returns `[]` |
| `send(): PurchaseResponse` | |

## Message\PurchaseResponse

| Method | Description |
|---|---|
| `isSuccessful(): bool` | Always `true` |
| `isRedirect(): bool` | Always `false` |
| `getQrData(): ?string` | Base64 PNG of the QR code |
| `getQrUrl(): ?string` | Viamo QR API URL |
| `getAppLink(): ?string` | `viamo://` or `viamo-staging://` link |
| `getVs(): ?string` | Variable symbol of the request |
| `getData(): mixed` | `['vs', 'template', 'qr_url', 'qr_data', 'app_link']` |

## Message\CompletePurchaseRequest

| Method | Description |
|---|---|
| `getData(): array` | Parses and verifies `responseString` from the gateway's HTTP request query: `['RES' => string, 'VS' => string, 'message' => string]` (`VS`/`message` when present) |
| `send(): CompletePurchaseResponse` | |

## Message\CompletePurchaseResponse

| Method | Description |
|---|---|
| `isSuccessful(): bool` | `RES` is `OK` |
| `getRes(): ?string` | `OK`, `FAIL` or `ERROR` |
| `getVs(): ?string` | Variable symbol |
| `getMessage(): ?string` | Reason of an `ERROR` |

## Message\WebhookRequest

| Method | Description |
|---|---|
| `getJson(): ?array`, `setJson(?array): static` | The decoded notification body |
| `getData(): array` | Validates `bid`, `key2`, `json`; returns `[]` |
| `send(): WebhookResponse` | Verifies the notification |

## Message\WebhookResponse

| Method | Description |
|---|---|
| `isSuccessful(): bool` | Correctly signed and `result` is `OK` |
| `isRedirect(): bool` | Always `false` |
| `getVs(): ?string` | Variable symbol |
| `getPaymentId(): ?string` | Viamo payment id |
| `getNotificationId(): ?string` | Notification id |
| `errorMessage(): ?string`, `getMessage(): ?string` | Reason of a rejection |

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

Omnipay's `InvalidRequestException` is thrown for a missing required parameter.
