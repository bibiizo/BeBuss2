<?php
session_start(); // Pastikan session dimulai di awal
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? ''); // Gunakan null coalescing operator untuk menghindari undefined index
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $errors = []; // Array untuk menyimpan pesan error

    // --- Validasi Input ---

    // Validasi Email
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    // Validasi Password
    if (empty($password)) {
        $errors[] = "Password tidak boleh kosong.";
    } elseif (strlen($password) < 8) { // Contoh: minimal 8 karakter
        $errors[] = "Password minimal 8 karakter.";
    }

    // Validasi Konfirmasi Password
    if ($password !== $confirm) {
        $errors[] = "Konfirmasi password tidak cocok.";
    }

    // Jika ada error validasi dasar, simpan ke session dan redirect
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = ['email' => $email]; // Simpan input lama agar tidak perlu mengetik ulang
        header('Location: ../view/auth/register.php');
        exit;
    }

    // Inisialisasi User Model
    $userModel = new User($pdo);

    // Cek apakah email sudah terdaftar
    $existing = $userModel->findByEmail($email);
    if ($existing) {
        $errors[] = "Email sudah terdaftar. Silakan gunakan email lain atau login.";
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = ['email' => $email];
        header('Location: ../view/auth/register.php');
        exit;
    }

    // Jika semua validasi lolos, lanjutkan proses registrasi
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $success = $userModel->create($email, $hashed);

    if ($success) {
        // Set pesan sukses ke session
        $_SESSION['success_message'] = "Registrasi berhasil! Silakan login.";
        header('Location: ../view/auth/login.php');
        exit;
    } else {
        // Jika ada masalah saat menyimpan ke database
        $errors[] = "Registrasi gagal. Terjadi kesalahan internal.";
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = ['email' => $email];
        header('Location: ../view/auth/register.php');
        exit;
    }
} else {
    // Jika akses langsung ke controller tanpa metode POST
    header('Location: ../view/auth/register.php');
    exit;
}
?>