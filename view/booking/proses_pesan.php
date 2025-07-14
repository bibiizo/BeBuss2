<?php
session_start();
require_once '../../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    // Redirect ke login, simpan pesan error jika perlu
    $_SESSION['login_errors'] = ["Anda harus login untuk melanjutkan pemesanan."];
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $bus_id = $_POST['bus_id'] ?? null;
    $harga = $_POST['harga'] ?? null;
    $lokasi_naik = trim($_POST['lokasi_naik'] ?? '');
    $kursi_terpilih_json = $_POST['kursi'] ?? '[]'; // JSON string of selected seats

    $errors = []; // Array untuk menyimpan pesan error
    $redirect_url = '../booking/booking_detail.php?bus_id=' . urlencode($bus_id);

    // --- Validasi Input Dasar ---
    if (!is_numeric($bus_id) || $bus_id <= 0) {
        $errors[] = "ID bus tidak valid.";
    }
    if (!is_numeric($harga) || $harga <= 0) {
        $errors[] = "Harga tiket tidak valid.";
    }
    if (empty($lokasi_naik)) {
        $errors[] = "Titik Naik tidak boleh kosong.";
    }

    $kursi_terpilih = json_decode($kursi_terpilih_json, true);
    if (empty($kursi_terpilih) || !is_array($kursi_terpilih)) {
        $errors[] = "Tidak ada kursi yang dipilih atau pilihan kursi tidak valid.";
    }

    // Jika ada error validasi dasar, simpan ke session dan redirect
    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['old_booking_input'] = [
            'lokasi_naik' => $lokasi_naik,
            'kursi' => $kursi_terpilih_json // Kirim kembali sebagai JSON
        ];
        header('Location: ' . $redirect_url);
        exit;
    }

    // --- Validasi Keberadaan Bus ---
    // Pastikan bus_id yang dikirimkan benar-benar ada di database dan harganya cocok
    try {
        $stmt_bus = $pdo->prepare("SELECT harga FROM bus WHERE id = ?");
        $stmt_bus->execute([$bus_id]);
        $bus_data = $stmt_bus->fetch(PDO::FETCH_ASSOC);

        if (!$bus_data) {
            $errors[] = "Bus yang dipilih tidak ditemukan.";
        } elseif (intval($bus_data['harga']) !== intval($harga)) {
            $errors[] = "Harga bus tidak cocok. Ada potensi manipulasi data.";
        }
    } catch (PDOException $e) {
        $errors[] = "Kesalahan database saat memverifikasi bus.";
        error_log("Error verifying bus in proses_pesan.php: " . $e->getMessage());
    }

    if (!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['old_booking_input'] = [
            'lokasi_naik' => $lokasi_naik,
            'kursi' => $kursi_terpilih_json
        ];
        header('Location: ' . $redirect_url);
        exit;
    }

    // Hitung total harga
    $total_harga_final = count($kursi_terpilih) * intval($harga);

    // --- Mulai Transaksi ---
    $pdo->beginTransaction();

    try {
        // Simpan ke tabel pemesanan
        // tanggal_pesan akan otomatis terisi oleh DEFAULT CURRENT_TIMESTAMP di DB
        $stmt_pemesanan = $pdo->prepare("INSERT INTO pemesanan (user_id, bus_id, lokasi_naik, total_harga, status) VALUES (?, ?, ?, ?, 'aktif')");
        $stmt_pemesanan->execute([$user_id, $bus_id, $lokasi_naik, $total_harga_final]);

        $pemesanan_id = $pdo->lastInsertId();

        $kursi_to_update_ids = []; // Untuk menyimpan ID kursi yang akan diupdate statusnya
        $kursi_terpilih_valid = []; // Untuk menyimpan nomor kursi yang valid saja

        // Verifikasi dan Simpan detail kursi
        foreach ($kursi_terpilih as $nomor) {
            // Ambil kursi_id dan status
            $stmt_kursi = $pdo->prepare("SELECT id, status FROM kursi WHERE bus_id = ? AND nomor_kursi = ?");
            $stmt_kursi->execute([$bus_id, $nomor]);
            $kursi_data = $stmt_kursi->fetch(PDO::FETCH_ASSOC);

            if ($kursi_data && $kursi_data['status'] === 'kosong') {
                $kursi_id = $kursi_data['id'];
                // Masukkan ke detail_kursi_pesan
                $stmt_insert_detail = $pdo->prepare("INSERT INTO detail_kursi_pesan (pemesanan_id, kursi_id) VALUES (?, ?)");
                $stmt_insert_detail->execute([$pemesanan_id, $kursi_id]);

                $kursi_to_update_ids[] = $kursi_id; // Tambahkan ke daftar update
                $kursi_terpilih_valid[] = $nomor; // Tambahkan ke daftar kursi yang benar-benar dipilih
            } else {
                // Kursi sudah tidak kosong (terisi atau tidak valid)
                $pdo->rollBack();
                $_SESSION['booking_errors'] = ["Kursi $nomor sudah tidak tersedia atau tidak valid. Silakan pilih ulang."];
                $_SESSION['old_booking_input'] = [
                    'lokasi_naik' => $lokasi_naik,
                    'kursi' => '[]' // Kosongkan pilihan kursi lama
                ];
                header('Location: ' . $redirect_url);
                exit; // Hentikan eksekusi
            }
        }

        // Update status kursi menjadi 'terisi' secara massal jika memungkinkan, atau per satu
        if (!empty($kursi_to_update_ids)) {
            $placeholders = implode(',', array_fill(0, count($kursi_to_update_ids), '?'));
            $stmt_update_kursi = $pdo->prepare("UPDATE kursi SET status = 'terisi' WHERE id IN ($placeholders)");
            $stmt_update_kursi->execute($kursi_to_update_ids);
        }

        $pdo->commit(); // Commit transaksi jika semua berhasil

        // Redirect ke halaman riwayat dengan pesan sukses
        $_SESSION['success_message'] = "Pemesanan tiket berhasil. Silakan selesaikan pembayaran dalam 1 jam.";
        header("Location: ../history/history_index.php");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback jika ada error
        error_log("Error in proses_pesan.php transaction: " . $e->getMessage()); // Log error
        $_SESSION['booking_errors'] = ["Terjadi kesalahan saat memproses pemesanan. Mohon coba lagi."];
        $_SESSION['old_booking_input'] = [
            'lokasi_naik' => $lokasi_naik,
            'kursi' => $kursi_terpilih_json
        ];
        header('Location: ' . $redirect_url);
        exit;
    }
} else {
    // Jika akses langsung ke controller tanpa metode POST
    header('Location: ../home/index_home.php'); // Arahkan ke halaman utama/pencarian
    exit;
}
?>