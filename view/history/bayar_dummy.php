<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Pastikan ada ID pemesanan yang dikirimkan melalui GET
if (isset($_GET['id'])) {
    $pemesanan_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Simulasikan proses pembayaran berhasil
    // Update status pemesanan menjadi 'selesai' atau 'lunas'
    try {
        // Menggunakan 'selesai' sesuai dengan ENUM yang kita definisikan (aktif, batal, selesai)
        $stmt = $pdo->prepare("UPDATE pemesanan SET status = 'selesai' WHERE id = ? AND user_id = ? AND status = 'aktif'");
        $stmt->execute([$pemesanan_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $message = "Pembayaran untuk pemesanan ID $pemesanan_id berhasil diproses secara simulasi. Status tiket Anda kini 'Selesai'.";
            $alert_type = "success";
        } else {
            // Ini akan terjadi jika pemesanan sudah dibayar, dibatalkan, atau tidak ditemukan/bukan milik user
            $stmt_check_status = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ? AND user_id = ?");
            $stmt_check_status->execute([$pemesanan_id, $user_id]);
            $current_status = $stmt_check_status->fetchColumn();

            if ($current_status === 'batal') {
                $message = "Pemesanan ID " . htmlspecialchars($pemesanan_id) . " tidak dapat dibayar karena sudah dibatalkan.";
                $alert_type = "warning";
            } elseif ($current_status === 'selesai') {
                $message = "Pemesanan ID " . htmlspecialchars($pemesanan_id) . " sudah berstatus 'Selesai'.";
                $alert_type = "warning";
            } else {
                $message = "Pemesanan tidak ditemukan, sudah dibayar, atau sudah dibatalkan.";
                $alert_type = "warning";
            }
        }
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan saat memproses pembayaran: " . $e->getMessage();
        $alert_type = "danger";
    }

} else {
    $message = "ID Pemesanan tidak valid.";
    $alert_type = "danger";
    header('Location: history_index.php'); // Redirect jika tidak ada ID
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .payment-status-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .payment-status-box.success {
            border: 2px solid #28a745;
            color: #28a745;
        }
        .payment-status-box.warning {
            border: 2px solid #ffc107;
            color: #ffc107;
        }
        .payment-status-box.danger {
            border: 2px solid #dc3545;
            color: #dc3545;
        }
        .payment-status-box h2 {
            margin-bottom: 20px;
        }
        .btn-back {
            margin-top: 20px;
            background-color: #6c757d; /* Abu-abu */
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; // Sertakan navbar ?>
<div class="container">
    <div class="payment-status-box <?= $alert_type ?>">
        <h2>Status Pembayaran</h2>
        <p><?= $message ?></p>
        <a href="history_detail.php?pemesanan_id=<?= $pemesanan_id ?>" class="btn btn-back">Kembali ke Detail Pemesanan</a>
        <a href="history_index.php" class="btn btn-back">Kembali ke Riwayat</a>
    </div>
</div>
</body>
</html>