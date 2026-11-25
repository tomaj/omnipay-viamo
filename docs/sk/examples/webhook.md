---
description: 'Endpoint pre webhook Viamo s kontrolou sumy a idempotentnou aktualizáciou objednávok, otestovaný sfalšovanými a poškodenými notifikáciami.'
---

# Webhook

Endpoint pre webhook e-shopu na vstavanom PHP serveri. Skript hrá server Viamo a posiela notifikácie metódou POST: zaplatenú, tú istú znova, neúspešnú platbu, notifikáciu so sumou zmenenou po podpísaní, správne podpísanú notifikáciu so zlou sumou, notifikáciu pre neznámu objednávku, notifikáciu bez podpisu a telo, ktoré nie je JSON.

## Endpoint

<<< @/../examples/webhook/public/index.php

## Falošný server Viamo

Podpisovaný text je `rid`, `vs` a `e2e` (každé len keď nie je prázdne), potom `result`, `amount` a `id`, podpísaný kľúčom `key2`:

<<< @/../examples/webhook/fake-viamo.php

## Výstup

<<< @/../examples/webhook/expected-output.txt{text}

Čo si všimnúť:

- Druhé doručenie zaplatenej notifikácie odpovie `already paid`: objednávka sa aktualizovala len raz.
- Neúspešná platba je správne podpísaná; `webhook()` ju hlási ako `Invalid result` a e-shop ju zaznamená ako neúspešnú s odpoveďou `200`.
- Zmena sumy po podpísaní poruší podpis (`Wrong signature`).
- Správne podpísanú notifikáciu, ktorej suma sa líši od objednávky, zastaví vlastná kontrola sumy v e-shope (`422`). `webhook()` celkovú sumu vašej objednávky poznať nemôže.
- Neznámy variabilný symbol zastaví vyhľadanie objednávky v e-shope (`404`).

## Spustenie

```bash
cd examples/webhook && composer install && ./e2e.sh
```

Úložisko objednávok je rovnaké ako v [príklade s návratovou URL](./return-url#the-order-store).
