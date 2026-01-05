<?php
/**
 * create_users.php
 * Adds or updates operator users with secure bcrypt hashes.
 * Usage: open in browser: http://localhost/proje/create_users.php
 */

require_once __DIR__ . '/db.php';
$pdo = getPDO();

// Create or update recommended demo accounts (non-destructive).
// Operator: Akkaya (operator role)
// Kitchen users: Hilmi, Tunahan (kitchen role)
$accounts = [
    ['username' => 'Akkaya', 'password' => '4578', 'role' => 'operator'],
    ['username' => 'Hilmi', 'password' => '1111', 'role' => 'kitchen'],
    ['username' => 'Tunahan', 'password' => '2222', 'role' => 'kitchen'],
];

header('Content-Type: text/plain; charset=utf-8');
echo "User create/update helper - will INSERT or UPDATE the listed demo accounts (non-destructive)\n\n";

foreach ($accounts as $acc) {
    $username = $acc['username'];
    $password = $acc['password'];
    $role = $acc['role'];

    // generate secure hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // if user exists, DO NOT change their password_hash; only ensure role is set.
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $exists = $stmt->fetchColumn();
    if ($exists) {
        $u = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $u->execute([$role, $exists]);
        echo "User exists — role updated (password preserved): $username (role: $role)\n";
    } else {
        $i = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
        $i->execute([$username, $hash, $role]);
        echo "Inserted user: $username (role: $role)\n";
    }
}

echo "\nDone. Demo credentials:\n";
foreach ($accounts as $a) {
    echo sprintf("- %s / %s (role: %s)\n", $a['username'], $a['password'], $a['role']);
}
echo "\nYou can login via: http://localhost/proje/login.php?redirect=operator.php (operator) or ?redirect=kitchen.php (kitchen)\n";

?>
