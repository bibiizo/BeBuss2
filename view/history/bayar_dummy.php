<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$message = "ID Pemesanan tidak valid.";
$alert_type = "danger";
$pemesanan_id = null;

// Pastikan ada ID pemesanan yang dikirimkan melalui POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pemesanan_id'])) {
    $pemesanan_id = $_POST['pemesanan_id'];
    $user_id = $_SESSION['user_id'];

    // Simulasikan proses pembayaran berhasil
    // Update status pemesanan menjadi 'selesai'
    try {
        $stmt = $pdo->prepare("UPDATE pemesanan SET status = 'selesai' WHERE id = ? AND user_id = ? AND status = 'aktif'");
        $stmt->execute([$pemesanan_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Pembayaran untuk pemesanan ID $pemesanan_id berhasil. Tiket Anda kini selesai dan dapat dicetak.";
            header("Location: history_detail.php?pemesanan_id=$pemesanan_id");
            exit;
        } else {
            // Cek status saat ini jika gagal update
            $stmt_check_status = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ? AND user_id = ?");
            $stmt_check_status->execute([$pemesanan_id, $user_id]);
            $current_status = $stmt_check_status->fetchColumn();

            if ($current_status === 'batal') {
                $message = "Pemesanan ID " . htmlspecialchars($pemesanan_id) . " tidak dapat dibayar karena sudah dibatalkan.";
                $alert_type = "warning";
            } elseif ($current_status === 'selesai') {
                $message = "Pemesanan ID " . htmlspecialchars($pemesanan_id) . " sudah dibayar dan selesai.";
                $alert_type = "warning";
            } else {
                $message = "Pemesanan tidak ditemukan atau waktu pembayaran telah habis.";
                $alert_type = "danger";
            }
        }
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan database: " . $e->getMessage();
        $alert_type = "danger";
    }
} else {
    // Jika akses langsung tanpa POST, tampilkan pesan error
    $message = "Akses tidak sah.";
    $alert_type = "danger";
}

// Tampilkan halaman status jika ada error atau akses tidak sah
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pembayaran - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <main class="container dummy-page-container">
        <div class="status-card">
            <?php
            $icon = '❌'; // Default icon
            if ($alert_type === 'success') $icon = '✅';
            if ($alert_type === 'warning') $icon = '⚠️';
            ?>
            <div class="status-icon <?= $alert_type ?>"><?= $icon ?></div>
            <h2>Proses Pembayaran Gagal</h2>
            <p><?= htmlspecialchars($message) ?></p>
            
            <?php if ($pemesanan_id): ?>
                <a href="history_detail.php?pemesanan_id=<?= htmlspecialchars($pemesanan_id) ?>" class="btn btn-secondary">Kembali ke Detail Pesanan</a>
            <?php else: ?>
                <a href="history_index.php" class="btn btn-secondary">Kembali ke Riwayat</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>