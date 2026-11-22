# Test mode

With `testMode` set to `true`, `purchase()` returns an app link for Viamo's staging app (`viamo-staging://` instead of `viamo://`). Everything else stays the same: the request string, the signature, the QR code and the QR API URL `https://qr.viamo.sk/api/`.

<<< @/snippets/test-mode.php

Use the test credentials (`bid`, `key1`, `key2`) Viamo gives you for the staging environment together with test mode.

## Testing locally

The return URL and the webhook need no connection to Viamo: build a signed `responseString` or webhook body yourself with `ViamoSign` and your test key (see [Signatures](./signatures)), and call your endpoint with it. The library's own tests do exactly that; see [`tests/Message`](https://github.com/tomaj/omnipay-viamo/tree/master/tests/Message).

[payment-simulator](https://github.com/tomaj/payment-simulator) simulates the Slovak bank gateways (Tatra banka TatraPay, CardPay and ComfortPay, VÚB ePlatby, SLSP SporoPay); it does not simulate Viamo.
