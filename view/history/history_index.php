<?php
session_start();
require_once '../../config/database.php';

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    die("Anda belum login.");
}

$user_id = $_SESSION['user_id'];

// Handle session messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Import expired orders handler from index_home.php logic
function handleExpiredOrders($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT p.id as pemesanan_id 
                              FROM pemesanan p
                              WHERE p.status = 'aktif'
                              AND p.tanggal_pesan < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $stmt->execute();
        $expired_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expired_orders as $order) {
            $pdo->beginTransaction();
            try {
                $pemesanan_id = $order['pemesanan_id'];

                // Check and lock status
                $stmt_check = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ? FOR UPDATE");
                $stmt_check->execute([$pemesanan_id]);
                $current_status = $stmt_check->fetchColumn();

                if ($current_status === 'aktif') {
                    // Update pemesanan status
                    $stmt_update = $pdo->prepare("UPDATE pemesanan SET status = 'batal' WHERE id = ?");
                    $stmt_update->execute([$pemesanan_id]);

                    // Free up seats
                    $stmt_kursi = $pdo->prepare("SELECT kursi_id FROM detail_kursi_pesan WHERE pemesanan_id = ?");
                    $stmt_kursi->execute([$pemesanan_id]);
                    $kursi_ids = $stmt_kursi->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($kursi_ids)) {
                        $placeholders = str_repeat('?,', count($kursi_ids) - 1) . '?';
                        $stmt_free = $pdo->prepare("UPDATE kursi SET status = 'kosong' WHERE id IN ($placeholders)");
                        $stmt_free->execute($kursi_ids);
                    }
                }
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Failed to cancel expired order {$pemesanan_id}: " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log("Error handling expired orders: " . $e->getMessage());
    }
}

// Handle expired orders
handleExpiredOrders($pdo);

// Get user bookings
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, bus.kode_perjalanan, bus.plat_nomor, po.nama_po, rute.kota_asal, rute.kota_tujuan,
                              GROUP_CONCAT(kursi.nomor_kursi ORDER BY kursi.nomor_kursi ASC SEPARATOR ', ') as nomor_kursi
                       FROM pemesanan
                       JOIN bus ON pemesanan.bus_id = bus.id
                       JOIN po ON bus.po_id = po.id
                       JOIN rute ON bus.rute_id = rute.id
                       LEFT JOIN detail_kursi_pesan ON pemesanan.id = detail_kursi_pesan.pemesanan_id
                       LEFT JOIN kursi ON detail_kursi_pesan.kursi_id = kursi.id
                       WHERE pemesanan.user_id = ?
                       GROUP BY pemesanan.id
                       ORDER BY pemesanan.tanggal_pesan DESC");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll();

// Include navbar
include '../components/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - BeBuss</title>
    <link rel="icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <main class="container history-container">
        <div class="page-header">
            <h1>Riwayat Pemesanan Anda</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (empty($data)): ?>
            <div class="alert alert-info">Anda belum memiliki riwayat pemesanan.</div>
        <?php else: ?>
            <?php foreach ($data as $row): ?>
                <a href="history_detail.php?pemesanan_id=<?= $row['id'] ?>" class="booking-card">
                    <div class="booking-card-content">
                        <div class="booking-info po">
                            <span class="label">Operator Bus</span>
                            <span class="value po-name"><?= htmlspecialchars($row['nama_po']) ?></span>
                        </div>
                        <div class="booking-info">
                            <span class="label">Plat Nomor</span>
                            <span class="value"><?= htmlspecialchars($row['plat_nomor'] ?? 'N/A') ?></span>
                        </div>
                        <div class="booking-info">
                            <span class="label">Rute</span>
                            <span class="value"><?= htmlspecialchars($row['kota_asal']) ?> → <?= htmlspecialchars($row['kota_tujuan']) ?></span>
                        </div>
                        <div class="booking-info">
                            <span class="label">Tanggal Berangkat</span>
                            <span class="value"><?= date('d M Y', strtotime($row['tanggal_berangkat'])) ?></span>
                        </div>
                        <div class="booking-info">
                            <span class="label">Nomor Kursi</span>
                            <span class="value"><?= htmlspecialchars($row['nomor_kursi'] ?? 'N/A') ?></span>
                        </div>
                        
                        <?php
                        $status = strtolower($row['status']);
                        $status_class = 'status-' . $status;
                        ?>
                        <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
