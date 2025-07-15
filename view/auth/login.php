<?php
session_start(); // Pastikan session dimulai di sini juga
$errors = $_SESSION['login_errors'] ?? []; // Ambil error dari session, jika ada
$old_email = $_SESSION['old_login_email'] ?? ''; // Ambil email lama, jika ada
$success_message = $_SESSION['success_message'] ?? ''; // Ambil pesan sukses dari session (dari AuthController)

// Hapus error, old_input, dan success_message dari session setelah diambil
unset($_SESSION['login_errors']);
unset($_SESSION['old_login_email']);
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BeBuss</title>
    <link rel="icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header">
                <a href="../../index.php" class="brand">
                    <img src="../../assets/images/logo/logo.png" alt="BeBuss Logo" class="auth-logo">
                    <span class="brand-text">BeBuss</span>
                </a>
                <h1>Selamat Datang Kembali</h1>
                <p>Masuk untuk melanjutkan perjalanan Anda.</p>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <span class="alert-icon">⚠️</span>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="../../controller/LoginController.php">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($old_email) ?>" required placeholder="contoh@email.com">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Masukkan password Anda">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </form>

            <div class="auth-footer">
                <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
            </div>
        </div>
    </div>
</body>
</html>
