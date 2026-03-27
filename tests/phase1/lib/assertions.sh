#!/usr/bin/env bash

fail() {
  printf 'ASSERTION FAILED: %s\n' "$1" >&2
  return 1
}

assert_exit_code() {
  local actual="$1"
  local expected="$2"
  local context="${3:-exit code assertion}"

  if [ "$actual" -ne "$expected" ]; then
    fail "$context (expected=$expected actual=$actual)" || return 1
  fi
}

assert_contains() {
  local haystack="$1"
  local needle="$2"
  local context="${3:-contains assertion}"

  if ! printf '%s' "$haystack" | grep -Fq -- "$needle"; then
    fail "$context (missing \"$needle\")" || return 1
  fi
}

assert_not_contains() {
  local haystack="$1"
  local needle="$2"
  local context="${3:-not-contains assertion}"

  if printf '%s' "$haystack" | grep -Fq -- "$needle"; then
    fail "$context (unexpected \"$needle\")" || return 1
  fi
}

assert_numeric_equal() {
  local actual="$1"
  local expected="$2"
  local context="${3:-numeric equality assertion}"

  case "$actual:$expected" in
    (*[!0-9-]*:*) fail "$context (actual is not numeric: \"$actual\")" || return 1 ;;
    (*:*[!0-9-]*) fail "$context (expected is not numeric: \"$expected\")" || return 1 ;;
  esac

  if [ "$actual" -ne "$expected" ]; then
    fail "$context (expected=$expected actual=$actual)" || return 1
  fi
}
