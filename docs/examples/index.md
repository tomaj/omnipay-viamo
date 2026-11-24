---
description: 'Complete, runnable omnipay-viamo recipes that run offline and are tested in CI against their expected output.'
---

# Examples

Every example is a small, complete program in [`examples/`](https://github.com/tomaj/omnipay-viamo/tree/master/examples) of the repository. They run offline: the shops run on the PHP built-in server on `127.0.0.1`, and a small script plays the Viamo app and Viamo's server with the test keys. No request reaches Viamo and no money moves.

The [Examples workflow](https://github.com/tomaj/omnipay-viamo/actions?query=workflow%3AExamples) runs each one against the code of every pull request and compares its output with the committed `expected-output.txt`, which is the output shown on each page. The signature examples also recompute every signature with the `openssl` CLI.

| Example | Shows | Runs |
|---|---|---|
| [QR checkout](./qr-checkout) | `purchase()`: request string, QR code PNG, QR API URL, app link, test mode | CLI |
| [Return URL](./return-url) | `completePurchase()` with paid, declined, forged and incomplete responses | PHP built-in server |
| [Webhook](./webhook) | `webhook()` with idempotency, an amount check, forged and malformed notifications | PHP built-in server |
| [Shop with a fake Viamo](./shop) | The whole flow: checkout page, fake Viamo app, webhook, return URL, a tampered link | PHP built-in server |
| [Signatures and errors](./signatures) | `ViamoSign` against `openssl`, `verify()` edge cases, key and parameter exceptions | CLI |

## Running an example

Each example has its own `composer.json` that requires `tomaj/omnipay-viamo`:

```bash
git clone https://github.com/tomaj/omnipay-viamo.git
cd omnipay-viamo/examples/shop
composer install
./e2e.sh
```

`e2e.sh` prints the output shown on the example's page. To run an example against your local checkout of the library instead of the released package, use the same script as CI, from the repository root:

```bash
examples/ci/recipe.sh shop
```

It copies the example to a temporary directory, installs the library from the checkout through a Composer path repository, runs `e2e.sh` and diffs the output with `expected-output.txt`.

The examples use the test keys of the library's own test suite (`bid` `42`, `key1` `000102030405060708090a0b0c0d0e0f`, `key2` `F0E1D2C3B4A5968778695A4B3C2D1E0F`). Override them with the `VIAMO_BID`, `VIAMO_KEY1` and `VIAMO_KEY2` environment variables; the expected output only matches the defaults.
