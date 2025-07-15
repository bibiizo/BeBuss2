<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$pemesanan_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pemesanan_id'])) {
    $pemesanan_id = $_POST['pemesanan_id'];
} else {
    // Handle jika diakses langsung tanpa POST
    // Bisa redirect atau tampilkan pesan error
    die("Akses tidak sah. Silakan cetak tiket dari halaman detail riwayat.");
}

$user_id = $_SESSION['user_id'];

// Ambil data pemesanan, pastikan statusnya 'selesai'
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, bus.jam_berangkat, bus.harga,
                       po.nama_po, rute.kota_asal, rute.kota_tujuan, users.nama_lengkap, users.email, users.no_hp,
                       COUNT(detail_kursi_pesan.kursi_id) as jumlah_penumpang
                       FROM pemesanan
                       JOIN bus ON pemesanan.bus_id = bus.id
                       JOIN po ON bus.po_id = po.id
                       JOIN rute ON bus.rute_id = rute.id
                       JOIN users ON pemesanan.user_id = users.id
                       LEFT JOIN detail_kursi_pesan ON pemesanan.id = detail_kursi_pesan.pemesanan_id
                       WHERE pemesanan.id = ? AND pemesanan.user_id = ? AND pemesanan.status = 'selesai'
                       GROUP BY pemesanan.id");
$stmt->execute([$pemesanan_id, $user_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Tiket tidak ditemukan, belum selesai pembayarannya, atau bukan milik Anda.");
}

// Ambil kursi yang dipesan
$stmt_k = $pdo->prepare("SELECT nomor_kursi FROM detail_kursi_pesan 
                         JOIN kursi ON detail_kursi_pesan.kursi_id = kursi.id 
                         WHERE pemesanan_id = ?");
$stmt_k->execute([$pemesanan_id]);
$kursi = $stmt_k->fetchAll(PDO::FETCH_COLUMN);
$kursi_str = implode(', ', $kursi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tiket BeBuss - BP-<?= str_pad($pemesanan_id, 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="print-controls container">
        <button type="button" class="btn btn-primary print-ticket-btn">Cetak Tiket</button>
        <a href="history_detail.php?pemesanan_id=<?= $pemesanan_id ?>" class="btn btn-secondary">Kembali</a>
    </div>

    <div class="ticket-card">
        <div class="ticket-header">
            <div class="ticket-brand">BeBuss</div>
            <div class="ticket-title">
                <h2>E-TICKET BUS</h2>
                <p>Kode Booking: <strong>BP-<?= str_pad($pemesanan_id, 6, '0', STR_PAD_LEFT) ?></strong></p>
            </div>
        </div>

        <div class="ticket-section">
            <h3>Detail Penumpang</h3>
            <div class="ticket-grid">
                <div class="ticket-item">
                    <span class="label">Nama</span>
                    <span class="value"><?= htmlspecialchars($data['nama_lengkap']) ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">Email</span>
                    <span class="value"><?= htmlspecialchars($data['email']) ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">No. Handphone</span>
                    <span class="value"><?= htmlspecialchars($data['no_hp']) ?></span>
                </div>
            </div>
        </div>

        <div class="ticket-section">
            <h3>Detail Perjalanan</h3>
            <div class="ticket-grid">
                <div class="ticket-item">
                    <span class="label">Operator Bus</span>
                    <span class="value"><?= htmlspecialchars($data['nama_po']) ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">Rute</span>
                    <span class="value"><?= htmlspecialchars($data['kota_asal']) ?> → <?= htmlspecialchars($data['kota_tujuan']) ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">Tanggal Berangkat</span>
                    <span class="value"><?= date('l, d F Y', strtotime($data['tanggal_berangkat'])) ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">Jam Berangkat</span>
                    <span class="value"><?= date('H:i', strtotime($data['jam_berangkat'])) ?> WIB</span>
                </div>
                <div class="ticket-item">
                    <span class="label">Titik Naik</span>
                    <span class="value"><?= htmlspecialchars($data['lokasi_naik']) ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">Nomor Kursi</span>
                    <span class="value"><?= htmlspecialchars($kursi_str) ?></span>
                </div>
            </div>
        </div>

        <div class="ticket-section">
            <h3>Detail Pembayaran</h3>
            <div class="ticket-grid">
                <div class="ticket-item">
                    <span class="label">Jumlah Penumpang</span>
                    <span class="value"><?= htmlspecialchars($data['jumlah_penumpang']) ?> orang</span>
                </div>
                <div class="ticket-item">
                    <span class="label">Total Pembayaran</span>
                    <span class="value">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></span>
                </div>
                <div class="ticket-item">
                    <span class="label">Status</span>
                    <span class="value status-success">SELESAI</span>
                </div>
            </div>
        </div>

        <div class="ticket-footer">
            <p>Terima kasih telah menggunakan layanan BeBuss. Tunjukkan e-tiket ini kepada petugas saat akan berangkat.</p>
            <p>&copy; <?= date('Y') ?> BeBuss. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Replace inline onclick handler
        document.addEventListener('DOMContentLoaded', function() {
            const printBtn = document.querySelector('.print-ticket-btn');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    window.print();
                });
            }
        });
    </script>

</body>
</html>