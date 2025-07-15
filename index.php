<?php
// BeBuss Authentication Gate
// Redirect users to appropriate pages based on their login status
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: view/home/index_home.php');
    exit;
}

// For new visitors, redirect to registration page
header('Location: view/auth/register.php');
exit;
?>
