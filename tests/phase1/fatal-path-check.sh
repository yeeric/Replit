#!/usr/bin/env bash
set -uo pipefail

source tests/phase1/lib/assertions.sh

# DATA-01: startup must fail fast (non-zero) when a critical migration path fails.
tmp_output="$(mktemp)"
trap 'rm -f "$tmp_output"' EXIT

run_startup_with_forced_migration_failure() {
  (
    export DATABASE_URL="postgresql://invalid:invalid@127.0.0.1:1/invalid_db"
    bash start.sh
  ) >"$tmp_output" 2>&1 &

  local pid="$!"
  sleep 3

  if kill -0 "$pid" 2>/dev/null; then
    kill "$pid" 2>/dev/null || true
    wait "$pid" 2>/dev/null || true
    return 143
  fi

  wait "$pid"
  return $?
}

run_startup_with_forced_migration_failure
status="$?"
output="$(cat "$tmp_output")"

if [ "$status" -eq 0 ]; then
  fail "startup should exit non-zero when migration fails critically" || exit 1
fi

assert_not_contains "$output" "Starting PHP server on port 5000..." \
  "startup must not proceed to web server when migration fails" || exit 1
assert_contains "$output" "Running database migration..." \
  "startup should report migration stage before failing" || exit 1

echo "PASS: DATA-01 fatal startup-path behavior verified"
