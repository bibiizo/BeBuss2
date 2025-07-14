<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/User.php';

// Helper function to handle redirect with errors
function redirectWithErrors($errors, $email = '') {
    $_SESSION['login_errors'] = $errors;
    if (!empty($email)) {
        $_SESSION['old_login_email'] = $email;
    }
    header('Location: ../view/auth/login.php');
    exit;
}

// Helper function to validate login input
function validateLoginInput($email, $password) {
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    
    if (empty($password)) {
        $errors[] = "Password tidak boleh kosong.";
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    $errors = validateLoginInput($email, $password);
    if (!empty($errors)) {
        redirectWithErrors($errors, $email);
    }

    try {
        $userModel = new User($pdo);
        $user = $userModel->findByEmail($email);

        // Verify credentials
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            
            // Direct redirect to home
            header('Location: ../view/home/index_home.php');
            exit;
        } else {
            redirectWithErrors(["Email atau password salah."], $email);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        redirectWithErrors(["Terjadi kesalahan sistem. Silakan coba lagi."], $email);
    }
} else {
    // Direct access redirect
    header('Location: ../view/auth/login.php');
    exit;
}
?>