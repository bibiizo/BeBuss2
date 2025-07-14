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
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, bus.jam_berangkat, po.nama_po, rute.kota_asal, rute.kota_tujuan 
                       FROM pemesanan 
                       JOIN bus ON pemesanan.bus_id = bus.id 
                       JOIN po ON bus.po_id = po.id 
                       JOIN rute ON bus.rute_id = rute.id 
                       WHERE pemesanan.id = ? AND pemesanan.user_id = ?");
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
// Tidak ada lagi output debugging di HTML
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Pemesanan - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .detail-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .detail-box h2 {
            margin-bottom: 20px;
        }

        .detail-item {
            margin-bottom: 10px;
        }

        .btn-group {
            margin-top: 30px;
        }

        .btn-group form {
            display: inline;
            margin-right: 10px; /* Memberi sedikit jarak antar tombol */
        }
        .btn-group .btn {
            margin: 0; /* Override margin dari .btn global jika ada */
        }
        /* Styling untuk pesan sukses dan error */
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
        /* Styling untuk countdown timer */
        .countdown-timer {
            font-size: 1.2em;
            font-weight: bold;
            color: #d9534f; /* Merah untuk menarik perhatian */
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            border: 1px dashed #d9534f;
            border-radius: 5px;
            background-color: #fefefe;
        }
        .countdown-timer.expired {
            color: #5cb85c; /* Hijau jika sudah kedaluwarsa */
            border-color: #5cb85c;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>
<div class="container">
    <?php if (!empty($success_message)): ?>
        <p class="success-message-alert"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <p class="error-message-alert"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>
    <div class="detail-box">
        <h2>Detail Pemesanan</h2>

        <div class="detail-item"><strong>PO:</strong> <?= $data['nama_po'] ?></div>
        <div class="detail-item"><strong>Rute:</strong> <?= $data['kota_asal'] ?> &rarr; <?= $data['kota_tujuan'] ?></div>
        <div class="detail-item"><strong>Tanggal Berangkat:</strong> <?= date('d-m-Y', strtotime($data['tanggal_berangkat'])) ?></div>
        <div class="detail-item"><strong>Jam Berangkat:</strong> <?= $data['jam_berangkat'] ?></div>
        <div class="detail-item"><strong>Titik Naik:</strong> <?= $data['lokasi_naik'] ?></div>
        <div class="detail-item"><strong>Kursi:</strong> <?= $kursi_str ?></div>
        <div class="detail-item"><strong>Total Harga:</strong> Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></div>
        <div class="detail-item"><strong>Status:</strong> <span id="pemesananStatus"><?= ucfirst($data['status']) ?></span></div>

        <?php if ($data['status'] === 'aktif'): ?>
            <div class="countdown-timer">
                Sisa waktu pembayaran: <span id="countdown"></span>
            </div>
        <?php endif; ?>

        <div class="btn-group">
            <?php if ($data['status'] === 'aktif'): ?>
                <form id="formBatalkan" method="post" action="batalkan.php" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pemesanan ini? Kursi akan dikosongkan kembali.')">
                    <input type="hidden" name="id" value="<?= $data['id'] ?>">
                    <button type="submit" class="btn" id="btnBatalkan">Batalkan</button>
                </form>
                <a href="bayar_dummy.php?id=<?= $data['id'] ?>" class="btn" id="btnBayar">Bayar</a>
            <?php endif; ?>

            <?php if ($data['status'] === 'selesai'): ?>
                <a href="cetak_dummy.php?id=<?= $data['id'] ?>" class="btn">Cetak Bukti</a>
            <?php endif; ?>

            <?php if ($data['status'] === 'batal'): ?>
                <form method="post" action="hapus_pemesanan.php" onsubmit="return confirm('PERINGATAN: Menghapus pemesanan akan menghilangkan data ini secara permanen dari riwayat Anda. Apakah Anda yakin?')">
                    <input type="hidden" name="id" value="<?= $data['id'] ?>">
                    <button type="submit" class="btn" style="background-color: #dc3545;">Hapus</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Ambil sisa waktu dari PHP (dalam detik)
    let sisaWaktuDetik = <?= $sisa_waktu_detik ?>;
    const pemesananStatusElement = document.getElementById('pemesananStatus');
    const countdownElement = document.getElementById('countdown');
    const btnBayar = document.getElementById('btnBayar');
    const btnBatalkan = document.getElementById('btnBatalkan');
    const formBatalkan = document.getElementById('formBatalkan');
    const btnGroup = document.querySelector('.btn-group');

    let countdownInterval;

    // Fungsi untuk memformat waktu ke MM:SS (karena hanya 10 menit, HH tidak diperlukan)
    function formatTime(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        const formattedMinutes = minutes.toString().padStart(2, '0');
        const formattedSeconds = seconds.toString().padStart(2, '0');

        return `${formattedMinutes}:${formattedSeconds}`; 
    }

    function updateCountdown() {
        if (sisaWaktuDetik <= 0) {
            clearInterval(countdownInterval);
            if (countdownElement) {
                countdownElement.textContent = "Waktu habis!";
                countdownElement.parentElement.classList.add('expired');
            }
            pemesananStatusElement.textContent = "Batal (Waktu Habis)";
            
            if (btnBayar) btnBayar.style.display = 'none';
            if (formBatalkan) formBatalkan.style.display = 'none';

            setTimeout(() => {
                location.reload(); 
            }, 2000);
            
            return;
        }

        if (countdownElement) {
            countdownElement.textContent = formatTime(sisaWaktuDetik);
        }
        sisaWaktuDetik--;
    }

    // Hanya jalankan countdown jika status pemesanan adalah 'aktif'
    if (pemesananStatusElement && pemesananStatusElement.textContent.toLowerCase().includes('aktif')) {
        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 1000);
    } else {
        if (countdownElement && countdownElement.parentElement) {
            countdownElement.parentElement.style.display = 'none';
        }
    }
</script>
</body>
</html>