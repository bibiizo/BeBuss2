<?php
session_start();
require_once '../../config/database.php';
require_once '../../model/User.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userModel = new User($pdo);
$user = $userModel->findById($_SESSION['user_id']);

// Ambil error dan old_input dari session untuk update profil
$profile_errors = $_SESSION['profile_errors'] ?? [];
$old_profile_input = $_SESSION['old_profile_input'] ?? [];
$profile_success_message = $_SESSION['profile_success_message'] ?? ''; // Untuk pesan sukses profil

// Ambil error dan old_input dari session untuk update password
$password_errors = $_SESSION['password_errors'] ?? [];
$password_success_message = $_SESSION['password_success_message'] ?? ''; // Untuk pesan sukses password

// Hapus semua pesan dari session setelah diambil
unset($_SESSION['profile_errors']);
unset($_SESSION['old_profile_input']);
unset($_SESSION['profile_success_message']);
unset($_SESSION['password_errors']);
unset($_SESSION['password_success_message']);


// Gunakan data dari $old_profile_input jika ada error, jika tidak, gunakan data dari database
$display_nama_lengkap = $old_profile_input['nama_lengkap'] ?? ($user['nama_lengkap'] ?? '');
$display_no_hp = $old_profile_input['no_hp'] ?? ($user['no_hp'] ?? '');
$display_jenis_kelamin = $old_profile_input['jenis_kelamin'] ?? ($user['jenis_kelamin'] ?? '');

// Pastikan navbar di-include juga di sini
include '../components/navbar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Pengguna - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background-color: #2c2c2c; /* Background gelap sesuai gambar */
            color: #eee; /* Warna teks terang */
            font-family: 'Segoe UI', sans-serif;
            margin-top: 0; /* Navbar sudah ada, jadi margin-top tidak perlu tinggi */
            text-align: left; /* Default text-align */
        }
        .container { /* Sesuaikan container agar lebih lebar dan tidak terlalu senter */
            width: 90%;
            max-width: 1000px;
            margin: 20px auto;
            background: #3a3a3a; /* Warna background container */
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex; /* Gunakan flexbox untuk layout 2 kolom */
            flex-wrap: wrap; /* Agar bisa wrap ke bawah di layar kecil */
            gap: 30px; /* Jarak antar kolom */
        }
        h2 {
            text-align: center;
            font-size: 2em;
            margin-bottom: 30px;
            color: #eee;
            width: 100%; /* Agar H2 tetap di atas dan mengambil seluruh lebar */
        }
        .profile-section {
            flex: 1; /* Agar kolom mengambil lebar yang tersedia */
            min-width: 300px; /* Lebar minimum sebelum wrap */
            background-color: #4a4a4a; /* Warna background section */
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .profile-section h3 {
            font-size: 1.5em;
            margin-top: 0;
            margin-bottom: 20px;
            color: #fff;
        }
        .profile-section p {
            font-size: 1em;
            margin-bottom: 20px;
            color: #ccc;
        }
        form label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #ccc;
        }
        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #555;
            border-radius: 5px;
            box-sizing: border-box;
            background-color: #333; /* Warna input gelap */
            color: #eee; /* Warna teks input */
            font-size: 1em;
        }
        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form input[type="password"]:focus,
        form select:focus {
            outline: none;
            border-color: #007bff; /* Border fokus warna biru */
            box-shadow: 0 0 5px rgba(0,123,255,0.5);
        }
        form input[readonly] {
            background-color: #555;
            cursor: not-allowed;
            opacity: 0.8;
        }
        .btn {
            display: block; /* Agar tombol mengambil seluruh lebar */
            width: 100%;
            padding: 12px 25px;
            margin-top: 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 10px;
            list-style-type: none;
            padding-left: 0;
        }
        .error-message li {
            margin-bottom: 5px;
        }
        .success-message {
            color: #28a745;
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 10px;
            text-align: center;
            background-color: #e6ffe6;
            border: 1px solid #28a745;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php // Navbar sudah di-include di atas ?>
    <div class="container">
        <h2>Profil Pengguna</h2>

        <?php if (!empty($profile_success_message)): ?>
            <p class="success-message" style="width: 100%;"><?= htmlspecialchars($profile_success_message) ?></p>
        <?php endif; ?>
        <?php if (!empty($profile_errors)): ?>
            <ul class="error-message" style="width: 100%;">
                <?php foreach ($profile_errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <div class="profile-section">
            <h3>Profile Information</h3>
            <p>Update your account's profile information.</p>
            <form method="POST" action="../../controller/ProfileController.php">
                <input type="hidden" name="action" value="update_profile_info"> <label for="nama_lengkap">Name</label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" value="<?= htmlspecialchars($display_nama_lengkap) ?>" required>

                <label for="email_display">Email</label>
                <input type="email" id="email_display" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>

                <label for="no_hp">No. Handphone</label>
                <input type="text" name="no_hp" id="no_hp" value="<?= htmlspecialchars($display_no_hp) ?>" required>

                <label for="jenis_kelamin">Jenis Kelamin</label>
                <select name="jenis_kelamin" id="jenis_kelamin">
                    <option value="" <?= ($display_jenis_kelamin == '') ? 'selected' : '' ?>>Pilih (opsional)</option>
                    <option value="L" <?= ($display_jenis_kelamin == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= ($display_jenis_kelamin == 'P') ? 'selected' : '' ?>>Perempuan</option>
                </select>

                <button type="submit" class="btn">Save</button>
            </form>
        </div>

        <?php if (!empty($password_success_message)): ?>
            <p class="success-message" style="width: 100%;"><?= htmlspecialchars($password_success_message) ?></p>
        <?php endif; ?>
        <?php if (!empty($password_errors)): ?>
            <ul class="error-message" style="width: 100%;">
                <?php foreach ($password_errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="profile-section">
            <h3>Update Password</h3>
            <p>Ensure your account is using a long, random password to stay secure.</p>
            <form method="POST" action="../../controller/ProfileController.php">
                <input type="hidden" name="action" value="update_password"> <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <button type="submit" class="btn">Save</button>
            </form>
        </div>
    </div>
</body>
</html>