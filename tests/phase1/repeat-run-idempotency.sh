#!/usr/bin/env bash
set -uo pipefail

source tests/phase1/lib/assertions.sh

# DATA-04: repeated migration runs must converge to the same deterministic state.
tmp_first="$(mktemp)"
tmp_second="$(mktemp)"
tmp_probe_one="$(mktemp)"
tmp_probe_two="$(mktemp)"
trap 'rm -f "$tmp_first" "$tmp_second" "$tmp_probe_one" "$tmp_probe_two"' EXIT

capture_state() {
  local out_file="$1"
  php <<'PHP' >"$out_file" 2>&1
<?php
require_once __DIR__ . '/php/db.php';
$db = getDb();

$tables = [
  'attendee',
  'student',
  'professional',
  'sponsor',
  'company',
  'subcommittee',
  'committeemember',
  'memberofcommittee',
  'session',
  'hotelroom',
  'jobad',
];

$counts = [];
foreach ($tables as $table) {
  $counts[$table] = (int) $db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
ksort($counts);

$fingerprintParts = [];
foreach ($counts as $name => $count) {
  $fingerprintParts[] = "{$name}:{$count}";
}

echo "ATTENDEE_COUNT={$counts['attendee']}\n";
echo "STATE_FINGERPRINT=" . hash('sha256', implode('|', $fingerprintParts)) . "\n";
PHP
}

php php/migrate.php >"$tmp_first" 2>&1
first_status="$?"
assert_exit_code "$first_status" 0 "first migration run should complete" || exit 1
capture_state "$tmp_probe_one"
probe_one_status="$?"
assert_exit_code "$probe_one_status" 0 "first state probe should succeed" || exit 1

php php/migrate.php >"$tmp_second" 2>&1
second_status="$?"
assert_exit_code "$second_status" 0 "second migration run should complete" || exit 1
capture_state "$tmp_probe_two"
probe_two_status="$?"
assert_exit_code "$probe_two_status" 0 "second state probe should succeed" || exit 1

probe_one_output="$(cat "$tmp_probe_one")"
probe_two_output="$(cat "$tmp_probe_two")"

attendee_one="$(printf '%s\n' "$probe_one_output" | awk -F= '/^ATTENDEE_COUNT=/{print $2}')"
attendee_two="$(printf '%s\n' "$probe_two_output" | awk -F= '/^ATTENDEE_COUNT=/{print $2}')"
assert_numeric_equal "$attendee_one" "$attendee_two" \
  "attendee count should remain stable across repeated runs" || exit 1

fingerprint_one="$(printf '%s\n' "$probe_one_output" | awk -F= '/^STATE_FINGERPRINT=/{print $2}')"
fingerprint_two="$(printf '%s\n' "$probe_two_output" | awk -F= '/^STATE_FINGERPRINT=/{print $2}')"
if [ "$fingerprint_one" != "$fingerprint_two" ]; then
  fail "state fingerprint changed across repeated migration runs" || exit 1
fi

echo "PASS: DATA-04 repeat-run determinism verified"
