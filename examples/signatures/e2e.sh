#!/usr/bin/env bash
# Runs run.php and recomputes every "sign" line with the openssl CLI, independently of PHP:
# the first 16 bytes of SHA-256, AES-128-ECB without padding under the key, uppercase hex.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }

output="$(php run.php)"
echo "$output"

checked=0
while IFS= read -r line; do
    rest="${line#sign }"
    key="${rest%% *}"; rest="${rest#* }"
    sig="${rest%% *}"; text="${rest#* }"
    expected="$(printf '%s' "$text" | openssl dgst -sha256 -binary | head -c 16 \
        | openssl enc -aes-128-ecb -nopad -K "$key" | od -An -tx1 | tr -d ' \n' | tr 'a-f' 'A-F')"
    [[ "$sig" == "$expected" ]] || { echo "openssl: $text gives $expected, PHP gave $sig" >&2; exit 1; }
    checked=$((checked + 1))
done < <(echo "$output" | grep '^sign ')
echo "openssl: all $checked signatures match"
