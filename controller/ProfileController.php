<?php
session_start();
require_once '../config/database.php';
require_once '../config/Config.php';
require_once '../model/User.php';

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../view/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/auth/complete_profile.php');
    exit;
}

// Basic rate limiting for profile updates
$rate_limit_key = 'profile_update_' . $_SESSION['user_id'];
$now = time();
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}

// Clean old attempts (older than 5 minutes)
$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($now) {
    return ($now - $timestamp) < 300;
});

// Check if max attempts exceeded (10 attempts per 5 minutes)
if (count($_SESSION[$rate_limit_key]) >= 10) {
    $_SESSION['error_message'] = "Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.";
    header('Location: ../view/auth/complete_profile.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$userModel = new User($pdo);

// Basic input sanitization function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$action = sanitizeInput($_POST['action'] ?? '');

require_once '../config/database.php';

// Helper functions for validation
function validateProfileInput($nama, $no_hp, $jk) {
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = Config::getMessage('name_empty');
    } elseif (strlen($nama) < Config::NAME_MIN_LENGTH) {
        $errors[] = Config::getMessage('name_min_length');
    }

    if (empty($no_hp)) {
        $errors[] = Config::getMessage('phone_empty');
    } elseif (!ctype_digit($no_hp)) {
        $errors[] = Config::getMessage('phone_invalid');
    } elseif (strlen($no_hp) < Config::PHONE_MIN_LENGTH || strlen($no_hp) > Config::PHONE_MAX_LENGTH) {
        $errors[] = Config::getMessage('phone_length');
    }

    if (!in_array($jk, ['', 'L', 'P'])) {
        $errors[] = Config::getMessage('gender_invalid');
    }

    return $errors;
}

function validatePasswordInput($new_password, $confirm_password) {
    $errors = [];
    
    if (empty($new_password)) {
        $errors[] = Config::getMessage('password_empty');
    } elseif (strlen($new_password) < Config::PASSWORD_MIN_LENGTH) {
        $errors[] = Config::getMessage('password_min_length');
    }

    if ($new_password !== $confirm_password) {
        $errors[] = Config::getMessage('password_mismatch');
    }

    return $errors;
}

function redirectWithErrors($errors, $input_data = [], $type = 'profile') {
    $_SESSION["{$type}_errors"] = $errors;
    if (!empty($input_data)) {
        $_SESSION["old_{$type}_input"] = $input_data;
    }
    header('Location: ../view/auth/complete_profile.php');
    exit;
}

function redirectWithSuccess($message, $type = 'profile') {
    $_SESSION["{$type}_success_message"] = $message;
    header('Location: ../view/auth/complete_profile.php');
    exit;
}

// Process actions
try {
    switch ($action) {
        case 'update_profile_info':
            // Add to rate limit tracking
            $_SESSION[$rate_limit_key][] = $now;
            
            $nama = sanitizeInput($_POST['nama_lengkap'] ?? '');
            $no_hp = sanitizeInput($_POST['no_hp'] ?? '');
            $jk = sanitizeInput($_POST['jenis_kelamin'] ?? '');

            $profile_errors = validateProfileInput($nama, $no_hp, $jk);

            if (!empty($profile_errors)) {
                redirectWithErrors($profile_errors, [
                    'nama_lengkap' => $nama,
                    'no_hp' => $no_hp,
                    'jenis_kelamin' => $jk
                ]);
            }

            $success = $userModel->updateProfile($user_id, $nama, $no_hp, $jk);

            if ($success) {
                // Update session data if needed
                $updatedUser = $userModel->findById($user_id);
                if ($updatedUser) {
                    $_SESSION['email'] = $updatedUser['email'];
                }
                redirectWithSuccess("Data profil berhasil diperbarui.");
            } else {
                redirectWithErrors(["Gagal memperbarui profil. Terjadi kesalahan internal."], [
                    'nama_lengkap' => $nama,
                    'no_hp' => $no_hp,
                    'jenis_kelamin' => $jk
                ]);
            }
            break;

        case 'update_password':
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            $password_errors = validatePasswordInput($new_password, $confirm_password);

            if (!empty($password_errors)) {
                redirectWithErrors($password_errors, [], 'password');
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $success = $userModel->updatePassword($user_id, $hashed_password);

            if ($success) {
                redirectWithSuccess("Password berhasil diperbarui.", 'password');
            } else {
                redirectWithErrors(["Gagal memperbarui password. Terjadi kesalahan internal."], [], 'password');
            }
            break;

        default:
            header('Location: ../view/auth/complete_profile.php');
            exit;
    }
} catch (Exception $e) {
    error_log("ProfileController error: " . $e->getMessage());
    $_SESSION['error_message'] = "Terjadi kesalahan sistem. Silakan coba lagi.";
    header('Location: ../view/auth/complete_profile.php');
    exit;
}
?>
