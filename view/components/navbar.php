<?php
// Optimized session and initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/User.php';

// Helper function to generate base URL
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . '/Proyek%20PBW/BeBuss';
}

// Helper function to get user display data
function getUserDisplayData($pdo) {
    $data = [
        'name' => 'Guest',
        'email' => '',
        'user' => null
    ];
    
    if (!isset($_SESSION['user_id'])) {
        return $data;
    }
    
    try {
        $userModel = new User($pdo);
        $user = $userModel->findById($_SESSION['user_id']);
        
        if ($user) {
            $data['name'] = htmlspecialchars($user['nama_lengkap'] ?? $user['email']);
            $data['email'] = htmlspecialchars($user['email']);
            $data['user'] = $user;
        } else {
            // Fallback for invalid session
            $data['name'] = htmlspecialchars($_SESSION['email'] ?? 'Pengguna');
            $data['email'] = htmlspecialchars($_SESSION['email'] ?? '');
        }
    } catch (Exception $e) {
        error_log("Error getting user data: " . $e->getMessage());
    }
    
    return $data;
}

// Initialize variables
$current_page_basename = basename($_SERVER['PHP_SELF']);
$base_url = getBaseUrl();
$user_data = getUserDisplayData($pdo);
$user_profile_link = $base_url . '/view/auth/complete_profile.php';
?>

<style>
.navbar {
    background-color: #e7e9ec;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between; /* Untuk meletakkan elemen profil di kanan */
    align-items: center;
    gap: 20px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.navbar > div { /* Wrapper untuk link Home dan Riwayat */
    display: flex;
    gap: 20px;
}
.navbar a {
    text-decoration: none;
    color: #172532;
    padding: 8px 16px;
    border-radius: 8px;
}
.navbar a.active {
    background-color: #287fd7;
    color: white;
}

/* Styling untuk Profile Menu */
.profile-menu-container {
    position: relative;
    cursor: pointer;
    margin-left: auto; /* Mendorong ke kanan */
}
.profile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #ccc; /* Placeholder warna abu-abu */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    color: #fff;
    overflow: hidden; /* Pastikan gambar profil tidak keluar dari batas */
    user-select: none; /* Mencegah teks terpilih saat klik */
}
.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
/* Untuk avatar default (ikon orang) */
.profile-avatar .default-icon {
    font-size: 2em; /* Sesuaikan ukuran ikon */
    line-height: 1; /* Pastikan vertikal center */
    color: #555; /* Warna ikon default */
}

.profile-dropdown {
    display: none; /* Sembunyikan secara default */
    position: absolute;
    background-color: #fff;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 100;
    right: 0; /* Posisikan ke kanan */
    min-width: 200px;
    border-radius: 8px;
    overflow: hidden; /* Pastikan radius diterapkan */
    top: 50px; /* Jarak dari avatar */
    font-weight: normal; /* Override font-weight bold dari navbar */
}
.profile-dropdown.show {
    display: block; /* Tampilkan jika ada class 'show' */
}

.profile-info {
    padding: 15px;
    border-bottom: 1px solid #eee;
    text-align: center;
}
.profile-info .avatar-in-dropdown { /* Avatar di dalam dropdown */
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #ddd;
    display: inline-flex; /* Agar bisa diatur margin */
    align-items: center;
    justify-content: center;
    font-size: 2em;
    color: #555;
    margin-bottom: 10px;
    overflow: hidden;
}
.profile-info .avatar-in-dropdown img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-info strong {
    display: block;
    font-size: 1.1em;
    margin-bottom: 5px;
    color: #333;
}
.profile-info small {
    display: block;
    color: #666;
    font-size: 0.9em;
}

.profile-dropdown a {
    color: #172532;
    padding: 12px 15px;
    text-decoration: none;
    display: block;
    text-align: left;
    font-weight: normal; /* Hapus font-weight bold bawaan navbar */
    border-radius: 0; /* Hapus radius bawaan navbar a */
}
.profile-dropdown a:hover {
    background-color: #f0f0f0;
}
</style>

<div class="navbar">
    <div>
        <a href="<?= $base_url ?>/view/home/index_home.php" class="<?= $current_page_basename == 'index_home.php' && strpos($_SERVER['REQUEST_URI'], '/home/') !== false ? 'active' : '' ?>">Home</a>
        <a href="<?= $base_url ?>/view/history/history_index.php" class="<?= $current_page_basename == 'history_index.php' && strpos($_SERVER['REQUEST_URI'], '/history/') !== false ? 'active' : '' ?>">Riwayat</a>
    </div>

    <div class="profile-menu-container" id="profileMenu">
        <div class="profile-avatar">
            <?php if (isset($user_data['user']['profile_picture']) && !empty($user_data['user']['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($user_data['user']['profile_picture']) ?>" alt="Profil">
            <?php else: ?>
                <span class="default-icon">👤</span> 
            <?php endif; ?>
        </div>
        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-info">
                <div class="avatar-in-dropdown">
                    <?php if (isset($user_data['user']['profile_picture']) && !empty($user_data['user']['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($user_data['user']['profile_picture']) ?>" alt="Profil">
                    <?php else: ?>
                        <span class="default-icon">👤</span>
                    <?php endif; ?>
                </div>
                <strong><?= $user_data['name'] ?></strong>
                <small>@<?= $user_data['email'] ?></small>
            </div>
            <a href="<?= $user_profile_link ?>">Profile</a>
            <a href="<?= $base_url ?>/logout.php">Logout</a>
        </div>
    </div>
</div>

<script>
    const profileMenu = document.getElementById('profileMenu');
    const profileDropdown = document.getElementById('profileDropdown');

    profileMenu.addEventListener('click', function(event) {
        profileDropdown.classList.toggle('show'); // Toggle class 'show'
        event.stopPropagation(); // Mencegah event click menyebar ke document
    });

    // Tutup dropdown jika klik di luar area dropdown
    document.addEventListener('click', function(event) {
        if (!profileMenu.contains(event.target)) {
            profileDropdown.classList.remove('show');
        }
    });
</script>