---
description: 'Časté otázky o omnipay-viamo - označenie objednávky ako zaplatenej, návratová URL a webhook, sumy, kľúče, testovací režim, verzie PHP.'
---

# Časté otázky

## Stačí `isSuccessful()` na označenie objednávky ako zaplatenej?

Dokazuje, že Viamo poslalo úspešný výsledok pre vás ako obchodníka. Skôr než objednávku označíte ako zaplatenú, nájdite ju podľa `getVs()`, skontrolujte, že ešte nie je zaplatená, a pri webhooku aj to, že podpísaná suma sa zhoduje s celkovou sumou objednávky. Pozrite [Kontrola sumy](./guide/webhook#check-the-amount).

## Potrebujem návratovú URL aj webhook?

Za zdroj pravdy považujte webhook: príde, aj keď zákazník zavrie aplikáciu skôr, než sa vráti do e-shopu, a nesie podpísanú sumu. Návratová URL slúži na zobrazenie výsledku zákazníkovi. Obe spracúvajte idempotentne; môžu prísť v ľubovoľnom poradí. Presne tak to robí [príklad e-shopu](./examples/shop).

## Prečo mi návratová URL nepovie sumu?

`responseString` od Viamo obsahuje len `BID`, `VS` a `RES`. Sumu určila platobná požiadavka, ktorú podpísal váš e-shop, a podpísanú sumu nesie webhook.

## Prečo sa platba začatá z QR kódu nevráti na moju návratovú URL?

Návratová URL (`RURL`) je len v odkaze do aplikácie; reťazec požiadavky v QR kóde žiadnu návratovú URL nemá. Spoľahnite sa na webhook a nechajte stránku pokladne kontrolovať stav objednávky.

## Ako zistím sumu z webhooku?

Zavolajte `getAmount()`, keď `isSuccessful()` vráti `true`. Vráti `payment.amount` presne tak, ako ju Viamo podpísalo (napríklad `24.90`), a `null`, keď podpis nie je platný.

## Ako vyzerajú `key1` a `key2`?

Každý je 128-bitový kľúč AES zapísaný ako 32 hexadecimálnych znakov, napríklad `000102030405060708090a0b0c0d0e0f` (to je testovací kľúč; nikdy ho nepoužívajte naostro). `key1` podpisuje platobnú požiadavku a overuje návratovú URL; `key2` overuje webhook. Čokoľvek iné vyhodí `InvalidKeyException`.

## Pri každej platbe dostávam `Wrong sign` alebo `Wrong signature`

Zvyčajne sú kľúče prehodené (`key1` a `key2`), testovacie kľúče sa používajú v produkcii (alebo naopak), alebo `bid` nepatrí ku kľúčom. Pri návratovej URL skontrolujte aj to, či sa `responseString` dostane do PHP nezmenený (zalogujte `$_GET['responseString']`).

## `getVs()` z `completePurchase()` vo verzii 4.x vracalo `null`

Bola to chyba ([#20](https://github.com/tomaj/omnipay-viamo/issues/20)); 5.0 vracia variabilný symbol. `getData()` ho má teraz pod kľúčom `VS`, nie `vs`. Pozrite [Prechod zo 4.x](./upgrade).

## Volá brána Viamo?

Nie. `purchase()` podpisuje a vykresľuje lokálne, `completePurchase()` a `webhook()` overujú, čo dostanú. Na Viamo ukazuje len URL QR API (`getQrUrl()`), ak ju použijete namiesto PNG z `getQrData()`.

## Prečo teda brána potrebuje HTTP klienta?

Každá brána Omnipay sa s ním vytvára. Balík vyžaduje `symfony/http-client` a `nyholm/psr7`, aby `Omnipay::create('Viamo')` fungovalo bez ďalšieho nastavovania. Konštruktoru `Gateway` môžete odovzdať vlastný `Omnipay\Common\Http\ClientInterface`.

## Ako otestujem integráciu bez mobilnej aplikácie Viamo?

Podpíšte `responseString` alebo telo webhooku pomocou `ViamoSign` a testovacieho kľúča a zavolajte s ním svoj endpoint. Pozrite [Testovanie integrácie](./guide/testing) a [príklady](./examples/).

## Existuje simulátor Viamo?

Od Viamo v tomto balíku nie. [Príklad e-shopu](./examples/shop) obsahuje malú falošnú aplikáciu Viamo, ktorá skontroluje podpis požiadavky a pošle webhook aj návratovú URL, čo na automatické testy stačí. [payment-simulator](https://github.com/tomaj/payment-simulator) pokrýva platobné brány slovenských bánk, nie Viamo.

## Ktoré verzie PHP sú podporované?

PHP 8.4, 8.5 a 8.6, testované v CI (aj s najnižšími povolenými závislosťami). PHP 8.7 sa testuje ako nočný build.

## Je to oficiálny balík Viamo?

Nie. Je to open-source brána pre Omnipay, ktorú udržiava Tomas Majer a ktorá nie je prepojená s Viamo. S obchodníckym účtom, kľúčmi a vlastnou dokumentáciou Viamo sa obráťte na Viamo.
