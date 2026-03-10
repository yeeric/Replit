<?php
/**
 * Database connection using PDO + PostgreSQL.
 * Prefers DATABASE_URL (always present in Replit deployments),
 * falls back to individual PG* environment variables.
 */
function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $databaseUrl = getenv('DATABASE_URL');

    if ($databaseUrl) {
        // Parse the URL: postgresql://user:pass@host:port/dbname?params
        $p = parse_url($databaseUrl);
        $host   = $p['host'] ?? 'localhost';
        $port   = $p['port'] ?? 5432;
        $dbname = ltrim($p['path'] ?? '/postgres', '/');
        $user   = $p['user'] ?? 'postgres';
        $pass   = $p['pass'] ?? '';

        // Preserve any query string options (e.g. sslmode=disable)
        $options = '';
        if (!empty($p['query'])) {
            parse_str($p['query'], $qparams);
            $pairs = [];
            foreach ($qparams as $k => $v) {
                $pairs[] = "{$k}={$v}";
            }
            if ($pairs) $options = ';' . implode(';', $pairs);
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}{$options}";
    } else {
        // Fallback to individual PG* vars
        $host   = getenv('PGHOST')     ?: 'localhost';
        $port   = getenv('PGPORT')     ?: '5432';
        $dbname = getenv('PGDATABASE') ?: 'postgres';
        $user   = getenv('PGUSER')     ?: 'postgres';
        $pass   = getenv('PGPASSWORD') ?: '';
        $dsn    = "pgsql:host={$host};port={$port};dbname={$dbname}";
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
