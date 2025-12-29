<?php
session_start();
// Memastikan hanya user dengan role 'skpd' yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/login");
    exit;
}

// Memanggil file koneksi ke database
require "../includes/koneksi.php";
$user_id = $_SESSION['user_id'];

// Mengambil semua data bangunan yang diinput oleh user yang sedang login
$sql = "SELECT id, nama_bangunan, status_verifikasi FROM bg_data_bangunan WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Menyiapkan pesan notifikasi dari URL (setelah proses hapus/edit)
$notif_status = $_GET['status'] ?? '';
$notif_pesan = $_GET['pesan'] ?? '';
?>

<?php include "../includes/header.php"; ?>

<div class="container">
    <div class="page-header">
        <h2>Daftar Bangunan yang Telah Diinput</h2>
        <!-- Perubahan: Menggunakan path absolut untuk tautan -->
        <a href="/putr/bangunan/skpd/dashboard" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <!-- Area Notifikasi untuk menampilkan pesan sukses atau error -->
    <?php if ($notif_status == 'sukses'): ?>
        <div class="alert alert-sukses" role="alert">
            <?= htmlspecialchars($notif_pesan) ?>
        </div>
    <?php elseif ($notif_status == 'error'): ?>
        <div class="alert alert-gagal" role="alert">
            <?= htmlspecialchars($notif_pesan) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table-styled">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Bangunan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $nomor = 1; ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="No."><?= $nomor++ ?></td>
                            <td data-label="Nama Bangunan"><?= htmlspecialchars($row['nama_bangunan']) ?></td>
                            <td data-label="Status">
                                <?php
                                    // Logika untuk memberikan warna pada status
                                    $status = $row['status_verifikasi'];
                                    $badge_class = '';
                                    if ($status == 'Diverifikasi') {
                                        $badge_class = 'status-verified';
                                    } elseif ($status == 'Revisi Formulir') {
                                        $badge_class = 'status-revision';
                                    } else {
                                        // Default untuk 'Belum Diverifikasi' dan 'Menunggu Tinjauan Ulang'
                                        $badge_class = 'status-pending';
                                    }
                                ?>
                                <span class="status-badge <?= $badge_class ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td data-label="Aksi" class="kolom-aksi">
                                <!-- Perubahan: Menggunakan path absolut untuk semua tautan aksi -->
                                <a href="/putr/bangunan/skpd/lihat_bangunan/<?= $row['id'] ?>" class="tombol-lihat">Lihat</a>
                                <a href="/putr/bangunan/skpd/edit_bangunan/<?= $row['id'] ?>" class="tombol-edit">Edit</a>
                                <a href="/putr/bangunan/skpd/hapus_bangunan/<?= $row['id'] ?>" class="tombol-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data bangunan ini? Proses ini tidak dapat dibatalkan.');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding: 20px;">Anda belum menginput data bangunan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
?>
