<?php
$host = 'localhost';
$dbname = 'bebuss';
$user = 'root';
$pass = ''; // default XAMPP, kosongkan jika tidak diubah

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    // Aktifkan error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>