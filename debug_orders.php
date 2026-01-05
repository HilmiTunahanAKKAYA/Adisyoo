<?php
require_once __DIR__ . '/db.php';
$pdo = getPDO();

header('Content-Type: text/plain; charset=utf-8');

echo "-- orders table info --\n";
$row = $pdo->query("SHOW CREATE TABLE orders")->fetch(PDO::FETCH_ASSOC);
if ($row && isset($row['Create Table'])) {
    echo $row['Create Table'] . "\n\n";
}

echo "-- orders rows (all) --\n";
$rows = $pdo->query("SELECT * FROM orders ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n-- order_items rows --\n";
$its = $pdo->query("SELECT * FROM order_items ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($its as $i) echo json_encode($i, JSON_UNESCAPED_UNICODE) . "\n";

echo "\n-- counts --\n";
echo 'orders_count: ' . $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() . "\n";
echo 'order_items_count: ' . $pdo->query('SELECT COUNT(*) FROM order_items')->fetchColumn() . "\n";

exit;
