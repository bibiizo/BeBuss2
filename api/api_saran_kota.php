<?php
// Pastikan tidak ada spasi, baris baru, atau karakter lain sebelum tag <?php
ob_clean(); // Membersihkan output buffer jika ada output yang tidak disengaja sebelumnya
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$search_query = $_GET['query'] ?? '';
$search_query = trim($search_query);

$suggestions = [];

if (strlen($search_query) >= 2) {
    $like_query = $search_query . '%';

    // Cari kota asal di tabel rute
    $stmt_asal = $pdo->prepare("SELECT DISTINCT kota_asal FROM rute WHERE kota_asal LIKE ? LIMIT 10");
    $stmt_asal->execute([$like_query]);
    while ($row = $stmt_asal->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['kota_asal'], $suggestions)) {
            $suggestions[] = $row['kota_asal'];
        }
    }

    // Cari kota tujuan di tabel rute
    $stmt_tujuan = $pdo->prepare("SELECT DISTINCT kota_tujuan FROM rute WHERE kota_tujuan LIKE ? LIMIT 10");
    $stmt_tujuan->execute([$like_query]);
    while ($row = $stmt_tujuan->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['kota_tujuan'], $suggestions)) {
            $suggestions[] = $row['kota_tujuan'];
        }
    }

    sort($suggestions);
    $suggestions = array_slice($suggestions, 0, 10);
}

echo json_encode($suggestions);