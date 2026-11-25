---
description: 'A Viamo webhook endpoint with an amount check and idempotent order updates, tested with forged and malformed notifications.'
---

# Webhook

A shop's webhook endpoint on the PHP built-in server. A script plays Viamo's server and POSTs notifications: a paid one, the same one again, a failed payment, one whose amount was changed after signing, a correctly signed one with the wrong amount, one for an unknown order, one without a signature, and a body that is not JSON.

## The endpoint

<<< @/../examples/webhook/public/index.php

## The fake Viamo server

The signed text is `rid`, `vs` and `e2e` (each only when not empty), then `result`, `amount` and `id`, signed with `key2`:

<<< @/../examples/webhook/fake-viamo.php

## Output

<<< @/../examples/webhook/expected-output.txt{text}

What to notice:

- The second delivery of the paid notification answers `already paid`: the order was updated once.
- A failed payment is correctly signed; `webhook()` reports it as `Invalid result` and the shop records it as failed with a `200`.
- Changing the amount after signing breaks the signature (`Wrong signature`).
- A correctly signed notification whose amount differs from the order is stopped by the shop's own amount check (`422`). `webhook()` cannot know your order total.
- An unknown variable symbol is stopped by the shop's order lookup (`404`).

## Run it

```bash
cd examples/webhook && composer install && ./e2e.sh
```

The order store is the same as in the [return URL example](./return-url#the-order-store).
