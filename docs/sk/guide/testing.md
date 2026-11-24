---
description: 'Otestujte integráciu Viamo bez mobilnej aplikácie Viamo: návratové URL a webhooky si podpíšte sami cez ViamoSign a testovací kľúč.'
---

# Testovanie integrácie

Návratová URL ani webhook nepotrebujú spojenie s Viamo: odpovede Viamo sú obyčajné podpísané reťazce. Podpíšte si ich sami cez `Omnipay\Viamo\Core\ViamoSign` a svoje **testovacie** kľúče a potom s nimi zavolajte svoje endpointy.

## Podpísanie návratovej URL

<<< @/snippets/testing-return-url.php

## Podpísanie webhooku

Podpísaný text je `rid` + `vs` + `e2e` (každé len vtedy, keď nie je prázdne) + `result` + `amount` + `id`:

<<< @/snippets/testing-webhook.php

## Čo testovať

| Prípad | Očakávaný výsledok |
|---|---|
| Návratová URL s `RES:OK` | Objednávka je zaplatená; obnovenie stránky nič nezmení |
| Návratová URL s `RES:FAIL` | Objednávka zostane nezaplatená alebo sa označí ako neúspešná |
| Pole zmenené po podpísaní | Odmietnuté (`Wrong sign`), objednávka sa nezmení |
| `BID` iného obchodníka | Odmietnuté (`Wrong BID`) |
| Chýba `responseString` | Odmietnuté, žiadne PHP warningy |
| Webhook s `result` `OK` | Objednávka je zaplatená raz; na tú istú notifikáciu znova sa odpovie bez druhého vedľajšieho účinku |
| Webhook s `result` `FAIL` | Zaznamenaný ako neúspešný (`Invalid result`) |
| Suma vo webhooku zmenená po podpísaní | Odmietnuté (`Wrong signature`) |
| Správne podpísaný webhook s inou sumou, než má objednávka | Odmietnuté vašou kontrolou sumy |
| Webhook pre neznámy variabilný symbol | Odmietnuté vaším vyhľadaním objednávky |
| Návratová URL a webhook v ľubovoľnom poradí | Objednávka je zaplatená raz |

Príklady [návratovej URL](../examples/return-url), [webhooku](../examples/webhook) a [e-shopu](../examples/shop) sú presne takéto testy: každý spustí e-shop na vstavanom PHP serveri, zahrá proti nemu Viamo a porovná výstup s commitnutým súborom. Skopírujte si jeden a nasmerujte ho na svoje endpointy.

## V PHPUnit

Bránu vytvorte so Symfony `Request`, ktorý obsahuje query reťazec, namiesto spoliehania sa na `$_GET`:

<<< @/snippets/testing-phpunit.php

## Proti Viamo

Testovacie prístupové údaje, ktoré vám dá Viamo, používajte s [testovacím režimom](./test-mode): odkaz do aplikácie otvorí testovaciu (staging) aplikáciu Viamo (`viamo-staging://`). Kým prejdete na produkčné kľúče, urobte tam jednu skutočnú platbu od začiatku do konca.

[payment-simulator](https://github.com/tomaj/payment-simulator) simuluje platobné brány slovenských bánk (Tatra banka, VÚB, Slovenská sporiteľňa); Viamo nesimuluje.
