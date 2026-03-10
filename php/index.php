<?php
/**
 * CISC 332 Conference Management — PHP Entry Point
 * All requests are routed here by the PHP built-in server.
 * Serves HTML pages and HTMX partial responses.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/layout.php';

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = ($path === '' || $path === null) ? '/' : $path;

// Debug endpoint — surface DB/env status in production
if ($path === '/debug-status') {
    header('Content-Type: text/plain');
    echo "PHP: " . phpversion() . "\n";
    $dbUrl = getenv('DATABASE_URL');
    echo "DATABASE_URL set: " . ($dbUrl ? 'yes (len=' . strlen($dbUrl) . ')' : 'NO') . "\n";
    echo "PGHOST: " . (getenv('PGHOST') ?: 'not set') . "\n";
    echo "PGPORT: " . (getenv('PGPORT') ?: 'not set') . "\n";
    echo "PGDATABASE: " . (getenv('PGDATABASE') ?: 'not set') . "\n";
    try {
        $db = getDb();
        $row = $db->query("SELECT COUNT(*) AS n FROM attendee")->fetch();
        echo "DB connection: OK\n";
        echo "Attendees count: " . ($row['n'] ?? '?') . "\n";
    } catch (Throwable $e) {
        echo "DB connection: FAILED\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    exit;
}

// Route to the correct page handler
$routes = [
    '/'           => __DIR__ . '/pages/dashboard.php',
    '/committees' => __DIR__ . '/pages/committees.php',
    '/hotels'     => __DIR__ . '/pages/hotels.php',
    '/schedule'   => __DIR__ . '/pages/schedule.php',
    '/sponsors'   => __DIR__ . '/pages/sponsors.php',
    '/jobs'       => __DIR__ . '/pages/jobs.php',
    '/attendees'  => __DIR__ . '/pages/attendees.php',
];

// Strip query string and match exact or prefix
$base = strtok($path, '?') ?: '/';

foreach ($routes as $route => $file) {
    if ($base === $route || str_starts_with($base, $route . '/')) {
        require $file;
        exit;
    }
}

// 404
http_response_code(404);
echo '<!DOCTYPE html><html><body><h1>404 — Page Not Found</h1><a href="/">Go home</a></body></html>';
