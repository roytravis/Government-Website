<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    header("Location: ../login.php");
    exit;
}

require "../includes/koneksi.php";
$user_id = $_SESSION['user_id'];

// Ambil data user login
$sql = "SELECT * FROM bg_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<?php include "../includes/header.php"; ?>
<div class="container">
    <h2 style="margin-bottom:18px;">Dashboard SKPD</h2>
    <div style="margin-bottom:24px;">
        Selamat datang, <b><?= htmlspecialchars($user['nama_lengkap']) ?></b>
        <br>
        <span style="color:#3c4250;">Jabatan: <?= htmlspecialchars($user['jabatan']) ?></span>
        <br>
        <span style="color:#3c4250;">SKPD: <?= htmlspecialchars($user['nama_instansi']) ?></span>
    </div>
    <div style="display:flex; align-items:center; margin-bottom:32px;">
        <div>
            <a href="input_bangunan.php" class="btn btn-primary">Input Data Bangunan</a>
            <a href="daftar_bangunan.php" class="btn btn-secondary" style="margin-left:12px;">Daftar Bangunan</a>
        </div>
        <div style="flex:1"></div>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <!-- Area data/statistik, status, dll bisa dikembangkan di sini -->
</div>