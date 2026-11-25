#!/usr/bin/env bash
# Runs one example against this checkout of omnipay-viamo and compares its output with the
# committed expected-output.txt:
#   examples/ci/recipe.sh qr-checkout
# The example is copied to a temporary directory first, so the checkout stays untouched.
# Everything runs offline: no request leaves 127.0.0.1.
set -euo pipefail
examples="$(cd "$(dirname "$0")/.." && pwd)"
name="${1:?usage: $0 <example>}"
[[ -x "$examples/$name/e2e.sh" ]] || { echo "fail: $examples/$name/e2e.sh not found" >&2; exit 1; }
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cp -R "$examples/$name" "$work/$name"
rm -rf "$work/$name/vendor" "$work/$name/composer.lock"
"$examples/ci/use-local-package.sh" "$work/$name" > "$work/install.log" 2>&1 \
    || { cat "$work/install.log" >&2; exit 1; }
"$work/$name/e2e.sh" > "$work/output.txt"
if diff -u "$examples/$name/expected-output.txt" "$work/output.txt"; then
    echo "ok: $name matches expected-output.txt"
else
    echo "fail: the output of $name differs from expected-output.txt (diff above)" >&2
    exit 1
fi
