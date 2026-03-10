#!/bin/bash
# Start PHP API server on port 8001 in the background
echo "Starting PHP API server on port 8001..."
nix-shell -p php -p php82Extensions.pdo_pgsql --run \
  "php -S 0.0.0.0:8001 -t php php/router.php" &

PHP_PID=$!
echo "PHP server started (PID: $PHP_PID)"

# Start Node.js / Vite dev server (handles port 5000, serves frontend + proxies /api to PHP)
npm run dev

# When npm exits, kill PHP
kill $PHP_PID 2>/dev/null
