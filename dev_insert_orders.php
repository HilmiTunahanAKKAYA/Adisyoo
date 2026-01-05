<?php
// Dev helper: insert sample orders for testing UI.
// Usage (browser): http://localhost/proje/dev_insert_orders.php?count=5

require_once __DIR__ . '/db.php';
$pdo = getPDO();

$count = isset($_GET['count']) ? (int)$_GET['count'] : 5;
if ($count < 1) $count = 1;

// find or create a sample product to reference in order_items
$prod = $pdo->query("SELECT id, name, price FROM products LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
    $pdo->exec("INSERT INTO products (category_id,name,price) VALUES (NULL,'Test Ürün',25.00)");
    $pid = $pdo->lastInsertId();
    $prod = ['id' => $pid, 'name' => 'Test Ürün', 'price' => 25.00];
}

for ($i=0;$i<$count;$i++) {
    $tableId = 1; // test masa
    $total = (float)$prod['price'];
    $stmt = $pdo->prepare('INSERT INTO orders (table_id,status,total) VALUES (?,?,?)');
    $stmt->execute([$tableId, 'open', $total]);
    $orderId = $pdo->lastInsertId();
    $it = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
    $it->execute([$orderId, $prod['id'], 1, $prod['price']]);
}

echo "Inserted {$count} test orders.\n";
echo "Open operator panel and refresh to see them appended.\n";
