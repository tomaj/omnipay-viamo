---
description: 'Endpoint návratovej URL na vstavanom PHP serveri, ktorý spracuje zaplatené, zamietnuté, sfalšované a neúplné odpovede Viamo.'
---

# Návratová URL

Endpoint návratovej URL e-shopu na vstavanom PHP serveri. Skript hrá mobilnú aplikáciu Viamo: vráti zákazníka so zaplateným výsledkom, s tým istým výsledkom znova (obnovenie stránky), so zamietnutou platbou, s dvoma sfalšovanými výsledkami, so správne podpísaným výsledkom pre neznámu objednávku a úplne bez `responseString`.

Objednávky sú v malom JSON súbore, ktorý zastupuje vašu databázu; označenie objednávky ako zaplatenej je idempotentné.

## Endpoint

<<< @/../examples/return-url/public/index.php

## Falošná aplikácia Viamo

Každý `responseString` podpíše tak ako Viamo, pomocou `ViamoSign` a `key1`:

<<< @/../examples/return-url/fake-viamo.php

## Výstup

<<< @/../examples/return-url/expected-output.txt{text}

Čo si všimnúť:

- Obnovenie stránky so zaplateným výsledkom zobrazí tú istú stránku a nič nezmení.
- Zamietnutá platba (`RES:FAIL`) je správne podpísaná: objednávka sa označí ako neúspešná a zákazník to môže skúsiť znova.
- Zmena `RES:FAIL` na `RES:OK` poruší podpis (`Wrong sign`); odpoveď pre iného obchodníka sa odmietne ešte pred kontrolou podpisu (`Wrong BID`).
- Správne podpísanú odpoveď pre objednávku, ktorú e-shop nepozná, odmietne vlastné vyhľadanie objednávky v e-shope.
- Pri každom odmietnutí sa zákazníkovi zobrazí, že sa objednávka aktualizuje po potvrdení platby (nie že platba zlyhala), a dôvod sa zaloguje.

## Úložisko objednávok {#the-order-store}

<<< @/../examples/return-url/Orders.php

## Spustenie

```bash
cd examples/return-url && composer install && ./e2e.sh
```

<<< @/../examples/return-url/e2e.sh{bash}
