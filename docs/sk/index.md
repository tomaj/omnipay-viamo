---
layout: home
title: Omnipay Viamo

hero:
  name: Omnipay Viamo
  text: Mobilné platby Viamo pre PHP
  tagline: Podpíšte platbu ako QR kód a odkaz do aplikácie a potom overte, čo príde späť na návratovú URL a do notifikácie (webhooku). Brána Omnipay pre PHP 8.4+.
  image:
    src: /illustrations/hero-sk-light.svg
    alt: E-shop zobrazí podpísaný QR kód Viamo, zákazník zaplatí v mobilnej aplikácii Viamo a e-shop overí návratovú URL aj notifikáciu
  actions:
    - theme: brand
      text: Začíname
      link: /sk/guide/getting-started
    - theme: alt
      text: Príklady
      link: /sk/examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-viamo

features:
  - icon:
      src: /icons/qr.svg
      alt: QR kód
      width: 48px
      height: 48px
    title: QR kód a odkaz do aplikácie
    details: purchase() podpíše platobnú požiadavku kľúčom key1 a vráti ju ako QR kód v PNG (base64), URL QR API od Viamo a odkaz viamo://, ktorý v mobile otvorí aplikáciu.
    link: /sk/guide/purchase
    linkText: purchase()
  - icon:
      src: /icons/verified.svg
      alt: Overené
      width: 48px
      height: 48px
    title: Overená návratová URL
    details: completePurchase() prečíta responseString z návratovej URL, skontroluje, že BID je váš a podpis sedí, a vráti OK, FAIL alebo ERROR aj s dôvodom.
    link: /sk/guide/return-url
    linkText: completePurchase()
  - icon:
      src: /icons/webhook.svg
      alt: Webhook
      width: 48px
      height: 48px
    title: Overená notifikácia
    details: webhook() overí podpis kľúčom key2 nad rid, vs, e2e, result, amount a id platby, takže notifikáciu nemožno podvrhnúť ani zmeniť jej sumu.
    link: /sk/guide/webhook
    linkText: webhook()
  - icon:
      src: /icons/key.svg
      alt: Kľúč
      width: 48px
      height: 48px
    title: Podpisy, ktoré si overíte
    details: AES-128 nad hashom SHA-256, porovnanie cez hash_equals(). Testy aj príklady prepočítajú každý podpis nástrojom openssl.
    link: /sk/guide/signatures
    linkText: Ako funguje podpisovanie
  - icon:
      src: /icons/omnipay.svg
      alt: Zástrčka
      width: 48px
      height: 48px
    title: Obyčajná brána Omnipay
    details: Omnipay::create('Viamo'), raz nastavíte bid, key1 a key2 a používate známe API požiadaviek a odpovedí. Brána sama nevolá Viamo cez HTTP.
    link: /sk/guide/configuration
    linkText: Konfigurácia
  - icon:
      src: /icons/offline.svg
      alt: Laboratórna banka
      width: 48px
      height: 48px
    title: Príklady, ktoré bežia offline
    details: Päť úplných receptov, od platby cez QR kód po e-shop, ktorý na vstavanom PHP serveri zaplatí falošná aplikácia Viamo. CI každý spustí a porovná jeho výstup.
    link: /sk/examples/
    linkText: Prezrieť príklady
---

<div class="home-more">

## Ako to vyzerá

Bránu vytvoríte raz, potom podpíšete platbu a ukážete ju zákazníkovi:

<<< @/../examples/qr-checkout/run.php#purchase{php}

QR kód obsahuje podpísaný reťazec požiadavky a odkaz do aplikácie nesie tie isté polia a k tomu vašu návratovú URL. Toto je skutočný výstup [príkladu platby cez QR kód](/sk/examples/qr-checkout):

```text
request string: QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*SIG:5B0A9AE6F98801683C4D63687D2B3FD8
app link: viamo://?requestString=QP:1.0*BID:42*AM:24.90*CC:EUR*VS:2026000123*MSG:Order 2026000123*RURL:https://shop.example/viamo/return?order=2026000123*SIG:DF2A817C8B1A274AD04619AF4B6897B4
```

Keď zákazník zaplatí, Viamo zavolá váš webhook a aplikácia vráti zákazníka do e-shopu. Obe odpovede nesú podpis a obe overíte jedným riadkom:

```php
$paid = $gateway->webhook(['json' => $json])->send()->isSuccessful();
$paid = $gateway->completePurchase()->send()->isSuccessful();
```

## Kam ďalej

- [Začíname](/sk/guide/getting-started): inštalácia balíka a jedna platba od začiatku do konca.
- [Priebeh platby](/sk/guide/payment-flow): kto s kým komunikuje a akým kľúčom je ktorá správa podpísaná.
- [Príklady](/sk/examples/): úplné, otestované recepty: platba cez QR kód, návratová URL, webhook, celý e-shop, podpisy.
- [Konfigurácia](/sk/guide/configuration): všetky parametre brány a platby.
- [Referencia API](/sk/api/reference): každá verejná trieda a metóda.

</div>
