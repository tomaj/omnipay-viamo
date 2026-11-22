# Contributing

Contributions are welcome and will be credited.

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold it.

Security vulnerabilities are reported in private. See [SECURITY.md](SECURITY.md). Do not open a public issue for a security report.

## Pull requests

- Follow [PER Coding Style](https://www.php-fig.org/per/coding-style/). `composer phpcs` checks `src` and `tests`.
- Add tests for every behaviour change. A patch without tests will not be accepted.
- A change near signing or signature verification must keep the expected signatures computed independently with the `openssl` CLI (see `tests/Viamo/Core/ViamoSignTest.php`). Signatures are compared with `hash_equals()`.
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

The documentation site lives in `docs/` (VitePress, Node.js 22):

```bash
cd docs
npm ci
npm run docs:dev
npm run docs:links
```

Maintainers publish releases by pushing a version tag; see [RELEASING.md](RELEASING.md).

By contributing, you agree that your contributions are licensed under the MIT License.
