#!/usr/bin/env bash
# Points an example at this checkout of omnipay-viamo instead of Packagist:
#   examples/ci/use-local-package.sh /path/to/example
# Adds a path repository for the repository root, requires tomaj/omnipay-viamo:dev-master from it and
# installs the example. Used by the examples workflow, so a pull request runs the examples against
# its own code. It rewrites the example's composer.json: run it on a copy.
set -euo pipefail
root="$(cd "$(dirname "$0")/../.." && pwd)"
project="${1:?usage: $0 /path/to/example}"
cd "$project"
# shellcheck disable=SC2016 # $argv is PHP, not shell
# json_encode, so a checkout path with quotes or backslashes still gives valid JSON
repository="$(php -r 'echo json_encode(["type" => "path", "url" => $argv[1], "options" => ["symlink" => false, "versions" => ["tomaj/omnipay-viamo" => "dev-master"]]], JSON_UNESCAPED_SLASHES);' "$root")"
composer config repositories.omnipay-viamo "$repository"
composer config minimum-stability dev
composer config prefer-stable true
composer require --no-interaction --no-progress --with-all-dependencies tomaj/omnipay-viamo:dev-master
# fail when Composer took the package from anywhere else
diff -rq "$root/src" vendor/tomaj/omnipay-viamo/src > /dev/null \
    || { echo "fail: vendor/tomaj/omnipay-viamo is not this checkout" >&2; exit 1; }
echo "tomaj/omnipay-viamo installed from $root"
