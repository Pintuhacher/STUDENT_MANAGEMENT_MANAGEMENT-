<?php
/**
 * Database configuration. Set DB_HOST, DB_NAME, DB_USER, DB_PASS and DB_PORT
 * as environment variables in Render; never commit database credentials.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'EduDash - Student Management System');
define('APP_VERSION', '2.5.0');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$protocol = $https ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$base_path = preg_replace('#/(admin|teacher|student)$#', '', rtrim($script_dir, '/'));
define('BASE_URL', $protocol . $host . $base_path);

if (DB_HOST === '' || DB_NAME === '' || DB_USER === '' || DB_PASS === '') {
    http_response_code(503);
    exit('Database is not configured. Set DB_HOST, DB_NAME, DB_USER and DB_PASS in the Render service environment.');
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Unable to connect to the configured database. Check the DB_* variables and database network access in Render.');
}
