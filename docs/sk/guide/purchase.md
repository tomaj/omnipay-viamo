---
description: 'purchase() podpíše platobnú požiadavku Viamo a vráti ju ako PNG s QR kódom, URL QR API a odkaz do aplikácie viamo://.'
---

# purchase(): QR kód a odkaz do aplikácie

`purchase()` zostaví reťazec platobnej požiadavky Viamo, podpíše ho kľúčom `key1` a vráti ho v troch podobách. Do Viamo sa nič neposiela: výsledok ukážete zákazníkovi a čakáte na [webhook](./webhook) a [návratovú URL](./return-url).

<<< @/snippets/purchase.php

## Parametre

| Parameter | Pole požiadavky | Povinný | Poznámky |
|---|---|---|---|
| `amount` | `AM` | áno | Naformátovaný Omnipayom pre danú menu |
| `currency` | `CC` | nie | ISO 4217 |
| `vs` | `VS` | v praxi áno | Číslo vašej objednávky; jediné spojenie medzi odpoveďami Viamo a vašou objednávkou. Najviac 10 číslic |
| `cs`, `ss` | `CS`, `SS` | nie | Konštantný a špecifický symbol. Najviac 10 číslic |
| `msg` | `MSG` | nie | Zobrazí sa platiteľovi. Nesmie obsahovať `*` |
| `rpt` | `RPT` | nie | Posiela sa ako celé číslo |
| `rurl` | `RURL` | pre návratovú URL | Len v odkaze do aplikácie |
| `dt` | | nie | Len sa uloží |

Stránka [Konfigurácia](./configuration#payment-parameters) uvádza typy, pravidlá formátovania a výnimky.

## Reťazec požiadavky

Reťazec požiadavky je zoznam polí `KEY:value` spojených znakom `*`, ktorý končí podpisom všetkého pred ním:

```text
QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*SIG:5B0A9AE6F98801683C4D63687D2B3FD8
```

Polia sa objavujú v tomto poradí a len vtedy, keď sú nastavené: `QP` (vždy `1.0`), `BID`, `AM`, `CC`, `VS`, `CS`, `SS`, `RPT`, `MSG`. `SIG` je [podpis](./signatures) časti pred `*SIG:` kľúčom `key1`. Hodnota, ktorá obsahuje `*`, sa odmietne výnimkou `InvalidRequestException` (escapuje sa len `rurl`), takže správa nemôže pridať polia, napríklad druhé `AM:`.

Odkaz do aplikácie sa zostaví z tých istých polí plus `RURL` (návratová URL, v ktorej je každá `*` zapísaná ako `%2A`) a podpisuje sa samostatne:

```text
QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*RURL:https://shop.example/viamo/return?order=2026000123*SIG:DF2A817C8B1A274AD04619AF4B6897B4
```

URL QR API aj odkaz do aplikácie nesú tieto reťazce URL-enkódované (`rawurlencode()`), takže medzery, `&`, `#` či `%` v `msg` alebo `rurl` dorazia do Viamo nezmenené; dekódovaním query parametra dostanete presne podpísaný reťazec:

```text
viamo://?requestString=QP%3A1.0%2ABID%3A42%2AAM%3A24.90%2ACC%3AEUR%2AVS%3A2026000123%2AMSG%3AOrder%202026000123%2ARURL%3Ahttps%3A%2F%2Fshop.example%2Fviamo%2Freturn%3Forder%3D2026000123%2ASIG%3ADF2A817C8B1A274AD04619AF4B6897B4
```

Všetky tri riadky pochádzajú z [príkladu platby cez QR kód](../examples/qr-checkout), ktorého beh v CI oba podpisy prepočíta cez `openssl`.

## Odpoveď

`PurchaseResponse` nie je presmerovanie: `isRedirect()` je `false` a `isSuccessful()` je vždy `true` (znamená „podpísané“, nie „zaplatené“).

| Getter | Kľúč v `getData()` | Hodnota |
|---|---|---|
| `getQrData()` | `qr_data` | QR kód reťazca požiadavky ako PNG v base64, 300 px plus 10 px okraj (320 × 320). Použite ho ako `data:image/png;base64,…`. |
| `getQrUrl()` | `qr_url` | `https://qr.viamo.sk/api/?text=<URL-encoded request string>&template=300`: QR API Viamo vykreslí ten istý kód. |
| `getAppLink()` | `app_link` | `viamo://?requestString=<URL-encoded request string with RURL>`, alebo `viamo-staging://` v [testovacom režime](./test-mode). |
| `getVs()` | `vs` | Variabilný symbol požiadavky alebo `null`. |
| | `template` | `300` (`PurchaseRequest::QR_TEMPLATE`) |

## Počítač alebo telefón

Na počítači zobrazte QR kód: zákazník ho naskenuje mobilnou aplikáciou Viamo. Na telefóne sa QR kód na tej istej obrazovke naskenovať nedá, preto zobrazte odkaz do aplikácie ako tlačidlo. Len odkaz do aplikácie nesie `rurl`: reťazec požiadavky v QR kóde nemá návratovú URL, takže platba začatá cez QR kód sa do vášho e-shopu dostane cez [webhook](./webhook). Nechajte stránku pokladne kontrolovať stav objednávky (obnovením alebo pollingom), aby ukázala výsledok.

Jednoduchou voľbou je zobraziť oboje, ako to robí [príklad e-shopu](../examples/shop), alebo vybrať jedno podľa user agenta.

## Keď zákazník zaplatí neskôr

QR kód neobsahuje žiadnu platnosť. Ak sa objednávka dá zrušiť alebo sa jej suma môže zmeniť, kontrolujte stav objednávky, keď príde webhook (webhook nesie podpísanú sumu), nielen keď sa QR kód zobrazuje.
