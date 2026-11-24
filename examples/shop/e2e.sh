#!/usr/bin/env bash
# Starts the shop on the PHP built-in server, lets a fake Viamo app pay through it and
# prints what the customer sees, the reasons the shop logged, and the orders afterwards.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }

work="$(mktemp -d)"
export ORDERS_FILE="$work/orders.json" LOG_FILE="$work/shop.log"
: > "$LOG_FILE"
php -r 'require "Orders.php"; (new Orders(getenv("ORDERS_FILE")))->seed([
    "2026000123" => ["total" => "24.90", "currency" => "EUR"],
    "2026000124" => ["total" => "9.90", "currency" => "EUR"],
    "2026000125" => ["total" => "120.00", "currency" => "EUR"],
]);'

php -S 127.0.0.1:8363 public/index.php > "$work/server.log" 2>&1 &
server=$!
disown "$server"
trap 'kill $server 2>/dev/null || true; rm -rf "$work"' EXIT
for _ in $(seq 1 50); do php -r 'exit(@fsockopen("127.0.0.1", 8363) ? 0 : 1);' 2>/dev/null && break; sleep 0.1; done

php fake-viamo-app.php
echo
echo "shop log:"
cat "$LOG_FILE"
echo
echo "orders:"
php -r 'require "Orders.php"; foreach ((new Orders(getenv("ORDERS_FILE")))->all() as $id => $o) { echo "  $id {$o["total"]} {$o["currency"]} {$o["status"]}", $o["paid_by"] ? " (by {$o["paid_by"]})" : "", PHP_EOL; }'

# the shop must not have emitted any PHP warning or error
if grep -E 'PHP (Warning|Notice|Deprecated|Fatal|Parse)' "$work/server.log" >&2; then exit 1; fi
