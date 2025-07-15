<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Pastikan request adalah POST dan ada ID pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pemesanan_id'])) {
    $pemesanan_id = $_POST['pemesanan_id'];
    $user_id = $_SESSION['user_id'];

    // Mulai transaksi untuk memastikan konsistensi data
    $pdo->beginTransaction();

    try {
        // 1. Verifikasi pemesanan dan kunci baris untuk update (FOR UPDATE)
        $stmt_check = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt_check->execute([$pemesanan_id, $user_id]);
        $pemesanan = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$pemesanan) {
            throw new Exception("Pemesanan tidak ditemukan atau bukan milik Anda.");
        }

        if ($pemesanan['status'] !== 'aktif') {
            throw new Exception("Hanya pemesanan dengan status 'aktif' yang dapat dibatalkan.");
        }

        // 2. Ambil informasi kursi yang terkait dengan pemesanan ini
        $stmt_get_kursi = $pdo->prepare("SELECT kursi_id FROM detail_kursi_pesan WHERE pemesanan_id = ?");
        $stmt_get_kursi->execute([$pemesanan_id]);
        $kursi_ids = $stmt_get_kursi->fetchAll(PDO::FETCH_COLUMN);

        // 3. Update status pemesanan menjadi 'batal'
        $stmt_pemesanan = $pdo->prepare("UPDATE pemesanan SET status = 'batal' WHERE id = ?");
        $stmt_pemesanan->execute([$pemesanan_id]);

        // 4. Jika ada kursi yang ditemukan, update status kursi menjadi 'kosong'
        if (!empty($kursi_ids)) {
            $placeholders = implode(',', array_fill(0, count($kursi_ids), '?'));
            $stmt_kursi = $pdo->prepare("UPDATE kursi SET status = 'kosong' WHERE id IN ($placeholders)");
            $stmt_kursi->execute($kursi_ids);
        }

        $pdo->commit(); // Commit transaksi jika semua berhasil
        $_SESSION['success_message'] = "Pemesanan dengan ID $pemesanan_id berhasil dibatalkan.";
        
    } catch (Exception $e) {
        $pdo->rollBack(); // Rollback jika ada error
        $_SESSION['error_message'] = "Gagal membatalkan pemesanan: " . $e->getMessage();
    }

} else {
    // Jika akses langsung atau tanpa ID
    $_SESSION['error_message'] = "Akses tidak sah.";
}

// Redirect kembali ke halaman detail atau index
if (isset($pemesanan_id)) {
    header('Location: history_detail.php?pemesanan_id=' . $pemesanan_id);
} else {
    header('Location: history_index.php');
}
exit;
?>