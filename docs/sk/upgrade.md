---
title: 'Prechod zo 4.x'
description: 'Prechod omnipay-viamo zo 4.x na 5.0: PHP 8.4, natívne typy, null namiesto false, výnimka pre neplatné kľúče, kľúč VS.'
---

# Prechod zo 4.x na 5.0

Táto stránka je prekladom [UPGRADE-5.0.md](https://github.com/tomaj/omnipay-viamo/blob/master/UPGRADE-5.0.md); kód je rovnaký ako v anglickej verzii.

## PHP 8.4

Verzia 5.0 vyžaduje PHP 8.4 alebo novšie a rozšírenie `gd` (QR kód sa vždy vykresľoval cez GD; 5.0 to teraz deklaruje ako závislosť).

## Natívne typy

Všetky metódy majú natívne typy parametrov a návratových hodnôt a brána, požiadavky, odpovede aj `ViamoSign` sú `final`.

- Settery parametrov prijímajú `?string` a vracajú `static`. Parametre odovzdané cez `initialize()`, `purchase([...])`, `completePurchase([...])` alebo `webhook([...])` sa stále prevádzajú z celých čísel na reťazce. Ak však setter zavoláte priamo s hodnotou `int` zo súboru s `declare(strict_types=1)`, vyhodí `TypeError`; `setRpt()` prijíma `int|string|null`.

  <!-- snippet: skip -->
  ```php
  // 4.x: a TypeError in 5.0 under strict_types
  $request->setVs(2026000123);
  ```

  ```php
  // 5.0
  $request->setVs('2026000123');
  ```

- Gettery vracajú `?string` (`getBid()`, `getKey1()`, `getKey2()`, `getVs()`, `getCs()`, `getSs()`, `getMsg()`, `getRpt()`, `getRurl()`, `getDt()`). `WebhookRequest::getJson()` vracia `?array`.
- `ViamoSign::sign(string $input, string $secret): string`.
- Triedy už nemožno rozširovať. Namiesto dedenia ich obaľte (kompozícia).

## Gettery odpovedí vracajú `null` namiesto `false`

`PurchaseResponse::getVs()`, `WebhookResponse::getVs()`, `getPaymentId()` a `getNotificationId()` vracali `false`, keď hodnota chýbala; teraz vracajú `null`. Hodnoty sa vracajú ako reťazce (číselné id platby z JSON-u notifikácie sa predtým vracalo ako `int`).

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

## Neplatné kľúče vyhodia výnimku

`key1` a `key2` musia byť 128-bitové kľúče zapísané ako 32 hexadecimálnych znakov. Verzia 4.x potichu podpisovala nesprávnym kľúčom (doplneným nulami alebo orezaným); 5.0 vyhodí `Omnipay\Viamo\Exception\InvalidKeyException` (výnimku Omnipay `InvalidRequestException`, ktorá implementuje `Omnipay\Viamo\Exception\ViamoException`).

```php
try {
    $response = $gateway->purchase($parameters)->send();
} catch (\Omnipay\Viamo\Exception\ViamoException $e) {
    // misconfigured key
}
```

## Návratová URL sa číta z HTTP požiadavky brány

`completePurchase()` číta `responseString` zo Symfony `Request`, s ktorým bola brána vytvorená (predvolene `Request::createFromGlobals()`, teda `$_GET` ako doteraz), a nie priamo z `$_GET`. Ak bránu vytvárate s vlastným objektom požiadavky, musí obsahovať query string návratovej URL.

```php
$gateway = new \Omnipay\Viamo\Gateway(null, $symfonyRequest);
$result = $gateway->completePurchase()->send();
```

## Dáta `completePurchase()` používajú kľúč `VS`

Variabilný symbol je v `CompletePurchaseResponse::getData()` pod kľúčom `VS` (predtým `vs`), teda pod tým istým kľúčom, ktorý číta `getVs()`; vo verzii 4.x `getVs()` vracal `null`. Použite getter:

<!-- snippet: skip -->
```php
// 4.x
$vs = $result->getData()['vs'];
```

```php
// 5.0
$vs = $result->getVs();
```

Chýbajúci alebo poškodený `responseString` vráti neúspešnú odpoveď (`getRes() === 'ERROR'`, dôvod je v `getMessage()`) namiesto varovaní PHP.

## Povinné parametre sa kontrolujú aj na prázdne hodnoty

`purchase()` vyhodí `Omnipay\Common\Exception\InvalidRequestException`, keď je `bid`, `key1` alebo `amount` `null` alebo prázdny reťazec. Verzia 4.x podpísala `BID:` alebo `AM:` s prázdnou hodnotou, ktorú Viamo nemôže prijať; brána vytvorená cez `Omnipay::create()` začína s `bid` nastaveným na `''`. `purchase()` už nevyžaduje `key2`. `webhook()` vyhodí výnimku pre prázdny `bid` alebo `key2`.

```php
$gateway = \Omnipay\Omnipay::create('Viamo');
$gateway->initialize(['bid' => '42', 'key1' => '000102030405060708090a0b0c0d0e0f']);

$response = $gateway->purchase(['amount' => '24.90', 'currency' => 'EUR', 'vs' => '2026000123'])->send();
```

## Platobné symboly a `*` v hodnotách

`vs`, `cs` a `ss` musia byť čísla s najviac 10 číslicami; iné hodnoty vyhodia `InvalidRequestException` („The vs parameter must be a number of at most 10 digits“). Hodnota poľa, ktorá obsahuje `*`, teda oddeľovač polí v reťazci požiadavky, vyhodí `InvalidRequestException` a nepodpíše sa; ako doteraz sa escapuje iba `rurl` (`*` sa zmení na `%2A`). Ak `msg` skladáte z textu, ktorý napísal zákazník, najprv z neho `*` odstráňte:

<!-- snippet: skip -->
```php
// 4.x: signed as is
$gateway->purchase(['amount' => '24.90', 'vs' => 'A-123', 'msg' => $customerNote]);
```

```php
// 5.0
$customerNote = 'Gift *wrapped*';
$gateway = \Omnipay\Omnipay::create('Viamo');
$gateway->initialize(['bid' => '42', 'key1' => '000102030405060708090a0b0c0d0e0f']);

$response = $gateway->purchase(['amount' => '24.90', 'vs' => '123', 'msg' => str_replace('*', '', $customerNote)])->send();
```

## URL-kódovaná URL QR API a odkaz do aplikácie

`PurchaseResponse::getQrUrl()` a `getAppLink()` URL-kódujú reťazec požiadavky (`rawurlencode()`): `?text=QP%3A1.0%2ABID%3A42…` namiesto `?text=QP:1.0*BID:42…`. Odkazy, ktoré len vypisujete, netreba meniť. Ak si reťazec požiadavky z týchto URL vyberáte sami, dekódujte ho:

```php
$gateway = \Omnipay\Omnipay::create('Viamo');
$gateway->initialize(['bid' => '42', 'key1' => '000102030405060708090a0b0c0d0e0f']);
$response = $gateway->purchase(['amount' => '24.90'])->send();

// 4.x: substr($response->getAppLink(), strlen('viamo://?requestString='))
$requestString = rawurldecode(substr((string) $response->getAppLink(), strlen('viamo://?requestString=')));
```

## Suma z notifikácie

`WebhookResponse::getAmount()` vracia podpísanú hodnotu `payment.amount`; už ju nemusíte čítať z JSON-u:

<!-- snippet: skip -->
```php
// 4.x
$amount = $json['payment']['amount'];
```

```php
// 5.0
$amount = $notification->getAmount();
```
