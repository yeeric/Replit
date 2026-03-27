#!/bin/bash
# CISC 332 Conference — PHP + HTMX + Tailwind
# Runs DB migration/seed, then serves the app on port 5000.
set -euo pipefail

echo "[start] phase=migration begin"
echo "Running database migration..."
php php/migrate.php
echo "[start] phase=migration success"
echo "[start] phase=serve begin"
echo "Starting PHP server on port 5000..."
exec php -S 0.0.0.0:5000 php/index.php
