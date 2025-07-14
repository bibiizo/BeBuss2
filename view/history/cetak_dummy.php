<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("ID pemesanan tidak ditemukan.");
}

$pemesanan_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data pemesanan, pastikan statusnya 'selesai'
$stmt = $pdo->prepare("SELECT pemesanan.*, bus.tanggal_berangkat, bus.jam_berangkat, bus.harga,
                       po.nama_po, rute.kota_asal, rute.kota_tujuan, users.nama_lengkap, users.email, users.no_hp
                       FROM pemesanan
                       JOIN bus ON pemesanan.bus_id = bus.id
                       JOIN po ON bus.po_id = po.id
                       JOIN rute ON bus.rute_id = rute.id
                       JOIN users ON pemesanan.user_id = users.id
                       WHERE pemesanan.id = ? AND pemesanan.user_id = ? AND pemesanan.status = 'selesai'");
$stmt->execute([$pemesanan_id, $user_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Pemesanan tidak ditemukan, belum selesai, atau bukan milik Anda.");
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
<html>
<head>
    <title>Cetak Bukti Pemesanan - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff; /* Latar belakang putih untuk cetak */
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .ticket-container {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            border: 1px solid #ddd;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 15px;
        }
        .ticket-header h1 {
            color: #007bff;
            margin-bottom: 5px;
        }
        .ticket-details table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .ticket-details table td {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            vertical-align: top;
        }
        .ticket-details table td:first-child {
            font-weight: bold;
            width: 35%;
        }
        .ticket-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
            font-size: 0.9em;
            color: #666;
        }
        .print-button {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            border: none;
        }
        .print-button:hover {
            background-color: #0056b3;
        }

        /* Media Print Styles */
        @media print {
            .navbar, nav, header {
                display: none !important; /* Sembunyikan navbar saat mencetak */
            }
            body {
                margin: 0;
                padding: 0;
            }
            .ticket-container {
                box-shadow: none;
                border: none;
                margin: 0;
                width: 100%;
                max-width: none;
            }
            .print-button {
                display: none !important; /* Sembunyikan tombol cetak saat mencetak */
            }
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; // Sertakan navbar ?>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>BUKTI PEMESANAN TIKET BUS</h1>
            <p><strong>BeBuss</strong></p>
        </div>

        <div class="ticket-details">
            <h3>Informasi Pemesanan</h3>
            <table>
                <tr>
                    <td>Nomor Pemesanan:</td>
                    <td><?= htmlspecialchars($data['id']) ?></td>
                </tr>
                <tr>
                    <td>PO Bus:</td>
                    <td><?= htmlspecialchars($data['nama_po']) ?></td>
                </tr>
                <tr>
                    <td>Rute:</td>
                    <td><?= htmlspecialchars($data['kota_asal']) ?> &rarr; <?= htmlspecialchars($data['kota_tujuan']) ?></td>
                </tr>
                <tr>
                    <td>Tanggal Berangkat:</td>
                    <td><?= date('d M Y', strtotime($data['tanggal_berangkat'])) ?></td>
                </tr>
                <tr>
                    <td>Jam Berangkat:</td>
                    <td><?= htmlspecialchars($data['jam_berangkat']) ?></td>
                </tr>
                <tr>
                    <td>Titik Naik:</td>
                    <td><?= htmlspecialchars($data['lokasi_naik']) ?></td>
                </tr>
                <tr>
                    <td>Nomor Kursi:</td>
                    <td><?= htmlspecialchars($kursi_str) ?></td>
                </tr>
                <tr>
                    <td>Harga per Tiket:</td>
                    <td>Rp <?= number_format($data['harga'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Jumlah Tiket:</td>
                    <td><?= count($kursi) ?></td>
                </tr>
                <tr>
                    <td>Total Harga:</td>
                    <td><strong>Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></strong></td>
                </tr>
                <tr>
                    <td>Status Pemesanan:</td>
                    <td><?= ucfirst(htmlspecialchars($data['status'])) ?></td>
                </tr>
                <tr>
                    <td>Tanggal Pesan:</td>
                    <td><?= date('d M Y H:i', strtotime($data['tanggal_pesan'])) ?></td>
                </tr>
            </table>

            <h3>Informasi Penumpang</h3>
            <table>
                <tr>
                    <td>Nama Lengkap:</td>
                    <td><?= htmlspecialchars($data['nama_lengkap']) ?></td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td><?= htmlspecialchars($data['email']) ?></td>
                </tr>
                <tr>
                    <td>Nomor HP:</td>
                    <td><?= htmlspecialchars($data['no_hp']) ?></td>
                </tr>
            </table>
        </div>

        <div class="ticket-footer">
            <p>Terima kasih telah memesan tiket Anda di BeBuss.</p>
            <p>Harap tunjukkan bukti ini saat naik bus.</p>
        </div>
    </div>

    <button onclick="window.print()" class="print-button">Cetak Bukti Ini</button>
    <a href="history_detail.php?pemesanan_id=<?= htmlspecialchars($pemesanan_id) ?>" class="btn print-button" style="background-color: #6c757d;">Kembali</a>

</body>
</html>