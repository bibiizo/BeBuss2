<?php
session_start(); // Pastikan session dimulai di sini juga
$errors = $_SESSION['errors'] ?? []; // Ambil error dari session, jika ada
$old_input = $_SESSION['old_input'] ?? ['email' => '']; // Ambil input lama, jika ada

// Hapus error dan old_input dari session setelah diambil agar tidak muncul lagi saat refresh
unset($_SESSION['errors']);
unset($_SESSION['old_input']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - BeBuss</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Daftar Akun BeBuss</h2>

        <?php if (!empty($errors)): // Tampilkan pesan error jika ada ?>
            <ul class="error-message">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="../../controller/AuthController.php">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($old_input['email']) ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm">Ulangi Password</label>
            <input type="password" name="confirm" id="confirm" required>

            <button type="submit" class="btn">Daftar</button>
        </form>
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>
