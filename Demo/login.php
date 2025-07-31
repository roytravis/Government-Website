<?php
session_start();
require "includes/koneksi.php";

// Proses login jika form dikirim
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = trim($_POST["nip"]);
    $password = $_POST["password"];

    // Cari user sesuai nip
    $sql = "SELECT * FROM bg_users WHERE nip = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nip);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Cek user & password
    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nama"] = $user["nama_lengkap"];
        $_SESSION["role"] = $user["role"];
        // Redirect ke dashboard sesuai role
        if ($user["role"] == "admin") {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: skpd/dashboard.php");
        }
        exit;
    } else {
        $error = "NIP atau Password salah!";
    }
}
?>

<?php include "includes/header.php"; ?>
<head>
  <meta charset="utf-8">
  <title>Pelaporan Bangunan Gedung Pemerintah</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<div class="container login-container">
    <h2 style="text-align:center; margin-bottom:24px;">Login Sistem Pelaporan Bangunan Gedung</h2>
    <?php if($error): ?>
    <div style="color:red; text-align:center; margin-bottom:12px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label for="nip">NIP</label>
            <input type="text" name="nip" id="nip" class="form-control" required autofocus>
        </div>
        <div class="form-group" style="margin-top:16px;">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div style="margin-top:24px; text-align:center;">
            <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
        </div>
    </form>
</div>