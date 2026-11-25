---
description: 'Úplný e-shop so stránkou pokladne, webhookom a návratovou URL, ktorý zaplatí falošná aplikácia Viamo kontrolujúca podpis požiadavky.'
---

# E-shop s falošným Viamom

Celá platba od začiatku do konca. Minimálny e-shop na vstavanom PHP serveri má tri endpointy: stránku pokladne s QR kódom a odkazom do aplikácie, webhook a návratovú URL. Skript hrá mobil zákazníka aj Viamo:

1. otvorí stránku pokladne a nasleduje odkaz do aplikácie,
2. skontroluje podpis požiadavky kľúčom `key1`, tak ako Viamo, a ukáže, čo sa bude platiť,
3. zaplatí: pošle podpísaný webhook (`key2`) metódou POST,
4. vráti zákazníka na `RURL` s podpísaným `responseString` (`key1`).

Traja zákazníci: jeden zaplatí, jeden platbu zruší a jeden v odkaze do aplikácie upraví sumu skôr, než ho aplikácia otvorí.

## E-shop

<<< @/../examples/shop/public/index.php

## Falošná aplikácia Viamo

<<< @/../examples/shop/fake-viamo-app.php

## Výstup

<<< @/../examples/shop/expected-output.txt{text}

Čo si všimnúť:

- Pri zaplatenej objednávke príde webhook prvý a označí ju ako zaplatenú; návratová URL ju potom nájde zaplatenú a len zobrazí poďakovanie. Objednávka si pamätá, čo ju označilo (`by webhook`).
- Zrušená objednávka nedostane žiadny webhook, len podpísaný `RES:FAIL` na návratovej URL.
- Upravený odkaz do aplikácie sa k e-shopu nikdy nedostane: jeho podpis už nesedí, takže ho Viamo odmietne. Práve preto `purchase()` požiadavku podpisuje.

## Spustenie

```bash
cd examples/shop && composer install && ./e2e.sh
```

<<< @/../examples/shop/e2e.sh{bash}
