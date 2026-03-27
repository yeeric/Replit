#!/usr/bin/env bash
set -euo pipefail

bash tests/phase1/fatal-path-check.sh
bash tests/phase1/log-classification-check.sh

echo "PASS: quick migration reliability checks completed"
