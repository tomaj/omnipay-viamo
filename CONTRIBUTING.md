# Contributing

Contributions are welcome and will be credited.

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold it.

Security vulnerabilities are reported in private. See [SECURITY.md](SECURITY.md). Do not open a public issue for a security report.

## Pull requests

- Follow [PER Coding Style](https://www.php-fig.org/per/coding-style/). `composer phpcs` checks `src` and `tests`.
- Add tests for every behaviour change. A patch without tests will not be accepted.
- A change near signing or signature verification must keep the expected signatures computed independently with the `openssl` CLI (see `tests/Core/ViamoSignTest.php`). Signatures are compared with `hash_equals()`.
- Document behaviour changes in `docs/`, `README.md` and `CHANGELOG.md`.
- Follow [Semantic Versioning](https://semver.org/). The current development line is 5.x. Breaking changes wait for the next major version.
- Open one pull request per change, from a feature branch. Do not ask us to pull from your `master` branch.
- Keep the history coherent. Squash work-in-progress commits before submitting.

## Development

omnipay-viamo supports PHP 8.4, 8.5 and 8.6. Continuous integration runs on PHP 8.4, PHP 8.4 with the lowest dependencies, PHP 8.5, and PHP 8.6, which also runs the 90% coverage check and Infection. A nightly PHP 8.7 build runs too, but its failure does not fail the workflow.

```bash
git clone https://github.com/tomaj/omnipay-viamo.git
cd omnipay-viamo
composer install
composer phpunit
composer phpstan
composer phpcs
composer rector
```

`composer check` runs the code style check, PHPStan, Rector and the tests in one go.

PHPUnit fails on any notice, deprecation or warning, including PHPUnit's own. Deprecations that one dependency raises inside another are ignored, so the lowest supported dependencies still pass.

`composer phpunit` sets `XDEBUG_MODE=off` and passes `--no-coverage`. To generate an HTML and Clover coverage report or run mutation testing (requires Xdebug or PCOV):

```bash
composer coverage
composer infection
```

The documentation site lives in `docs/` (VitePress, Node.js 22). PHP examples are files in `docs/snippets/`, imported into the pages with `<<< @/snippets/<file>.php`; `tests/DocsSnippetsTest.php` runs every one of them, and PHPStan and PHP-CS-Fixer check them like the code.

```bash
cd docs
npm ci
npm run docs:dev
npm run docs:links
```

Short PHP blocks written directly in `README.md`, `UPGRADE-5.0.md` or a docs page run in `tests/InlineSnippetsTest.php`; put `<!-- snippet: skip -->` before a block that on purpose no longer works (the 4.x side of an upgrade step).

The runnable recipes live in `examples/<name>/` (`e2e.sh` and `expected-output.txt`), and their docs pages include the code with `<<< @/../examples/<name>/...`. The Examples workflow runs each one against the pull request's code; run one locally the same way:

```bash
examples/ci/recipe.sh shop
```

When a change alters an example's output on purpose, update its `expected-output.txt` in the same pull request.

The site is in English and Slovak: English pages are in `docs/`, Slovak pages in `docs/sk/` with the same paths. Change both in the same pull request; `npm run docs:locales` (run in CI) fails when a page has no twin in the other language. Code and example output are shared, only the prose is translated.

The logo, favicon, hero illustration, feature icons, payment flow diagram and social card are generated (the hero illustration and the payment flow diagram in English and Slovak): edit `docs/scripts/art.py`, then run `npm run art` in `docs/` (Python 3 and Google Chrome; set `CHROME_PATH` if Chrome is elsewhere) and commit the generated files.

Maintainers publish releases by pushing a version tag; see [RELEASING.md](RELEASING.md).

By contributing, you agree that your contributions are licensed under the MIT License.
