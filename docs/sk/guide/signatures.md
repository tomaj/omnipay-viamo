---
description: 'Algoritmus podpisu Viamo (AES-128-ECB nad prvými 16 bajtmi SHA-256), čo sa podpisuje ktorým kľúčom a ako to overiť cez openssl.'
---

# Podpisy

Každá správa medzi vaším e-shopom a Viamo nesie `SIG` (alebo `signature.sign`), ktorý vypočíta `Omnipay\Viamo\Core\ViamoSign`:

1. SHA-256 podpisovaného reťazca, prvých 16 bajtov (binárne).
2. Šifrovanie týchto 16 bajtov algoritmom AES-128-ECB bez paddingu kľúčom obchodníka (32 hexadecimálnych znakov dekódovaných na 16 bajtov).
3. 16-bajtový výsledok ako hexadecimálny reťazec veľkými písmenami (32 znakov).

Tá istá hodnota cez `openssl` CLI:

```bash
printf '%s' 'BID:42*VS:777*RES:OK' | openssl dgst -sha256 -binary | head -c 16 \
  | openssl enc -aes-128-ecb -nopad -K 000102030405060708090a0b0c0d0e0f | xxd -p -u
# 04B15D742A42AD489F05F2174C32DBBD
```

<<< @/snippets/signature.php

## Čo sa podpisuje

| Správa | Kľúč | Podpisovaný reťazec |
|---|---|---|
| Platobná požiadavka (QR kód, `qr_url`) | `key1` | `QP:1.0*BID:…*AM:…[*CC:…][*VS:…][*CS:…][*SS:…][*RPT:…][*MSG:…]` |
| Odkaz do aplikácie | `key1` | Tie isté polia plus `*RURL:…` |
| Návratová URL (`responseString`) | `key1` | Všetko pred posledným `*SIG:`, napríklad `BID:42*VS:777*RES:OK` |
| Webhook | `key2` | `rid` + `vs` + `e2e` (každé len vtedy, keď nie je prázdne) + `result` + `amount` + `id`, bez oddeľovačov |

## Overenie

`ViamoSign::verify()` porovná očakávaný podpis s prijatým cez `hash_equals()` (v konštantnom čase). Na veľkosti hexadecimálnych písmen nezáleží; skrátený, prázdny alebo nereťazcový podpis sa odmietne. Používajú ho `completePurchase()` aj `webhook()`, takže ho nemusíte volať sami.

Kľúč, ktorý nemá presne 32 hexadecimálnych znakov, vyhodí `InvalidKeyException`, namiesto toho aby sa podpisovalo nesprávnym kľúčom.

## Overte si to sami

[Príklad podpisov](../examples/signatures) vypíše podpisy platobnej požiadavky, návratovej URL, webhooku a správy v UTF-8 a jeho `e2e.sh` každý z nich prepočíta cez `openssl` CLI, nezávisle od PHP. Text v UTF-8 sa podpisuje ako jeho bajty; nič sa vopred nenormalizuje ani nekóduje.
