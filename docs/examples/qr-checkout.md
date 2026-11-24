---
description: 'Sign an order as a Viamo payment and show it as a QR code PNG and an app link; signatures recomputed with openssl.'
---

# QR checkout

Sign one order as a Viamo payment request and render it for a computer (QR code) and for a phone (app link). Then switch to test mode and see the staging app link. Nothing is sent anywhere: `purchase()` only signs and renders.

## The code

The gateway (every example that needs one has the same `gateway.php`):

<<< @/../examples/qr-checkout/gateway.php

The checkout:

<<< @/../examples/qr-checkout/run.php

## Output

<<< @/../examples/qr-checkout/expected-output.txt{text}

- The **request string** is what the QR code contains; `SIG` signs everything before `*SIG:`.
- The **QR API URL** renders the same code on Viamo's server. Note that the request string is not URL-encoded in it.
- The **QR code** is a 320 × 320 px PNG (300 px plus the margin). The example also saves it to the system temp directory.
- The **app link** adds `RURL` (the return URL) and has its own signature.
- In **test mode** only the scheme changes, to `viamo-staging://`.

## The openssl check

`e2e.sh` takes the request string and the app link from the output and recomputes both signatures with the `openssl` CLI, without PHP; the last two lines of the output are its verdict:

<<< @/../examples/qr-checkout/e2e.sh{bash}

## Run it

```bash
cd examples/qr-checkout && composer install && ./e2e.sh
```
