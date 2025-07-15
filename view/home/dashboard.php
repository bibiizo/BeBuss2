<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - BeBuss</title>
    <link rel="stylesheet" href="../../assets/css/style-simple.css">
</head>
<body>
    <div class="container">
        <h2>Halo, <?php echo $_SESSION['email']; ?>!</h2>
        <p>Selamat datang di Dashboard BeBuss</p>
        <a href="../../logout.php" class="btn">Logout</a>
        <a href="index_home_simple.php" class="btn">Cari Tiket</a>
    </div>
</body>
</html>
