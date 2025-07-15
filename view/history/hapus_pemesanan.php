<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pemesanan_id'])) {
    $pemesanan_id = $_POST['pemesanan_id'];
    $user_id = $_SESSION['user_id'];

    $pdo->beginTransaction();

    try {
        // 1. Verifikasi pemesanan: Pastikan milik user dan statusnya 'batal' atau 'selesai'
        $stmt_check = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt_check->execute([$pemesanan_id, $user_id]);
        $pemesanan = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$pemesanan) {
            throw new Exception("Pemesanan tidak ditemukan atau bukan milik Anda.");
        }

        // Logika awal hanya mengizinkan penghapusan status 'batal'.
        // Pesanan yang selesai sebaiknya tetap disimpan sebagai riwayat perjalanan.
        if ($pemesanan['status'] !== 'batal') {
            throw new Exception("Hanya riwayat pemesanan yang dibatalkan yang dapat dihapus.");
        }

        // 2. Hapus entri dari detail_kursi_pesan terlebih dahulu
        $stmt_delete_detail = $pdo->prepare("DELETE FROM detail_kursi_pesan WHERE pemesanan_id = ?");
        $stmt_delete_detail->execute([$pemesanan_id]);

        // 3. Hapus entri dari tabel pemesanan
        $stmt_delete_pemesanan = $pdo->prepare("DELETE FROM pemesanan WHERE id = ?");
        $stmt_delete_pemesanan->execute([$pemesanan_id]);

        if ($stmt_delete_pemesanan->rowCount() > 0) {
            $pdo->commit();
            $_SESSION['success_message'] = "Riwayat pemesanan berhasil dihapus secara permanen.";
            header('Location: history_index.php');
            exit;
        } else {
            // Ini seharusnya tidak terjadi jika pengecekan awal berhasil
            throw new Exception("Gagal menghapus data pemesanan utama.");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Gagal menghapus riwayat: " . $e->getMessage();
        header('Location: history_detail.php?pemesanan_id=' . $pemesanan_id);
        exit;
    }
} else {
    $_SESSION['error_message'] = "Akses tidak sah.";
    header('Location: history_index.php');
    exit;
}
?>