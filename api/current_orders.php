<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
$pdo = getPDO();

$stmt = $pdo->prepare("SELECT id,table_id,total,created_at,status FROM orders ORDER BY created_at ASC");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// attach items
$orderIds = array_column($rows, 'id');
$itemsByOrder = [];
if (!empty($orderIds)) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itStmt = $pdo->prepare("SELECT oi.order_id, oi.quantity, COALESCE(NULLIF(oi.price,0), p.price, 0) AS price, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($placeholders) ORDER BY oi.id ASC");
    $itStmt->execute($orderIds);
    while ($r = $itStmt->fetch(PDO::FETCH_ASSOC)) {
        $oid = $r['order_id'];
        if (!isset($itemsByOrder[$oid])) $itemsByOrder[$oid] = [];
        $itemsByOrder[$oid][] = [
            'name' => $r['name'] ?? 'Ürün',
            'qty' => (int)$r['quantity'],
            'price' => (float)$r['price']
        ];
    }
}

foreach ($rows as &$o) {
    $o['items'] = $itemsByOrder[$o['id']] ?? [];
}

echo json_encode(['ok' => true, 'orders' => $rows], JSON_UNESCAPED_UNICODE);
