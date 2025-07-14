<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Pastikan request adalah POST dan ada ID pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pemesanan_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    // Mulai transaksi untuk memastikan konsistensi data
    $pdo->beginTransaction();

    try {
        // 1. Ambil informasi kursi yang terkait dengan pemesanan ini
        $stmt_get_kursi = $pdo->prepare("SELECT kursi_id FROM detail_kursi_pesan WHERE pemesanan_id = ?");
        $stmt_get_kursi->execute([$pemesanan_id]);
        $kursi_ids = $stmt_get_kursi->fetchAll(PDO::FETCH_COLUMN);

        // 2. Update status pemesanan menjadi 'batal' (sesuai ENUM database)
        // Kita mengembalikan kondisi 'aktif' di WHERE untuk memastikan hanya pemesanan aktif yang bisa dibatalkan
        $stmt_pemesanan = $pdo->prepare("UPDATE pemesanan SET status = 'batal' WHERE id = ? AND user_id = ? AND status = 'aktif'");
        $stmt_pemesanan->execute([$pemesanan_id, $user_id]);

        // Cek apakah pemesanan berhasil dibatalkan (milik user dan status aktif)
        if ($stmt_pemesanan->rowCount() > 0) {
            // 3. Jika ada kursi yang ditemukan, update status kursi menjadi 'kosong'
            if (!empty($kursi_ids)) {
                $placeholders = implode(',', array_fill(0, count($kursi_ids), '?'));
                $stmt_kursi = $pdo->prepare("UPDATE kursi SET status = 'kosong' WHERE id IN ($placeholders)");
                $stmt_kursi->execute($kursi_ids);
            }

            $pdo->commit(); // Commit transaksi jika semua berhasil
            echo "<script>alert('Pemesanan berhasil dibatalkan!'); window.location.href = 'history_index.php';</script>";
        } else {
            $pdo->rollBack(); // Rollback jika pemesanan tidak ditemukan/bukan milik user/sudah tidak aktif
            echo "<script>alert('Gagal membatalkan pemesanan atau pemesanan sudah tidak aktif.'); window.location.href = 'history_detail.php?pemesanan_id=$pemesanan_id';</script>";
        }

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback jika ada error
        die("Error membatalkan pemesanan: " . $e->getMessage());
    }

} else {
    // Jika akses langsung atau tanpa ID
    header('Location: history_index.php');
    exit;
}
?>