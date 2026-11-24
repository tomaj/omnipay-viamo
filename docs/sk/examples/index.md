---
description: 'Úplné spustiteľné recepty pre omnipay-viamo, ktoré bežia offline a CI ich testuje proti očakávanému výstupu.'
---

# Príklady

Každý príklad je malý, úplný program v priečinku [`examples/`](https://github.com/tomaj/omnipay-viamo/tree/master/examples) repozitára. Bežia offline: e-shopy bežia na vstavanom PHP serveri na `127.0.0.1` a malý skript s testovacími kľúčmi hrá mobilnú aplikáciu Viamo aj server Viamo. K Viamu sa nedostane žiadna požiadavka a nepohnú sa žiadne peniaze.

[Workflow Examples](https://github.com/tomaj/omnipay-viamo/actions?query=workflow%3AExamples) spustí každý príklad proti kódu každého pull requestu a porovná jeho výstup s commitnutým súborom `expected-output.txt`, ktorý je zároveň výstupom zobrazeným na stránke príkladu. Príklady s podpismi navyše každý podpis prepočítajú nástrojom `openssl`.

| Príklad | Ukazuje | Beží |
|---|---|---|
| [Platba cez QR kód](./qr-checkout) | `purchase()`: reťazec požiadavky, QR kód ako PNG, URL QR API, odkaz do aplikácie, testovací režim | CLI |
| [Návratová URL](./return-url) | `completePurchase()` so zaplatenými, zamietnutými, sfalšovanými a neúplnými odpoveďami | vstavaný PHP server |
| [Webhook](./webhook) | `webhook()` s idempotenciou, kontrolou sumy, sfalšovanými a poškodenými notifikáciami | vstavaný PHP server |
| [E-shop s falošným Viamom](./shop) | Celý priebeh: stránka pokladne, falošná aplikácia Viamo, webhook, návratová URL, upravený odkaz | vstavaný PHP server |
| [Podpisy a chyby](./signatures) | `ViamoSign` oproti `openssl`, hraničné prípady `verify()`, výnimky pre kľúče a parametre | CLI |

## Spustenie príkladu

Každý príklad má vlastný `composer.json`, ktorý vyžaduje `tomaj/omnipay-viamo`:

```bash
git clone https://github.com/tomaj/omnipay-viamo.git
cd omnipay-viamo/examples/shop
composer install
./e2e.sh
```

`e2e.sh` vypíše výstup zobrazený na stránke príkladu. Ak chcete príklad spustiť proti lokálnemu klonu knižnice namiesto vydaného balíka, použite z koreňa repozitára ten istý skript ako CI:

```bash
examples/ci/recipe.sh shop
```

Skopíruje príklad do dočasného priečinka, nainštaluje knižnicu z klonu cez path repozitár Composera, spustí `e2e.sh` a porovná výstup s `expected-output.txt`.

Príklady používajú testovacie kľúče z testov samotnej knižnice (`bid` `42`, `key1` `000102030405060708090a0b0c0d0e0f`, `key2` `F0E1D2C3B4A5968778695A4B3C2D1E0F`). Prepíšete ich premennými prostredia `VIAMO_BID`, `VIAMO_KEY1` a `VIAMO_KEY2`; očakávaný výstup sedí len s predvolenými hodnotami.
