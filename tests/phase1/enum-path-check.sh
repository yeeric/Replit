#!/usr/bin/env bash
set -uo pipefail

source tests/phase1/lib/assertions.sh

# DATA-02: sponsor enum/type path must be valid and consistent.
tmp_output="$(mktemp)"
tmp_probe="$(mktemp)"
trap 'rm -f "$tmp_output" "$tmp_probe"' EXIT

php php/migrate.php >"$tmp_output" 2>&1
migrate_status="$?"
assert_exit_code "$migrate_status" 0 "migration should complete before enum checks" || exit 1

php <<'PHP' >"$tmp_probe" 2>&1
<?php
require_once __DIR__ . '/php/db.php';
$db = getDb();

$enumExists = (int) $db->query("SELECT COUNT(*) FROM pg_type WHERE typname = 'sponsor_level'")->fetchColumn();
$column = $db->query("
  SELECT data_type, udt_name
  FROM information_schema.columns
  WHERE table_schema = 'public'
    AND table_name = 'sponsor'
    AND column_name = 'sponsorlevel'
")->fetch(PDO::FETCH_ASSOC);

$castOk = 0;
try {
  $db->query("SELECT 'Gold'::sponsor_level")->fetchColumn();
  $castOk = 1;
} catch (Throwable $e) {
  $castOk = 0;
}

echo "ENUM_EXISTS={$enumExists}\n";
echo "COLUMN_DATA_TYPE=" . ($column['data_type'] ?? '') . "\n";
echo "COLUMN_UDT_NAME=" . ($column['udt_name'] ?? '') . "\n";
echo "CAST_OK={$castOk}\n";
PHP

probe_status="$?"
assert_exit_code "$probe_status" 0 "enum probe should execute without runtime errors" || exit 1
probe_output="$(cat "$tmp_probe")"

enum_exists="$(printf '%s\n' "$probe_output" | awk -F= '/^ENUM_EXISTS=/{print $2}')"
cast_ok="$(printf '%s\n' "$probe_output" | awk -F= '/^CAST_OK=/{print $2}')"
assert_numeric_equal "$enum_exists" 1 "sponsor_level enum type must exist" || exit 1
assert_numeric_equal "$cast_ok" 1 "sponsor_level cast path must be valid" || exit 1
assert_contains "$probe_output" "COLUMN_UDT_NAME=sponsor_level" \
  "sponsor.sponsorlevel must use sponsor_level enum type path" || exit 1

echo "PASS: DATA-02 sponsor enum/type path verified"
