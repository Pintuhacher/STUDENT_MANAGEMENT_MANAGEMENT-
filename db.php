<?php
/**
 * Database Configuration & Connection File
 * Host: sql108.freehosting.digitalplat.org
 * Database: alalr_43295771_surajitdb
 * User: alalr_43295771
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'sql108.freehosting.digitalplat.org');
define('DB_NAME', 'alalr_43295771_surajitdb');
define('DB_USER', 'alalr_43295771');
define('DB_PASS', 'INU573Wl54n93nVW5NU03wValzoM');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Base Application Configuration
define('APP_NAME', 'EduDash - Student Management System');
define('APP_VERSION', '2.5.0');

// Calculate Dynamic Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Normalize root folder
$base_path = preg_replace('/\/(admin|teacher|student)$/', '', rtrim($script_dir, '/'));
define('BASE_URL', $protocol . $host . $base_path);

// Establish PDO Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Graceful error display without exposing sensitive traces
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Connection Notice - <?php echo htmlspecialchars(APP_NAME); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Database Connection Error</h5>
                        </div>
                        <div class="card-body">
                            <p class="lead text-danger">Unable to connect to the MySQL database on host: <code><?php echo htmlspecialchars(DB_HOST); ?></code></p>
                            <p><strong>Error Message:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
                            <hr>
                            <h6>Troubleshooting Guide for DigitalPlat / FreeHosting:</h6>
                            <ol class="small">
                                <li>Verify that your MySQL user <code><?php echo htmlspecialchars(DB_USER); ?></code> has full privileges assigned to database <code><?php echo htmlspecialchars(DB_NAME); ?></code> in your cPanel.</li>
                                <li>Ensure the SQL schema has been imported in phpMyAdmin via <code>database/schema.sql</code>.</li>
                                <li>Check if remote MySQL connections are enabled if connecting outside the hosting web server.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
