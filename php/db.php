<?php
/**
 * Database connection using PDO + PostgreSQL.
 * Reads connection details from environment variables set by Replit.
 */
function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host     = getenv('PGHOST')     ?: 'localhost';
    $port     = getenv('PGPORT')     ?: '5432';
    $dbname   = getenv('PGDATABASE') ?: 'postgres';
    $user     = getenv('PGUSER')     ?: 'postgres';
    $password = getenv('PGPASSWORD') ?: '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
