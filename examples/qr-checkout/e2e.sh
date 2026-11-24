#!/usr/bin/env bash
# Runs the checkout, then recomputes the signatures with the openssl CLI: SIG is the first
# 16 bytes of SHA-256 of everything before "*SIG:", encrypted with AES-128-ECB under key1,
# as uppercase hexadecimal.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }

output="$(php run.php)"
echo "$output"

check() { # check <label> <signed string ending with *SIG:…>
    local signed="${2%\*SIG:*}" sig="${2##*\*SIG:}" expected
    expected="$(printf '%s' "$signed" | openssl dgst -sha256 -binary | head -c 16 \
        | openssl enc -aes-128-ecb -nopad -K 000102030405060708090a0b0c0d0e0f | od -An -tx1 | tr -d ' \n' | tr 'a-f' 'A-F')"
    if [[ "$sig" == "$expected" ]]; then
        echo "openssl: SIG of the $1 matches ($expected)"
    else
        echo "openssl: SIG of the $1 is $sig, expected $expected" >&2
        exit 1
    fi
}
check "request string" "$(echo "$output" | sed -n 's/^request string: //p')"
check "app link" "$(echo "$output" | sed -n 's/^app link request string: //p')"
