<?php
// Simple endpoint to update order status via POST (expects order_id and status)
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}

$pdo = getPDO();
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = $_POST['status'] ?? '';
$allowed = ['pending','open','preparing','ready','served','paid'];
if ($orderId <= 0 || !in_array($status, $allowed)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_input']);
    exit;
}

$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$status, $orderId]);
echo json_encode(['ok'=>true]);
