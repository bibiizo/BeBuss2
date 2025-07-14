<?php
// Mulai session untuk akses login nanti
session_start();

// Jika user sudah login, arahkan ke halaman utama (index_home)
if (isset($_SESSION['user_id'])) {
    header('Location: view/home/index_home.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BeBuss - Selamat Datang</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Selamat Datang di BeBuss</h1>
        <p>Pemesanan tiket bus jadi lebih mudah dan praktis!</p>
        <a href="view/auth/login.php" class="btn">Login</a>
        <a href="view/auth/register.php" class="btn">Daftar</a>
    </div>
</body>
</html>
