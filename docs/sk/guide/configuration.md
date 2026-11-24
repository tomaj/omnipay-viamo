---
description: 'Všetky parametre brány (bid, key1, key2, testMode) a všetky platobné parametre omnipay-viamo s typmi, predvolenými hodnotami a poľom požiadavky, ktorým sa každý stane.'
---

# Konfigurácia

## Parametre brány

Nastavte ich raz cez `initialize()` (alebo settery). Každá požiadavka, ktorú brána vytvorí, začína s parametrami brány a pole, ktoré odovzdáte do `purchase([...])`, `completePurchase([...])` alebo `webhook([...])`, ktorýkoľvek z nich pre danú požiadavku prepíše.

| Parameter | Typ | Predvolená hodnota | Setter | Význam |
|---|---|---|---|---|
| `bid` | `?string` | `''` | `setBid()` | Identifikátor obchodníka (príjemcu) pridelený od Viamo. Posiela sa ako `BID` v platobnej požiadavke; `completePurchase()` odmietne odpoveď, ktorej `BID` nie je presne táto hodnota. |
| `key1` | `?string` | `''` | `setKey1()` | 128-bitový kľúč ako 32 hexadecimálnych znakov (malé aj veľké písmená). Podpisuje platobnú požiadavku a overuje návratovú URL. |
| `key2` | `?string` | `''` | `setKey2()` | 128-bitový kľúč ako 32 hexadecimálnych znakov. Overuje webhook. |
| `testMode` | `bool` | `false` | `setTestMode()` | Pri `true` vráti `purchase()` odkaz do aplikácie `viamo-staging://` pre testovaciu (staging) aplikáciu Viamo. Pozrite [Testovací režim](./test-mode). |

<<< @/snippets/gateway.php

Kľúče nedávajte do repozitára: čítajte ich z prostredia alebo z úložiska tajomstiev.

### Chýbajúce a neplatné hodnoty

- Kľúč, ktorý nemá presne 32 hexadecimálnych znakov (vrátane prázdneho), vyhodí `Omnipay\Viamo\Exception\InvalidKeyException` vo chvíli, keď sa ním má podpisovať alebo overovať: v `purchase()->send()` pre `key1`, v `completePurchase()->send()` pre `key1`, keď má odpoveď správny `BID`, a vo `webhook()->send()` pre `key2`, keď má notifikácia všetky polia.
- `purchase()` vyhodí `InvalidRequestException` („The bid parameter is required“), keď je `bid`, `key1` alebo `amount` `null` alebo prázdny reťazec; `webhook()` robí to isté pre `bid` a `key2` a vyžaduje `json`. Brána vytvorená cez `Omnipay::create()` začína s `''` pre `bid`, `key1` aj `key2`, takže brána, ktorú ste zabudli nakonfigurovať, zlyhá tu, namiesto toho aby podpísala prázdny `BID`. `purchase()` nepotrebuje `key2` a `webhook()` nepotrebuje `key1`.

<<< @/snippets/errors.php

Stránka [Spracovanie chýb](./errors) uvádza všetky výnimky a všetky dôvody odmietnutia.

## Platobné parametre {#payment-parameters}

Odovzdávajú sa do `purchase([...])`. Každý má aj setter a getter na `PurchaseRequest` (`setVs()`/`getVs()` a tak ďalej). Nepovinné polia sa posielajú, len keď nie sú prázdne; reťazec `'0'` sa považuje za prázdny, rovnako ako v PHP.

| Parameter | Typ | Pole požiadavky | Význam |
|---|---|---|---|
| `amount` | `string` (alebo číslo) | `AM` | Povinný. Suma naformátovaná Omnipayom pre danú menu: z `'10'` je pre EUR `10.00` a pre JPY zostane `5`. Chýbajúca alebo prázdna suma, viac desatinných miest, než mena dovoľuje, alebo záporná suma vyhodí `InvalidRequestException`. |
| `currency` | `string` | `CC` | Kód podľa ISO 4217, napríklad `'EUR'`. Nepovinný. Neznámy kód vyhodí `Money\Exception\UnknownCurrencyException` z `moneyphp/money`. |
| `vs` | `?string` | `VS` | Variabilný symbol: identifikuje objednávku vo webhooku a na návratovej URL. Použite číslo objednávky. Len číslice, najviac 10. |
| `cs` | `?string` | `CS` | Konštantný symbol. Nepovinný. Len číslice, najviac 10. |
| `ss` | `?string` | `SS` | Špecifický symbol. Nepovinný. Len číslice, najviac 10. |
| `msg` | `?string` | `MSG` | Správa pre platiteľa, zobrazí sa v aplikácii. Nepovinná. Nesmie obsahovať `*`. |
| `rpt` | `int\|string\|null` | `RPT` | Posiela sa ako celé číslo: z `'5x'` je `5`, `'0'` sa neposiela. Nepovinný. |
| `rurl` | `?string` | `RURL` | Návratová URL. Posiela sa len v odkaze do aplikácie, za ostatnými poliami; každá `*` v nej sa zapíše ako `%2A`, pretože `*` oddeľuje polia. |
| `dt` | `?string` | | Uloží sa na požiadavke (`getDt()`); reťazec požiadavky Viamo preň nemá pole. |

::: warning Hodnoty polí nesmú obsahovať `*`
Reťazec požiadavky tvoria dvojice `KEY:value` spojené znakom `*`, takže `*` vnútri hodnoty by začala nové pole (napríklad druhé `AM:`). `rurl` sa escapuje (`*` sa zmení na `%2A`); každá iná hodnota s `*` sa odmietne výnimkou `InvalidRequestException` („The msg parameter must not contain \"*\", …“) ešte pred podpísaním. `vs`, `cs` a `ss` musia byť čísla s najviac 10 číslicami (ako platobné symboly formátu Short Payment Descriptor), čokoľvek iné vyhodí `InvalidRequestException` („The vs parameter must be a number of at most 10 digits“). Ak `msg` môže obsahovať text, ktorý napísal zákazník, najprv `*` odstráňte alebo nahraďte.
:::

Polia sa v reťazci požiadavky objavujú v tomto poradí, každé len vtedy, keď je nastavené: `QP` (vždy `1.0`), `BID`, `AM`, `CC`, `VS`, `CS`, `SS`, `RPT`, `MSG`, potom `SIG`. Odkaz do aplikácie pridáva `RURL` pred `SIG` a podpisuje sa samostatne.

## Parametre ostatných operácií

- `completePurchase()` neberie žiadne platobné parametre. Číta `responseString` z query reťazca HTTP požiadavky, s ktorou bola brána vytvorená: `Request::createFromGlobals()`, keď použijete `Omnipay::create('Viamo')`, alebo Symfony `Request`, ktorý odovzdáte ako druhý argument konštruktora (`new Gateway($httpClient, $request)`).
- `webhook()` berie `json`: dekódované JSON telo notifikácie ako pole (`setJson(?array)`). Je povinný.
