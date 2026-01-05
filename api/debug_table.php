<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'error'=>'not_logged_in']);
    exit;
}
$pdo = getPDO();
$table = isset($_GET['table']) ? (int)$_GET['table'] : 0;
if ($table <= 0) {
    echo json_encode(['ok'=>false,'error'=>'missing_table']);
    exit;
}

$stmt = $pdo->prepare("SELECT o.id,o.table_id,o.status,o.total,o.created_at FROM orders o WHERE o.table_id = ? ORDER BY o.created_at ASC");
$stmt->execute([$table]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$orderIds = array_column($orders,'id');
$items = [];
if (!empty($orderIds)){
    $ph = implode(',', array_fill(0,count($orderIds),'?'));
    $it = $pdo->prepare("SELECT oi.order_id, oi.product_id, oi.quantity, COALESCE(NULLIF(oi.price,0), p.price, 0) AS price, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($ph) ORDER BY oi.id ASC");
    $it->execute($orderIds);
    while($r = $it->fetch(PDO::FETCH_ASSOC)){
        $items[] = $r;
    }
}

$calcTotal = 0.0;
foreach ($items as $it){
    $calcTotal += ((float)$it['price']) * ((int)$it['quantity']);
}

echo json_encode(['ok'=>true,'table'=>$table,'orders'=>$orders,'items'=>$items,'calculated_total'=>number_format($calcTotal,2)], JSON_UNESCAPED_UNICODE);

?>
