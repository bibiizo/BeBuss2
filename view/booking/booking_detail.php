<?php
session_start();
require_once '../../config/database.php';

// Ambil error dan old_input dari session, jika ada
$errors = $_SESSION['booking_errors'] ?? [];
$old_input = $_SESSION['old_booking_input'] ?? ['lokasi_naik' => '', 'kursi' => '[]'];

// Hapus error dan old_input dari session setelah diambil
unset($_SESSION['booking_errors']);
unset($_SESSION['old_booking_input']);

// Jika ada bus_id yang tidak valid di GET request, kita bisa redirect atau tampilkan error
if (!isset($_GET['bus_id']) || !is_numeric($_GET['bus_id']) || $_GET['bus_id'] <= 0) {
    // Tampilkan pesan error dan redirect ke halaman search/home
    $_SESSION['error_message'] = "ID Bus tidak ditemukan atau tidak valid.";
    header('Location: ../home/index_home.php');
    exit;
}

$bus_id = $_GET['bus_id'];

// Ambil info bus, rute, po
$stmt = $pdo->prepare("SELECT bus.*, po.nama_po, rute.kota_asal, rute.kota_tujuan 
                       FROM bus 
                       JOIN po ON bus.po_id = po.id 
                       JOIN rute ON bus.rute_id = rute.id 
                       WHERE bus.id = ?");
$stmt->execute([$bus_id]);
$bus = $stmt->fetch();

if (!$bus) {
    // Jika bus tidak ditemukan, tampilkan pesan error dan redirect
    $_SESSION['error_message'] = "Detail bus tidak valid atau tidak ditemukan.";
    header('Location: ../home/index_home.php');
    exit;
}

// Ambil titik naik dari tabel titik_naik berdasarkan rute_id
$stmt = $pdo->prepare("SELECT nama_titik FROM titik_naik WHERE rute_id = ?");
$stmt->execute([$bus['rute_id']]);
$titik_naik_list = $stmt->fetchAll();

// Jika tidak ada titik naik dari tabel titik_naik, gunakan dari kolom bus
if (empty($titik_naik_list) && !empty($bus['titik_naik'])) {
    $titik_naik_list = [['nama_titik' => $bus['titik_naik']]];
}

// Ambil daftar kursi
$stmt = $pdo->prepare("SELECT * FROM kursi WHERE bus_id = ? ORDER BY nomor_kursi ASC");
$stmt->execute([$bus_id]);
$kursi = $stmt->fetchAll();

// Tentukan jumlah kolom
$jumlah_kolom = ($bus_id >= 8 && $bus_id <= 13) ? 3 : 4; // Logika ini sepertinya spesifik, pastikan relevan

// Konversi old_input kursi dari JSON string ke array jika ada
$old_selected_kursi = json_decode($old_input['kursi'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Kursi - <?= htmlspecialchars($bus['nama_po']) ?> - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <main class="container booking-detail-container">
        <div class="page-header">
            <a href="../home/index_home.php" class="btn btn-secondary">&larr; Ganti Bus</a>
            <h1>Detail Pemesanan</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="proses_pesan.php" method="POST" id="bookingForm">
            <input type="hidden" name="bus_id" value="<?= $bus_id ?>">
            <input type="hidden" name="harga" id="harga_per_kursi" value="<?= $bus['harga'] ?>">
            <input type="hidden" name="kursi" id="selected_seats_input" value='<?= json_encode($old_selected_kursi) ?>'>

            <div class="booking-grid">
                <div class="seating-card">
                    <div class="seating-card-header">
                        <h3>Pilih Kursi Anda</h3>
                    </div>
                    
                    <div class="seating-layout <?= $jumlah_kolom === 3 ? 'cols-3' : '' ?>">
                        <?php foreach ($kursi as $k): ?>
                            <div class="seat <?= $k['status'] === 'terisi' ? 'unavailable' : 'available' ?>" 
                                 data-seat-id="<?= $k['id'] ?>" 
                                 data-seat-number="<?= $k['nomor_kursi'] ?>">
                                <?= $k['nomor_kursi'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="seating-legend">
                        <div class="legend-item">
                            <span class="legend-color selected">1</span>
                            Terpilih
                        </div>
                        <div class="legend-item">
                            <span class="legend-color available">2</span>
                            Tersedia
                        </div>
                        <div class="legend-item">
                            <span class="legend-color unavailable">3</span>
                            Terisi
                        </div>
                    </div>
                </div>

                <aside class="summary-card">
                    <div class="summary-card-header">
                        <h3>Ringkasan Pesanan</h3>
                    </div>
                    
                    <div class="summary-item">
                        <span class="label">Operator Bus</span>
                        <span class="value"><?= htmlspecialchars($bus['nama_po']) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Rute</span>
                        <span class="value"><?= htmlspecialchars($bus['kota_asal']) ?> → <?= htmlspecialchars($bus['kota_tujuan']) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Tanggal</span>
                        <span class="value"><?= date('d M Y', strtotime($bus['tanggal_berangkat'])) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Kursi Terpilih</span>
                        <span class="value" id="selected_seats_display">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Jumlah Penumpang</span>
                        <span class="value" id="passenger_count">0</span>
                    </div>

                    <div class="form-group booking-form-spacing">
                        <label for="lokasi_naik" class="form-label">Pilih Titik Naik</label>
                        <select name="lokasi_naik" id="lokasi_naik" class="form-control">
                            <option value="">-- Pilih Lokasi --</option>
                            <?php foreach ($titik_naik_list as $titik): ?>
                                <option value="<?= htmlspecialchars($titik['nama_titik']) ?>" <?= ($old_input['lokasi_naik'] == $titik['nama_titik']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($titik['nama_titik']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="summary-total summary-item">
                        <span class="label">Total Bayar</span>
                        <span class="value" id="total_price">Rp 0</span>
                    </div>

                    <button type="submit" class="btn btn-primary booking-submit-btn">Lanjutkan ke Pembayaran</button>
                </aside>
            </div>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const seatsContainer = document.querySelector('.seating-layout');
            const selectedSeatsInput = document.getElementById('selected_seats_input');
            const selectedSeatsDisplay = document.getElementById('selected_seats_display');
            const passengerCountDisplay = document.getElementById('passenger_count');
            const totalPriceDisplay = document.getElementById('total_price');
            const hargaPerKursi = parseFloat(document.getElementById('harga_per_kursi').value);

            let selectedSeats = JSON.parse(selectedSeatsInput.value || '[]');

            function updateSummary() {
                const seatNumbers = selectedSeats.map(id => {
                    const seatElement = seatsContainer.querySelector(`[data-seat-id='${id}']`);
                    return seatElement ? seatElement.dataset.seatNumber : '';
                }).filter(Boolean);

                selectedSeatsDisplay.textContent = seatNumbers.length > 0 ? seatNumbers.join(', ') : '-';
                passengerCountDisplay.textContent = selectedSeats.length;
                
                const totalPrice = selectedSeats.length * hargaPerKursi;
                totalPriceDisplay.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');

                selectedSeatsInput.value = JSON.stringify(selectedSeats);
            }

            seatsContainer.addEventListener('click', function(e) {
                const seat = e.target.closest('.seat.available');
                if (!seat) return;

                const seatId = seat.dataset.seatId;
                
                if (selectedSeats.includes(seatId)) {
                    selectedSeats = selectedSeats.filter(id => id !== seatId);
                    seat.classList.remove('selected');
                } else {
                    selectedSeats.push(seatId);
                    seat.classList.add('selected');
                }
                updateSummary();
            });

            // Initial state setup
            document.querySelectorAll('.seat.available').forEach(seat => {
                if (selectedSeats.includes(seat.dataset.seatId)) {
                    seat.classList.add('selected');
                }
            });

            updateSummary();
        });
    </script>
</body>
</html>
