---
description: 'Inštalácia omnipay-viamo, vytvorenie brány, zobrazenie QR kódu Viamo a overenie návratovej URL a webhooku.'
---

# Začíname

omnipay-viamo je brána pre [Omnipay](https://omnipay.thephpleague.com/), ktorá pridáva platby cez [Viamo](https://www.viamo.sk/), slovenskú mobilnú platobnú aplikáciu. Váš e-shop platbu podpíše a zobrazí ju ako QR kód (alebo na telefóne ako odkaz), zákazník ju potvrdí v mobilnej aplikácii Viamo a Viamo oznámi výsledok dvakrát: na váš **webhook** a na vašu **návratovú URL**. Tento balík podpíše požiadavku a overí obe odpovede. Sám neposiela žiadne HTTP požiadavky.

## Požiadavky

- PHP 8.4 alebo novšie
- `ext-openssl` a `ext-hash` (podpisy), `ext-gd` (PNG s QR kódom)
- Obchodnícky účet vo Viamo: identifikátor obchodníka (`bid`) a dva 128-bitové kľúče (`key1`, `key2`), každý zapísaný ako 32 hexadecimálnych znakov

Ostatné nainštaluje Composer: `omnipay/common`, `endroid/qr-code` pre QR kód a `symfony/http-client` s `nyholm/psr7` ako HTTP klienta, s ktorým sa vytvára každá brána Omnipay.

## Inštalácia

```bash
composer require tomaj/omnipay-viamo
```

## Platba od začiatku do konca

### 1. Vytvorte bránu

Vytvorte ju raz, s prístupovými údajmi od Viamo (všetky možnosti sú na stránke [Konfigurácia](./configuration)):

<<< @/snippets/gateway.php

### 2. Zobrazte platbu

`purchase()` podpíše platobnú požiadavku kľúčom `key1` a vráti ju ako QR kód a odkaz do aplikácie. Na počítači zobrazte QR kód, na telefóne odkaz:

<<< @/snippets/purchase.php

[Príklad platby cez QR kód](../examples/qr-checkout) spustí presne tento kód a vypíše, čo vytvoril; reťazec požiadavky v QR kóde vyzerá takto:

```text
QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*SIG:5B0A9AE6F98801683C4D63687D2B3FD8
```

### 3. Spracujte webhook

Keď zákazník zaplatí, Viamo pošle podpísanú JSON notifikáciu metódou POST na URL vášho webhooku. Príde aj vtedy, keď sa zákazník do e-shopu nikdy nevráti, preto ju berte ako zdroj pravdy:

<<< @/snippets/webhook.php

### 4. Spracujte návratovú URL

Mobilná aplikácia Viamo vráti zákazníka na `rurl` s podpísaným `responseString` v query reťazci. `completePurchase()` ho overí:

<<< @/snippets/complete-purchase.php

Pre podpísanú návratovú URL snippet vypíše:

```text
Payment for order 777 received.
```

a pre pozmenenú (podpis už nesedí):

```text
Payment failed: Wrong sign
```

::: warning Vždy overte, potom porovnajte s objednávkou
Platba je zaplatená, len keď `isSuccessful()` vráti `true`. Podpis dokazuje, že správa prišla od Viamo; o vašej objednávke však nič nevie. Objednávku nájdite podľa variabilného symbolu (`getVs()`), porovnajte sumu a stav so svojimi záznamami a spracovanie urobte idempotentné: návratová URL aj webhook môžu prísť obe, v ľubovoľnom poradí, a webhooky sa môžu opakovať. [Príklad e-shopu](../examples/shop) robí všetko toto.
:::

## Ďalšie kroky

- [Priebeh platby](./payment-flow): kto posiela čo a ktorým kľúčom je to podpísané
- [Konfigurácia](./configuration): všetky parametre brány a platby
- [purchase()](./purchase), [completePurchase()](./return-url), [webhook()](./webhook): každá operácia podrobne
- [Príklady](../examples/): päť úplných receptov, ktoré bežia offline
- [Testovanie integrácie](./testing) bez mobilnej aplikácie Viamo
