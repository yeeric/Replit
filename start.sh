#!/bin/bash
# CISC 332 Conference — PHP + HTMX + Tailwind
# Serves the full app (HTML pages + HTMX partials) on port 5000 via PHP's built-in server.
echo "Starting PHP server on port 5000..."
exec php -S 0.0.0.0:5000 php/index.php
