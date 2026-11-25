---
description: 'ViamoSign vypočítaný v PHP a prepočítaný nástrojom openssl, hraničné prípady verify() a výnimky nesprávne nastavenej brány.'
---

# Podpisy a chyby

Pomocou `ViamoSign` podpíše platobnú požiadavku, návratovú URL, text webhooku a správu v UTF-8, overí, ako `verify()` naloží so zmeneným, skráteným, prázdnym alebo nereťazcovým podpisom, a vyvolá výnimky nesprávne nastavenej brány. `e2e.sh` potom každý podpis prepočíta nástrojom `openssl`, bez PHP.

## Kód

<<< @/../examples/signatures/run.php

## Výstup

<<< @/../examples/signatures/expected-output.txt{text}

- Každý riadok `sign` obsahuje kľúč, podpis a podpisovaný text. Posledný riadok výstupu potvrdzuje, že `openssl` vypočítal tie isté štyri podpisy.
- `verify()` prijme podpis malými písmenami a všetko ostatné odmietne bez výnimky, aj hodnotu, ktorá nie je reťazec.
- Kľúč, ktorý nemá 32 hexadecimálnych znakov, vyhodí `InvalidKeyException`, priamo cez `ViamoSign` aj cez bránu.
- `webhook()` bez `json` vyhodí výnimku `InvalidRequestException` z Omnipay.

## Kontrola cez openssl

<<< @/../examples/signatures/e2e.sh{bash}

## Spustenie

```bash
cd examples/signatures && composer install && ./e2e.sh
```
