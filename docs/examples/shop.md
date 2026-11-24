---
description: 'A complete shop with a checkout page, webhook and return URL, paid by a fake Viamo app that checks the request signature.'
---

# Shop with a fake Viamo

The whole payment, end to end. A minimal shop on the PHP built-in server has the three endpoints: the checkout page with the QR code and the app link, the webhook, and the return URL. A script plays the customer's phone and Viamo:

1. opens the checkout page and follows the app link,
2. checks the request's signature with `key1`, the way Viamo does, and shows what will be paid,
3. pays: POSTs the signed webhook (`key2`),
4. sends the customer back to `RURL` with a signed `responseString` (`key1`).

Three customers: one pays, one cancels, and one edits the amount in the app link before the app opens it.

## The shop

<<< @/../examples/shop/public/index.php

## The fake Viamo app

<<< @/../examples/shop/fake-viamo-app.php

## Output

<<< @/../examples/shop/expected-output.txt{text}

What to notice:

- For the paid order the webhook comes first and marks it paid; the return URL then finds it paid and only shows the thank-you page. The order records which one marked it (`by webhook`).
- The cancelled order gets no webhook, only a signed `RES:FAIL` on the return URL.
- The edited app link never reaches the shop: its signature no longer matches, so Viamo refuses it. This is why `purchase()` signs the request.

## Run it

```bash
cd examples/shop && composer install && ./e2e.sh
```

<<< @/../examples/shop/e2e.sh{bash}
