<?php
require_once 'config/database.php';
$stmt = $pdo->prepare('SELECT id, email, nama_lengkap, no_hp, jenis_kelamin FROM users WHERE email = ?');
$stmt->execute(['qistan@mail.com']);
$user = $stmt->fetch();
if ($user) {
    echo 'User: ' . $user['email'] . PHP_EOL;
    echo 'Nama: ' . ($user['nama_lengkap'] ?: 'NULL') . PHP_EOL;
    echo 'HP: ' . ($user['no_hp'] ?: 'NULL') . PHP_EOL;
    echo 'JK: ' . ($user['jenis_kelamin'] ?: 'NULL') . PHP_EOL;
    $complete = !empty($user['nama_lengkap']) && !empty($user['no_hp']) && !empty($user['jenis_kelamin']);
    echo 'Complete: ' . ($complete ? 'YES' : 'NO') . PHP_EOL;
} else {
    echo 'User not found' . PHP_EOL;
}
?>
