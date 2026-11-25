---
description: 'ViamoSign computed by PHP and recomputed by the openssl CLI, verify() edge cases, and the exceptions of a misconfigured gateway.'
---

# Signatures and errors

Signs a payment request, a return URL, a webhook text and a UTF-8 message with `ViamoSign`, checks how `verify()` treats a changed, truncated, empty or non-string signature, and triggers the exceptions of a misconfigured gateway. `e2e.sh` then recomputes every signature with the `openssl` CLI, without PHP.

## The code

<<< @/../examples/signatures/run.php

## Output

<<< @/../examples/signatures/expected-output.txt{text}

- Each `sign` line is the key, the signature and the signed text. The last line of the output confirms that `openssl` computed the same four signatures.
- `verify()` accepts the lowercase form, and rejects anything else, including a non-string value, without throwing.
- A key that is not 32 hexadecimal characters throws `InvalidKeyException`, through `ViamoSign` directly and through the gateway.
- `webhook()` without `json` throws Omnipay's `InvalidRequestException`.

## The openssl check

<<< @/../examples/signatures/e2e.sh{bash}

## Run it

```bash
cd examples/signatures && composer install && ./e2e.sh
```
