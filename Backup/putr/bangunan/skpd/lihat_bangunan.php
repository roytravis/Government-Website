<?php
session_start();
require "../includes/koneksi.php";

// 1. Validasi Akses Pengguna dan ID Bangunan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/login");
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/skpd/daftar_bangunan?status=error&pesan=Permintaan tidak valid.");
    exit;
}

$id_bangunan = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. Ambil Data Lengkap dari Database
$sql = "SELECT * FROM bg_data_bangunan WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_bangunan, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/skpd/daftar_bangunan?status=error&pesan=Data tidak ditemukan atau Anda tidak memiliki hak akses.");
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Fungsi bantu untuk menampilkan baris data
function display_row($label, $value, $is_file = false, $is_image = false) {
    echo '<div class="data-row">';
    echo '  <div class="data-label">' . htmlspecialchars($label) . '</div>';
    echo '  <div class="data-value">';
    
    if (empty($value)) {
        echo '<em>- (tidak ada data)</em>';
    } elseif ($is_file) {
        // Perubahan: Menggunakan path absolut untuk file
        $file_path = "/putr/bangunan/" . htmlspecialchars($value);
        if ($is_image) {
            echo '<a href="' . $file_path . '" target="_blank">';
            echo '  <img src="' . $file_path . '" alt="Pratinjau ' . htmlspecialchars($label) . '" class="file-preview-image">';
            echo '</a>';
        } else {
            echo '<a href="' . $file_path . '" target="_blank" class="file-link">Lihat File (' . basename($value) . ')</a>';
        }
    } else {
        echo htmlspecialchars($value);
    }
    
    echo '  </div>';
    echo '</div>';
}
?>

<?php include "../includes/header.php"; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    .detail-container {
        max-width: 900px;
        margin: 20px auto;
        padding: 25px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }
    .detail-header h2 { margin: 0; color: #2c3e50; }
    .data-section { margin-bottom: 30px; }
    .data-section h3 {
        font-size: 1.3em;
        color: #2176d2;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
    .data-row {
        display: flex;
        flex-wrap: wrap;
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    .data-row:last-child { border-bottom: none; }
    .data-label {
        flex: 0 0 250px;
        font-weight: 600;
        color: #555;
        padding-right: 15px;
    }
    .data-value { flex: 1; color: #333; }
    .file-preview-image {
        max-width: 100%;
        max-height: 400px;
        border-radius: 6px;
        border: 1px solid #ddd;
        margin-top: 5px;
        transition: transform 0.2s;
    }
    .file-preview-image:hover { transform: scale(1.02); }
    .file-link { font-weight: 500; color: #007bff; text-decoration: none; }
    .file-link:hover { text-decoration: underline; }
    #mapDetail {
        height: 400px;
        width: 100%;
        border-radius: 8px;
        margin-top: 10px;
        z-index: 1;
    }
    .status-box {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border-left-width: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .status-box-revisi { background-color: #fff5f5; border-color: #e53e3e; color: #c53030; }
    .status-box-diverifikasi { background-color: #f0fff4; border-color: #38a169; color: #2f855a; }
    .status-box-pending { background-color: #fffaf0; border-color: #dd6b20; color: #c05621; }
    .status-box h3 {
        margin-top: 0;
        font-size: 1.2em;
        margin-bottom: 8px;
        font-weight: 700;
    }
    .status-box p { margin-bottom: 0; white-space: pre-wrap; }
    @media (max-width: 768px) {
        .data-label { flex-basis: 100%; margin-bottom: 5px; }
        .data-value { flex-basis: 100%; padding-left: 10px; }
    }
</style>

<div class="container detail-container">
    <div class="detail-header">
        <h2>Detail Data Bangunan</h2>
        <!-- Perubahan: Menggunakan path absolut untuk tautan -->
        <a href="/putr/bangunan/skpd/daftar_bangunan" class="btn btn-secondary">Kembali</a>
    </div>

    <?php
        $status = $data['status_verifikasi'];
        $catatan = $data['catatan_admin'];
        $status_class = '';
        $status_title = '';

        if ($status == 'Revisi Formulir') {
            $status_class = 'status-box-revisi';
            $status_title = 'Status: Perlu Revisi';
        } elseif ($status == 'Diverifikasi') {
            $status_class = 'status-box-diverifikasi';
            $status_title = 'Status: Telah Diverifikasi';
        } else {
            $status_class = 'status-box-pending';
            $status_title = 'Status: Menunggu Verifikasi';
        }
    ?>
    <div class="status-box <?= $status_class ?>">
        <h3><?= $status_title ?></h3>
        <?php if ($status == 'Revisi Formulir' && !empty($catatan)): ?>
            <p><strong>Catatan dari Admin:</strong><br><?= nl2br(htmlspecialchars($catatan)) ?></p>
        <?php elseif ($status == 'Diverifikasi'): ?>
            <p>Data bangunan ini telah diperiksa dan disetujui oleh admin.</p>
        <?php else: ?>
            <p>Data sedang dalam antrian untuk ditinjau oleh admin.</p>
        <?php endif; ?>
    </div>

    <!-- Foto Utama Bangunan -->
    <div class="data-section">
        <h3>Foto Bangunan</h3>
        <?php display_row('Foto Kondisi Bangunan', $data['foto_bangunan'], true, true); ?>
    </div>

    <!-- Informasi Umum -->
    <div class="data-section">
        <h3>Informasi Umum & Lokasi</h3>
        <?php display_row('Tanggal Pendataan', date("d F Y", strtotime($data['tanggal_pendataan']))); ?>
        <?php display_row('Nama Bangunan Gedung', $data['nama_bangunan']); ?>
        <?php display_row('Alamat', $data['alamat_bangunan']); ?>
        <?php display_row('Kecamatan', $data['kecamatan']); ?>
        <?php display_row('Kelurahan', $data['kelurahan']); ?>
        <?php display_row('Latitude', $data['koordinat_lat']); ?>
        <?php display_row('Longitude', $data['koordinat_lon']); ?>
    </div>

    <!-- Data Tanah -->
    <div class="data-section">
        <h3>Data Profil Tanah</h3>
        <?php display_row('Jenis Kepemilikan Tanah', $data['jenis_kepemilikan_tanah']); ?>
        <?php display_row('Nama Pemilik Tanah', $data['nama_pemilik_tanah']); ?>
        <?php display_row('Luas Tanah (m²)', number_format($data['luas_tanah'], 2, ',', '.')); ?>
        <?php display_row('Jenis Bukti Kepemilikan', $data['jenis_bukti_tanah']); ?>
        <?php display_row('Nomor Bukti / ID', $data['no_id_pemilik_tanah']); ?>
        <?php display_row('File Bukti Kepemilikan', $data['file_bukti_tanah'], true); ?>
    </div>

    <!-- Data Teknis & Legalitas Bangunan -->
    <div class="data-section">
        <h3>Data Teknis & Legalitas Bangunan</h3>
        <?php display_row('Jumlah Lantai', $data['jumlah_lantai']); ?>
        <?php display_row('Total Luas Lantai Bangunan (m²)', number_format($data['total_luas_lantai'], 2, ',', '.')); ?>
        <?php display_row('Nomor IMB / PBG', $data['no_imb_pbg']); ?>
        <?php display_row('File IMB / PBG', $data['file_imb_pbg'], true); ?>
        <?php display_row('Nomor SLF', $data['no_slf']); ?>
        <?php display_row('File SLF', $data['file_slf'], true); ?>
    </div>

    <!-- Peta Lokasi -->
    <div class="data-section">
        <h3>Peta Lokasi</h3>
        <div id="mapDetail"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lat = <?= !empty($data['koordinat_lat']) ? $data['koordinat_lat'] : '0'; ?>;
    const lon = <?= !empty($data['koordinat_lon']) ? $data['koordinat_lon'] : '0'; ?>;

    if (lat !== 0 && lon !== 0) {
        const map = L.map('mapDetail').setView([lat, lon], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        L.marker([lat, lon]).addTo(map)
            .bindPopup('<b><?= htmlspecialchars($data['nama_bangunan'], ENT_QUOTES) ?></b><br><?= htmlspecialchars($data['alamat_bangunan'], ENT_QUOTES) ?><hr>Lat: <?= htmlspecialchars($data['koordinat_lat']) ?><br>Lon: <?= htmlspecialchars($data['koordinat_lon']) ?>')
            .openPopup();
    } else {
        document.getElementById('mapDetail').innerHTML = '<p style="text-align:center; padding: 20px;"><em>Lokasi tidak tersedia di peta.</em></p>';
    }
});
</script>
