#!/usr/bin/env bash
set -uo pipefail

source tests/phase1/lib/assertions.sh

# DATA-03: migration output must separate WARN vs FATAL with summary counters.
tmp_normal="$(mktemp)"
tmp_fatal="$(mktemp)"
trap 'rm -f "$tmp_normal" "$tmp_fatal"' EXIT

php php/migrate.php >"$tmp_normal" 2>&1
normal_status="$?"
assert_exit_code "$normal_status" 0 "normal migration run should succeed" || exit 1

normal_output="$(cat "$tmp_normal")"
assert_contains "$normal_output" "WARN" \
  "normal run should emit WARN-classified non-fatal messages when present" || exit 1
assert_contains "$normal_output" "SUMMARY" \
  "normal run should emit summary block" || exit 1
assert_contains "$normal_output" "warning_count=" \
  "summary should include warning counter" || exit 1
assert_contains "$normal_output" "fatal_count=0" \
  "summary should report zero fatal events on success" || exit 1

(
  export DATABASE_URL="postgresql://invalid:invalid@127.0.0.1:1/invalid_db"
  php php/migrate.php
) >"$tmp_fatal" 2>&1
fatal_status="$?"
fatal_output="$(cat "$tmp_fatal")"

if [ "$fatal_status" -eq 0 ]; then
  fail "forced fatal migration path should exit non-zero" || exit 1
fi

assert_contains "$fatal_output" "FATAL" \
  "fatal path should emit FATAL classification" || exit 1
assert_contains "$fatal_output" "SUMMARY" \
  "fatal path should still emit summary block" || exit 1
assert_contains "$fatal_output" "fatal_count=" \
  "summary should include fatal counter" || exit 1

echo "PASS: DATA-03 log classification and summary behavior verified"
