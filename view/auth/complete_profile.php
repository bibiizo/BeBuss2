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

// Hapus semua pesan dari session setelah diambil
unset($_SESSION['profile_errors']);
unset($_SESSION['old_profile_input']);
unset($_SESSION['profile_success_message']);


// Gunakan data dari $old_profile_input jika ada error, jika tidak, gunakan data dari database
$display_nama_lengkap = $old_profile_input['nama_lengkap'] ?? ($user['nama_lengkap'] ?? '');
$display_no_hp = $old_profile_input['no_hp'] ?? ($user['no_hp'] ?? '');
$display_jenis_kelamin = $old_profile_input['jenis_kelamin'] ?? ($user['jenis_kelamin'] ?? '');

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - BeBuss</title>
    <link rel="icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <main class="container profile-container">
        
        <!-- Profile Information Card -->
        <div class="profile-card">
            <div class="profile-card-header">
                <h3>Informasi Profil</h3>
                <p>Perbarui informasi profil dan alamat email akun Anda.</p>
            </div>
            
            <?php if (!empty($profile_success_message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($profile_success_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($profile_errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($profile_errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="../../controller/ProfileController.php">
                <input type="hidden" name="action" value="update_profile_info">
                
                <div class="form-group">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" value="<?= htmlspecialchars($display_nama_lengkap) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email_display" class="form-label">Email</label>
                    <input type="email" id="email_display" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="no_hp" class="form-label">No. Handphone</label>
                    <input type="text" name="no_hp" id="no_hp" class="form-control" value="<?= htmlspecialchars($display_no_hp) ?>" required>
                </div>

                <div class="form-group">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" class="form-control">
                        <option value="" <?= ($display_jenis_kelamin == '') ? 'selected' : '' ?>>Pilih Jenis Kelamin</option>
                        <option value="L" <?= ($display_jenis_kelamin == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($display_jenis_kelamin == 'P') ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-button-group">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>