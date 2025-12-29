<?php
session_start();
require "../includes/koneksi.php";

// Validasi Akses Pengguna dan ID Bangunan
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
$error = "";
$errorMessages = [];

// 1. Selalu ambil data asli dari database terlebih dahulu
$sql_select = "SELECT * FROM bg_data_bangunan WHERE id = ? AND user_id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("ii", $id_bangunan, $user_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
if ($result->num_rows === 0) {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/skpd/daftar_bangunan?status=error&pesan=Data tidak ditemukan atau Anda tidak punya hak akses.");
    exit;
}
$data_asli = $result->fetch_assoc();
$stmt_select->close();

$data = $data_asli; // Data yang akan digunakan untuk mengisi form

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $formData = $_POST;

    // Fungsi bantu untuk memproses upload file dan mengembalikan path baru atau path lama
    function processFileUpload($fileKey, $oldFilePath, &$errorMessages, $allowed_types, $max_size, $label) {
        $target_dir = "../uploads/";
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];
            // Hapus file lama jika ada dan file baru diupload
            if (!empty($oldFilePath) && file_exists("../" . $oldFilePath)) {
                unlink("../" . $oldFilePath);
            }
            if (!in_array($file['type'], $allowed_types)) {
                $errorMessages[] = "Tipe file untuk $label tidak valid. Harap unggah file yang benar.";
                return null;
            }
            if ($file['size'] > $max_size) {
                $errorMessages[] = "Ukuran file untuk $label terlalu besar (Maks " . ($max_size / 1024) . " KB).";
                return null;
            }
            $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $new_filename = str_replace('_', '-', $fileKey) . '-' . uniqid() . '.' . $extension;
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                return str_replace('../', '', $target_file);
            } else {
                $errorMessages[] = "Terjadi kesalahan sistem saat mengunggah file $label.";
                return null;
            }
        }
        // Jika tidak ada file baru diupload, kembalikan path file lama
        return $oldFilePath;
    }

    $upload_rules = [
        'foto_bangunan' => ['label' => 'Foto Bangunan', 'types' => ['image/jpeg', 'image/png'], 'size' => 300 * 1024],
        'file_bukti'    => ['label' => 'Bukti Kepemilikan', 'types' => ['application/pdf'], 'size' => 1024 * 1024],
        'file_imb'      => ['label' => 'IMB/PBG', 'types' => ['application/pdf'], 'size' => 1024 * 1024],
        'file_slf'      => ['label' => 'SLF', 'types' => ['application/pdf'], 'size' => 1024 * 1024],
    ];

    // Inisialisasi path file dengan nilai yang ada di database
    $path_foto_bangunan = $data_asli['foto_bangunan'];
    $path_file_bukti    = $data_asli['file_bukti_tanah'];
    $path_file_imb      = $data_asli['file_imb_pbg'];
    $path_file_slf      = $data_asli['file_slf'];

    // Proses upload untuk setiap file
    $path_foto_bangunan = processFileUpload('foto_bangunan', $path_foto_bangunan, $errorMessages, $upload_rules['foto_bangunan']['types'], $upload_rules['foto_bangunan']['size'], $upload_rules['foto_bangunan']['label']);
    
    // Logika kondisional untuk file bukti tanah
    if (($formData['punya_bukti_tanah'] ?? '') == 'Ada') {
        $path_file_bukti = processFileUpload('file_bukti', $path_file_bukti, $errorMessages, $upload_rules['file_bukti']['types'], $upload_rules['file_bukti']['size'], $upload_rules['file_bukti']['label']);
    } else {
        // Jika tidak ada bukti tanah, hapus file lama jika ada
        if (!empty($path_file_bukti) && file_exists("../" . $path_file_bukti)) { unlink("../" . $path_file_bukti); }
        $path_file_bukti = null;
    }

    // Logika kondisional untuk file IMB
    if (($formData['punya_imb'] ?? '') == 'Ada') {
        $path_file_imb = processFileUpload('file_imb', $path_file_imb, $errorMessages, $upload_rules['file_imb']['types'], $upload_rules['file_imb']['size'], $upload_rules['file_imb']['label']);
    } else {
        // Jika tidak ada IMB, hapus file lama jika ada
        if (!empty($path_file_imb) && file_exists("../" . $path_file_imb)) { unlink("../" . $path_file_imb); }
        $path_file_imb = null;
    }

    // Logika kondisional untuk file SLF
    if (($formData['punya_slf'] ?? '') == 'Ada') {
        $path_file_slf = processFileUpload('file_slf', $path_file_slf, $errorMessages, $upload_rules['file_slf']['types'], $upload_rules['file_slf']['size'], $upload_rules['file_slf']['label']);
    } else {
        // Jika tidak ada SLF, hapus file lama jika ada
        if (!empty($path_file_slf) && file_exists("../" . $path_file_slf)) { unlink("../" . $path_file_slf); }
        $path_file_slf = null;
    }

    $requiredFields = [
        'tanggal_pendataan' => 'Tanggal Pendataan', 'jenis_kepemilikan_tanah' => 'Jenis Kepemilikan Tanah',
        'nama_pemilik_tanah' => 'Nama Pemilik Tanah', 'luas_tanah' => 'Luas Tanah',
        'nama_bangunan' => 'Nama Bangunan Gedung', 'alamat_bangunan' => 'Alamat Bangunan Gedung',
        'nama_kecamatan' => 'Kecamatan', 'nama_kelurahan' => 'Kelurahan',
        'koordinat_lat' => 'Koordinat Latitude', 'koordinat_lon' => 'Koordinat Longitude',
        'jumlah_lantai' => 'Jumlah Lantai', 'total_luas_lantai' => 'Total Luas Bangunan',
        'punya_imb' => 'Status Kepemilikan IMB / PBG', 'punya_slf' => 'Status Kepemilikan SLF',
    ];
    foreach ($requiredFields as $field => $label) {
        if (empty(trim($formData[$field] ?? ''))) { $errorMessages[] = "$label wajib diisi."; }
    }
    if (($formData['punya_bukti_tanah'] ?? '') == 'Ada') {
        if (empty($formData['jenis_bukti_tanah'])) $errorMessages[] = "Jenis Bukti Kepemilikan wajib dipilih.";
        if (empty(trim($formData['nomor_bukti']))) $errorMessages[] = "Nomor Bukti Kepemilikan wajib diisi.";
        if (empty($path_file_bukti) && empty($data_asli['file_bukti_tanah'])) $errorMessages[] = "File Bukti Kepemilikan wajib diunggah.";
    }
    if (($formData['punya_imb'] ?? '') == 'Ada') {
        if (empty(trim($formData['nomor_imb']))) $errorMessages[] = "Nomor IMB/PBG wajib diisi.";
        if (empty($path_file_imb) && empty($data_asli['file_imb_pbg'])) $errorMessages[] = "File IMB/PBG wajib diunggah.";
    }
    if (($formData['punya_slf'] ?? '') == 'Ada') {
        if (empty(trim($formData['nomor_slf']))) $errorMessages[] = "Nomor SLF wajib diisi.";
        if (empty($path_file_slf) && empty($data_asli['file_slf'])) $errorMessages[] = "File SLF wajib diunggah.";
    }
    if (empty($path_foto_bangunan) && empty($data_asli['foto_bangunan'])) {
        $errorMessages[] = "Foto Bangunan wajib diunggah.";
    }
    
    if (empty($errorMessages)) {
        $status_sebelumnya = $data_asli['status_verifikasi'];
        $status_baru = ($status_sebelumnya == 'Revisi Formulir') ? 'Menunggu Tinjauan Ulang' : 'Belum Diverifikasi';
        
        // Siapkan data untuk update
        $jenis_bukti_tanah = ($formData['punya_bukti_tanah'] == 'Ada') ? ($formData['jenis_bukti_tanah'] ?? null) : null;
        $nomor_bukti = ($formData['punya_bukti_tanah'] == 'Ada') ? ($formData['nomor_bukti'] ?? null) : null;
        $no_imb_pbg = ($formData['punya_imb'] == 'Ada') ? ($formData['nomor_imb'] ?? null) : null;
        $no_slf = ($formData['punya_slf'] == 'Ada') ? ($formData['nomor_slf'] ?? null) : null;

        $sql_update = "UPDATE bg_data_bangunan SET tanggal_pendataan=?, jenis_kepemilikan_tanah=?, nama_pemilik_tanah=?, jenis_bukti_tanah=?, no_id_pemilik_tanah=?, file_bukti_tanah=?, luas_tanah=?, nama_bangunan=?, alamat_bangunan=?, kecamatan=?, kelurahan=?, koordinat_lat=?, koordinat_lon=?, jumlah_lantai=?, total_luas_lantai=?, foto_bangunan=?, no_imb_pbg=?, file_imb_pbg=?, no_slf=?, file_slf=?, status_verifikasi=? WHERE id=? AND user_id=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssssdssssddisssssssii", 
            $formData['tanggal_pendataan'], 
            $formData['jenis_kepemilikan_tanah'], 
            $formData['nama_pemilik_tanah'], 
            $jenis_bukti_tanah, 
            $nomor_bukti, 
            $path_file_bukti, 
            $formData['luas_tanah'], 
            $formData['nama_bangunan'], 
            $formData['alamat_bangunan'], 
            $formData['nama_kecamatan'], // Menggunakan nama_kecamatan dari form
            $formData['nama_kelurahan'], // Menggunakan nama_kelurahan dari form
            $formData['koordinat_lat'], 
            $formData['koordinat_lon'], 
            $formData['jumlah_lantai'], 
            $formData['total_luas_lantai'], 
            $path_foto_bangunan, 
            $no_imb_pbg, 
            $path_file_imb, 
            $no_slf, 
            $path_file_slf, 
            $status_baru, 
            $id_bangunan, 
            $user_id
        );
        
        if ($stmt_update->execute()) {
            // --- LOGGING: EDIT ACTION ---
            // Re-fetch the updated data to ensure we have the exact current state
            $sql_reselect = "SELECT * FROM bg_data_bangunan WHERE id = ?";
            $stmt_reselect = $conn->prepare($sql_reselect);
            $stmt_reselect->bind_param("i", $id_bangunan);
            $stmt_reselect->execute();
            $updated_data = $stmt_reselect->get_result()->fetch_assoc();
            $stmt_reselect->close();

            $changes = [];
            $fields_to_compare = [
                'tanggal_pendataan', 'jenis_kepemilikan_tanah', 'nama_pemilik_tanah', 
                'jenis_bukti_tanah', 'no_id_pemilik_tanah', 'file_bukti_tanah', 'luas_tanah', 
                'nama_bangunan', 'alamat_bangunan', 'kecamatan', 'kelurahan', 
                'koordinat_lat', 'koordinat_lon', 'jumlah_lantai', 'total_luas_lantai', 
                'foto_bangunan', 'no_imb_pbg', 'file_imb_pbg', 'no_slf', 'file_slf', 
                'status_verifikasi'
            ];

            foreach ($fields_to_compare as $field) {
                $old_value = $data_asli[$field] ?? null;
                $new_value = $updated_data[$field] ?? null;

                // Handle NULL vs empty string comparison (e.g., for file paths)
                if (empty($old_value) && empty($new_value)) {
                    continue; // Both are empty/null, no change
                }
                if ($old_value === null) $old_value = '';
                if ($new_value === null) $new_value = '';

                if ($old_value != $new_value) {
                    $changes[$field] = ['old' => $old_value, 'new' => $new_value];
                }
            }
            
            // Only log if there are actual changes
            if (!empty($changes)) {
                $log_stmt = $conn->prepare("INSERT INTO bg_activity_log (user_id, action_type, entity_type, entity_id, old_data, new_data, changes) VALUES (?, 'edit', 'bangunan', ?, ?, ?, ?)");
                $log_old_data_json = json_encode($data_asli);
                $log_new_data_json = json_encode($updated_data);
                $log_changes_json = json_encode($changes);
                $log_stmt->bind_param("iisss", $user_id, $id_bangunan, $log_old_data_json, $log_new_data_json, $log_changes_json);
                $log_stmt->execute();
                $log_stmt->close();
            }
            // --- END LOGGING ---

            // Perubahan: Menggunakan path absolut untuk redirect
            header("Location: /putr/bangunan/skpd/daftar_bangunan?status=sukses&pesan=Data bangunan berhasil diperbarui.");
            exit;
        } else {
            $error = "Gagal memperbarui data: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
    
    // Jika ada error, isi kembali $data dengan $formData yang di-submit agar input user tidak hilang
    if (!empty($errorMessages)) {
        $error = "<ul><li>" . implode("</li><li>", $errorMessages) . "</li></ul>";
        // Gabungkan data asli dengan data dari POST untuk mengisi ulang form
        $data = array_merge($data_asli, $formData);
        // Pastikan nama kecamatan dan kelurahan diambil dari POST jika ada
        $data['kecamatan'] = $formData['nama_kecamatan'] ?? $data_asli['kecamatan'];
        $data['kelurahan'] = $formData['nama_kelurahan'] ?? $data_asli['kelurahan'];
        // Pastikan path file yang digunakan adalah yang terbaru (hasil upload atau yang lama)
        $data['foto_bangunan'] = $path_foto_bangunan;
        $data['file_bukti_tanah'] = $path_file_bukti;
        $data['file_imb_pbg'] = $path_file_imb;
        $data['file_slf'] = $path_file_slf;
    }
}

function display_existing_file($file_path, $file_key) {
    if (!empty($file_path)) {
        $fileName = basename($file_path);
        // Perubahan: Menggunakan path absolut untuk tautan file
        echo '<div class="file-upload-info" id="info-'.$file_key.'">';
        echo '<span class="file-name">File saat ini: <a href="/putr/bangunan/'.htmlspecialchars($file_path).'" target="_blank">'.htmlspecialchars($fileName).'</a></span>';
        echo '</div>';
    }
}
?>

<?php include "../includes/header.php"; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map { height: 400px; width: 100%; border-radius: 8px; margin-top: 10px; }
    .file-upload-info { background-color: #e9f7ef; border: 1px solid #a3d9b8; padding: 10px; border-radius: 5px; margin-top: 5px; margin-bottom: 10px;}
    .file-upload-info .file-name a { font-weight: bold; color: #1d643b; }
    .form-group small { color: #6c757d; }
    .file-validation-error { color: #dc3545; font-size: 0.875em; margin-top: 5px; font-weight: bold; }
    .file-preview-img { max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 5px; margin-top: 10px; object-fit: cover; }
    .header-selebaran { z-index: 1020 !important; }
</style>

<div class="container" style="max-width:850px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0;">Edit Data Bangunan Gedung</h2>
        <!-- Perubahan: Menggunakan path absolut untuk tautan -->
        <a href="/putr/bangunan/skpd/daftar_bangunan" class="btn btn-secondary">Batal</a>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-gagal"><?= $error ?></div>
    <?php endif; ?>

    <form id="formBangunan" method="POST" enctype="multipart/form-data" novalidate>
        <!-- Konten Form (tidak ada perubahan di sini) -->
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Informasi Umum</legend>
            <div class="form-group">
                <label for="tanggal_pendataan">Tanggal Pendataan</label>
                <input type="date" name="tanggal_pendataan" id="tanggal_pendataan" class="form-control" required value="<?= htmlspecialchars($data['tanggal_pendataan'] ?? '') ?>">
            </div>
        </fieldset>

        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Data Profil Tanah</legend>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Jenis Kepemilikan</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Perorangan" required <?= (($data['jenis_kepemilikan_tanah'] ?? '') == 'Perorangan') ? 'checked' : '' ?>>Perorangan</label>
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Badan Hukum" <?= (($data['jenis_kepemilikan_tanah'] ?? '') == 'Badan Hukum') ? 'checked' : '' ?>>Badan Hukum</label>
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Negara" <?= (($data['jenis_kepemilikan_tanah'] ?? '') == 'Negara') ? 'checked' : '' ?>>Negara</label>
                </div>
            </div>
            <div class="form-group">
                <label for="nama_pemilik_tanah">Nama Pemilik Tanah</label>
                <input type="text" id="nama_pemilik_tanah" name="nama_pemilik_tanah" class="form-control" required value="<?= htmlspecialchars($data['nama_pemilik_tanah'] ?? '') ?>">
            </div>
            <?php $punya_bukti_tanah = !empty($data['file_bukti_tanah']) || (isset($data['punya_bukti_tanah']) && $data['punya_bukti_tanah'] == 'Ada') ? 'Ada' : 'Tidak Ada'; ?>
            <div class="form-group" style="margin-top:12px;">
                <label>Apakah Memiliki Bukti Kepemilikan Tanah?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_bukti_tanah" value="Ada" <?= ($punya_bukti_tanah == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_bukti_tanah" value="Tidak Ada" <?= ($punya_bukti_tanah == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
            <div id="kolom-bukti-tanah" style="display: <?= ($punya_bukti_tanah == 'Ada') ? 'block' : 'none' ?>; margin-top:16px; border-top: 1px solid #ddd; padding-top:16px;">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Jenis Bukti</label>
                    <div class="radio-inline-group">
                        <label><input type="radio" name="jenis_bukti_tanah" value="SHM" <?= (($data['jenis_bukti_tanah'] ?? '') == 'SHM') ? 'checked' : '' ?>>SHM</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="HGB" <?= (($data['jenis_bukti_tanah'] ?? '') == 'HGB') ? 'checked' : '' ?>>HGB</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Letter C" <?= (($data['jenis_bukti_tanah'] ?? '') == 'Letter C') ? 'checked' : '' ?>>Letter C</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Girik" <?= (($data['jenis_bukti_tanah'] ?? '') == 'Girik') ? 'checked' : '' ?>>Girik</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="nomor_bukti">Nomor Bukti / ID Pemilik Tanah</label>
                    <input type="text" id="nomor_bukti" name="nomor_bukti" class="form-control" value="<?= htmlspecialchars($data['no_id_pemilik_tanah'] ?? $data['nomor_bukti'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_bukti">Upload File Bukti Kepemilikan Baru (HANYA PDF, maks 1MB)</label>
                    <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                    <?php display_existing_file($data['file_bukti_tanah'], 'file_bukti'); ?>
                    <input type="file" id="file_bukti" name="file_bukti" class="form-control" accept="application/pdf" data-max-size="1048576">
                    <input type="hidden" name="existing_file_bukti" value="<?= htmlspecialchars($data['file_bukti_tanah'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Luas Tanah (m<sup>2</sup>)</label>
                <input type="number" name="luas_tanah" class="form-control" min="0" step="any" required value="<?= htmlspecialchars($data['luas_tanah'] ?? '') ?>">
            </div>
        </fieldset>

        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Data Profil & Dokumen Bangunan</legend>
            <div class="form-group">
                <label>Nama Bangunan Gedung</label>
                <input type="text" name="nama_bangunan" class="form-control" required value="<?= htmlspecialchars($data['nama_bangunan'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Alamat Bangunan Gedung</label>
                <textarea name="alamat_bangunan" class="form-control" required><?= htmlspecialchars($data['alamat_bangunan'] ?? '') ?></textarea>
            </div>
            <?php
                $selected_kecamatan_name = $data['kecamatan'] ?? '';
                $selected_kelurahan_name = $data['kelurahan'] ?? '';
            ?>
            <div class="form-group">
                <label for="kecamatan">Kecamatan</label>
                <select name="kecamatan" id="kecamatan" class="form-control" required data-selected-name="<?= htmlspecialchars($selected_kecamatan_name) ?>">
                    <option value="">-- Pilih Kecamatan --</option>
                </select>
                <input type="hidden" name="nama_kecamatan" id="nama_kecamatan" value="<?= htmlspecialchars($selected_kecamatan_name) ?>">
            </div>
            <div class="form-group">
                <label for="kelurahan">Kelurahan</label>
                <select name="kelurahan" id="kelurahan" class="form-control" required disabled data-selected-name="<?= htmlspecialchars($selected_kelurahan_name) ?>">
                    <option value="">Pilih Kecamatan Terlebih Dahulu</option>
                </select>
                <input type="hidden" name="nama_kelurahan" id="nama_kelurahan" value="<?= htmlspecialchars($selected_kelurahan_name) ?>">
            </div>
        </fieldset>

        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Tentukan Lokasi di Peta</legend>
            <div id="map"></div>
            <div class="form-group" style="margin-top:16px;">
                <label for="koordinat_lat">Latitude</label>
                <input type="text" id="koordinat_lat" name="koordinat_lat" class="form-control" required readonly value="<?= htmlspecialchars($data['koordinat_lat'] ?? '') ?>">
            </div>
             <div class="form-group" style="margin-top:8px;">
                <label for="koordinat_lon">Longitude</label>
                <input type="text" id="koordinat_lon" name="koordinat_lon" class="form-control" required readonly value="<?= htmlspecialchars($data['koordinat_lon'] ?? '') ?>">
            </div>
        </fieldset>
        
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Data Teknis & Legalitas Bangunan</legend>
            <div class="form-group">
                <label>Jumlah Lantai</label>
                <input type="number" name="jumlah_lantai" class="form-control" min="1" required value="<?= htmlspecialchars($data['jumlah_lantai'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Total Luas Bangunan (m<sup>2</sup>)</label>
                <input type="number" name="total_luas_lantai" class="form-control" min="0" step="any" required value="<?= htmlspecialchars($data['total_luas_lantai'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label for="foto_bangunan">Upload Foto Kondisi Bangunan Baru (JPG/PNG, maks 300KB)</label>
                <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                <?php display_existing_file($data['foto_bangunan'], 'foto_bangunan'); ?>
                <input type="file" id="foto_bangunan" name="foto_bangunan" class="form-control" accept="image/png, image/jpeg" data-max-size="307200">
                <input type="hidden" name="existing_foto_bangunan" value="<?= htmlspecialchars($data['foto_bangunan'] ?? '') ?>">
            </div>
            <?php $punya_imb = !empty($data['file_imb_pbg']) || (isset($data['punya_imb']) && $data['punya_imb'] == 'Ada') ? 'Ada' : 'Tidak Ada'; ?>
            <div class="form-group" style="margin-top:12px;">
                <label>Apakah Memiliki IMB / PBG?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_imb" value="Ada" required <?= ($punya_imb == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_imb" value="Tidak Ada" <?= ($punya_imb == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
            <div id="kolom-imb" style="display:<?= ($punya_imb == 'Ada') ? 'block' : 'none' ?>; margin-top:16px; border-top: 1px solid #ddd; padding-top:16px;">
                <div class="form-group">
                    <label for="nomor_imb">Nomor IMB / PBG</label>
                    <input type="text" id="nomor_imb" name="nomor_imb" class="form-control" value="<?= htmlspecialchars($data['no_imb_pbg'] ?? $data['nomor_imb'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_imb">Upload File IMB / PBG Baru (HANYA PDF, maks 1MB)</label>
                    <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                    <?php display_existing_file($data['file_imb_pbg'], 'file_imb'); ?>
                    <input type="file" id="file_imb" name="file_imb" class="form-control" accept="application/pdf" data-max-size="1048576">
                    <input type="hidden" name="existing_file_imb" value="<?= htmlspecialchars($data['file_imb_pbg'] ?? '') ?>">
                </div>
            </div>
            <?php $punya_slf = !empty($data['file_slf']) || (isset($data['punya_slf']) && $data['punya_slf'] == 'Ada') ? 'Ada' : 'Tidak Ada'; ?>
            <div class="form-group" style="margin-top:12px;">
                <label>Apakah Memiliki SLF?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_slf" value="Ada" required <?= ($punya_slf == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_slf" value="Tidak Ada" <?= ($punya_slf == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
             <div id="kolom-slf" style="display:<?= ($punya_slf == 'Ada') ? 'block' : 'none' ?>; margin-top:16px; border-top: 1px solid #ddd; padding-top:16px;">
                <div class="form-group">
                    <label for="nomor_slf">Nomor SLF</label>
                    <input type="text" id="nomor_slf" name="nomor_slf" class="form-control" value="<?= htmlspecialchars($data['no_slf'] ?? $data['nomor_slf'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_slf">Upload File SLF Baru (HANYA PDF, maks 1MB)</label>
                    <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                    <?php display_existing_file($data['file_slf'], 'file_slf'); ?>
                    <input type="file" id="file_slf" name="file_slf" class="form-control" accept="application/pdf" data-max-size="1048576">
                    <input type="hidden" name="existing_file_slf" value="<?= htmlspecialchars($data['file_slf'] ?? '') ?>">
                </div>
            </div>
        </fieldset>
        <!-- Akhir Konten Form -->

        <button type="submit" class="btn btn-success" style="padding: 12px 20px; font-size: 1.1em;">Simpan Perubahan</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formBangunan');
    function setupFileValidation(inputId) {
        const fileInput = document.getElementById(inputId);
        if (!fileInput) return;
        const infoDiv = document.getElementById('info-' + inputId);
        const hiddenInput = document.querySelector(`input[name="existing_${inputId}"]`);
        let errorContainer = document.getElementById('error-' + inputId);
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.className = 'file-validation-error';
            errorContainer.id = 'error-' + inputId;
            fileInput.parentNode.insertBefore(errorContainer, fileInput.nextSibling);
        }
        let previewContainer = document.getElementById('preview-' + inputId);
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.id = 'preview-' + inputId;
            errorContainer.parentNode.insertBefore(previewContainer, errorContainer.nextSibling);
        }
        const displayPreview = (source) => {
            previewContainer.innerHTML = '';
            if (!source) return;
            // Perubahan: Menggunakan path absolut untuk preview gambar
            if (typeof source === 'string' && source.match(/\.(jpg|jpeg|png|gif)$/i)) {
                const img = new Image();
                img.src = '/putr/bangunan/' + source;
                img.className = 'file-preview-img';
                previewContainer.appendChild(img);
            } else if (source instanceof File && source.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.src = e.target.result;
                    img.className = 'file-preview-img';
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(source);
            }
        };
        if(hiddenInput && hiddenInput.value) {
            displayPreview(hiddenInput.value);
        }
        fileInput.addEventListener('change', function(event) {
            errorContainer.textContent = '';
            const [file] = event.target.files;
            if (!file) {
                if (infoDiv) infoDiv.style.display = 'block';
                displayPreview(hiddenInput ? hiddenInput.value : null);
                return;
            }
            if (infoDiv) {
                infoDiv.style.display = 'none';
            }
            const allowedTypes = fileInput.getAttribute('accept').split(',').map(t => t.trim());
            const maxSize = parseInt(fileInput.getAttribute('data-max-size'), 10);
            let isValid = true;
            if (!allowedTypes.includes(file.type)) {
                const allowedExtensions = allowedTypes.map(t => t.split('/')[1].toUpperCase()).join(' / ');
                errorContainer.textContent = `Jenis file salah. Harap unggah: ${allowedExtensions}.`;
                isValid = false;
            }
            if (isValid && file.size > maxSize) {
                const maxSizeInMB = (maxSize / 1024 / 1024);
                const sizeString = maxSizeInMB < 1 ? (maxSize / 1024) + ' KB' : maxSizeInMB.toFixed(1) + ' MB';
                errorContainer.textContent = `Ukuran file terlalu besar. Maksimal ${sizeString}.`;
                isValid = false;
            }
            if (isValid) {
                displayPreview(file);
            } else {
                fileInput.value = ''; 
                displayPreview(hiddenInput ? hiddenInput.value : null);
                if (infoDiv) infoDiv.style.display = 'block';
            }
        });
    }

    setupFileValidation('foto_bangunan');
    setupFileValidation('file_bukti');
    setupFileValidation('file_imb');
    setupFileValidation('file_slf');

    if (form) {
        form.addEventListener('submit', function(event) {
            const errorMessages = document.querySelectorAll('.file-validation-error');
            let hasErrors = false;
            errorMessages.forEach(function(msg) {
                if (msg.textContent.trim() !== '') {
                    hasErrors = true;
                }
            });
            if (hasErrors) {
                event.preventDefault();
                alert('Terdapat kesalahan pada file yang Anda unggah. Harap perbaiki sebelum menyimpan.');
            }
        });
    }

    function setupConditionalDisplay(radioGroupName, targetElementId) {
        const radios = document.querySelectorAll(`input[name="${radioGroupName}"]`);
        const targetElement = document.getElementById(targetElementId);
        function toggleVisibility() {
            const isAdaChecked = document.querySelector(`input[name="${radioGroupName}"][value="Ada"]`).checked;
            targetElement.style.display = isAdaChecked ? 'block' : 'none';
        }
        radios.forEach(radio => radio.addEventListener('change', toggleVisibility));
    }
    setupConditionalDisplay('punya_bukti_tanah', 'kolom-bukti-tanah');
    setupConditionalDisplay('punya_imb', 'kolom-imb');
    setupConditionalDisplay('punya_slf', 'kolom-slf');

    const latInput = document.getElementById('koordinat_lat');
    const lonInput = document.getElementById('koordinat_lon');
    const initialLat = parseFloat(latInput.value) || -7.3271;
    const initialLon = parseFloat(lonInput.value) || 108.2199;
    const map = L.map('map').setView([initialLat, initialLon], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    const marker = L.marker([initialLat, initialLon], { draggable: true }).addTo(map);
    marker.on('dragend', e => updateCoordinates(e.target.getLatLng()));
    map.on('click', e => {
        marker.setLatLng(e.latlng);
        updateCoordinates(e.latlng);
    });
    function updateCoordinates(latlng) {
        latInput.value = latlng.lat.toFixed(6);
        lonInput.value = latlng.lng.toFixed(6);
    }

    const kecamatanSelect = document.getElementById('kecamatan');
    const kelurahanSelect = document.getElementById('kelurahan');
    const namaKecamatanInput = document.getElementById('nama_kecamatan');
    const namaKelurahanInput = document.getElementById('nama_kelurahan');
    const KOTA_ID = '3278';

    async function loadWilayah() {
        const kecResponse = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${KOTA_ID}.json`);
        const districts = await kecResponse.json();
        kecamatanSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        let selectedKecamatanId = '';
        districts.forEach(d => {
            const option = document.createElement('option');
            option.value = d.id;
            option.textContent = d.name;
            if (d.name === kecamatanSelect.getAttribute('data-selected-name')) {
                option.selected = true;
                selectedKecamatanId = d.id;
            }
            kecamatanSelect.appendChild(option);
        });
        if (selectedKecamatanId) {
            kelurahanSelect.disabled = false;
            const kelResponse = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${selectedKecamatanId}.json`);
            const villages = await kelResponse.json();
            kelurahanSelect.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
            villages.forEach(v => {
                const option = document.createElement('option');
                option.value = v.id;
                option.textContent = v.name;
                if (v.name === kelurahanSelect.getAttribute('data-selected-name')) {
                    option.selected = true;
                }
                kelurahanSelect.appendChild(option);
            });
        }
    }

    kecamatanSelect.addEventListener('change', async function() {
        const selectedOption = this.options[this.selectedIndex];
        namaKecamatanInput.value = selectedOption.value ? selectedOption.text : '';
        const kecamatanId = this.value;
        kelurahanSelect.disabled = true;
        namaKelurahanInput.value = '';
        kelurahanSelect.innerHTML = '<option value="">Memuat...</option>';
        if (!kecamatanId) {
            kelurahanSelect.innerHTML = '<option value="">Pilih Kecamatan Terlebih Dahulu</option>';
            return;
        }
        const response = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${kecamatanId}.json`);
        const villages = await response.json();
        kelurahanSelect.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
        villages.forEach(v => {
            const option = document.createElement('option');
            option.value = v.id;
            option.textContent = v.name;
            kelurahanSelect.appendChild(option);
        });
        kelurahanSelect.disabled = false;
    });

    kelurahanSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        namaKelurahanInput.value = selectedOption.value ? selectedOption.text : '';
    });

    loadWilayah();
});
</script>

