---
description: 'completePurchase() overí podpísaný responseString, ktorý mobilná aplikácia Viamo pripojí k vašej návratovej URL.'
---

# completePurchase(): návratová URL

Po platbe pošle mobilná aplikácia Viamo zákazníka na návratovú URL (`rurl` z [odkazu do aplikácie](./purchase)) s query parametrom `responseString`:

```text
https://shop.example/viamo/return?responseString=BID:42*VS:2026000123*RES:OK*SIG:88F0AC8F3D9B94BB1C4241EDC85D4E40
```

`completePurchase()` ho prečíta z HTTP požiadavky brány (`Request::createFromGlobals()`, ak ste konštruktoru `Gateway` neodovzdali vlastný Symfony `Request`) a overí ho:

<<< @/snippets/complete-purchase.php

## Čo sa kontroluje, v tomto poradí

1. `responseString` existuje, je to reťazec a nie je prázdny, inak `Missing responseString`.
2. Obsahuje `*SIG:`, inak `Missing signature`. Podpis je všetko za **posledným** `*SIG:`; podpísaná časť je všetko pred ním.
3. Polia sa rozparsujú z dvojíc `KEY:value` oddelených znakom `*` (hodnota môže obsahovať `:`; časti bez `:` sa ignorujú). `BID` sa musí presne rovnať vášmu `bid`, inak `Wrong BID`.
4. Podpis musí sedieť s podpísanou časťou a kľúčom `key1` (v konštantnom čase, bez ohľadu na veľkosť písmen), inak `Wrong sign`.
5. `RES` a `VS` musia byť prítomné, inak `Missing RES` alebo `Missing VS`.

Až potom sa z odpovede prevezme `RES`: `OK` alebo `FAIL`.

## Odpoveď

| Getter | Hodnota |
|---|---|
| `isSuccessful()` | `true` len pre overenú odpoveď s `RES:OK` |
| `getRes()` | `OK` alebo `FAIL` od Viamo, alebo `ERROR`, keď sa odpoveď nepodarilo overiť |
| `getVs()` | Variabilný symbol. Pri `ERROR` sa vráti, ak je prítomný, ale nie je overený: použite ho len na logovanie |
| `getMessage()` | Prečo bola odpoveď odmietnutá (pozrite vyššie), inak `null` |
| `getData()` | `['RES' => …, 'VS' => …]`, pri `ERROR` navyše `message` |

`getRes()` rozlíši tri prípady:

| `getRes()` | Význam | Čo zobraziť |
|---|---|---|
| `OK` | Viamo hlási, že platba prešla | „Ďakujeme, vaša objednávka je zaplatená“ (označte ju ako zaplatenú, ak to ešte neurobil webhook) |
| `FAIL` | Správne podpísané zlyhanie: zamietnutá alebo zrušená platba | „Platba neprešla, skúste to znova“ |
| `ERROR` | Nedá sa overiť: podvrhnutá, skrátená, iného obchodníka alebo `responseString` úplne chýba | Ani zaplatená, ani zlyhaná: „Objednávku aktualizujeme, keď sa platba potvrdí“, a zalogujte `getMessage()` |

Návratová URL nenesie sumu. Dokazuje, že Viamo pre daný variabilný symbol oznámilo `RES:OK`; sumu určila podpísaná požiadavka, ktorú ste vytvorili. Podpísanú sumu nesie [webhook](./webhook).

[Príklad návratovej URL](../examples/return-url) spustí e-shop na vstavanom PHP serveri a pošle mu zaplatené, zamietnuté, podvrhnuté a neúplné odpovede; jeho výstup ukazuje stav, stránku a zalogovaný dôvod pre každú z nich.

## Vlastný objekt požiadavky

Vo frameworku, ktorý už má objekt požiadavky, odovzdajte bráne Symfony `Request` s query reťazcom návratovej URL:

```php
$gateway = new \Omnipay\Viamo\Gateway(null, $symfonyRequest);
```

S PSR-7 ho zostavte cez `symfony/psr-http-message-bridge` alebo `new Request($psrRequest->getQueryParams())`.
