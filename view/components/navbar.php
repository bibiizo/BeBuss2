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
        'user' => null,
        'initial' => 'G'
    ];
    
    if (!isset($_SESSION['user_id'])) {
        return $data;
    }
    
    try {
        $userModel = new User($pdo);
        $user = $userModel->findById($_SESSION['user_id']);
        
        if ($user) {
            $name = $user['nama_lengkap'] ?? $user['email'];
            $data['name'] = htmlspecialchars($name);
            $data['email'] = htmlspecialchars($user['email']);
            $data['user'] = $user;
            $data['initial'] = strtoupper(substr($name, 0, 1));
        } else {
            // Fallback for invalid session
            $email = $_SESSION['email'] ?? 'Pengguna';
            $data['name'] = htmlspecialchars($email);
            $data['email'] = htmlspecialchars($email);
            $data['initial'] = strtoupper(substr($email, 0, 1));
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
$history_link = $base_url . '/view/history/history_index.php';
$home_link = $base_url . '/view/home/index_home.php';
?>

<nav class="navbar">
    <div class="navbar-container">
        <a href="<?= $home_link ?>" class="navbar-brand">
            <span class="brand-text">BeBuss</span>
        </a>

        <!-- Hamburger Menu Button (Mobile Only) -->
        <button class="navbar-toggle" id="navbarToggle" type="button">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <!-- Desktop Navigation -->
        <div class="navbar-links desktop-nav">
            <a href="<?= $home_link ?>" class="nav-link <?= ($current_page_basename == 'index_home.php') ? 'active' : '' ?>">Home</a>
            <a href="<?= $history_link ?>" class="nav-link <?= ($current_page_basename == 'history_index.php') ? 'active' : '' ?>">Riwayat</a>
        </div>

        <!-- Desktop Profile Dropdown -->
        <div class="profile-dropdown desktop-nav" id="profileDropdown">
            <button class="profile-toggle" type="button">
                <div class="profile-avatar">
                    <?= $user_data['initial'] ?>
                </div>
                <span class="profile-name"><?= $user_data['name'] ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <strong><?= $user_data['name'] ?></strong>
                    <div class="email"><?= $user_data['email'] ?></div>
                </div>
                <a href="<?= $user_profile_link ?>" class="dropdown-item">Profil Saya</a>
                <div class="dropdown-divider"></div>
                <a href="<?= $base_url ?>/logout.php" class="dropdown-item">Logout</a>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <div class="mobile-profile">
                    <div class="profile-avatar">
                        <?= $user_data['initial'] ?>
                    </div>
                    <div class="profile-info">
                        <strong><?= $user_data['name'] ?></strong>
                        <div class="email"><?= $user_data['email'] ?></div>
                    </div>
                </div>
            </div>
            <div class="mobile-menu-content">
                <a href="<?= $home_link ?>" class="mobile-nav-link <?= ($current_page_basename == 'index_home.php') ? 'active' : '' ?>">
                    Home
                </a>
                <a href="<?= $history_link ?>" class="mobile-nav-link <?= ($current_page_basename == 'history_index.php') ? 'active' : '' ?>">
                    Riwayat
                </a>
                <a href="<?= $user_profile_link ?>" class="mobile-nav-link">
                    Profil Saya
                </a>
                <a href="<?= $base_url ?>/logout.php" class="mobile-nav-link logout">
                    Logout
                </a>
            </div>
        </div>

        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileDropdown) {
        const toggle = profileDropdown.querySelector('.profile-toggle');
        const menu = profileDropdown.querySelector('.dropdown-menu');

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });
    }

    // Hamburger menu functionality
    const navbarToggle = document.getElementById('navbarToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    
    if (navbarToggle && mobileMenu && mobileMenuOverlay) {
        function toggleMobileMenu() {
            navbarToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            mobileMenuOverlay.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        navbarToggle.addEventListener('click', toggleMobileMenu);
        mobileMenuOverlay.addEventListener('click', toggleMobileMenu);
        
        // Close mobile menu when clicking on nav links
        const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (!link.classList.contains('logout')) {
                    toggleMobileMenu();
                }
            });
        });
        
        // Close mobile menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navbarToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
});
</script>   