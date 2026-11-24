---
description: 'Test mode switches the app link to Viamo''s staging app; everything else stays the same.'
---

# Test mode

With `testMode` set to `true`, `purchase()` returns an app link for Viamo's staging app: `viamo-staging://` instead of `viamo://`.

<<< @/snippets/test-mode.php

Nothing else changes:

| | Production | Test mode |
|---|---|---|
| App link | `viamo://?requestString=…` | `viamo-staging://?requestString=…` |
| Request string and its signature | same | same |
| QR code (`getQrData()`) | same | same |
| QR API URL (`getQrUrl()`) | `https://qr.viamo.sk/api/` | `https://qr.viamo.sk/api/` |
| `completePurchase()`, `webhook()` | verify with your keys | verify with your keys |

The keys decide which environment a payment belongs to. Use the test credentials (`bid`, `key1`, `key2`) Viamo gives you for the staging environment together with test mode, and the production credentials without it. Switching only `testMode` while keeping production keys signs production requests that open the staging app.

You can switch per request: `$gateway->purchase(['testMode' => true, ...])`.

## Testing without Viamo

For automated tests you do not need the staging app at all: sign the return URL and the webhook yourself. See [Testing your integration](./testing).
