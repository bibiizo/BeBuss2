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
        // Langkah 1: Verifikasi ketersediaan semua kursi yang dipilih SEBELUM membuat pesanan
        // Ini mencegah pembuatan pesanan jika salah satu kursi sudah tidak tersedia.
        $placeholders = implode(',', array_fill(0, count($kursi_terpilih), '?'));
        $stmt_check_kursi = $pdo->prepare("SELECT id, status FROM kursi WHERE id IN ($placeholders) AND bus_id = ? FOR UPDATE");
        
        $params = $kursi_terpilih;
        $params[] = $bus_id;
        $stmt_check_kursi->execute($params);
        
        $kursi_ditemukan = $stmt_check_kursi->fetchAll(PDO::FETCH_ASSOC);

        if (count($kursi_ditemukan) !== count($kursi_terpilih)) {
            throw new Exception("Beberapa kursi yang dipilih tidak valid atau tidak ditemukan untuk bus ini.");
        }

        foreach ($kursi_ditemukan as $k) {
            if ($k['status'] !== 'kosong') {
                throw new Exception("Kursi dengan ID {$k['id']} sudah terisi. Silakan pilih kursi lain.");
            }
        }

        // Langkah 2: Simpan ke tabel pemesanan
        $stmt_pemesanan = $pdo->prepare(
            "INSERT INTO pemesanan (user_id, bus_id, lokasi_naik, total_harga, status) VALUES (?, ?, ?, ?, 'aktif')"
        );
        $stmt_pemesanan->execute([$user_id, $bus_id, $lokasi_naik, $total_harga_final]);
        $pemesanan_id = $pdo->lastInsertId();

        // Langkah 3: Simpan detail setiap kursi yang dipesan
        $stmt_detail_kursi = $pdo->prepare("INSERT INTO detail_kursi_pesan (pemesanan_id, kursi_id) VALUES (?, ?)");
        foreach ($kursi_terpilih as $kursi_id) {
            $stmt_detail_kursi->execute([$pemesanan_id, $kursi_id]);
        }

        // Langkah 4: Update status semua kursi yang dipilih menjadi 'terisi'
        $stmt_update_kursi = $pdo->prepare("UPDATE kursi SET status = 'terisi' WHERE id IN ($placeholders)");
        $stmt_update_kursi->execute($kursi_terpilih);

        // Jika semua berhasil, commit transaksi
        $pdo->commit();

        // Redirect ke halaman detail riwayat dengan pesan sukses
        $_SESSION['success_message'] = "Pemesanan Anda berhasil dibuat! Segera selesaikan pembayaran.";
        header('Location: ../history/history_detail.php?pemesanan_id=' . $pemesanan_id);
        exit;

    } catch (Exception $e) {
        // Jika ada error, rollback semua perubahan
        $pdo->rollBack();
        
        // Simpan pesan error dan data input lama ke session
        $_SESSION['booking_errors'] = [$e->getMessage()];
        $_SESSION['old_booking_input'] = [
            'lokasi_naik' => $lokasi_naik,
            'kursi' => $kursi_terpilih_json
        ];
        
        // Redirect kembali ke halaman booking
        header('Location: ' . $redirect_url);
        exit;
    }
} else {
    // Jika bukan metode POST, redirect ke halaman utama
    header('Location: ../home/index_home.php');
    exit;
}
?>