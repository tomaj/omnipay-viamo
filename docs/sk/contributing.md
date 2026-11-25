---
title: 'Ako prispieť'
description: 'Ako prispieť do omnipay-viamo: pull requesty, vývojové prostredie, testy, dokumentácia v angličtine a slovenčine a spustiteľné príklady.'
---

# Ako prispieť

Príspevky sú vítané a uvedieme vás ako autora.

Projekt sa riadi [Kódexom správania Contributor Covenant](https://github.com/tomaj/omnipay-viamo/blob/master/CODE_OF_CONDUCT.md). Účasťou na projekte sa zaväzujete ho dodržiavať.

Bezpečnostné zraniteľnosti sa hlásia neverejne, pozrite [Bezpečnosť](./security). Bezpečnostný problém nehláste cez verejné issue.

Úplné pravidlá v angličtine sú v [CONTRIBUTING.md](https://github.com/tomaj/omnipay-viamo/blob/master/CONTRIBUTING.md).

## Pull requesty

- Dodržiavajte [PER Coding Style](https://www.php-fig.org/per/coding-style/). `composer phpcs` kontroluje `src` a `tests`.
- Ku každej zmene správania pridajte testy. Zmenu bez testov neprijmeme.
- Zmena v okolí podpisovania alebo overovania podpisov musí zachovať očakávané podpisy vypočítané nezávisle nástrojom `openssl` (pozrite `tests/Core/ViamoSignTest.php`). Podpisy sa porovnávajú cez `hash_equals()`.
- Zmeny správania zdokumentujte v `docs/`, `README.md` a `CHANGELOG.md`.
- Dodržiavajte [sémantické verzovanie](https://semver.org/). Aktuálna vývojová línia je 5.x. Nekompatibilné zmeny počkajú na ďalšiu hlavnú verziu.
- Na každú zmenu otvorte jeden pull request z vlastnej vetvy. Nežiadajte nás o pull z vašej vetvy `master`.
- Udržiavajte súdržnú históriu. Rozpracované commity pred odoslaním spojte (squash).

## Vývoj

omnipay-viamo podporuje PHP 8.4, 8.5 a 8.6. CI beží na PHP 8.4, PHP 8.4 s najnižšími verziami závislostí, PHP 8.5 a PHP 8.6, kde beží aj kontrola 90 % pokrytia kódu testami a Infection. Beží aj nočný build PHP 8.7, jeho zlyhanie však workflow nezhodí.

```bash
git clone https://github.com/tomaj/omnipay-viamo.git
cd omnipay-viamo
composer install
composer phpunit
composer phpstan
composer phpcs
composer rector
```

`composer check` spustí naraz kontrolu štýlu kódu, PHPStan, Rector a testy.

PHPUnit zlyhá pri akomkoľvek notice, deprecation alebo warningu, vrátane tých od samotného PHPUnit. Deprecations, ktoré jedna závislosť vyvolá v inej, sa ignorujú, aby prešli aj najnižšie podporované verzie závislostí.

`composer phpunit` nastaví `XDEBUG_MODE=off` a odovzdá `--no-coverage`. Report pokrytia v HTML a Clover alebo mutačné testovanie (vyžaduje Xdebug alebo PCOV) spustíte takto:

```bash
composer coverage
composer infection
```

## Dokumentácia

Tento web sa zostavuje nástrojom [VitePress](https://vitepress.dev/) (Node.js 22) z priečinka [`docs/`](https://github.com/tomaj/omnipay-viamo/tree/master/docs). Anglické stránky sú v koreni `docs/`, slovenské v `docs/sk/` s rovnakými cestami. Obe verzie meňte v tom istom pull requeste; `npm run docs:locales` (beží aj v CI) zlyhá, keď niektorej stránke chýba náprotivok v druhom jazyku. Kód a výstupy príkladov sú spoločné, prekladá sa len text.

```bash
cd docs
npm ci
npm run docs:dev
npm run docs:links
```

PHP príklady sú súbory v `docs/snippets/`, ktoré stránky vkladajú cez `<<< @/snippets/<file>.php`; `tests/DocsSnippetsTest.php` spustí každý z nich a PHPStan aj PHP-CS-Fixer ich kontrolujú ako ostatný kód.

Krátke PHP bloky napísané priamo v `README.md`, `UPGRADE-5.0.md` alebo na stránke dokumentácie spúšťa `tests/InlineSnippetsTest.php`; pred blok, ktorý zámerne už nefunguje (strana 4.x pri prechode na novú verziu), dajte `<!-- snippet: skip -->`.

Spustiteľné recepty sú v `examples/<name>/` (`e2e.sh` a `expected-output.txt`) a ich stránky v dokumentácii vkladajú kód cez `<<< @/../examples/<name>/...`. Workflow Examples spustí každý z nich proti kódu pull requestu; lokálne ho spustíte rovnako:

```bash
examples/ci/recipe.sh shop
```

Keď zmena zámerne zmení výstup príkladu, upravte v tom istom pull requeste aj jeho `expected-output.txt`.

Logo, favicon, úvodná ilustrácia, ikony, diagram priebehu platby a obrázok pre sociálne siete sa generujú (úvodná ilustrácia a diagram po anglicky aj po slovensky): upravte `docs/scripts/art.py`, potom v `docs/` spustite `npm run art` (Python 3 a Google Chrome; ak je Chrome inde, nastavte `CHROME_PATH`) a vygenerované súbory commitnite.

Vydania publikujú správcovia pushnutím tagu s verziou, pozrite [RELEASING.md](https://github.com/tomaj/omnipay-viamo/blob/master/RELEASING.md).

Príspevkom súhlasíte, že váš kód bude licencovaný pod licenciou MIT.
