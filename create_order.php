<?php
session_start();
require_once __DIR__ . '/db.php';
$pdo = getPDO();

// Basit checkout: session'daki cart ve cart_samples'i kullanarak orders ve order_items tablosuna yaz
if (empty($_SESSION['table'])) {
    die('Masa seçili değil.');
}

$tableId = (int)$_SESSION['table'];

$items = [];
// DB cart: product ids in $_SESSION['cart']
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id,name,price FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $qty = $_SESSION['cart'][$r['id']];
        $items[] = ['product_id' => $r['id'], 'name' => $r['name'], 'price' => $r['price'], 'qty' => $qty];
    }
}

// Sample items: upsert into products if necessary
if (!empty($_SESSION['cart_samples'])) {
    foreach ($_SESSION['cart_samples'] as $s) {
        // try find product by name+price
        $stmt = $pdo->prepare('SELECT id FROM products WHERE name = ? AND price = ? LIMIT 1');
        $stmt->execute([$s['name'], $s['price']]);
        $r = $stmt->fetch();
        if ($r) {
            $pid = $r['id'];
        } else {
            $ins = $pdo->prepare('INSERT INTO products (category_id,name,price) VALUES (NULL,?,?)');
            $ins->execute([$s['name'], $s['price']]);
            $pid = $pdo->lastInsertId();
        }
        $items[] = ['product_id' => $pid, 'name' => $s['name'], 'price' => $s['price'], 'qty' => $s['qty']];
    }
}

if (empty($items)) {
    die('Sepet boş.');
}

$total = 0;
foreach ($items as $it) $total += $it['price'] * $it['qty'];

$ins = $pdo->prepare('INSERT INTO orders (table_id,status,total) VALUES (?,?,?)');
// New orders created by customers start as 'pending' so operator can approve them
$ins->execute([$tableId, 'pending', $total]);
$orderId = $pdo->lastInsertId();

// insert order_items
$stmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
foreach ($items as $it) {
    $stmt->execute([$orderId, $it['product_id'], $it['qty'], $it['price']]);
}

// temizle session sepetleri
unset($_SESSION['cart']);
unset($_SESSION['cart_samples']);

// redirect back with success
header('Location: customer.php?created=1');
exit;
