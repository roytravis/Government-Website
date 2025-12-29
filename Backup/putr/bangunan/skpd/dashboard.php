<?php
session_start();

// Validasi sesi dan peran pengguna
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/login");
    exit;
}

require "../includes/koneksi.php";
$user_id = $_SESSION['user_id'];

// Ambil data pengguna yang sedang login
$sql = "SELECT * FROM bg_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>

<?php include "../includes/header.php"; ?>
<div class="container">
    <h2 style="margin-bottom:18px;">Dashboard User Perangkat Daerah</h2>
    <div style="margin-bottom:24px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
        Selamat datang, <b><?= htmlspecialchars($user['nama_lengkap']) ?></b>
        <br>
        <span style="color:#3c4250;">Jabatan: <?= htmlspecialchars($user['jabatan']) ?></span>
        <br>
        <span style="color:#3c4250;">Perangkat Daerah: <?= htmlspecialchars($user['nama_instansi']) ?></span>
    </div>

    <!-- Tombol aksi utama dan tombol logout -->
    <div style="display:flex; align-items:center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <!-- Grup tombol aksi utama -->
        <div style="display:flex; gap: 10px;">
            <!-- Perubahan: Menggunakan path absolut untuk tautan -->
            <a href="/putr/bangunan/skpd/input_bangunan" class="btn btn-primary">Input Bangunan Baru</a>
            <a href="/putr/bangunan/skpd/daftar_bangunan" class="btn btn-secondary">Lihat Daftar Bangunan</a>
        </div>
        <!-- Tombol Logout -->
        <a href="/putr/bangunan/skpd/logout" class="btn btn-danger">Logout</a>
    </div>
</div>
