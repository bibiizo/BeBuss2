<?php
session_start(); // Pastikan session dimulai di sini juga
$errors = $_SESSION['errors'] ?? []; // Ambil error dari session, jika ada
$old_input = $_SESSION['old_input'] ?? ['email' => '']; // Ambil input lama, jika ada

// Hapus error dan old_input dari session setelah diambil agar tidak muncul lagi saat refresh
unset($_SESSION['errors']);
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - BeBuss</title>
    <link rel="icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="../../assets/images/logo/favicon.ico">
    <link rel="stylesheet" href="../../assets/css/modern.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card">
            <div class="auth-header">
                <a href="../../index.php" class="brand">
                    <span class="brand-text">BeBuss</span>
                </a>
                <h1>Buat Akun Baru</h1>
            </div>

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

            <form method="POST" action="../../controller/AuthController.php">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($old_input['email'] ?? '') ?>" required placeholder="contoh@email.com">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Minimal 8 karakter">
                </div>

                <div class="form-group">
                    <label for="confirm" class="form-label">Ulangi Password</label>
                    <input type="password" name="confirm" id="confirm" class="form-control" required placeholder="Ketik ulang password Anda">
                </div>

                <button type="submit" class="btn btn-primary btn-full">Daftar</button>
            </form>

            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>
