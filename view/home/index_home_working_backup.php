<?php
session_start();
require_once '../../config/database.php';
require_once '../../model/User.php';

// Set timezone dan validasi session
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user dan cek kelengkapan profil
$userModel = new User($pdo);
$user = $userModel->findById($user_id);
$profile_complete = !empty($user['nama_lengkap']) && !empty($user['no_hp']) && !empty($user['jenis_kelamin']);

// Optimasi: Gabungkan expired orders handling dalam satu function
function handleExpiredOrders($pdo, $user_id) {
    $cancelled_ticket_ids = [];
    
    try {
        // Ambil semua order yang expired
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

                    $pdo->commit();
                    $cancelled_ticket_ids[] = $pemesanan_id;
                } else {
                    $pdo->rollBack();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Failed to cancel expired order {$pemesanan_id}: " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log("Error handling expired orders: " . $e->getMessage());
    }
    
    return $cancelled_ticket_ids;
}

// Handle expired orders
$cancelled_ticket_ids = handleExpiredOrders($pdo, $user_id);

// Get active tickets count
$stmt_tiket_aktif = $pdo->prepare("SELECT COUNT(*) FROM pemesanan WHERE user_id = ? AND status = 'aktif'");
$stmt_tiket_aktif->execute([$user_id]);
$total_tiket_aktif = $stmt_tiket_aktif->fetchColumn();

// Search parameters
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* Layout */
        .wrapper {
            display: flex;
            align-items: flex-start;
            padding: 20px;
        }

        .sidebar {
            position: sticky;
            top: 20px;
            width: 250px;
            background: #f4f4f4;
            padding: 20px;
            border-radius: 8px;
            height: fit-content;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .sidebar input, .sidebar label, .sidebar select {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            box-sizing: border-box;
        }
        
        .content {
            flex: 1;
            max-height: 600px;
            overflow-y: auto;
            padding-left: 30px;
        }

        /* Cards */
        .po-box, .dashboard-info-card {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .po-box {
            background-color: #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .po-box:hover {
            background-color: #d0d0d0;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .dashboard-info-card {
            background-color: #fff;
            padding: 15px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .welcome-dashboard-section {
            background-color: #007bff;
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Typography */
        .welcome-dashboard-section h2 {
            color: white;
            margin: 0 0 10px 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        .dashboard-info-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.1em;
        }

        .dashboard-info-card p {
            font-size: 1.8em;
            font-weight: bold;
            color: #007bff;
            margin: 5px 0 0;
        }

        .dashboard-info-card small {
            font-size: 0.8em;
            color: #666;
            display: block;
            margin-top: 5px;
        }

        .dashboard-info-card .action-message {
            font-size: 0.9em;
            color: #dc3545;
            margin-top: 10px;
            font-weight: bold;
        }

        /* Alerts */
        .alert-info {
            background-color: #e0f7fa;
            color: #007bff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #007bff;
        }
        /* Styling untuk Autocomplete Suggestions */
        .autocomplete-container {
            position: relative; /* Penting untuk positioning dropdown */
            width: 100%;
            margin-bottom: 15px; /* Sesuaikan margin-bottom label input */
        }
        .autocomplete-list {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            border-top: none;
            z-index: 99; /* Pastikan di atas elemen lain */
            top: 100%; /* Posisi di bawah input */
            left: 0;
            right: 0;
            background-color: #f9f9f9;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .autocomplete-list-item {
            padding: 10px;
            cursor: pointer;
            background-color: #fff;
            border-bottom: 1px solid #d4d4d4;
            text-align: left;
        }
        .autocomplete-list-item:hover {
            background-color: #e9e9e9;
        }
        .autocomplete-list-item.active { /* Untuk navigasi keyboard */
            background-color: #007bff;
            color: white;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: none;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .modal-header h3 {
            margin: 0;
            color: #dc3545;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close:hover,
        .close:focus {
            color: #000;
        }
        .modal-body {
            margin-bottom: 20px;
        }
        .modal-footer {
            text-align: right;
        }
        .btn-complete-profile {
            background-color: #ffc107;
            color: #212529;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }
        .btn-complete-profile:hover {
            background-color: #e0a800;
            text-decoration: none;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <!-- Modal untuk alert profil belum lengkap -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚠️ Profil Belum Lengkap</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Anda perlu melengkapi profil sebelum dapat memesan tiket.</p>
                <p><strong>Data yang diperlukan:</strong></p>
                <ul>
                    <li>Nama Lengkap</li>
                    <li>Nomor Handphone</li>
                    <li>Jenis Kelamin</li>
                </ul>
                <p>Silakan lengkapi profil Anda terlebih dahulu.</p>
            </div>
            <div class="modal-footer">
                <a href="../auth/complete_profile.php" class="btn-complete-profile">
                    📝 Lengkapi Profil
                </a>
                <button type="button" class="btn-cancel" onclick="modal.close()">Tutup</button>
            </div>
        </div>
    </div>

    <div class="wrapper">
        <div class="sidebar">
            <div class="welcome-dashboard-section">
                <h2>Halo, <?php 
                    // Prioritas: nama_lengkap, jika kosong gunakan bagian pertama email, jika tidak ada gunakan "Pengguna"
                    if (!empty($user['nama_lengkap'])) {
                        echo htmlspecialchars($user['nama_lengkap']);
                    } else {
                        // Ambil bagian pertama email (sebelum @) dan buat title case
                        $email_parts = explode('@', $user['email']);
                        $username = ucfirst($email_parts[0]);
                        echo htmlspecialchars($username);
                    }
                ?>!</h2>
            </div>
            
            <?php if (!empty($cancelled_ticket_ids)): ?>
                <div class="alert-info" style="background-color: #ffe0b2; border-color: #ff9800; color: #e65100;">
                    <p><strong>Pemberitahuan:</strong> Tiket dengan ID berikut telah otomatis dibatalkan karena batas waktu pembayaran habis: <?= implode(', ', $cancelled_ticket_ids) ?>. Silakan pesan ulang jika masih ingin bepergian.</p>
                </div>
            <?php endif; ?>

            <div class="dashboard-info-card">
                <h3>Tiket Aktif Anda</h3>
                <p><?= $total_tiket_aktif ?></p>
                <small><i>(belum dibayar)</i></small>
                <?php if ($total_tiket_aktif > 0): ?>
                    <div class="action-message">
                        Segera lakukan pembayaran untuk mengamankan tiket Anda!
                    </div>
                <?php endif; ?>
            </div>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #ccc;">

            <h3>Filter Pencarian</h3>
            <label>Dari (Kota Asal):</label>
            <div class="autocomplete-container">
                <input type="text" id="from" value="<?= htmlspecialchars($from) ?>" placeholder="Contoh: Padang">
                <div id="from-autocomplete-list" class="autocomplete-list"></div>
            </div>

            <label>Ke (Kota Tujuan):</label>
            <div class="autocomplete-container">
                <input type="text" id="to" value="<?= htmlspecialchars($to) ?>" placeholder="Contoh: Jakarta">
                <div id="to-autocomplete-list" class="autocomplete-list"></div>
            </div>

            <label>Tanggal Berangkat:</label>
            <input type="date" id="date" value="<?= htmlspecialchars($date) ?>">
        </div>

        <div class="content" id="result-area">
            <div class="po-box">Memuat daftar bus...</div>
        </div>
    </div>
    <script>
    // Basic elements
    const fromInput = document.getElementById('from');
    const toInput = document.getElementById('to');
    const dateInput = document.getElementById('date');
    const resultArea = document.getElementById('result-area');

    let fetchPOTimeout;

    // Simple fetchPO function
    function fetchPO() {
        clearTimeout(fetchPOTimeout);
        fetchPOTimeout = setTimeout(() => {
            const from = fromInput.value;
            const to = toInput.value;
            const date = dateInput.value;

            fetch(`po_list.php?from=${from}&to=${to}&date=${date}`)
                .then(response => response.text())
                .then(data => resultArea.innerHTML = data)
                .catch(error => {
                    console.error('Error:', error);
                    resultArea.innerHTML = '<div class="po-box" style="color: red;">Gagal memuat daftar bus.</div>';
                });
        }, 300);
    }

    // Very simple autocomplete
    function simpleAutocomplete(inputElement, listElement) {
        inputElement.addEventListener('input', function() {
            const val = this.value;
            listElement.innerHTML = '';

            if (val.length < 2) {
                fetchPO();
                return;
            }

            // Use XMLHttpRequest instead of fetch for better compatibility
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `../../api/api_saran_kota.php?query=${encodeURIComponent(val)}`, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const suggestions = JSON.parse(xhr.responseText);
                        
                        suggestions.forEach(suggestion => {
                            const item = document.createElement('div');
                            item.className = 'autocomplete-list-item';
                            item.textContent = suggestion;
                            
                            item.onclick = function() {
                                inputElement.value = suggestion;
                                listElement.innerHTML = '';
                                fetchPO();
                            };
                            
                            listElement.appendChild(item);
                        });
                    } catch (e) {
                        console.error('Parse error:', e);
                    }
                }
            };
            xhr.send();
            
            fetchPO();
        });
    }

    // Initialize
    simpleAutocomplete(fromInput, document.getElementById('from-autocomplete-list'));
    simpleAutocomplete(toInput, document.getElementById('to-autocomplete-list'));
    dateInput.addEventListener('change', fetchPO);
    
    // Handle PO selection clicks
    resultArea.addEventListener('click', function(e) {
        const poBox = e.target.closest('.po-box');
        if (poBox && poBox.hasAttribute('data-booking-url')) {
            const bookingUrl = poBox.getAttribute('data-booking-url');
            // Check if profile is complete (from PHP config)
            const profileComplete = <?= $profile_complete ? 'true' : 'false' ?>;
            
            if (!profileComplete) {
                // Show modal if profile not complete
                document.getElementById('profileModal').style.display = 'block';
            } else {
                // Go to booking page
                window.location.href = bookingUrl;
            }
        }
    });
    
    // Modal event handlers
    const modal = document.getElementById('profileModal');
    const closeBtn = document.querySelector('.close');
    
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
    }
    
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
    
    // Load initial data
    fetchPO();
</script>
</body>
</html>
