<?php
session_start();
require_once '../../config/database.php';

// Penting: Pastikan timezone disetel dengan benar
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    die("Anda belum login.");
}

if (!isset($_POST['pemesanan_id'])) {
    die("ID pemesanan tidak ditemukan.");
}

$pemesanan_id = $_POST['pemesanan_id'];
$user_id = $_SESSION['user_id'];

// Ambil data pemesanan lengkap untuk tiket
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, bus.jam_berangkat, bus.kode_perjalanan, bus.plat_nomor, po.nama_po, rute.kota_asal, rute.kota_tujuan,
                       bus.harga, users.nama_lengkap as nama_penumpang, users.email, users.no_hp,
                       COUNT(detail_kursi_pesan.kursi_id) as jumlah_penumpang
                       FROM pemesanan 
                       JOIN bus ON pemesanan.bus_id = bus.id 
                       JOIN po ON bus.po_id = po.id 
                       JOIN rute ON bus.rute_id = rute.id 
                       JOIN users ON pemesanan.user_id = users.id
                       LEFT JOIN detail_kursi_pesan ON pemesanan.id = detail_kursi_pesan.pemesanan_id
                       WHERE pemesanan.id = ? AND pemesanan.user_id = ?
                       GROUP BY pemesanan.id");
$stmt->execute([$pemesanan_id, $user_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Pemesanan tidak ditemukan atau bukan milik Anda.");
}

// Ambil daftar kursi
$stmt_k = $pdo->prepare("SELECT nomor_kursi FROM detail_kursi_pesan 
                         JOIN kursi ON detail_kursi_pesan.kursi_id = kursi.id 
                         WHERE pemesanan_id = ?
                         ORDER BY nomor_kursi");
$stmt_k->execute([$pemesanan_id]);
$kursi_list = $stmt_k->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tiket BeBuss - BB<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/modern.css">
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            .ticket { box-shadow: none; border: 2px solid #000; }
        }
        .ticket {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .ticket-header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .ticket-body {
            padding: 30px;
        }
        .ticket-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
        }
        .ticket-section:last-child {
            border-bottom: none;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .qr-placeholder {
            width: 120px;
            height: 120px;
            background: #f0f0f0;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container">
        <div class="ticket">
            <!-- Header Tiket -->
            <div class="ticket-header">
                <h1>E-TIKET BEBUSS</h1>
                <p>Kode Pemesanan: <strong>BB<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></strong></p>
            </div>

            <!-- Body Tiket -->
            <div class="ticket-body">
                <!-- Informasi Perjalanan -->
                <div class="ticket-section">
                    <h3>Informasi Perjalanan</h3>
                    <div class="info-row">
                        <span><strong>Rute:</strong></span>
                        <span><?= htmlspecialchars($data['kota_asal']) ?> → <?= htmlspecialchars($data['kota_tujuan']) ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>PO Bus:</strong></span>
                        <span><?= htmlspecialchars($data['nama_po']) ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Kode Perjalanan:</strong></span>
                        <span><?= htmlspecialchars($data['kode_perjalanan'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Plat Nomor Bus:</strong></span>
                        <span><?= htmlspecialchars($data['plat_nomor'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Tanggal Berangkat:</strong></span>
                        <span><?= date('d F Y', strtotime($data['tanggal_berangkat'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Jam Berangkat:</strong></span>
                        <span><?= date('H:i', strtotime($data['jam_berangkat'])) ?> WIB</span>
                    </div>
                </div>

                <!-- Informasi Penumpang -->
                <div class="ticket-section">
                    <h3>Informasi Penumpang</h3>
                    <div class="info-row">
                        <span><strong>Nama:</strong></span>
                        <span><?= htmlspecialchars($data['nama_penumpang']) ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Email:</strong></span>
                        <span><?= htmlspecialchars($data['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>No. HP:</strong></span>
                        <span><?= htmlspecialchars($data['no_hp']) ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Jumlah Penumpang:</strong></span>
                        <span><?= $data['jumlah_penumpang'] ?> orang</span>
                    </div>
                    <div class="info-row">
                        <span><strong>Nomor Kursi:</strong></span>
                        <span><?= implode(', ', $kursi_list) ?></span>
                    </div>
                </div>

                <!-- Informasi Pembayaran -->
                <div class="ticket-section">
                    <h3>Informasi Pembayaran</h3>
                    <div class="info-row">
                        <span><strong>Total Harga:</strong></span>
                        <span>Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="info-row">
                        <span><strong>Status:</strong></span>
                        <span class="status-<?= $data['status'] ?>"><?= ucfirst($data['status']) ?></span>
                    </div>
                    <?php if ($data['status'] == 'selesai'): ?>
                    <div class="info-row">
                        <span><strong>Tanggal Bayar:</strong></span>
                        <span><?= date('d F Y H:i') ?> WIB</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- QR Code Placeholder -->
                <div class="ticket-section">
                    <h3>QR Code</h3>
                    <div class="qr-placeholder">
                        <span style="color: #999;">QR Code<br>Placeholder</span>
                    </div>
                    <p style="text-align: center; color: #666; font-size: 14px;">
                        Tunjukkan QR Code ini kepada petugas saat naik bus
                    </p>
                </div>

                <!-- Informasi Tambahan -->
                <div class="ticket-section">
                    <h3>ℹ️ Informasi Penting</h3>
                    <ul style="color: #666; font-size: 14px;">
                        <li>Harap tiba di terminal 30 menit sebelum keberangkatan</li>
                        <li>Bawa identitas diri yang sah (KTP/SIM/Paspor)</li>
                        <li>Tiket ini berlaku untuk 1x perjalanan sesuai jadwal</li>
                        <li>Simpan tiket ini hingga tiba di tujuan</li>
                        <li>Hubungi customer service untuk perubahan/pembatalan</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="text-center no-print" style="margin: 20px 0;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak Tiket</button>
            <a href="history_detail.php?pemesanan_id=<?= $pemesanan_id ?>" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>

    <script>
        // Auto print ketika halaman dimuat (opsional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>