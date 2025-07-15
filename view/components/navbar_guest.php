<?php
// view/components/navbar_guest.php

// Helper function to generate base URL
function getBaseUrlGuest() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . '/Proyek%20PBW/BeBuss';
}

$base_url = getBaseUrlGuest();
?>
<nav class="navbar">
    <div class="container">
        <div class="navbar-brand">
            <a href="<?= $base_url ?>/index.php" class="navbar-brand-link">
                <img src="<?= $base_url ?>/assets/images/logo/logo.png" alt="BeBuss Logo" class="navbar-logo">
                <span class="brand-text">BeBuss</span>
            </a>
        </div>
        <div class="navbar-menu">
            <a href="<?= $base_url ?>/view/auth/login.php" class="btn btn-secondary">Login</a>
            <a href="<?= $base_url ?>/view/auth/register.php" class="btn btn-primary">Register</a>
        </div>
    </div>
</nav>
