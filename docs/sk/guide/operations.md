---
description: 'Tri operácie brány Viamo, čo vracia každá požiadavka a ktorý kľúč používa.'
---

# Operácie

Brána má tri operácie. Každá vracia požiadavku Omnipay; `send()` vráti jej odpoveď. Žiadna z nich neposiela HTTP požiadavku.

| Operácia | Request | Response | Kľúč | Číta |
|---|---|---|---|---|
| [`purchase(array $parameters = [])`](./purchase) | `PurchaseRequest` | `PurchaseResponse` | `key1` | [platobné parametre](./configuration#payment-parameters) |
| [`completePurchase(array $parameters = [])`](./return-url) | `CompletePurchaseRequest` | `CompletePurchaseResponse` | `key1` | `responseString` z query reťazca HTTP požiadavky |
| [`webhook(array $parameters = [])`](./webhook) | `WebhookRequest` | `WebhookResponse` | `key2` | parameter `json` |

## Čo znamená `isSuccessful()`

| Response | `isSuccessful()` je `true`, keď | `isRedirect()` |
|---|---|---|
| `PurchaseResponse` | vždy: požiadavka bola podpísaná (nič ešte nie je zaplatené) | `false` |
| `CompletePurchaseResponse` | `responseString` je podpísaný kľúčom `key1`, má váš `BID` a `RES` je `OK` | `false` (zdedené) |
| `WebhookResponse` | notifikácia je podpísaná kľúčom `key2` a `payment.result` je `OK` | `false` |

Keď je `false`, `getMessage()` povie prečo. Stránka [Spracovanie chýb](./errors) uvádza všetky dôvody.

## Gettery na prvý pohľad

| | `PurchaseResponse` | `CompletePurchaseResponse` | `WebhookResponse` |
|---|---|---|---|
| Variabilný symbol | `getVs()` | `getVs()` | `getVs()` |
| Výsledok | | `getRes()`: `OK`, `FAIL`, `ERROR` | |
| Dôvod odmietnutia | | `getMessage()` | `getMessage()`, `errorMessage()` |
| Platba | `getQrData()`, `getQrUrl()`, `getAppLink()` | | `getAmount()`, `getPaymentId()`, `getNotificationId()` |
| Surové dáta | `getData()` | `getData()` | `getData()` |
