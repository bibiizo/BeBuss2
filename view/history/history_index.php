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
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, po.nama_po, rute.kota_asal, rute.kota_tujuan
                       FROM pemesanan
                       JOIN bus ON pemesanan.bus_id = bus.id
                       JOIN po ON bus.po_id = po.id
                       JOIN rute ON bus.rute_id = rute.id
                       WHERE pemesanan.user_id = ?
                       ORDER BY pemesanan.tanggal_pesan DESC");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Pemesanan - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        tr.clickable-row:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }
        /* Tambahan styling untuk pesan sukses dan error */
        .success-message-alert {
            color: #28a745;
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 10px;
            text-align: center;
            background-color: #e6ffe6;
            border: 1px solid #28a745;
            padding: 10px;
            border-radius: 5px;
        }
        .error-message-alert {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 10px;
            text-align: center;
            background-color: #ffe6e6;
            border: 1px solid #dc3545;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; // Sertakan navbar ?>
<div class="container">
    <h2>Riwayat Pemesanan Anda</h2>

    <?php if (!empty($success_message)): ?>
        <?php
        // Periksa apakah pesan berasal dari proses pemesanan baru
        if (strpos($success_message, 'Pemesanan tiket berhasil') !== false) {
            $success_message = "Pemesanan tiket berhasil. Silakan selesaikan pembayaran dalam 10 menit.";
        }
        ?>
        <p class="success-message-alert"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <p class="error-message-alert"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <?php if (empty($data)): ?>
        <p>Belum ada tiket yang dipesan.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>PO</th>
                <th>Rute</th>
                <th>Tanggal Berangkat</th>
                <th>Titik Naik</th>
                <th>Total Harga</th>
                <th>Status</th>
            </tr>
            <?php foreach ($data as $row): ?>
                <?php
                // Ambil kursi untuk pemesanan ini
                $stmt_k = $pdo->prepare("SELECT nomor_kursi FROM detail_kursi_pesan 
                                        JOIN kursi ON detail_kursi_pesan.kursi_id = kursi.id 
                                        WHERE pemesanan_id = ?");
                $stmt_k->execute([$row['id']]);
                $kursi_list = $stmt_k->fetchAll(PDO::FETCH_COLUMN);
                $kursi_str = implode(', ', $kursi_list);
                ?>
                <tr class="clickable-row" onclick="window.location='history_detail.php?pemesanan_id=<?= $row['id'] ?>'">
                    <td><?= $row['nama_po'] ?></td>
                    <td><?= $row['kota_asal'] ?> &rarr; <?= $row['kota_tujuan'] ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tanggal_berangkat'])) ?></td>
                    <td><?= $row['lokasi_naik'] ?><br><small>Kursi: <?= $kursi_str ?></small></td>
                    <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
