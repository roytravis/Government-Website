<?php
// Selalu mulai sesi di awal file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PERBAIKAN KEAMANAN: Membuat token CSRF jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require "../includes/koneksi.php";

// Validasi Sesi, Peran, dan ID Bangunan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /putr/bangunan/login");
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /putr/bangunan/admin/verifikasi_data");
    exit;
}

$id_bangunan = $_GET['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // PERBAIKAN KEAMANAN: Validasi token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error: Aksi tidak diizinkan (invalid CSRF token).');
    }

    $status_verifikasi = $_POST['status_verifikasi'];
    $catatan_admin = trim($_POST['catatan_admin']);

    if ($status_verifikasi == 'Revisi Formulir' && empty($catatan_admin)) {
        $error = "Catatan Admin wajib diisi jika status adalah 'Revisi Formulir'.";
    } else {
        $sql_update = "UPDATE bg_data_bangunan SET status_verifikasi = ?, catatan_admin = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $status_verifikasi, $catatan_admin, $id_bangunan);
        
        if ($stmt_update->execute()) {
            header("Location: /putr/bangunan/admin/verifikasi_data?status=sukses&pesan=Status data bangunan berhasil diperbarui.");
            exit; 
        } else {
            $error = "Gagal memperbarui data ke database.";
        }
        $stmt_update->close();
    }
}

$sql = "SELECT b.*, u.nama_instansi, u.nama_lengkap as nama_penginput 
        FROM bg_data_bangunan b
        JOIN bg_users u ON b.user_id = u.id
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_bangunan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /putr/bangunan/admin/verifikasi_data");
    exit;
}
$data = $result->fetch_assoc();
$stmt->close();

function display_row($label, $value, $is_file = false, $is_image = false) {
    echo '<div class="grid grid-cols-3 gap-4 py-3 border-b border-gray-200">';
    echo '  <div class="text-sm font-medium text-gray-500 col-span-1">' . htmlspecialchars($label) . '</div>';
    echo '  <div class="text-sm text-gray-900 col-span-2">';
    
    if (empty(trim($value))) {
        echo '<em class="text-gray-400">- Tidak ada data -</em>';
    } elseif ($is_file) {
        $file_path = "/putr/bangunan/" . htmlspecialchars($value);
        if ($is_image) {
            echo '<a href="' . $file_path . '" target="_blank" class="block w-full md:w-1/2">';
            echo '  <img src="' . $file_path . '" alt="Pratinjau ' . htmlspecialchars($label) . '" class="rounded-lg shadow-md hover:shadow-xl transition-shadow">';
            echo '</a>';
        } else {
            echo '<a href="' . $file_path . '" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium rounded-md">';
            echo '  <i data-feather="file-text" class="w-4 h-4 mr-2"></i>Lihat File PDF';
            echo '</a>';
        }
    } else {
        echo nl2br(htmlspecialchars($value));
    }
    
    echo '  </div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Verifikasi: <?= htmlspecialchars($data['nama_bangunan']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        #mapDetail { height: 400px; z-index: 1; }
    </style>
</head>
<body>
    <div class="p-4 md:p-8 max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Detail Verifikasi Bangunan</h1>
                <p class="text-gray-600">Tinjau data dan lakukan verifikasi di bawah ini.</p>
            </div>
            <a href="/putr/bangunan/admin/verifikasi_data" class="text-blue-600 hover:underline flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
                Kembali ke Daftar
            </a>
        </div>

        <!-- Detail Data (Tidak ada perubahan di sini) -->
        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">Informasi Bangunan</h2>
            <?php display_row('Nama Bangunan', $data['nama_bangunan']); ?>
            <?php display_row('SKPD Penginput', $data['nama_instansi']); ?>
            <?php display_row('Nama Penginput', $data['nama_penginput']); ?>
            <?php display_row('Tanggal Pendataan', date("d F Y", strtotime($data['tanggal_pendataan']))); ?>
            <?php display_row('Alamat', $data['alamat_bangunan']); ?>
            <?php display_row('Kecamatan', $data['kecamatan']); ?>
            <?php display_row('Kelurahan', $data['kelurahan']); ?>
            <?php display_row('Koordinat', $data['koordinat_lat'] . ', ' . $data['koordinat_lon']); ?>
            <?php display_row('Foto Bangunan', $data['foto_bangunan'], true, true); ?>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">Data Pertanahan</h2>
            <?php display_row('Jenis Kepemilikan', $data['jenis_kepemilikan_tanah']); ?>
            <?php display_row('Nama Pemilik Tanah', $data['nama_pemilik_tanah']); ?>
            <?php display_row('Luas Tanah (m²)', number_format($data['luas_tanah'], 2, ',', '.')); ?>
            <?php display_row('Jenis Bukti', $data['jenis_bukti_tanah']); ?>
            <?php display_row('Nomor Bukti', $data['no_id_pemilik_tanah']); ?>
            <?php display_row('File Bukti', $data['file_bukti_tanah'], true); ?>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">Data Teknis & Legalitas</h2>
            <?php display_row('Jumlah Lantai', $data['jumlah_lantai']); ?>
            <?php display_row('Total Luas Lantai (m²)', number_format($data['total_luas_lantai'], 2, ',', '.')); ?>
            <?php display_row('Nomor IMB/PBG', $data['no_imb_pbg']); ?>
            <?php display_row('File IMB/PBG', $data['file_imb_pbg'], true); ?>
            <?php display_row('Nomor SLF', $data['no_slf']); ?>
            <?php display_row('File SLF', $data['file_slf'], true); ?>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">Riwayat Pemeliharaan/Rehabilitasi Terakhir</h2>
            <?php
            if (!empty($data['riwayat_pemeliharaan_terakhir'])) {
                $riwayat = json_decode($data['riwayat_pemeliharaan_terakhir'], true);
                display_row('Nama Kegiatan', $riwayat['nama_kegiatan'] ?? '-');
                display_row('Tahun Anggaran', $riwayat['tahun_anggaran'] ?? '-');
                display_row('Nilai Anggaran', 'Rp ' . number_format($riwayat['nilai_anggaran'] ?? 0, 0, ',', '.'));
            } else {
                echo '<p class="text-sm text-gray-500">Tidak ada data riwayat pemeliharaan yang diinput.</p>';
            }
            ?>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">Peta Lokasi</h2>
            <div id="mapDetail" class="rounded-lg"></div>
        </div>

        <!-- Form Verifikasi -->
        <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">Formulir Tindakan Verifikasi</h2>
            <?php if(!empty($error)): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= $error ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" id="verifikasiForm">
                <!-- PERBAIKAN KEAMANAN: Menambahkan field token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="mb-4">
                    <label for="status_verifikasi" class="block text-sm font-medium text-gray-700 mb-2">Ubah Status Menjadi:</label>
                    <select id="status_verifikasi" name="status_verifikasi" class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="Diverifikasi" <?= ($data['status_verifikasi'] == 'Diverifikasi') ? 'selected' : '' ?>>Diverifikasi</option>
                        <option value="Revisi Formulir" <?= ($data['status_verifikasi'] == 'Revisi Formulir') ? 'selected' : '' ?>>Revisi Formulir</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label for="catatan_admin" class="block text-sm font-medium text-gray-700 mb-2">Catatan Admin (Wajib diisi jika status 'Revisi')</label>
                    <textarea id="catatan_admin" name="catatan_admin" rows="4" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Tuliskan catatan atau alasan jika status diubah menjadi 'Revisi Formulir'..."><?= htmlspecialchars($_POST['catatan_admin'] ?? $data['catatan_admin'] ?? '') ?></textarea>
                    <p id="catatan-error" class="text-red-600 text-sm mt-1 hidden">Catatan Admin wajib diisi.</p>
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan Perubahan Status
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        feather.replace();

        const form = document.getElementById('verifikasiForm');
        const statusSelect = document.getElementById('status_verifikasi');
        const catatanTextarea = document.getElementById('catatan_admin');
        const catatanError = document.getElementById('catatan-error');

        form.addEventListener('submit', function(event) {
            const statusValue = statusSelect.value;
            const catatanValue = catatanTextarea.value.trim();

            if (statusValue === 'Revisi Formulir' && catatanValue === '') {
                event.preventDefault(); 
                catatanError.classList.remove('hidden');
                catatanTextarea.classList.add('border-red-500');
                catatanTextarea.focus();
            } else {
                catatanError.classList.add('hidden');
                catatanTextarea.classList.remove('border-red-500');
            }
        });

        catatanTextarea.addEventListener('input', function() {
            if (catatanTextarea.value.trim() !== '') {
                catatanError.classList.add('hidden');
                catatanTextarea.classList.remove('border-red-500');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const lat = <?= !empty($data['koordinat_lat']) && is_numeric($data['koordinat_lat']) ? $data['koordinat_lat'] : '0'; ?>;
            const lon = <?= !empty($data['koordinat_lon']) && is_numeric($data['koordinat_lon']) ? $data['koordinat_lon'] : '0'; ?>;
            const mapContainer = document.getElementById('mapDetail');

            if (lat !== 0 && lon !== 0 && mapContainer) {
                const map = L.map('mapDetail').setView([lat, lon], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.marker([lat, lon]).addTo(map)
                    .bindPopup('<b><?= htmlspecialchars($data['nama_bangunan'], ENT_QUOTES) ?></b><br><?= htmlspecialchars($data['alamat_bangunan'], ENT_QUOTES) ?>')
                    .openPopup();
            } else if (mapContainer) {
                mapContainer.innerHTML = '<p class="text-center text-gray-500 py-4"><em>Lokasi tidak tersedia di peta.</em></p>';
                mapContainer.style.height = 'auto';
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
