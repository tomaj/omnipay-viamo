---
description: 'A return URL endpoint on the PHP built-in server that handles paid, declined, forged and incomplete Viamo responses.'
---

# Return URL

A shop's return URL endpoint on the PHP built-in server. A script plays the Viamo app: it sends the customer back with a paid result, the same result again (a reload), a declined payment, two forged results, a correctly signed result for an unknown order, and no `responseString` at all.

The orders live in a small JSON file standing in for your database; marking an order paid is idempotent.

## The endpoint

<<< @/../examples/return-url/public/index.php

## The fake Viamo app

It signs each `responseString` the way Viamo does, with `ViamoSign` and `key1`:

<<< @/../examples/return-url/fake-viamo.php

## Output

<<< @/../examples/return-url/expected-output.txt{text}

What to notice:

- The reload of a paid result shows the same page and changes nothing.
- A declined payment (`RES:FAIL`) is correctly signed: the order is marked failed, and the customer can try again.
- Changing `RES:FAIL` to `RES:OK` breaks the signature (`Wrong sign`); a response for another merchant is refused before the signature is checked (`Wrong BID`).
- A correctly signed response for an order the shop does not know is refused by the shop's own lookup.
- Every refusal tells the customer that the order updates when the payment is confirmed, not that it failed, and logs the reason.

## The order store

<<< @/../examples/return-url/Orders.php

## Run it

```bash
cd examples/return-url && composer install && ./e2e.sh
```

<<< @/../examples/return-url/e2e.sh{bash}
