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
// Periksa apakah nama_lengkap, no_hp, dan jenis_kelamin tidak kosong
$profile_complete = !empty($user['nama_lengkap']) && !empty($user['no_hp']) && !empty($user['jenis_kelamin']);

// Optimasi: Gabungkan expired orders handling dalam satu function
function handleExpiredOrders($pdo) { // Tidak perlu $user_id di sini, karena query SELECT sudah filter semua
    $cancelled_ticket_ids = [];
    
    try {
        // Ambil semua order yang expired (untuk semua user)
        $stmt = $pdo->prepare("SELECT p.id as pemesanan_id 
                                FROM pemesanan p
                                WHERE p.status = 'aktif'
                                AND p.tanggal_pesan < DATE_SUB(NOW(), INTERVAL 10 MINUTE) FOR UPDATE"); // Tambah FOR UPDATE untuk menghindari race condition
        $stmt->execute();
        $expired_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expired_orders as $order) {
            $pdo->beginTransaction(); // Mulai transaksi per pesanan
            try {
                $pemesanan_id = $order['pemesanan_id'];

                // Verifikasi ulang status sebelum update
                $stmt_check = $pdo->prepare("SELECT status FROM pemesanan WHERE id = ?");
                $stmt_check->execute([$pemesanan_id]);
                $current_status = $stmt_check->fetchColumn();

                if ($current_status === 'aktif') { // Hanya batalkan jika status masih 'aktif'
                    // Update pemesanan status
                    $stmt_update = $pdo->prepare("UPDATE pemesanan SET status = 'batal' WHERE id = ?");
                    $stmt_update->execute([$pemesanan_id]);

                    // Free up seats
                    $stmt_kursi = $pdo->prepare("SELECT kursi_id FROM detail_kursi_pesan WHERE pemesanan_id = ?");
                    $stmt_kursi->execute([$pemesanan_id]);
                    $kursi_ids = $stmt_kursi->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($kursi_ids)) {
                        $placeholders = implode(',', array_fill(0, count($kursi_ids), '?'));
                        $stmt_free = $pdo->prepare("UPDATE kursi SET status = 'kosong' WHERE id IN ($placeholders)");
                        $stmt_free->execute($kursi_ids);
                    }

                    $pdo->commit();
                    $cancelled_ticket_ids[] = $pemesanan_id;
                } else {
                    $pdo->rollBack(); // Rollback jika status sudah berubah atau bukan 'aktif'
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Failed to cancel expired order {$pemesanan_id}: " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching expired orders: " . $e->getMessage());
    }
    
    return $cancelled_ticket_ids;
}

// Handle expired orders
$cancelled_ticket_ids = handleExpiredOrders($pdo);

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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket Bus Online - BeBuss</title>
    <link rel="icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="hero-title">
                    Temukan Perjalanan <span class="highlight">Impianmu</span>
                </h1>
                <p class="hero-subtitle">
                    Pesan tiket bus antar kota dengan mudah dan cepat! Jelajahi keindahan nusantara dengan nyaman bersama BeBuss - platform booking terpercaya untuk perjalanan impianmu.
                </p>
            </div>
            <div class="hero-image">
                <!-- Placeholder - Ganti dengan tag img untuk gambar asli -->
                <!-- <div class="hero-image-placeholder">
                    <div class="icon">🚌</div>
                    <div>Tempat untuk Gambar Bus</div>
                    <small class="hero-image-note">Upload gambar ke: assets/images/hero/</small>
                </div> -->
                
                <!-- Uncomment dan sesuaikan path setelah upload gambar: -->
                <img src="../../assets/images/hero/bus-hero.jpg" alt="BeBuss - Perjalanan Impianmu">
               
            </div>
        </div>
    </header>

    <main class="container">
        <!-- Search Section -->
        <section class="search-section">
            <div class="search-form-container">
                <form class="search-form" id="searchForm" onsubmit="return false;">
                    <div class="form-group">
                        <label for="from" class="form-label">Kota Asal</label>
                        <div class="autocomplete-container">
                            <input type="text" id="from" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>" placeholder="Contoh: Padang">
                            <div id="from-autocomplete-list" class="autocomplete-list"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="to" class="form-label">Kota Tujuan</label>
                        <div class="autocomplete-container">
                            <input type="text" id="to" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>" placeholder="Contoh: Jakarta">
                            <div id="to-autocomplete-list" class="autocomplete-list"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="date" class="form-label">Tanggal Berangkat</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-primary btn-full search-bus-btn">
                            🚌 Cari Bus
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- PO List Section -->
        <section class="po-list-section">
            <div class="grid" id="result-area">
                <!-- Hasil pencarian akan dimuat di sini oleh JavaScript -->
            </div>
        </section>
    </main>

    <!-- Modal untuk alert profil belum lengkap -->
    <div id="profileModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚠️ Profil Belum Lengkap</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Anda perlu melengkapi profil sebelum dapat memesan tiket.</p>
                <p><strong>Data yang diperlukan:</strong> Nama Lengkap, Nomor Handphone, dan Jenis Kelamin.</p>
                <p>Silakan lengkapi profil Anda terlebih dahulu.</p>
            </div>
            <div class="modal-footer">
                <a href="../auth/complete_profile.php" class="btn btn-primary">Lengkapi Profil</a>
                <button type="button" class="btn btn-secondary modal-close-btn">Tutup</button>
            </div>
        </div>
    </div>

    <script>
    // DOM Elements - Organized structure
    const elements = {
        fromInput: document.getElementById('from'),
        toInput: document.getElementById('to'),
        dateInput: document.getElementById('date'),
        resultArea: document.getElementById('result-area'),
        profileModal: document.getElementById('profileModal'),
        fromAutocompleteList: document.getElementById('from-autocomplete-list'),
        toAutocompleteList: document.getElementById('to-autocomplete-list')
    };
    
    // Configuration object
    const config = {
        profileComplete: <?= $profile_complete ? 'true' : 'false' ?>,
        debounceDelay: 300,
        autocompleteMinLength: 2,
        autocompleteDelay: 200
    };

    let fetchPOTimeout;

    // Modal management object
    const modal = {
        show: () => elements.profileModal.style.display = 'block',
        close: () => elements.profileModal.style.display = 'none'
    };

    // Optimized fetchPO function with better error handling
    function fetchPO() {
        clearTimeout(fetchPOTimeout);
        fetchPOTimeout = setTimeout(() => {
            const params = new URLSearchParams({
                from: elements.fromInput.value,
                to: elements.toInput.value,
                date: elements.dateInput.value
            });

            // Show a loading indicator
            elements.resultArea.innerHTML = '<div class="alert alert-info alert-full-width">Mencari bus...</div>';

            fetch(`po_list.php?${params}`)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.text();
                })
                .then(data => {
                    elements.resultArea.innerHTML = data;
                    // After loading data, re-check if the click handler needs to be attached
                    // The old handlePOSelection is removed because the resultArea is overwritten
                    // A better approach is event delegation, which is already in use.
                })
                .catch(error => {
                    console.error('Error fetching PO list:', error);
                    elements.resultArea.innerHTML = '<div class="alert alert-danger alert-full-width">Gagal memuat daftar bus. Periksa koneksi atau coba lagi.</div>';
                });
        }, config.debounceDelay);
    }

    // Autocomplete utilities object
    const autocomplete = {
        closeAll: (exceptElement) => {
            document.querySelectorAll('.autocomplete-list').forEach(list => {
                if (list !== exceptElement) {
                    list.innerHTML = '';
                    list.style.display = 'none';
                }
            });
        },

        addActive: (items, currentFocus) => {
            autocomplete.removeActive(items);
            if (currentFocus >= items.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = items.length - 1;
            if (items[currentFocus]) {
                items[currentFocus].classList.add('active');
                items[currentFocus].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
            return currentFocus;
        },

        removeActive: (items) => {
            Array.from(items).forEach(item => item.classList.remove('active'));
        }
    };

    // Optimized autocomplete setup function
    function setupAutocomplete(inputElement, listElement) {
        let currentFocus = -1;
        let fetchTimeout;

        inputElement.addEventListener('input', function() {
            const val = this.value;
            autocomplete.closeAll(listElement);
            listElement.innerHTML = '';

            if (!val || val.length < config.autocompleteMinLength) {
                fetchPO();
                return false;
            }

            currentFocus = -1;
            clearTimeout(fetchTimeout);
            
            fetchTimeout = setTimeout(() => {
                // HANYA GUNAKAN SATU PATH YANG PASTI BENAR
                // index_home.php ada di view/home/
                // api_saran_kota.php ada di root/api/
                // Jadi, path relatifnya adalah: ../../api/api_saran_kota.php
                const apiUrl = `../../api/api_saran_kota.php?query=${encodeURIComponent(val)}`; // PATH YANG BENAR

                fetch(apiUrl)
                    .then(response => {
                        if (!response.ok) {
                            // Tangani error HTTP status seperti 404, 500
                            throw new Error(`HTTP error! status: ${response.status} from ${apiUrl}`);
                        }
                        return response.json(); // Pastikan respons adalah JSON
                    })
                    .then(suggestions => {
                        if (!Array.isArray(suggestions) || suggestions.length === 0) {
                            console.log(`No suggestions or invalid data from: ${apiUrl}`);
                            return; // Tidak ada saran atau data tidak valid
                        }
                        
                        suggestions.forEach(suggestion => {
                            const item = document.createElement('div');
                            item.classList.add('autocomplete-list-item');
                            
                            const i = suggestion.toLowerCase().indexOf(val.toLowerCase());
                            if (i !== -1) {
                                item.innerHTML = suggestion.substring(0, i) +
                                                 "<strong>" + suggestion.substring(i, i + val.length) + "</strong>" +
                                                 suggestion.substring(i + val.length);
                            } else {
                                item.innerHTML = suggestion;
                            }
                            
                            item.innerHTML += "<input type='hidden' value='" + suggestion + "'>";
                            
                            item.addEventListener('click', function() {
                                inputElement.value = this.querySelector('input').value;
                                autocomplete.closeAll();
                                fetchPO();
                            });
                            
                            listElement.appendChild(item);
                        });
                        
                        // Show the dropdown if there are suggestions
                        if (suggestions.length > 0) {
                            listElement.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching autocomplete suggestions:', error);
                        listElement.innerHTML = `<div class="error-message">Error: ${error.message}. Cek console.</div>`;
                    });
            }, config.autocompleteDelay);
            
            fetchPO(); 
        });

        // Enhanced keyboard navigation
        inputElement.addEventListener('keydown', function(e) {
            const items = listElement.querySelectorAll('.autocomplete-list-item');
            
            if (items.length > 0) {
                switch(e.keyCode) {
                    case 40: // Arrow Down
                        currentFocus++;
                        currentFocus = autocomplete.addActive(items, currentFocus);
                        e.preventDefault();
                        break;
                    case 38: // Arrow Up
                        currentFocus--;
                        currentFocus = autocomplete.addActive(items, currentFocus);
                        e.preventDefault();
                        break;
                    case 13: // Enter
                        e.preventDefault();
                        if (currentFocus > -1 && items[currentFocus]) {
                            items[currentFocus].click();
                        } else {
                            fetchPO();
                            autocomplete.closeAll();
                        }
                        break;
                    case 27: // Escape
                        autocomplete.closeAll();
                        e.preventDefault();
                        break;
                }
            } else if (e.keyCode === 13) {
                fetchPO();
            }
        });
    }

    // This function now correctly handles clicks on dynamically loaded content
    // by attaching the listener to a static parent (`resultArea`).
    function setupPOSelectionListener() {
        elements.resultArea.addEventListener('click', function(e) {
            const poCard = e.target.closest('.po-card');
            if (poCard) { // Check if a card was clicked
                if (!config.profileComplete) {
                    modal.show(); // Show modal if profile is not complete
                } else {
                    // The URL is now part of the element's onclick attribute,
                    // which was set in po_list.php.
                    // The click event will trigger that `onclick` handler automatically.
                    // No need for `window.location.href` here if `onclick` is present.
                }
            }
        });
    }

    // Enhanced event listeners setup
    function setupEventListeners() {
        // Modal events
        const closeBtn = elements.profileModal.querySelector('.close');
        if (closeBtn) {
            closeBtn.onclick = modal.close;
        }
        
        window.onclick = (event) => {
            if (event.target === elements.profileModal) modal.close();
        };
        
        // Global click handler for autocomplete
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.autocomplete-container')) {
                autocomplete.closeAll();
            }
        });

        // Date input change
        elements.dateInput.addEventListener('change', fetchPO);
    }

    // Initialize application
    function initializeApp() {
        // Setup autocomplete
        setupAutocomplete(elements.fromInput, elements.fromAutocompleteList);
        setupAutocomplete(elements.toInput, elements.toAutocompleteList);
        
        // Setup event handlers
        setupEventListeners();
        setupPOSelectionListener(); // Attach PO card click listener to the parent container
        
        // Load initial data
        fetchPO();
        
        // Add event listeners for buttons that replaced inline handlers
        const searchBtn = document.querySelector('.search-bus-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', fetchPO);
        }
        
        const modalCloseBtn = document.querySelector('.modal-close-btn');
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener('click', () => modal.close());
        }
        
        // Add click handlers for po-cards (replacing inline onclick)
        document.addEventListener('click', (e) => {
            const poCard = e.target.closest('.po-card[data-booking-url]');
            if (poCard) {
                const url = poCard.getAttribute('data-booking-url');
                if (url) {
                    window.location.href = url;
                }
            }
        });
    }

    // Start application when DOM is ready
    document.addEventListener('DOMContentLoaded', initializeApp);
</script>
</body>
</html>