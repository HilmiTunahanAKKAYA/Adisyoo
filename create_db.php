<?php
/**
 * create_db.php
 * Small helper to create the `adisyon` database and import `init.sql`.
 * Usage: open in browser: http://localhost/proje/create_db.php
 * Note: Designed for local development (XAMPP).
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbName = 'adisyon';
$sqlFile = __DIR__ . '/init.sql';

header('Content-Type: text/plain; charset=utf-8');
echo "DB import helper\n";

if (!file_exists($sqlFile)) {
    echo "ERROR: init.sql not found at: $sqlFile\n";
    exit(1);
}

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_errno) {
    echo "Connect failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n";
    exit(1);
}

// create database if not exists with utf8mb4
$createSql = "CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!$mysqli->query($createSql)) {
    echo "Failed to create database: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
    exit(1);
}
echo "Database '$dbName' ready or already existed.\n";

// Select DB
if (!$mysqli->select_db($dbName)) {
    echo "Failed to select database: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
    exit(1);
}

// Read SQL file
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Could not read init.sql\n";
    exit(1);
}

// Execute multi queries
if ($mysqli->multi_query($sql)) {
    $count = 0;
    do {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
        if ($mysqli->more_results()) {
            // move to next
        }
        $count++;
    } while ($mysqli->more_results() && $mysqli->next_result());
    echo "Imported SQL (may have executed multiple statements).\n";
} else {
    echo "Error importing SQL: (" . $mysqli->errno . ") " . $mysqli->error . "\n";
    // show first 2000 chars of sql for debugging
    echo "\n--- SQL file head (2k) ---\n" . substr($sql,0,2000) . "\n";
    exit(1);
}

echo "Done. You can now visit http://localhost/proje/index.php\n";
echo "If you still see DB errors, check credentials in db.php and that MySQL is running.\n";

$mysqli->close();

?>
