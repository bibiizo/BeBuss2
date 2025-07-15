<?php
session_start();
require_once '../../config/database.php';

// Penting: Pastikan timezone disetel dengan benar di sini atau di config/database.php
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    die("Anda belum login.");
}

if (!isset($_GET['pemesanan_id'])) {
    die("ID pemesanan tidak ditemukan.");
}

$pemesanan_id = $_GET['pemesanan_id'];
$user_id = $_SESSION['user_id'];

// Ambil pesan sukses atau error dari session
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Hapus pesan dari session setelah diambil agar tidak muncul lagi saat refresh
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Ambil data pemesanan
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, bus.jam_berangkat, po.nama_po, rute.kota_asal, rute.kota_tujuan,
                       COUNT(detail_kursi_pesan.kursi_id) as jumlah_penumpang
                       FROM pemesanan 
                       JOIN bus ON pemesanan.bus_id = bus.id 
                       JOIN po ON bus.po_id = po.id 
                       JOIN rute ON bus.rute_id = rute.id 
                       LEFT JOIN detail_kursi_pesan ON pemesanan.id = detail_kursi_pesan.pemesanan_id
                       WHERE pemesanan.id = ? AND pemesanan.user_id = ?
                       GROUP BY pemesanan.id");
$stmt->execute([$pemesanan_id, $user_id]);
$data = $stmt->fetch();

if (!$data) {
    $_SESSION['error_message'] = "Pemesanan tidak ditemukan atau bukan milik Anda.";
    header('Location: history_index.php');
    exit;
}

// Ambil kursi
$stmt_k = $pdo->prepare("SELECT nomor_kursi FROM detail_kursi_pesan 
                         JOIN kursi ON detail_kursi_pesan.kursi_id = kursi.id 
                         WHERE pemesanan_id = ?");
$stmt_k->execute([$pemesanan_id]);
$kursi = $stmt_k->fetchAll(PDO::FETCH_COLUMN);
$kursi_str = implode(', ', $kursi);

// --- PERHITUNGAN WAKTU KEDALUWARSA MENGGUNAKAN OBJEK DateTime (10 MENIT) ---
$waktu_sekarang_obj = new DateTime('now', new DateTimeZone('Asia/Jakarta')); 

// Waktu pemesanan dari database
$tanggal_pesan_db_string = $data['tanggal_pesan'];
$tanggal_pesan_obj = new DateTime($tanggal_pesan_db_string, new DateTimeZone('Asia/Jakarta')); 

// Tambahkan 10 menit ke waktu pemesanan
$waktu_kedaluwarsa_obj = clone $tanggal_pesan_obj; 
$waktu_kedaluwarsa_obj->modify('+10 minutes'); // *** DIUBAH KE +10 minutes ***

// Hitung perbedaan waktu dalam detik
$sisa_waktu_detik = 0; // Default ke 0

// Jika waktu kedaluwarsa belum lewat, hitung sisa waktu
if ($waktu_sekarang_obj < $waktu_kedaluwarsa_obj) {
    $interval = $waktu_sekarang_obj->diff($waktu_kedaluwarsa_obj);
    $sisa_waktu_detik = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
}

// Include navbar
include '../components/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemesanan - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <main class="container history-detail-container">
        <div class="page-header">
            <a href="history_index.php" class="btn btn-secondary">&larr; Kembali ke Riwayat</a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="detail-grid">
            <div class="main-content">
                <div class="detail-card">
                    <div class="detail-header">
                        <h2>Detail Perjalanan</h2>
                        <?php
                        $status = strtolower($data['status']);
                        $status_class = 'status-' . $status;
                        ?>
                        <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    </div>
                    <div class="detail-list">
                        <div class="detail-item">
                            <span class="label">Operator Bus</span>
                            <span class="value"><?= htmlspecialchars($data['nama_po']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Rute</span>
                            <span class="value"><?= htmlspecialchars($data['kota_asal']) ?> → <?= htmlspecialchars($data['kota_tujuan']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Tanggal Berangkat</span>
                            <span class="value"><?= date('l, d F Y', strtotime($data['tanggal_berangkat'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Jam Berangkat</span>
                            <span class="value"><?= date('H:i', strtotime($data['jam_berangkat'])) ?> WIB</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Lokasi Naik</span>
                            <span class="value"><?= htmlspecialchars($data['lokasi_naik']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Nomor Kursi</span>
                            <span class="value"><?= htmlspecialchars($kursi_str) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Jumlah Penumpang</span>
                            <span class="value"><?= htmlspecialchars($data['jumlah_penumpang']) ?> orang</span>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-header">
                        <h2>Detail Pembayaran</h2>
                    </div>
                    <div class="detail-list">
                        <div class="detail-item">
                            <span class="label">Kode Pemesanan</span>
                            <span class="value">BP-<?= str_pad($data['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Tanggal Pesan</span>
                            <span class="value"><?= date('d M Y, H:i', strtotime($data['tanggal_pesan'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Total Pembayaran</span>
                            <span class="value">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="sidebar">
                <?php if ($data['status'] == 'aktif' && $sisa_waktu_detik > 0): ?>
                    <div class="actions-card">
                        <h4>Selesaikan Pembayaran</h4>
                        <div id="countdown" class="countdown-timer"></div>
                        <form action="bayar_dummy.php" method="POST" class="action-form">
                            <input type="hidden" name="pemesanan_id" value="<?= $pemesanan_id ?>">
                            <button type="submit" class="btn btn-primary">Bayar Sekarang</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="actions-card">
                    <h4>Aksi Lainnya</h4>
                    <?php if ($data['status'] == 'selesai'): ?>
                        <form action="cetak_dummy.php" method="POST" target="_blank" rel="noopener noreferrer" class="action-form print-form">
                            <input type="hidden" name="pemesanan_id" value="<?= $pemesanan_id ?>">
                            <button type="submit" class="btn btn-primary print-btn">
                                📄 Cetak E-Tiket
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($data['status'] == 'aktif'): ?>
                        <form action="batalkan.php" method="POST" class="action-form cancel-form" data-confirm="Apakah Anda yakin ingin membatalkan pesanan ini?">
                            <input type="hidden" name="pemesanan_id" value="<?= $pemesanan_id ?>">
                            <button type="submit" class="btn btn-danger">Batalkan Pesanan</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($data['status'] == 'batal'): ?>
                         <form action="hapus_pemesanan.php" method="POST" class="action-form delete-form" data-confirm="Menghapus riwayat ini bersifat permanen. Yakin?">
                            <input type="hidden" name="pemesanan_id" value="<?= $pemesanan_id ?>">
                            <button type="submit" class="btn btn-danger">Hapus Riwayat</button>
                        </form>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </main>

    <?php if ($data['status'] == 'aktif' && $sisa_waktu_detik > 0): ?>
    <script>
        // Countdown timer script
        const countdownElement = document.getElementById('countdown');
        let timeLeft = <?= $sisa_waktu_detik ?>;

        function updateCountdown() {
            if (timeLeft <= 0) {
                countdownElement.innerHTML = "Waktu pembayaran habis.";
                // Optional: auto-refresh the page
                setTimeout(() => window.location.reload(), 2000);
                return;
            }

            const minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            countdownElement.innerHTML = `Sisa Waktu: ${minutes}:${seconds}`;
            timeLeft--;
        }

        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
        
        // Add event listeners for forms with confirm dialogs
        document.addEventListener('DOMContentLoaded', function() {
            const cancelForm = document.querySelector('.cancel-form');
            if (cancelForm) {
                cancelForm.addEventListener('submit', function(e) {
                    const confirmMsg = this.getAttribute('data-confirm');
                    if (!confirm(confirmMsg)) {
                        e.preventDefault();
                    }
                });
            }
            
            const deleteForm = document.querySelector('.delete-form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    const confirmMsg = this.getAttribute('data-confirm');
                    if (!confirm(confirmMsg)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>