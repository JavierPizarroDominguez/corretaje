<?php

require __DIR__ . '/../vendor/autoload.php';

$dbPath = __DIR__ . '/../database/testing.sqlite';
$sqlPath = __DIR__ . '/../database/schema/testing.sqlite.sql';

// Ensure directory exists
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0777, true);
}

// Create empty file if not exists
if (!file_exists($dbPath)) {
    touch($dbPath);
}

// Use forward slashes for SQLite on Windows
$dbPath = str_replace('\\', '/', $dbPath);

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents($sqlPath);
$pdo->exec($sql);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

echo "SQLite test DB created at: {$dbPath}\n";
echo "Tables imported: " . count($tables) . "\n";
echo "Tables: " . implode(', ', $tables) . "\n";
