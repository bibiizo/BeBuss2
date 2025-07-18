<?php
session_start();
require_once '../../config/database.php';

// Penting: Pastikan timezone disetel dengan benar
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Anda belum login.";
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_POST['pemesanan_id'])) {
    $_SESSION['error_message'] = "ID pemesanan tidak ditemukan.";
    header('Location: history_index.php');
    exit;
}

$pemesanan_id = $_POST['pemesanan_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verifikasi pemesanan milik user dan masih aktif
    $stmt = $pdo->prepare("SELECT id, status FROM pemesanan WHERE id = ? AND user_id = ?");
    $stmt->execute([$pemesanan_id, $user_id]);
    $pemesanan = $stmt->fetch();
    
    if (!$pemesanan) {
        $_SESSION['error_message'] = "Pemesanan tidak ditemukan atau bukan milik Anda.";
        header('Location: history_index.php');
        exit;
    }
    
    if ($pemesanan['status'] !== 'aktif') {
        $_SESSION['error_message'] = "Pemesanan ini tidak dapat dibayar (status: " . $pemesanan['status'] . ").";
        header('Location: history_detail.php?pemesanan_id=' . $pemesanan_id);
        exit;
    }
    
    // Update status pemesanan menjadi selesai (simulasi pembayaran berhasil)
    $stmt = $pdo->prepare("UPDATE pemesanan SET status = 'selesai' WHERE id = ?");
    $stmt->execute([$pemesanan_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Pembayaran berhasil! Tiket Anda telah dikonfirmasi.";
    } else {
        $_SESSION['error_message'] = "Gagal memproses pembayaran. Silakan coba lagi.";
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

// Redirect kembali ke halaman detail
header('Location: history_detail.php?pemesanan_id=' . $pemesanan_id);
exit;
?>