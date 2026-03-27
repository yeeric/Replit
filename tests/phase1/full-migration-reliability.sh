#!/usr/bin/env bash
set -euo pipefail

bash tests/phase1/fatal-path-check.sh
bash tests/phase1/enum-path-check.sh
bash tests/phase1/log-classification-check.sh
bash tests/phase1/repeat-run-idempotency.sh

echo "PASS: full phase1 migration reliability suite completed"
