---
description: 'Podpíšte objednávku ako platbu Viamo a zobrazte ju ako QR kód PNG a odkaz do aplikácie; podpisy prepočíta openssl.'
---

# Platba cez QR kód

Podpíše jednu objednávku ako platobnú požiadavku Viamo a zobrazí ju pre počítač (QR kód) aj pre mobil (odkaz do aplikácie). Potom sa prepne do testovacieho režimu a ukáže odkaz do testovacej (staging) aplikácie. Nikam sa nič neposiela: `purchase()` len podpisuje a vykresľuje.

## Kód

Brána (každý príklad, ktorý ju potrebuje, má rovnaký `gateway.php`):

<<< @/../examples/qr-checkout/gateway.php

Pokladňa:

<<< @/../examples/qr-checkout/run.php

## Výstup

<<< @/../examples/qr-checkout/expected-output.txt{text}

- **Reťazec požiadavky** je obsah QR kódu; `SIG` podpisuje všetko pred `*SIG:`.
- **URL QR API** vykreslí ten istý kód na serveri Viamo; reťazec požiadavky je v jej parametri `text` zakódovaný pre URL.
- **QR kód** je PNG s rozmermi 320 × 320 px (300 px plus okraj). Príklad ho uloží aj do systémového dočasného priečinka.
- **Odkaz do aplikácie** pridáva `RURL` (návratovú URL) a má vlastný podpis. Reťazec požiadavky nesie zakódovaný pre URL; ďalší riadok ho ukazuje dekódovaný.
- **`msg` s `*`** (`Gift*AM:0.01`) sa odmietne: `*` oddeľuje polia, takže by inak pridal druhú sumu.
- V **testovacom režime** sa zmení len schéma, na `viamo-staging://`.

## Kontrola cez openssl

`e2e.sh` vezme z výstupu reťazec požiadavky a dekódovaný reťazec požiadavky z odkazu do aplikácie a oba podpisy prepočíta nástrojom `openssl`, bez PHP; posledné dva riadky výstupu sú jeho verdikt:

<<< @/../examples/qr-checkout/e2e.sh{bash}

## Spustenie

```bash
cd examples/qr-checkout && composer install && ./e2e.sh
```
