#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DB_FILE="$ROOT_DIR/database/testing.sqlite"

mkdir -p "$ROOT_DIR/database"
rm -f "$DB_FILE"
touch "$DB_FILE"

DB_CONNECTION=sqlite \
DB_DATABASE="$DB_FILE" \
CACHE_STORE=array \
CACHE_DRIVER=array \
"$ROOT_DIR/vendor/bin/testbench" migrate:fresh --realpath \
  --path="$ROOT_DIR/database/migrations"

DB_CONNECTION=sqlite \
DB_DATABASE="$DB_FILE" \
CACHE_STORE=array \
CACHE_DRIVER=array \
"$ROOT_DIR/vendor/bin/testbench" migrate --realpath \
  --path="$ROOT_DIR/vendor/secretwebmaster/wncms-tags/database/migrations"

DB_CONNECTION=sqlite \
DB_DATABASE="$DB_FILE" \
CACHE_STORE=array \
CACHE_DRIVER=array \
"$ROOT_DIR/vendor/bin/testbench" migrate --realpath \
  --path="$ROOT_DIR/vendor/secretwebmaster/wncms-translatable/database/migrations"

echo "Prepared test database: $DB_FILE"
