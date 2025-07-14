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
<html>
<head>
    <title>Login - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .error-message {
            color: #dc3545; /* Merah untuk pesan error */
            font-size: 0.9em;
            margin-top: 5px;
            margin-bottom: 10px;
            list-style-type: none; /* Hapus bullet point */
            padding-left: 0;
        }
        .error-message li {
            margin-bottom: 5px;
        }
        .success-message {
            color: #28a745; /* Hijau untuk pesan sukses */
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
    <div class="container">
        <h2>Login BeBuss</h2>

        <?php if (!empty($success_message)): // Tampilkan pesan sukses jika ada ?>
            <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): // Tampilkan pesan error jika ada ?>
            <ul class="error-message">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="../../controller/LoginController.php">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($old_email) ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="btn">Login</button>
        </form>
        <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </div>
</body>
</html>
