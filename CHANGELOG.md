# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added

* GitHub Actions CI: tests on PHP 8.4 - 8.6 (plus `--prefer-lowest`) and a nightly PHP 8.7 job that may fail, PHP-CS-Fixer (PER-CS), PHPStan level max with strict rules, Rector, `composer audit`, 90% statement coverage and Infection mutation testing, Dependabot, CodeQL, OpenSSF Scorecard, and a tag-driven release workflow ([#13](https://github.com/tomaj/omnipay-viamo/issues/13), [#14](https://github.com/tomaj/omnipay-viamo/issues/14)).
* `LICENSE.md` (MIT), `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md` and a `.gitattributes` that keeps tests and tooling out of the Composer archive ([#12](https://github.com/tomaj/omnipay-viamo/issues/12)).
* `PurchaseResponse` has `getQrData()`, `getQrUrl()` and `getAppLink()`; `CompletePurchaseResponse::getMessage()` and `WebhookResponse::getMessage()` return the rejection reason ([#16](https://github.com/tomaj/omnipay-viamo/issues/16)).

### Changed

* **BREAKING CHANGE**: Requires PHP 8.4 or newer (was 8.3) ([#15](https://github.com/tomaj/omnipay-viamo/issues/15)).
* Requires `ext-gd` (the QR code is rendered as PNG with GD, which 4.x used without declaring it), `psr/http-client` and `nyholm/psr7`, so Omnipay's default HTTP client works without the `php-http/discovery` Composer plugin installing them. `symfony/http-client` 8 is allowed ([#12](https://github.com/tomaj/omnipay-viamo/issues/12)).
* Requires `omnipay/common` ^3.3 (was ~3.0) and PHPUnit 13 for development; `omnipay/tests` is no longer used.
* **BREAKING CHANGE**: Native parameter and return types on every method; the gateway, requests, responses and `ViamoSign` are `final`. Setters accept `?string` (`setRpt()`: `int|string|null`), getters return `?string`. See [UPGRADE-5.0.md](UPGRADE-5.0.md) ([#16](https://github.com/tomaj/omnipay-viamo/issues/16)).
* **BREAKING CHANGE**: `PurchaseResponse::getVs()`, `WebhookResponse::getVs()`, `getPaymentId()` and `getNotificationId()` return `null` instead of `false` for a missing value, and return strings ([#16](https://github.com/tomaj/omnipay-viamo/issues/16)).
* **BREAKING CHANGE**: An invalid `key1`/`key2` (not 32 hexadecimal characters) throws `Omnipay\Viamo\Exception\InvalidKeyException` instead of signing with a zero-padded or truncated key. All package exceptions implement `Omnipay\Viamo\Exception\ViamoException` ([#16](https://github.com/tomaj/omnipay-viamo/issues/16)).
* **BREAKING CHANGE**: `completePurchase()` reads `responseString` from the gateway's HTTP request (`Request::createFromGlobals()` by default) instead of `$_GET` ([#16](https://github.com/tomaj/omnipay-viamo/issues/16)).
* `completePurchase()` compares the returned `BID` strictly (`!==`) instead of `!=`, so numeric-looking values no longer match loosely.

### Fixed

* `CompletePurchaseResponse::getVs()` returns the variable symbol; it always returned `null` because the request stored it under `vs`. **BREAKING CHANGE**: `getData()` now has it under `VS`; read it with `getVs()` ([#20](https://github.com/tomaj/omnipay-viamo/issues/20)).

### Security

* **Security**: The return URL and webhook signatures are compared with `hash_equals()` in constant time instead of `!=`/`!==`; a non-string webhook signature is rejected ([#16](https://github.com/tomaj/omnipay-viamo/issues/16)).

## [4.0.0] - 2026-03-27

### Changed

* **BREAKING CHANGE**: Requires PHP 8.3 or newer (was 8.1) ([#11](https://github.com/tomaj/omnipay-viamo/pull/11)).
* **BREAKING CHANGE**: Requires `endroid/qr-code` 6 (was 5). If your application uses `endroid/qr-code` directly, upgrade it to 6 as well ([#11](https://github.com/tomaj/omnipay-viamo/pull/11)).
* Requires `symfony/http-client` ^7.4 as the HTTP client for Omnipay ([#11](https://github.com/tomaj/omnipay-viamo/pull/11)).

## [3.1.1] - 2024-01-05

### Fixed

* Installation on PHP 8.1: the `symfony/http-client` ^7.0 requirement added in 3.1.0 needs PHP 8.2, so it is removed again, and `composer.lock` is no longer committed ([#10](https://github.com/tomaj/omnipay-viamo/pull/10)).

## [3.1.0] - 2024-01-05

### Changed

* **BREAKING CHANGE**: Requires PHP 8.1 or newer and `endroid/qr-code` 5 (was 3). The QR code is rendered with endroid's `PngWriter`; the `qr_data` (base64 PNG) in the purchase response is unchanged ([#9](https://github.com/tomaj/omnipay-viamo/pull/9)).
* Requires `symfony/http-client` ^7.0 ([#9](https://github.com/tomaj/omnipay-viamo/pull/9)).

## [3.0.1] - 2020-11-03

### Changed

* Development dependency update (`symfony/http-foundation` 4.4.16); no change in the library code ([#3](https://github.com/tomaj/omnipay-viamo/pull/3)).

## [3.0.0] - 2019-02-20

### Changed

* **BREAKING CHANGE**: Requires `omnipay/common` 3 (was 2). Follow the Omnipay 3 upgrade guide (HTTP client, `Omnipay\Common\Http\ClientInterface`).

## [1.0.1] - 2018-08-13

### Added

* The webhook response for a successful payment includes the Viamo `payment_id`.

## [1.0.0] - 2018-06-14

Initial release. Omnipay 2 gateway for Viamo mobile payments (Slovakia):

* `purchase()` builds the signed Viamo payment request (`BID`, amount, currency, `VS`/`CS`/`SS`, `RPT`, `MSG`, return URL) and returns a QR code (`qr_data` as a base64 PNG, `qr_url` for Viamo's QR API) and an app link (`viamo://`, `viamo-staging://` in test mode).
* `completePurchase()` verifies the `responseString` returned to the return URL (BID and AES signature with `key1`).
* `webhook()` verifies Viamo's payment notification (signed with `key2`) and returns the variable symbol and notification id.

[unreleased]: https://github.com/tomaj/omnipay-viamo/compare/4.0.0...HEAD
[4.0.0]: https://github.com/tomaj/omnipay-viamo/compare/3.1.1...4.0.0
[3.1.1]: https://github.com/tomaj/omnipay-viamo/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/tomaj/omnipay-viamo/compare/3.0.1...3.1.0
[3.0.1]: https://github.com/tomaj/omnipay-viamo/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/tomaj/omnipay-viamo/compare/1.0.1...3.0.0
[1.0.1]: https://github.com/tomaj/omnipay-viamo/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/tomaj/omnipay-viamo/releases/tag/1.0.0
