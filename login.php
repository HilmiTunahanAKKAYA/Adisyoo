<?php
// login.php — authentication handler only.
session_start();
require_once __DIR__ . '/db.php';

$pdo = getPDO();

// Only accept POST for authentication. For GET requests redirect to index.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Allowed redirect targets from the specific login pages
$allowedRedirects = ['operator.php','kitchen.php'];
$redirect = null;
if (!empty($_POST['redirect'])) {
    $r = basename((string)$_POST['redirect']);
    if (in_array($r, $allowedRedirects, true)) {
        $redirect = $r;
    }
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    // redirect back with error
    $back = $redirect === 'kitchen.php' ? 'login_kitchen.php' : 'login_operator.php';
    header('Location: ' . $back . '?error=' . urlencode('Kullanıcı adı ve parola giriniz.'));
    exit;
}

$stmt = $pdo->prepare('SELECT id,username,password_hash,role FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    $back = $redirect === 'kitchen.php' ? 'login_kitchen.php' : 'login_operator.php';
    header('Location: ' . $back . '?error=' . urlencode('Geçersiz kullanıcı adı veya parola.'));
    exit;
}

// role enforcement based on requested redirect
$requiredRole = null;
if ($redirect === 'operator.php') $requiredRole = 'operator';
if ($redirect === 'kitchen.php') $requiredRole = 'kitchen';

if ($requiredRole !== null && $user['role'] !== $requiredRole && $user['role'] !== 'manager') {
    $back = $redirect === 'kitchen.php' ? 'login_kitchen.php' : 'login_operator.php';
    header('Location: ' . $back . '?error=' . urlencode('Bu panel için yetkiniz yok.'));
    exit;
}

// Successful login
session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'role' => $user['role']
];

if ($redirect) {
    header('Location: ' . $redirect);
} else {
    // default based on role
    if ($user['role'] === 'kitchen') header('Location: kitchen.php');
    else header('Location: operator.php');
}
exit;
