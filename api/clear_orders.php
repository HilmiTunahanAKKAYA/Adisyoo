<?php
session_start();
require_once __DIR__ . '/../db.php';

// Only manager/operator/kitchen allowed to clear orders for testing
if (empty($_SESSION['user'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'not_authenticated']);
  exit;
}
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['manager','operator','kitchen'], true)) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'not_authorized']);
  exit;
}

// Only accept POST to perform destructive action
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
  exit;
}

$pdo = getPDO();
try {
  // Delete order_items first to satisfy FK constraints
  $pdo->beginTransaction();
  $pdo->exec('DELETE FROM order_items');
  $pdo->exec('DELETE FROM orders');
  $pdo->commit();
  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
