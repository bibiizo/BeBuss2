<?php
session_start();
require_once '../../config/database.php';

// Pastikan navbar di-include juga di sini (jika belum)
include '../components/navbar.php';

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
<html>
<head>
    <title>Detail Bus - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .bus-info {
            margin-bottom: 20px;
        }
        .kursi-grid {
            display: grid;
            grid-template-columns: repeat(<?= $jumlah_kolom ?>, 60px);
            gap: 10px;
            margin-bottom: 20px;
        }
        .kursi-box {
            padding: 12px;
            background-color: #e7e9ec;
            border: 2px solid #ccc;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
        }
        .kursi-box.terisi {
            background-color: #ccc;
            cursor: not-allowed;
            color: #888;
        }
        .kursi-box.selected {
            background-color: #287fd7;
            color: white;
            font-weight: bold;
        }
        /* Styling untuk pesan error */
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 15px;
            list-style-type: none;
            padding-left: 0;
        }
        .error-message li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php // Navbar sudah di-include di atas ?>
<div class="container">
    <h2>Detail Bus</h2>

    <?php if (!empty($errors)): // Tampilkan pesan error jika ada ?>
        <ul class="error-message">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="bus-info">
        <p><strong>PO:</strong> <?= $bus['nama_po'] ?></p>
        <p><strong>Rute:</strong> <?= $bus['kota_asal'] ?> → <?= $bus['kota_tujuan'] ?></p>
        <p><strong>Tanggal:</strong> <?= date('d M Y', strtotime($bus['tanggal_berangkat'])) ?></p>
        <p><strong>Jam:</strong> <?= $bus['jam_berangkat'] ?></p>
        <p><strong>Harga:</strong> Rp <?= number_format($bus['harga'], 0, ',', '.') ?></p>
    </div>

    <form method="POST" action="proses_pesan.php">
        <input type="hidden" name="bus_id" value="<?= $bus_id ?>">
        <input type="hidden" name="harga" value="<?= $bus['harga'] ?>">

        <label for="lokasi_naik">Titik Naik:</label>
        <select name="lokasi_naik" id="lokasi_naik" required>
            <option value="">-- Pilih Titik Naik --</option>
            <?php foreach ($titik_naik_list as $titik): ?>
                <option value="<?= htmlspecialchars($titik['nama_titik']) ?>"
                    <?= ($titik['nama_titik'] == $old_input['lokasi_naik']) ? 'selected' : '' ?>>
                    <?= $titik['nama_titik'] ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label><strong>Pilih Kursi:</strong></label>
        <div class="kursi-grid" id="kursi-container">
            <?php foreach ($kursi as $k): ?>
                <?php
                $is_selected_old = in_array($k['nomor_kursi'], $old_selected_kursi) ? 'selected' : '';
                $is_terisi = $k['status'] === 'terisi' ? 'terisi' : '';
                ?>
                <div class="kursi-box <?= $is_terisi ?> <?= $is_selected_old ?>"
                     data-kursi="<?= $k['nomor_kursi'] ?>">
                    <?= $k['nomor_kursi'] ?>
                </div>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="kursi" id="selected-kursi" value='<?= htmlspecialchars($old_input['kursi']) ?>'>
        <button type="submit" class="btn">Pesan Sekarang</button>
    </form>
</div>

<script>
    const kursiBox = document.querySelectorAll('.kursi-box:not(.terisi)');
    const selectedInput = document.getElementById('selected-kursi');

    let selected = [];

    // Jika ada kursi yang sebelumnya terpilih (dari old_input), tambahkan ke array selected
    const initialSelectedKursiJson = selectedInput.value;
    if (initialSelectedKursiJson) {
        try {
            selected = JSON.parse(initialSelectedKursiJson);
            // Tambahkan kelas 'selected' ke kotak kursi yang awalnya terpilih
            selected.forEach(kode => {
                const box = document.querySelector(`.kursi-box[data-kursi="${kode}"]`);
                if (box && !box.classList.contains('terisi')) { // Pastikan tidak terisi
                    box.classList.add('selected');
                }
            });
        } catch (e) {
            console.error("Error parsing initial selected kursi JSON:", e);
            selected = [];
        }
    }

    kursiBox.forEach(box => {
        box.addEventListener('click', () => {
            const kode = box.dataset.kursi;
            if (box.classList.contains('terisi')) {
                // Jangan lakukan apa-apa jika kursi terisi
                return;
            }

            if (selected.includes(kode)) {
                selected = selected.filter(k => k !== kode);
                box.classList.remove('selected');
            } else {
                selected.push(kode);
                box.classList.add('selected');
            }
            selectedInput.value = JSON.stringify(selected);
        });
    });
</script>
</body>
</html>
