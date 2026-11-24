---
description: 'webhook() overí podpísanú JSON notifikáciu, ktorú Viamo pošle metódou POST na váš server, a ako skontrolovať sumu a zostať idempotentný.'
---

# webhook(): notifikácia

Viamo pošle k platbe JSON notifikáciu metódou POST na URL vášho webhooku. Telo dekódujte a odovzdajte ho ako `json`:

<<< @/snippets/webhook.php

## Notifikácia

```json
{
  "notificationId": "N-1001",
  "payment": {
    "rid": "R-77",
    "vs": "2026000123",
    "e2e": "E2E-2026000123",
    "result": "OK",
    "amount": "24.90",
    "id": 555001
  },
  "signature": { "sign": "A88917626B50E704C2870E09B510EB03" }
}
```

| Pole | Povinné | Podpísané | Význam |
|---|---|---|---|
| `payment.result` | áno | áno | `OK` pre úspešnú platbu |
| `payment.amount` | áno | áno | Zaplatená suma |
| `payment.id` | áno | áno | Identifikátor platby vo Viamo |
| `payment.rid`, `payment.vs`, `payment.e2e` | nie | áno, keď nie sú prázdne | Identifikátor požiadavky, variabilný symbol, end-to-end referencia |
| `signature.sign` | áno | | Podpis, 32 hexadecimálnych znakov |
| `notificationId` | nie | nie | Identifikátor tejto notifikácie |

Podpísaný text tvoria `rid`, `vs` a `e2e` (každé len vtedy, keď je prítomné a nie je prázdne), potom `result`, `amount` a `id`, spojené bez oddeľovačov a podpísané kľúčom `key2`. Pre notifikáciu vyššie je to `R-772026000123E2E-2026000123OK24.90555001`. Pozrite [Podpisy](./signatures).

## Čo sa kontroluje, v tomto poradí {#what-is-checked-in-order}

`webhook()->send()` najprv overí, že `bid`, `key2` a `json` nie sú `null` (inak `InvalidRequestException`). Potom:

| Kontrola | `getMessage()`, keď zlyhá |
|---|---|
| `json` má kľúč `payment` | `Missing payment data` |
| `payment` je pole s `result`, `amount` a `id` | `Missing payment parameter` |
| `signature.sign` je prítomný | `Missing signature` |
| podpis sedí (v konštantnom čase, bez ohľadu na veľkosť písmen) | `Wrong signature` |
| `payment.result` je `OK` | `Invalid result` |

`Invalid result` je jediné odmietnutie správne podpísanej notifikácie: Viamo naozaj oznámilo platbu, ktorá neprešla.

## Odpoveď

| Getter | Hodnota |
|---|---|
| `isSuccessful()` | `true` len pre správne podpísanú notifikáciu s `result` `OK` |
| `getVs()` | `payment.vs` ako reťazec alebo `null` |
| `getPaymentId()` | `payment.id` ako reťazec alebo `null` |
| `getNotificationId()` | `notificationId` ako reťazec alebo `null` |
| `getAmount()` | `payment.amount` presne ako text, ktorý bol podpísaný (`24.90`), alebo `null`, keď podpis nie je platný |
| `getMessage()`, `errorMessage()` | Prečo bola notifikácia odmietnutá, inak `null` |
| `getData()` | `['success' => bool, 'vs' => …, 'payment_id' => …, 'notification_id' => …, 'amount' => …]` plus `error`; `vs`, `payment_id` a `notification_id` si tu zachovávajú svoje JSON typy |

`vs`, `payment_id` a `notification_id` sú vyplnené aj pri `Wrong signature` a `Invalid result` (pri `Wrong signature` nie sú overené). `amount` sa vyplní, až keď je podpis overený (`Invalid result` a úspech).

## Kontrola sumy {#check-the-amount}

Suma je pokrytá podpisom. Keď je `isSuccessful()` `true`, porovnajte `getAmount()` so svojou objednávkou:

<<< @/../examples/webhook/public/index.php#amount{php}

## Odpoveď pre Viamo a idempotencia

- Objednávku označte ako zaplatenú, len ak ešte zaplatená nie je; druhé doručenie tej istej notifikácie alebo návratová URL, ktorá prišla skôr, nesmú nič zmeniť.
- Keď výsledok uložíte, odpovedzte rýchlo so stavom 2xx; pomalú prácu (e-maily, faktúry) urobte až potom. Akú odpoveď Viamo očakáva a ako notifikácie opakuje, nájdete v dokumentácii Viamo pre obchodníkov.
- Na odmietnutú notifikáciu odpovedzte stavom 4xx a zalogujte `getMessage()`.

[Príklad webhooku](../examples/webhook) spustí takýto endpoint na vstavanom PHP serveri a pošle mu zaplatenú notifikáciu, tú istú ešte raz, neúspešnú platbu, notifikáciu so zmenenou sumou, správne podpísanú nesprávnu sumu, neznámu objednávku, notifikáciu bez podpisu a telo, ktoré nie je JSON.
