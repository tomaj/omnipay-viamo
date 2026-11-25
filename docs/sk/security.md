---
title: 'Bezpečnosť'
description: 'Ako nahlásiť zraniteľnosť v omnipay-viamo, podporované verzie a čo balík robí pre bezpečnosť platieb.'
---

# Bezpečnosť

## Čo robí balík

- **Podpisuje každú platobnú požiadavku** kľúčom `key1`, takže Viamo odmietne zmenenú sumu, variabilný symbol aj návratovú URL.
- **Overuje každú odpoveď**: návratovú URL kľúčom `key1` a vaším `BID`, notifikáciu (webhook) kľúčom `key2`. Podpisy sa porovnávajú cez `hash_equals()` v konštantnom čase, nikdy cez `==`.
- **Odmieta nesprávne kľúče**: kľúč, ktorý nie je 128-bitovým kľúčom v hexadecimálnom zápise, vyhodí `InvalidKeyException` namiesto podpisu doplneným alebo orezaným kľúčom.
- **Odpovede od Viamo považuje za nedôveryhodný vstup**: poškodený `responseString` alebo telo notifikácie vráti odmietnutú odpoveď, nikdy nie varovanie PHP ani výnimku.
- **Je testovaný proti nezávisle vypočítaným hodnotám**: testy aj príklady prepočítavajú podpisy nástrojom `openssl`.

## Čo musí urobiť váš e-shop

- Držte `key1` a `key2` v tajnosti, mimo repozitára a oddelené pre testovacie a produkčné prostredie.
- Objednávku označte ako zaplatenú, až keď je `isSuccessful()` `true`, po tom, čo ju nájdete podľa `getVs()` a porovnáte podpísanú sumu z notifikácie s objednávkou.
- Spracovanie platby urobte idempotentné: môže prísť návratová URL aj notifikácia a notifikácia sa môže opakovať.
- Do `vs`, `cs`, `ss` a `msg` dávajte len hodnoty, ktoré má pod kontrolou váš e-shop: `*` oddeľuje polia reťazca požiadavky.
- Návratovú URL aj webhook prevádzkujte cez HTTPS.

## Podporované verzie

Bezpečnostné opravy sa vydávajú pre tieto verzie:

| Verzia | Podpora |
| ------- | --------- |
| 5.x     | Áno: opravy chýb a bezpečnostné opravy (po vydaní 5.0.0) |
| 4.x     | Áno do vydania 5.0.0, potom šesť mesiacov len bezpečnostné opravy |
| < 4.0   | Nie |

Vo vetve `master` je pripravovaná verzia 5.0.0. Po jej vydaní dostáva 4.x ešte šesť mesiacov iba opravy bezpečnostných zraniteľností; oprava sa vtedy vydá z vetvy vytvorenej z tagu `4.0.0`. Verzie staršie ako 4.0 bezpečnostné aktualizácie nedostávajú.

## Nahlásenie zraniteľnosti

Bezpečnostnú zraniteľnosť nehláste cez verejné GitHub issue.

Uprednostňovaný spôsob je [neverejné nahlásenie zraniteľnosti na GitHube](https://github.com/tomaj/omnipay-viamo/security/advisories/new).

Ak ho nemôžete použiť, pošlite podrobnosti e-mailom na **tomasmajer@gmail.com**.

Uveďte prosím:

- popis zraniteľnosti,
- kroky na zopakovanie alebo proof of concept,
- verziu omnipay-viamo a príslušnú operáciu (`purchase()`, `completePurchase()`, `webhook()`),
- možný dopad,
- navrhovanú opravu, ak ju máte.

### Odpoveď

Potvrdenie prijatia dostanete do 14 dní.

### Koordinované zverejnenie

Postupujeme podľa pravidiel koordinovaného zverejnenia. Prosím, dajte nám čas pripraviť opravu skôr, ako problém zverejníte.

Keď je oprava hotová, zverejníme [GitHub Security Advisory](https://github.com/tomaj/omnipay-viamo/security/advisories) a bezpečnostnú opravu zapíšeme do [zoznamu zmien](./changelog).

## Bezpečnostné aspekty

omnipay-viamo podpisuje platobné požiadavky a overuje výsledky platieb, ktoré posiela späť Viamo:

- `key1` podpisuje platobnú požiadavku a overuje návratovú URL, `key2` overuje notifikáciu. Oba kľúče držte v premenných prostredia alebo v úložisku tajomstiev; nikdy ich necommitujte ani nelogujte.
- Platbu vždy potvrďte cez `completePurchase()` alebo `webhook()`: platba je zaplatená, len keď `isSuccessful()` vráti `true`. Nedôverujte parametrom v query ani telám notifikácií, ktoré ste neoverili knižnicou.
- Podpis pokrýva polia, ktoré podpisuje Viamo, nie vašu objednávku. Po úspešnej odpovedi nájdite objednávku podľa variabilného symbolu (`getVs()`) a pred označením za zaplatenú porovnajte sumu a stav s vlastnými záznamami; spracovanie urobte idempotentné (môže prísť notifikácia aj návratová URL a notifikácie sa môžu opakovať).
- Návratovú URL aj endpoint webhooku prevádzkujte cez HTTPS.

Anglický originál týchto zásad je v [SECURITY.md](https://github.com/tomaj/omnipay-viamo/blob/master/SECURITY.md).
