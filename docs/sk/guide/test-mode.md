---
description: 'Testovací režim prepne odkaz do aplikácie na testovaciu (staging) aplikáciu Viamo; všetko ostatné zostáva rovnaké.'
---

# Testovací režim

Keď je `testMode` nastavený na `true`, `purchase()` vráti odkaz do testovacej (staging) aplikácie Viamo: `viamo-staging://` namiesto `viamo://`.

<<< @/snippets/test-mode.php

Nič iné sa nemení:

| | Produkcia | Testovací režim |
|---|---|---|
| Odkaz do aplikácie | `viamo://?requestString=…` | `viamo-staging://?requestString=…` |
| Reťazec požiadavky a jeho podpis | rovnaký | rovnaký |
| QR kód (`getQrData()`) | rovnaký | rovnaký |
| URL QR API (`getQrUrl()`) | `https://qr.viamo.sk/api/` | `https://qr.viamo.sk/api/` |
| `completePurchase()`, `webhook()` | overujú vašimi kľúčmi | overujú vašimi kľúčmi |

O tom, do ktorého prostredia platba patrí, rozhodujú kľúče. Spolu s testovacím režimom používajte testovacie prístupové údaje (`bid`, `key1`, `key2`), ktoré vám Viamo dá pre staging prostredie, a bez neho produkčné. Ak prepnete len `testMode` a ponecháte produkčné kľúče, podpíšete produkčné požiadavky, ktoré otvoria testovaciu aplikáciu.

Prepnúť sa dá aj pre jednu požiadavku: `$gateway->purchase(['testMode' => true, ...])`.

## Testovanie bez Viamo

Na automatické testy testovaciu aplikáciu vôbec nepotrebujete: návratovú URL a webhook si podpíšte sami. Pozrite [Testovanie integrácie](./testing).
