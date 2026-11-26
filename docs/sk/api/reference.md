---
description: 'Každá verejná trieda a metóda omnipay-viamo - Gateway, požiadavky a odpovede, ViamoSign a výnimky.'
---

# Referencia API

Všetky triedy sú `final`. Menný priestor `Omnipay\Viamo`. `Core\CredentialsParameters` a `Core\Str` sú `@internal`: nie sú súčasťou verejného API.

## Gateway

`Omnipay\Viamo\Gateway` dedí od `Omnipay\Common\AbstractGateway`. Vytvoríte ju cez `Omnipay::create('Viamo')` alebo `new Gateway(?ClientInterface $httpClient = null, ?Request $httpRequest = null)`.

| Metóda | Popis |
|---|---|
| `getName(): string` | `'Viamo Gateway'` |
| `getDefaultParameters(): array` | `['bid' => '', 'key1' => '', 'key2' => '', 'testMode' => false]` |
| `getBid(): ?string`, `setBid(?string): static` | Identifikátor obchodníka |
| `getKey1(): ?string`, `setKey1(?string): static` | Kľúč pre platobnú požiadavku a návratovú URL |
| `getKey2(): ?string`, `setKey2(?string): static` | Kľúč pre webhook |
| `getTestMode(): bool`, `setTestMode(bool)` | Zdedené; pozrite [Testovací režim](../guide/test-mode) |
| `purchase(array $parameters = []): PurchaseRequest` | Pozrite [purchase()](../guide/purchase) |
| `completePurchase(array $parameters = []): CompletePurchaseRequest` | Pozrite [completePurchase()](../guide/return-url) |
| `webhook(array $parameters = []): WebhookRequest` | Pozrite [webhook()](../guide/webhook) |

Každá požiadavka má rovnaké prístupové metódy `getBid()`/`setBid()`, `getKey1()`/`setKey1()`, `getKey2()`/`setKey2()` a preberá hodnoty z brány.

## Message\PurchaseRequest

Dedí od `Omnipay\Common\Message\AbstractRequest` (preto sú `getAmount()`/`setAmount()`, `getCurrency()`/`setCurrency()`, `getTestMode()` zdedené).

| Člen | Popis |
|---|---|
| `QR_ENDPOINT` | `'https://qr.viamo.sk/api/'` |
| `QR_TEMPLATE` | `300` |
| `getVs(): ?string`, `setVs(?string): static` | Variabilný symbol |
| `getCs(): ?string`, `setCs(?string): static` | Konštantný symbol |
| `getSs(): ?string`, `setSs(?string): static` | Špecifický symbol |
| `getMsg(): ?string`, `setMsg(?string): static` | Správa pre platiteľa |
| `getRpt(): ?string`, `setRpt(int\|string\|null): static` | Pole `RPT`, posiela sa ako celé číslo |
| `getRurl(): ?string`, `setRurl(?string): static` | Návratová URL (len v odkaze do aplikácie) |
| `getDt(): ?string`, `setDt(?string): static` | Len sa uloží; neposiela sa |
| `getQrEndpoint(): string` | `QR_ENDPOINT` |
| `getData(): array` | Overí, že `bid`, `key1` a `amount` nie sú `null` ani prázdne a že `vs`, `cs`, `ss` majú najviac 10 číslic; vráti `[]`. Vyhadzuje `InvalidRequestException` |
| `send(): PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): PurchaseResponse` | Zostaví a podpíše reťazec požiadavky, vykreslí QR kód a vráti odpoveď (`$data` sa ignoruje). Vyhodí `InvalidRequestException`, keď hodnota poľa obsahuje `*` |

## Message\PurchaseResponse

| Metóda | Popis |
|---|---|
| `isSuccessful(): bool` | Vždy `true` |
| `isRedirect(): bool` | Vždy `false` |
| `getQrData(): ?string` | QR kód ako PNG v base64 |
| `getQrUrl(): ?string` | URL QR API Viamo; reťazec požiadavky je v `text` zakódovaný pre URL |
| `getAppLink(): ?string` | Odkaz `viamo://` alebo `viamo-staging://`; reťazec požiadavky je v `requestString` zakódovaný pre URL |
| `getVs(): ?string` | Variabilný symbol požiadavky |
| `getData(): mixed` | `['vs', 'template', 'qr_url', 'qr_data', 'app_link']` |

## Message\CompletePurchaseRequest

| Metóda | Popis |
|---|---|
| `getData(): array` | Rozparsuje a overí `responseString` z query HTTP požiadavky brány: `['RES' => string, 'VS' => string, 'message' => string]` (`VS`/`message`, ak sú prítomné). Bez `validate()`; pri neplatnom `key1` vyhodí `InvalidKeyException`, keď `BID` sedí |
| `send(): CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): CompletePurchaseResponse` | Zabalí `$data` do odpovede |

Ďalej `getBid()`/`setBid()`, `getKey1()`/`setKey1()`, `getKey2()`/`setKey2()`.

## Message\CompletePurchaseResponse

| Metóda | Popis |
|---|---|
| `isSuccessful(): bool` | `RES` je `OK` |
| `getRes(): ?string` | `OK`, `FAIL` alebo `ERROR` |
| `getVs(): ?string` | Variabilný symbol |
| `getMessage(): ?string` | Dôvod výsledku `ERROR` |
| `isRedirect(): bool` | `false` (zdedené) |
| `getData(): mixed` | `['RES' => …, 'VS' => …, 'message' => …]` |

## Message\WebhookRequest

| Metóda | Popis |
|---|---|
| `getJson(): ?array`, `setJson(?array): static` | Dekódované telo notifikácie |
| `getData(): array` | Overí, že `bid`, `key2`, `json` nie sú `null`; vráti `[]` |
| `send(): WebhookResponse` | `sendData(getData())` |
| `sendData(mixed $data): WebhookResponse` | Overí notifikáciu (`$data` sa ignoruje); pozrite [webhook()](../guide/webhook#what-is-checked-in-order) |

Ďalej `getBid()`/`setBid()`, `getKey1()`/`setKey1()`, `getKey2()`/`setKey2()`.

## Message\WebhookResponse

| Metóda | Popis |
|---|---|
| `isSuccessful(): bool` | Správne podpísaná a `result` je `OK` |
| `isRedirect(): bool` | Vždy `false` |
| `getVs(): ?string` | Variabilný symbol |
| `getPaymentId(): ?string` | Identifikátor platby vo Viamo |
| `getNotificationId(): ?string` | Identifikátor notifikácie |
| `errorMessage(): ?string`, `getMessage(): ?string` | Dôvod odmietnutia |
| `getAmount(): ?string` | Podpísaná `payment.amount` presne ako podpísaný text (napr. `24.90`); `null`, ak podpis nie je platný |
| `getData(): mixed` | `['success' => bool, 'vs', 'payment_id', 'notification_id', 'amount', 'error']` |

## Core\ViamoSign

| Metóda | Popis |
|---|---|
| `sign(string $input, string $secret): string` | Podpis ako 32 hexadecimálnych znakov veľkými písmenami; pri kľúči, ktorý nemá 32 hexadecimálnych znakov, vyhodí `InvalidKeyException` |
| `verify(string $input, string $secret, mixed $signature): bool` | Porovnanie v konštantnom čase; nezáleží na veľkosti písmen; `false` pre podpis, ktorý nie je reťazec |

Parameter `$secret` oboch metód a hodnota `setKey1()`/`setKey2()` sú označené `#[\SensitiveParameter]` ([Bezpečnosť](../security#keys-in-error-reports)).

Pozrite [Podpisy](../guide/signatures).

## Výnimky

| Trieda | Popis |
|---|---|
| `Exception\ViamoException` | Rozhranie, ktoré implementuje každá výnimka tohto balíka |
| `Exception\InvalidKeyException` | `key1`/`key2` nemá 32 hexadecimálnych znakov. Dedí od `InvalidRequestException` z Omnipay. |

`InvalidRequestException` z Omnipay sa vyhadzuje pre povinný parameter, ktorý je `null` alebo prázdny, platobný symbol, ktorý nie je číslo s najviac 10 číslicami, hodnotu poľa obsahujúcu `*`, zápornú sumu alebo sumu s priveľa desatinnými miestami; `Money\Exception\UnknownCurrencyException` pre neznámu menu. Pozrite [Spracovanie chýb](../guide/errors).

## Zdedené z Omnipay

Brána dedí od `AbstractGateway` metódy `initialize(array $parameters = [])`, `getParameters()`, `getShortName()`, `getTestMode()`/`setTestMode()`, `getCurrency()`/`setCurrency()`. Požiadavky dedia od `AbstractRequest` metódy `initialize()`, `getParameters()`, `getAmount()`/`setAmount()`, `getCurrency()`/`setCurrency()`, `getTestMode()`/`setTestMode()`, `getResponse()` a `validate()`. Odpovede dedia od `AbstractResponse` metódy `getRequest()`, `isCancelled()`, `getCode()` a `getTransactionReference()` (obe vracajú `null`). Pozrite [dokumentáciu Omnipay](https://omnipay.thephpleague.com/).
