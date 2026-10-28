<?php
/**
 * Database Configuration & Connection
 * Event Management System
 */

// 1. Detect Database Credentials from Environment Variables (Railway / Cloud) or fallback to XAMPP defaults
$dbHost = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$dbName = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'event_management_db';
$dbUser = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$dbPass = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
$dbCharset = 'utf8mb4';

// Check if a single connection URL is provided (DATABASE_URL or MYSQL_URL)
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($dbUrl) {
    $parsedUrl = parse_url($dbUrl);
    if (!empty($parsedUrl['host'])) {
        $dbHost = $parsedUrl['host'];
    }
    if (!empty($parsedUrl['port'])) {
        $dbPort = (string)$parsedUrl['port'];
    }
    if (!empty($parsedUrl['user'])) {
        $dbUser = urldecode($parsedUrl['user']);
    }
    if (isset($parsedUrl['pass'])) {
        $dbPass = urldecode($parsedUrl['pass']);
    }
    if (!empty($parsedUrl['path'])) {
        $dbName = ltrim($parsedUrl['path'], '/');
    }
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', $dbCharset);

// 2. Dynamic Base URL Detection
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', $scriptDir);

if (preg_match('#^(.*?/event-management)#i', $scriptDir, $matches)) {
    // When placed in an 'event-management' subdirectory (e.g., XAMPP htdocs)
    $baseUrl = $matches[1];
} elseif ($scriptDir === '/' || $scriptDir === '' || $scriptDir === '.') {
    // Root-level deployment (e.g. Railway, production domains)
    $baseUrl = '';
} else {
    // If inside /admin or other subdirectories at root level
    $parts = array_values(array_filter(explode('/', $scriptDir)));
    if (!empty($parts) && $parts[0] !== 'admin') {
        $baseUrl = '/' . $parts[0];
    } else {
        $baseUrl = '';
    }
}
define('BASE_URL', rtrim($baseUrl, '/'));

// 3. Establish PDO Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (Throwable $e) {
    $isRailway = getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID') || getenv('PORT');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required - EventHub</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
            .setup-card { background: #1e293b; border-radius: 12px; max-width: 680px; width: 100%; padding: 32px; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
            .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: <?= $isRailway ? '#3b82f6' : '#64748b' ?>; color: white; margin-bottom: 12px; }
            h1 { color: #f59e0b; font-size: 22px; margin: 0 0 10px 0; }
            p { color: #94a3b8; line-height: 1.6; font-size: 14px; margin: 8px 0; }
            ol { color: #cbd5e1; line-height: 1.8; padding-left: 20px; font-size: 14px; }
            code { background: #0b1120; color: #38bdf8; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 13px; }
            .err { background: #451a1a; color: #fca5a5; padding: 12px; border-radius: 6px; font-size: 13px; margin: 16px 0; border: 1px solid #7f1d1d; word-break: break-all; }
            .env-info { background: #0f172a; border-radius: 6px; padding: 12px; margin: 16px 0; border: 1px solid #1e293b; font-size: 13px; }
            .env-info table { width: 100%; border-collapse: collapse; }
            .env-info td { padding: 4px 8px; color: #94a3b8; }
            .env-info td:first-child { font-weight: 600; color: #cbd5e1; width: 120px; }
        </style>
    </head>
    <body>
        <div class="setup-card">
            <span class="badge"><?= $isRailway ? 'Railway Environment' : 'Local Environment' ?></span>
            <h1>Database Connection Required</h1>
            <p>The PHP server is live and responding, but it cannot connect to the MySQL database.</p>
            
            <div class="err">
                <strong>Connection Error:</strong> <?= htmlspecialchars($e->getMessage()) ?>
            </div>

            <div class="env-info">
                <table>
                    <tr><td>Host:</td><td><code><?= htmlspecialchars(DB_HOST) ?></code></td></tr>
                    <tr><td>Port:</td><td><code><?= htmlspecialchars(DB_PORT) ?></code></td></tr>
                    <tr><td>Database:</td><td><code><?= htmlspecialchars(DB_NAME) ?></code></td></tr>
                    <tr><td>User:</td><td><code><?= htmlspecialchars(DB_USER) ?></code></td></tr>
                </table>
            </div>

            <?php if ($isRailway): ?>
                <h3 style="color: #60a5fa; margin-top: 20px; font-size: 16px;">Quick Fix on Railway:</h3>
                <ol>
                    <li>In your Railway project, click <strong>+ New</strong> &rarr; <strong>Database</strong> &rarr; <strong>Add MySQL</strong>.</li>
                    <li>Open your <code>event_manager</code> service &rarr; click <strong>Variables</strong> tab &rarr; click <strong>New Variable</strong> &rarr; <strong>Add Reference</strong> to link the MySQL variables (<code>MYSQLHOST</code>, <code>MYSQLPORT</code>, <code>MYSQLUSER</code>, <code>MYSQLPASSWORD</code>, <code>MYSQLDATABASE</code>).</li>
                    <li>Connect to the MySQL service and import <code>database/event_management.sql</code> to create the tables.</li>
                </ol>
            <?php else: ?>
                <h3 style="color: #60a5fa; margin-top: 20px; font-size: 16px;">Quick Local Fix (XAMPP):</h3>
                <ol>
                    <li>Start MySQL in XAMPP Control Panel.</li>
                    <li>Visit <a href="http://localhost/phpmyadmin" target="_blank" style="color: #60a5fa;">phpMyAdmin</a> and import <code>database/event_management.sql</code>.</li>
                    <li>Refresh this page.</li>
                </ol>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}
