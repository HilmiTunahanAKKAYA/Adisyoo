<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

$since = isset($_GET['since']) ? (int)$_GET['since'] : 0;

if ($since > 0) {
    $stmt = $pdo->prepare('SELECT id,table_id,total,created_at,status FROM orders WHERE id > ? ORDER BY id ASC');
    $stmt->execute([$since]);
} else {
    $stmt = $pdo->query('SELECT id,table_id,total,created_at,status FROM orders ORDER BY id DESC LIMIT 20');
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// attach items to each order
$orderIds = array_column($rows, 'id');
$itemsByOrder = [];
if (!empty($orderIds)) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itStmt = $pdo->prepare("SELECT oi.order_id, oi.quantity, oi.price, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($placeholders) ORDER BY oi.id ASC");
    $itStmt->execute($orderIds);
    while ($r = $itStmt->fetch(PDO::FETCH_ASSOC)) {
        $oid = $r['order_id'];
        if (!isset($itemsByOrder[$oid])) $itemsByOrder[$oid] = [];
        $itemsByOrder[$oid][] = [
            'name' => $r['name'] ?? 'Ürün',
            'qty' => (int)$r['quantity'],
            'price' => (float)$r['price'],
            'subtotal' => ((float)$r['price']) * ((int)$r['quantity'])
        ];
    }
}

// merge
foreach ($rows as &$o) {
    $oid = $o['id'];
    $o['items'] = $itemsByOrder[$oid] ?? [];
}

echo json_encode(['ok' => true, 'orders' => $rows], JSON_UNESCAPED_UNICODE);
