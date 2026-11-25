---
description: 'Všetky výnimky, ktoré omnipay-viamo vyhadzuje, a všetky dôvody, prečo sa návratová URL alebo webhook odmietne, aj s tým, čo s nimi robiť.'
---

# Spracovanie chýb

Zlyhania sú dvojakého druhu: **výnimky** pri zle nakonfigurovanej bráne alebo neplatnom vstupe, ktorý ste odovzdali vy, a **odmietnuté odpovede** pri všetkom, čo prichádza zvonka (návratová URL alebo webhook, ktorým sa nedá dôverovať). Odpoveď od Viamo nikdy nevyhodí výnimku len preto, že je poškodená; dostanete odpoveď, ktorej `isSuccessful()` je `false`.

## Výnimky

| Výnimka | Vyhadzuje ju | Kedy |
|---|---|---|
| `Omnipay\Viamo\Exception\InvalidKeyException` | `purchase()->send()`, `completePurchase()->send()`, `webhook()->send()`, `ViamoSign::sign()`/`verify()` | `key1` alebo `key2` nemá 32 hexadecimálnych znakov (aj keď je prázdny) |
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()`, `webhook()->send()` | Povinný parameter je `null` alebo prázdny: `bid`, `key1`, `amount` pre `purchase()`; `bid`, `key2` (a `json` s hodnotou `null`) pre `webhook()`. Správa: `The bid parameter is required` |
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()` | `vs`, `cs` alebo `ss` nie je číslo s najviac 10 číslicami (`The vs parameter must be a number of at most 10 digits`), alebo hodnota poľa obsahuje `*`, oddeľovač polí (`The msg parameter must not contain "*", the field separator of the Viamo request string`) |
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()` | `amount` je záporná alebo má viac desatinných miest, než mena dovoľuje (z formátovania sumy v Omnipay) |
| `Money\Exception\UnknownCurrencyException` | `purchase()->send()` | `currency` nie je kód podľa ISO 4217 |
| `TypeError` | settery | Hodnota nesprávneho typu, napríklad `setVs(123)` so `strict_types` (odovzdávajte reťazce; polia odovzdané do `purchase([...])` sa konvertujú) |

`InvalidKeyException` dedí z `InvalidRequestException` z Omnipay a implementuje `Omnipay\Viamo\Exception\ViamoException`, rozhranie, ktoré implementuje každá výnimka tohto balíka. Zachyťte `ViamoException` pre „tento balík“, `InvalidRequestException` pre „tento balík alebo validácia Omnipay“:

<<< @/snippets/errors.php

Všetko toto sú chyby konfigurácie alebo programovania: opravte ich, neopakujte volanie. [Príklad podpisov](../examples/signatures) vyvolá `InvalidKeyException` aj `InvalidRequestException` a vypíše ich.

## Odmietnuté návratové URL

`completePurchase()->send()` vráti `getRes() === 'ERROR'` a dôvod v `getMessage()`:

| `getMessage()` | Príčina |
|---|---|
| `Missing responseString` | V query reťazci nie je `responseString`, je prázdny alebo to nie je reťazec (`?responseString[]=x`) |
| `Missing signature` | Neobsahuje `*SIG:` |
| `Wrong BID` | `BID` chýba alebo sa presne nerovná vášmu `bid` (striktné porovnanie) |
| `Wrong sign` | Podpis nesedí: zmenený, skrátený alebo podpísaný iným kľúčom |
| `Missing RES`, `Missing VS` | Správne podpísaná odpoveď bez tohto poľa |

`RES:FAIL` nie je chyba: je to správne podpísaná neúspešná platba (`getRes() === 'FAIL'`, `getMessage()` je `null`).

## Odmietnuté webhooky

`webhook()->send()` vráti `isSuccessful() === false` a dôvod v `getMessage()` (a `errorMessage()`):

| `getMessage()` | Príčina |
|---|---|
| `Missing payment data` | V JSON nie je kľúč `payment` |
| `Missing payment parameter` | `payment` nie je objekt alebo nemá `result`, `amount` či `id` |
| `Missing signature` | Chýba `signature.sign` |
| `Wrong signature` | Podpis nesedí |
| `Invalid result` | Správne podpísané, ale `payment.result` nie je `OK` |

## Čo robiť

- **Logujte každé odmietnutie** spolu s `getMessage()`; séria `Wrong sign` alebo `Wrong signature` znamená, že niekto skúša vaše endpointy, alebo máte pomiešané kľúče (`key1` a `key2`, testovacie a produkčné).
- **Nikdy neoznačte objednávku ako zaplatenú** na základe ničoho iného než `isSuccessful()`.
- **Nezobrazujte „platba zlyhala“** pri `ERROR` na návratovej URL: platba mohla pokojne prejsť a webhook vám to oznámi. Ukážte, že sa objednávka aktualizuje.
- **Na webhooky odpovedajte 4xx** pri odmietnutých notifikáciách a 2xx pri `Invalid result`, keď ste neúspešnú platbu zaznamenali.
