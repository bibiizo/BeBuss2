<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_errors'] = ["Anda harus login untuk melakukan tindakan ini."];
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pemesanan_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    // Mulai transaksi untuk memastikan konsistensi data
    $pdo->beginTransaction();

    try {
        // 1. Verifikasi pemesanan: Pastikan milik user dan statusnya HANYA 'batal'
        $stmt_check = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ? AND user_id = ?");
        $stmt_check->execute([$pemesanan_id, $user_id]);
        $pemesanan_status = $stmt_check->fetchColumn();

        // Jika tidak ditemukan ATAU statusnya BUKAN 'batal'
        if (!$pemesanan_status || $pemesanan_status !== 'batal') {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Pemesanan tidak dapat dihapus. Status tidak valid (hanya pemesanan 'batal' yang bisa dihapus) atau pemesanan bukan milik Anda.";
            header('Location: history_detail.php?pemesanan_id=' . urlencode($pemesanan_id));
            exit;
        }

        // 2. Hapus entri dari detail_kursi_pesan terlebih dahulu (karena foreign key constraint)
        $stmt_delete_detail = $pdo->prepare("DELETE FROM detail_kursi_pesan WHERE pemesanan_id = ?");
        $stmt_delete_detail->execute([$pemesanan_id]);

        // 3. Hapus entri dari tabel pemesanan
        $stmt_delete_pemesanan = $pdo->prepare("DELETE FROM pemesanan WHERE id = ? AND user_id = ?");
        $stmt_delete_pemesanan->execute([$pemesanan_id, $user_id]);

        if ($stmt_delete_pemesanan->rowCount() > 0) {
            $pdo->commit(); // Commit transaksi jika semua berhasil
            $_SESSION['success_message'] = "Pemesanan berhasil dihapus dari riwayat Anda.";
            header('Location: history_index.php'); // Redirect ke daftar riwayat
            exit;
        } else {
            $pdo->rollBack(); // Rollback jika pemesanan tidak ditemukan atau tidak terhapus
            $_SESSION['error_message'] = "Gagal menghapus pemesanan. Pemesanan tidak ditemukan atau bukan milik Anda.";
            header('Location: history_detail.php?pemesanan_id=' . urlencode($pemesanan_id));
            exit;
        }

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback jika ada error
        error_log("Error deleting pemesanan: " . $e->getMessage()); // Log error
        $_SESSION['error_message'] = "Terjadi kesalahan saat menghapus pemesanan. Mohon coba lagi.";
        header('Location: history_detail.php?pemesanan_id=' . urlencode($pemesanan_id));
        exit;
    }
} else {
    // Jika akses langsung atau tanpa ID
    header('Location: history_index.php'); // Arahkan ke daftar riwayat
    exit;
}
?>