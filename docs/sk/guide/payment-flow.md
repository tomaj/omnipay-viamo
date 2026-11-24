---
description: 'Ako putuje platba Viamo medzi zákazníkom, vaším e-shopom, mobilnou aplikáciou Viamo a Viamo a ktorý kľúč podpisuje ktorú správu.'
---

# Priebeh platby

Platba cez Viamo nie je presmerovanie na stránku banky. Váš e-shop zobrazí podpísanú platobnú požiadavku, zákazník zaplatí v mobilnej aplikácii Viamo na svojom telefóne a Viamo potom oznámi výsledok vášmu e-shopu dvoma nezávislými cestami.

<div class="diagram">

![Sekvencia: zákazník prejde k pokladni, purchase() e-shopu podpíše požiadavku kľúčom key1 a vráti QR kód alebo odkaz do aplikácie, zákazník ho naskenuje mobilnou aplikáciou Viamo, Viamo overí podpis a zákazník platbu potvrdí, Viamo pošle metódou POST webhook podpísaný kľúčom key2, ktorý e-shop overí cez webhook(), a aplikácia vráti zákazníka na návratovú URL s responseString podpísaným kľúčom key1, ktorý e-shop overí cez completePurchase().](/diagrams/payment-flow-sk-light.svg){.only-light width=940 height=690}
![Tá istá sekvencia priebehu platby v tmavých farbách.](/diagrams/payment-flow-sk-dark.svg){.only-dark width=940 height=690}

</div>

## Kroky

| # | Kto | Čo | Podpísané kľúčom | V tomto balíku |
|---|---|---|---|---|
| 1 | E-shop | Zostaví reťazec požiadavky `QP:1.0*BID:…*AM:…*…` a podpíše ho | `key1` | [`purchase()`](./purchase) |
| 2 | E-shop → zákazník | QR kód (počítač) alebo odkaz do aplikácie `viamo://` (telefón); odkaz do aplikácie nesie aj návratovú URL `RURL` | `key1` | `getQrData()`, `getQrUrl()`, `getAppLink()` |
| 3 | Zákazník → Viamo | Aplikácia načíta požiadavku; Viamo overí jej podpis, takže zmenenú sumu odmietne; zákazník platbu potvrdí | | |
| 4 | Viamo → e-shop | `POST` JSON notifikácie na URL vášho webhooku | `key2` | [`webhook()`](./webhook) |
| 5 | Aplikácia Viamo → e-shop | Zákazník sa vráti na `RURL` s `?responseString=BID:…*VS:…*RES:…*SIG:…` | `key1` | [`completePurchase()`](./return-url) |

Kroky 4 a 5 sú nezávislé. Ktorýkoľvek môže prísť prvý, ktorýkoľvek môže chýbať (zákazník zavrie aplikáciu; váš server bol nedostupný, keď prišiel webhook) a webhook môže byť doručený viackrát. Ako a kedy Viamo notifikácie opakuje, popisuje dokumentácia Viamo pre obchodníkov; tento balík ich len overuje.

## Ktorý kľúč na čo slúži

| Kľúč | Podpisuje | Overuje |
|---|---|---|
| `key1` | Platobnú požiadavku (QR kód a odkaz do aplikácie) | `responseString` návratovej URL |
| `key2` | | Notifikáciu webhooku |

Oba sú 128-bitové AES kľúče zapísané ako 32 hexadecimálnych znakov. Stránka [Podpisy](./signatures) ukazuje algoritmus a ako ktorýkoľvek podpis vypočítať cez `openssl` CLI.

## Čo musí urobiť váš e-shop

1. **Uložte objednávku skôr, než zobrazíte QR kód.** Jej číslo použite ako variabilný symbol (`vs`): je to jediné pole, ktoré spája odpovede Viamo s vašou objednávkou.
2. **Overte každú odpoveď.** Zavolajte `webhook()` alebo `completePurchase()` a nedôverujte ničomu, kým `isSuccessful()` nie je `true`.
3. **Porovnajte s objednávkou.** Nájdite ju podľa `getVs()`. Podpísaná suma (`amount`) z webhooku musí sedieť so sumou objednávky (návratová URL sumu nenesie).
4. **Označte ju ako zaplatenú raz.** Odpoveď, ktorá príde prvá, označí objednávku ako zaplatenú; druhá ju nájde zaplatenú a nič nezmení.
5. **Ukážte zákazníkovi výsledok** na návratovej URL. Ak sa ju nepodarilo overiť, napíšte, že objednávka sa aktualizuje po potvrdení platby, a nie „platba zlyhala“.

[Príklad e-shopu](../examples/shop) implementuje všetkých päť krokov proti falošnej aplikácii Viamo a jeho výstup ukazuje každý krok.
