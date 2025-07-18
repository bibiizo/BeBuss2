<?php
require_once 'config/database.php';

// Cek semua user
$stmt = $pdo->query('SELECT id, email, nama_lengkap, no_hp, jenis_kelamin FROM users LIMIT 5');
echo "List Users:\n";
while ($row = $stmt->fetch()) {
    $complete = !empty($row['nama_lengkap']) && !empty($row['no_hp']) && !empty($row['jenis_kelamin']);
    echo "ID: {$row['id']} | {$row['email']} | Complete: " . ($complete ? 'YES' : 'NO') . "\n";
}

// Buat user test yang belum lengkap
echo "\nCreating test user with incomplete profile...\n";
$stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
$stmt->execute(['test@incomplete.com', password_hash('password123', PASSWORD_DEFAULT)]);
$new_user_id = $pdo->lastInsertId();

echo "Created user ID: $new_user_id\n";

// Verifikasi user baru
$stmt = $pdo->prepare('SELECT id, email, nama_lengkap, no_hp, jenis_kelamin FROM users WHERE id = ?');
$stmt->execute([$new_user_id]);
$user = $stmt->fetch();

$complete = !empty($user['nama_lengkap']) && !empty($user['no_hp']) && !empty($user['jenis_kelamin']);
echo "New user complete status: " . ($complete ? 'YES' : 'NO') . "\n";
?>
