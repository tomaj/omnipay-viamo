# Signatures

Every message between your shop and Viamo carries a `SIG` (or `signature.sign`) computed by `Omnipay\Viamo\Core\ViamoSign`:

1. SHA-256 of the signed string, first 16 bytes (raw).
2. AES-128-ECB encryption of those 16 bytes, without padding, with the merchant key (32 hexadecimal characters decoded to 16 bytes).
3. The 16-byte result as uppercase hexadecimal (32 characters).

The same value with the `openssl` CLI:

```bash
printf '%s' 'BID:42*VS:777*RES:OK' | openssl dgst -sha256 -binary | head -c 16 \
  | openssl enc -aes-128-ecb -nopad -K 000102030405060708090a0b0c0d0e0f | xxd -p -u
# 04B15D742A42AD489F05F2174C32DBBD
```

<<< @/snippets/signature.php

## What is signed

| Message | Key | Signed string |
|---|---|---|
| Payment request (QR code, `qr_url`) | `key1` | `QP:1.0*BID:…*AM:…[*CC:…][*VS:…][*CS:…][*SS:…][*RPT:…][*MSG:…]` |
| App link | `key1` | The same fields plus `*RURL:…` |
| Return URL (`responseString`) | `key1` | Everything before the last `*SIG:`, for example `BID:42*VS:777*RES:OK` |
| Webhook | `key2` | `rid` + `vs` + `e2e` (each only when not empty) + `result` + `amount` + `id`, without separators |

## Verification

`ViamoSign::verify()` compares the expected signature with the received one with `hash_equals()` (constant time). Hexadecimal case does not matter; a truncated, empty or non-string signature is rejected. `completePurchase()` and `webhook()` use it, so you do not need to call it yourself.

A key that is not exactly 32 hexadecimal characters throws `InvalidKeyException` instead of signing with a wrong key.
