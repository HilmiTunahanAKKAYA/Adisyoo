<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['user'])) {
    echo json_encode(['ok'=>false,'error'=>'not_logged_in']);
    exit;
}
$role = $_SESSION['user']['role'] ?? '';
$allowed = ['cashier','manager','kitchen','operator'];
if (!in_array($role, $allowed, true)) {
    echo json_encode(['ok'=>false,'error'=>'forbidden']);
    exit;
}

$input = $_POST;
if (empty($input['table_id'])) {
    echo json_encode(['ok'=>false,'error'=>'missing_table']);
    exit;
}
$tableId = $input['table_id'];

$pdo = getPDO();
try {
    // find orders to remove (delete all orders for the table regardless of status)
    $q = $pdo->prepare("SELECT id FROM orders WHERE table_id = ?");
    $q->execute([$tableId]);
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);
    if (empty($ids)) {
        echo json_encode(['ok'=>true,'deleted'=>0]);
        exit;
    }
    // delete items then orders in transaction
    $pdo->beginTransaction();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $delItems = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($placeholders)");
    $delItems->execute($ids);
    $delOrders = $pdo->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
    $delOrders->execute($ids);
    $pdo->commit();
    echo json_encode(['ok'=>true,'deleted'=>count($ids)]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
