<?php
session_start();
// Memastikan hanya user dengan role 'skpd' yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    header("Location: /putr/bangunan/login");
    exit;
}
// Memanggil file koneksi ke database
require "../includes/koneksi.php";
$user_id = $_SESSION['user_id'];

// --- PERSIAPAN UNTUK MENYIMPAN FILE SEMENTARA ---
if (!isset($_SESSION['temp_files'])) {
    $_SESSION['temp_files'] = [];
}
if (!isset($_SESSION['temp_files_meta'])) {
    $_SESSION['temp_files_meta'] = [];
}

$temp_dir = "../uploads/temp/";
$target_dir = "../uploads/";
if (!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);


$success = $error = "";
$formData = [];
$errorMessages = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $formData = $_POST;

    // --- VALIDASI INPUT ---
    $requiredFields = [
        'tanggal_pendataan' => 'Tanggal Pendataan', 'jenis_kepemilikan_tanah' => 'Jenis Kepemilikan Tanah',
        'nama_pemilik_tanah' => 'Nama Pemilik Tanah', 'luas_tanah' => 'Luas Tanah',
        'nama_bangunan' => 'Nama Bangunan Gedung', 'alamat_bangunan' => 'Alamat Bangunan Gedung',
        'kecamatan' => 'Kecamatan', 'kelurahan' => 'Kelurahan',
        'koordinat_lat' => 'Koordinat Latitude', 'koordinat_lon' => 'Koordinat Longitude',
        'jumlah_lantai' => 'Jumlah Lantai', 'total_luas_lantai' => 'Total Luas Bangunan',
        'punya_imb' => 'Status Kepemilikan IMB / PBG', 'punya_slf' => 'Status Kepemilikan SLF',
        'punya_riwayat_pemeliharaan' => 'Status Riwayat Pemeliharaan', // Field baru
    ];
    foreach ($requiredFields as $field => $label) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errorMessages[] = "$label wajib diisi.";
        }
    }
    // Validasi kondisional untuk bukti tanah
    if (isset($_POST['punya_bukti_tanah']) && $_POST['punya_bukti_tanah'] == 'Ada') {
        if (empty($_POST['jenis_bukti_tanah'])) $errorMessages[] = "Jenis Bukti Kepemilikan wajib dipilih.";
        // Validasi baru untuk opsi "Lainnya"
        if ($_POST['jenis_bukti_tanah'] == 'Lainnya' && empty(trim($_POST['jenis_bukti_lainnya']))) {
             $errorMessages[] = "Jenis Bukti Lainnya wajib diisi jika Anda memilih 'Lainnya'.";
        }
        if (empty(trim($_POST['nomor_bukti']))) $errorMessages[] = "Nomor Bukti Kepemilikan wajib diisi.";
    }
    if (isset($_POST['punya_imb']) && $_POST['punya_imb'] == 'Ada' && empty(trim($_POST['nomor_imb']))) {
        $errorMessages[] = "Nomor IMB/PBG wajib diisi.";
    }
    if (isset($_POST['punya_slf']) && $_POST['punya_slf'] == 'Ada' && empty(trim($_POST['nomor_slf']))) {
        $errorMessages[] = "Nomor SLF wajib diisi.";
    }
    // Validasi baru untuk riwayat pemeliharaan
    if (isset($_POST['punya_riwayat_pemeliharaan']) && $_POST['punya_riwayat_pemeliharaan'] == 'Ada') {
        if (empty(trim($_POST['nama_kegiatan_pemeliharaan']))) $errorMessages[] = "Nama Kegiatan Pemeliharaan wajib diisi.";
        if (empty(trim($_POST['tahun_anggaran_pemeliharaan']))) $errorMessages[] = "Tahun Anggaran Pemeliharaan wajib diisi.";
        if (empty(trim($_POST['nilai_anggaran_pemeliharaan']))) $errorMessages[] = "Nilai Anggaran Pemeliharaan wajib diisi.";
    }


    // --- VALIDASI FILE UPLOAD ---
    $allowed_image_types = ['image/jpeg', 'image/png'];
    $allowed_pdf_only = ['application/pdf'];
    // Perubahan: Ukuran foto diubah menjadi 3MB
    $max_image_size = 3 * 1024 * 1024; // 3 MB
    $max_doc_size = 1024 * 1024; // 1 MB
    
    function handleTempUpload($fileKey, $allowed_types, $max_size, $temp_dir) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
            $file = $_FILES[$fileKey];
            if (!in_array($file['type'], $allowed_types)) {
                return ["error" => "Format file untuk " . ucwords(str_replace('_', ' ', $fileKey)) . " tidak valid."];
            }
            if ($file['size'] > $max_size) {
                $max_size_mb = round($max_size / 1024 / 1024, 1);
                return ["error" => "Ukuran file untuk " . ucwords(str_replace('_', ' ', $fileKey)) . " terlalu besar (Maks: {$max_size_mb}MB)."];
            }
            $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $file_name = str_replace('_', '-', $fileKey) . '-' . uniqid() . '.' . $extension;
            $temp_file_path = $temp_dir . $file_name;
            if (move_uploaded_file($file["tmp_name"], $temp_file_path)) {
                if (isset($_SESSION['temp_files'][$fileKey]) && file_exists($_SESSION['temp_files'][$fileKey])) {
                    unlink($_SESSION['temp_files'][$fileKey]);
                }
                $_SESSION['temp_files'][$fileKey] = $temp_file_path;
                $_SESSION['temp_files_meta'][$fileKey] = ['name' => $file['name'], 'type' => $file['type']];
                return ["success" => true];
            }
            return ["error" => "Gagal memindahkan file sementara untuk " . ucwords(str_replace('_', ' ', $fileKey)) . "."];
        }
        return ["success" => true];
    }

    $fotoBangunanResult = handleTempUpload('foto_bangunan', $allowed_image_types, $max_image_size, $temp_dir);
    if (isset($fotoBangunanResult['error'])) $errorMessages[] = $fotoBangunanResult['error'];
    if (isset($_POST['punya_bukti_tanah']) && $_POST['punya_bukti_tanah'] == 'Ada') {
        $fileBuktiResult = handleTempUpload('file_bukti', $allowed_pdf_only, $max_doc_size, $temp_dir);
        if (isset($fileBuktiResult['error'])) $errorMessages[] = $fileBuktiResult['error'];
    }
    if (isset($_POST['punya_imb']) && $_POST['punya_imb'] == 'Ada') {
        $fileImbResult = handleTempUpload('file_imb', $allowed_pdf_only, $max_doc_size, $temp_dir);
        if (isset($fileImbResult['error'])) $errorMessages[] = $fileImbResult['error'];
    }
    if (isset($_POST['punya_slf']) && $_POST['punya_slf'] == 'Ada') {
        $fileSlfResult = handleTempUpload('file_slf', $allowed_pdf_only, $max_doc_size, $temp_dir);
        if (isset($fileSlfResult['error'])) $errorMessages[] = $fileSlfResult['error'];
    }

    if (!isset($_SESSION['temp_files']['foto_bangunan'])) $errorMessages[] = "Anda wajib mengupload foto kondisi bangunan.";
    if (isset($_POST['punya_bukti_tanah']) && $_POST['punya_bukti_tanah'] == 'Ada' && !isset($_SESSION['temp_files']['file_bukti'])) $errorMessages[] = "File Bukti Kepemilikan wajib diupload.";
    if (isset($_POST['punya_imb']) && $_POST['punya_imb'] == 'Ada' && !isset($_SESSION['temp_files']['file_imb'])) $errorMessages[] = "File IMB/PBG wajib diupload.";
    if (isset($_POST['punya_slf']) && $_POST['punya_slf'] == 'Ada' && !isset($_SESSION['temp_files']['file_slf'])) $errorMessages[] = "File SLF wajib diupload.";

    if (!empty($errorMessages)) {
        $error = "<ul><li>" . implode("</li><li>", $errorMessages) . "</li></ul>";
    } else {
        // --- PROSES DAN SIMPAN DATA ---
        $foto_bangunan_path = null;
        if (isset($_SESSION['temp_files']['foto_bangunan'])) {
            $final_path = str_replace($temp_dir, $target_dir, $_SESSION['temp_files']['foto_bangunan']);
            if (rename($_SESSION['temp_files']['foto_bangunan'], $final_path)) {
                $foto_bangunan_path = str_replace('../', '', $final_path);
            } else { $error = "Gagal finalisasi file foto bangunan."; }
        }
        $file_bukti_tanah_path = null;
        if (empty($error) && isset($_SESSION['temp_files']['file_bukti'])) {
            $final_path = str_replace($temp_dir, $target_dir, $_SESSION['temp_files']['file_bukti']);
            if (rename($_SESSION['temp_files']['file_bukti'], $final_path)) {
                $file_bukti_tanah_path = str_replace('../', '', $final_path);
            } else { $error = "Gagal finalisasi file bukti kepemilikan."; }
        }
        $file_imb_pbg_path = null;
        if (empty($error) && isset($_SESSION['temp_files']['file_imb'])) {
            $final_path = str_replace($temp_dir, $target_dir, $_SESSION['temp_files']['file_imb']);
            if (rename($_SESSION['temp_files']['file_imb'], $final_path)) {
                $file_imb_pbg_path = str_replace('../', '', $final_path);
            } else { $error = "Gagal finalisasi file IMB/PBG."; }
        }
        $file_slf_path = null;
        if (empty($error) && isset($_SESSION['temp_files']['file_slf'])) {
            $final_path = str_replace($temp_dir, $target_dir, $_SESSION['temp_files']['file_slf']);
            if (rename($_SESSION['temp_files']['file_slf'], $final_path)) {
                $file_slf_path = str_replace('../', '', $final_path);
            } else { $error = "Gagal finalisasi file SLF."; }
        }

        if (empty($error)) {
            // Menyiapkan data untuk disimpan ke database
            $jenis_bukti_tanah_final = null;
            if ($_POST['punya_bukti_tanah'] == 'Ada') {
                if ($_POST['jenis_bukti_tanah'] == 'Lainnya') {
                    $jenis_bukti_tanah_final = $_POST['jenis_bukti_lainnya'];
                } else {
                    $jenis_bukti_tanah_final = $_POST['jenis_bukti_tanah'];
                }
            }
            
            $riwayat_pemeliharaan_json = null;
            if ($_POST['punya_riwayat_pemeliharaan'] == 'Ada') {
                $riwayat_data = [
                    'nama_kegiatan' => $_POST['nama_kegiatan_pemeliharaan'],
                    'tahun_anggaran' => $_POST['tahun_anggaran_pemeliharaan'],
                    'nilai_anggaran' => $_POST['nilai_anggaran_pemeliharaan']
                ];
                $riwayat_pemeliharaan_json = json_encode($riwayat_data);
            }

            // PERSIAPAN QUERY DATABASE
            $sql = "INSERT INTO bg_data_bangunan (user_id, tanggal_pendataan, jenis_kepemilikan_tanah, nama_pemilik_tanah, jenis_bukti_tanah, no_id_pemilik_tanah, file_bukti_tanah, luas_tanah, nama_bangunan, alamat_bangunan, kecamatan, kelurahan, koordinat_lat, koordinat_lon, jumlah_lantai, total_luas_lantai, foto_bangunan, no_imb_pbg, file_imb_pbg, no_slf, file_slf, status_verifikasi, riwayat_pemeliharaan_terakhir) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            $nomor_bukti = ($_POST['punya_bukti_tanah'] == 'Ada') ? $_POST['nomor_bukti'] : null;
            $no_imb_pbg = ($_POST['punya_imb'] == 'Ada') ? $_POST['nomor_imb'] : null;
            $no_slf = ($_POST['punya_slf'] == 'Ada') ? $_POST['nomor_slf'] : null;
            $nama_kecamatan = $_POST['nama_kecamatan'];
            $nama_kelurahan = $_POST['nama_kelurahan'];
            $status_verifikasi = 'Belum Diverifikasi';
            
            $stmt->bind_param("issssssdssssddissssssss", 
                $user_id, $_POST['tanggal_pendataan'], $_POST['jenis_kepemilikan_tanah'], 
                $_POST['nama_pemilik_tanah'], $jenis_bukti_tanah_final, $nomor_bukti, 
                $file_bukti_tanah_path, $_POST['luas_tanah'], $_POST['nama_bangunan'], 
                $_POST['alamat_bangunan'], $nama_kecamatan, $nama_kelurahan, 
                $_POST['koordinat_lat'], $_POST['koordinat_lon'], $_POST['jumlah_lantai'], 
                $_POST['total_luas_lantai'], $foto_bangunan_path, $no_imb_pbg, 
                $file_imb_pbg_path, $no_slf, $file_slf_path, $status_verifikasi,
                $riwayat_pemeliharaan_json
            );

            if ($stmt->execute()) {
                $success = "Data bangunan berhasil disimpan!";
                $new_building_id = $conn->insert_id;

                // --- LOGGING: ADD ACTION ---
                $log_data = [
                    'tanggal_pendataan' => $_POST['tanggal_pendataan'],
                    'jenis_kepemilikan_tanah' => $_POST['jenis_kepemilikan_tanah'],
                    'nama_pemilik_tanah' => $_POST['nama_pemilik_tanah'],
                    'jenis_bukti_tanah' => $jenis_bukti_tanah_final,
                    'no_id_pemilik_tanah' => $nomor_bukti,
                    'file_bukti_tanah' => $file_bukti_tanah_path,
                    'luas_tanah' => $_POST['luas_tanah'],
                    'nama_bangunan' => $_POST['nama_bangunan'],
                    'alamat_bangunan' => $_POST['alamat_bangunan'],
                    'kecamatan' => $nama_kecamatan,
                    'kelurahan' => $nama_kelurahan,
                    'koordinat_lat' => $_POST['koordinat_lat'],
                    'koordinat_lon' => $_POST['koordinat_lon'],
                    'jumlah_lantai' => $_POST['jumlah_lantai'],
                    'total_luas_lantai' => $_POST['total_luas_lantai'],
                    'foto_bangunan' => $foto_bangunan_path,
                    'no_imb_pbg' => $no_imb_pbg,
                    'file_imb_pbg' => $file_imb_pbg_path,
                    'no_slf' => $no_slf,
                    'file_slf' => $file_slf_path,
                    'status_verifikasi' => $status_verifikasi,
                    'riwayat_pemeliharaan_terakhir' => $riwayat_pemeliharaan_json
                ];
                $log_stmt = $conn->prepare("INSERT INTO bg_activity_log (user_id, action_type, entity_type, entity_id, new_data) VALUES (?, 'add', 'bangunan', ?, ?)");
                $log_new_data_json = json_encode($log_data);
                $log_stmt->bind_param("iis", $user_id, $new_building_id, $log_new_data_json);
                $log_stmt->execute();
                $log_stmt->close();
                // --- END LOGGING ---

                 $formData = []; 
                 $_SESSION['temp_files'] = []; 
                 $_SESSION['temp_files_meta'] = [];
            } else {
                $error = "Gagal menyimpan data ke database: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

function display_upload_status($fileKey) {
    if (isset($_SESSION['temp_files'][$fileKey]) && isset($_SESSION['temp_files_meta'][$fileKey])) {
        $filePath = $_SESSION['temp_files'][$fileKey];
        $fileMeta = $_SESSION['temp_files_meta'][$fileKey];
        $fileName = $fileMeta['name'];
        $fileType = $fileMeta['type'];
        $webPath = '/putr/bangunan/' . str_replace('../', '', $filePath);
        echo '<div class="file-upload-info" id="info-'.$fileKey.'">';
        if (strpos($fileType, 'image/') === 0) {
            echo '<img src="'.$webPath.'" alt="Preview '.htmlspecialchars($fileName).'" class="file-preview-img"/>';
        }
        echo '<span class="file-name">'.htmlspecialchars($fileName).'</span> (sudah diupload)
            <a href="#" class="ganti-file" data-target="'.$fileKey.'">Ganti</a>
        </div>';
    }
}
?>
<?php include "../includes/header.php"; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map { height: 400px; width: 100%; border-radius: 8px; margin-top: 10px; }
    .header-selebaran { z-index: 1020; }
    .file-upload-info { background-color: #e9f7ef; border: 1px solid #a3d9b8; padding: 10px; border-radius: 5px; margin-top: 5px; }
    .file-upload-info .file-name { font-weight: bold; color: #1d643b; display: block; margin-bottom: 5px; }
    .file-upload-info a.ganti-file { margin-left: 10px; color: #007bff; text-decoration: underline; }
    .file-preview-img { max-width: 150px; max-height: 100px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; display: block; }
    .js-file-preview { margin-top: 10px; }
    .file-validation-error { color: #dc3545; font-size: 0.875em; font-weight: bold; margin-top: 5px; display: block; }
    .form-section { border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px; }
    .form-section legend { font-weight:700; color:#2176d2; }
    .conditional-field { margin-top:16px; border-top: 1px solid #ddd; padding-top:16px; }
</style>

<div class="container" style="max-width:850px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0;">Formulir Input Data Bangunan Gedung</h2>
        <a href="/putr/bangunan/skpd/daftar_bangunan" class="btn btn-secondary">Batal</a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-sukses"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-gagal"><?= $error ?></div>
    <?php endif; ?>

    <form id="formBangunan" method="POST" enctype="multipart/form-data" novalidate>
        <fieldset class="form-section">
            <legend>Informasi Umum</legend>
            <div class="form-group">
                <label for="tanggal_pendataan">Tanggal Pendataan</label>
                <input type="date" name="tanggal_pendataan" id="tanggal_pendataan" class="form-control" required value="<?= htmlspecialchars($formData['tanggal_pendataan'] ?? date('Y-m-d')) ?>">
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Data Profil Tanah</legend>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Jenis Kepemilikan</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Perorangan" required <?= (($formData['jenis_kepemilikan_tanah'] ?? '') == 'Perorangan') ? 'checked' : '' ?>>Perorangan</label>
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Badan Hukum" <?= (($formData['jenis_kepemilikan_tanah'] ?? '') == 'Badan Hukum') ? 'checked' : '' ?>>Badan Hukum</label>
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Negara" <?= (($formData['jenis_kepemilikan_tanah'] ?? '') == 'Negara') ? 'checked' : '' ?>>Negara</label>
                </div>
            </div>
            <div class="form-group">
                <label for="nama_pemilik_tanah">Nama Pemilik Tanah</label>
                <input type="text" id="nama_pemilik_tanah" name="nama_pemilik_tanah" class="form-control" required value="<?= htmlspecialchars($formData['nama_pemilik_tanah'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Apakah Memiliki Bukti Kepemilikan Tanah?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_bukti_tanah" value="Ada" <?= (($formData['punya_bukti_tanah'] ?? '') == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_bukti_tanah" value="Tidak Ada" <?= (($formData['punya_bukti_tanah'] ?? 'Tidak Ada') == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
            <div id="kolom-bukti-tanah" class="conditional-field" style="display:none;">
                <div class="form-group" style="margin-bottom:12px;">
                    <label>Jenis Bukti</label>
                    <div class="radio-inline-group">
                        <label><input type="radio" name="jenis_bukti_tanah" value="SHM" <?= (($formData['jenis_bukti_tanah'] ?? '') == 'SHM') ? 'checked' : '' ?>>SHM</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="HGB" <?= (($formData['jenis_bukti_tanah'] ?? '') == 'HGB') ? 'checked' : '' ?>>HGB</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Letter C" <?= (($formData['jenis_bukti_tanah'] ?? '') == 'Letter C') ? 'checked' : '' ?>>Letter C</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Girik" <?= (($formData['jenis_bukti_tanah'] ?? '') == 'Girik') ? 'checked' : '' ?>>Girik</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Lainnya" <?= (($formData['jenis_bukti_tanah'] ?? '') == 'Lainnya') ? 'checked' : '' ?>>Lainnya</label>
                    </div>
                </div>
                <!-- Input field baru untuk Jenis Bukti Lainnya -->
                <div id="kolom-bukti-lainnya" class="form-group conditional-field" style="display:none; border-top:none; padding-top:0;">
                    <label for="jenis_bukti_lainnya">Sebutkan Jenis Bukti Lainnya</label>
                    <input type="text" id="jenis_bukti_lainnya" name="jenis_bukti_lainnya" class="form-control" value="<?= htmlspecialchars($formData['jenis_bukti_lainnya'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="nomor_bukti">Nomor Bukti / ID Pemilik Tanah</label>
                    <input type="text" id="nomor_bukti" name="nomor_bukti" class="form-control" value="<?= htmlspecialchars($formData['nomor_bukti'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_bukti">Upload File Bukti Kepemilikan (HANYA PDF, maks 1MB)</label>
                    <?php display_upload_status('file_bukti'); ?>
                    <div id="preview-container-file_bukti" class="js-file-preview"></div>
                    <input type="file" id="file_bukti" name="file_bukti" class="form-control" accept="application/pdf" data-max-size="1048576" <?= isset($_SESSION['temp_files']['file_bukti']) ? 'style="display:none;"' : '' ?>>
                </div>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Luas Tanah (m<sup>2</sup>)</label>
                <input type="number" name="luas_tanah" class="form-control" min="0" step="any" required value="<?= htmlspecialchars($formData['luas_tanah'] ?? '') ?>">
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Data Profil & Dokumen Bangunan</legend>
            <div class="form-group">
                <label>Nama Bangunan Gedung</label>
                <input type="text" name="nama_bangunan" class="form-control" required value="<?= htmlspecialchars($formData['nama_bangunan'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Alamat Bangunan Gedung</label>
                <textarea name="alamat_bangunan" class="form-control" required><?= htmlspecialchars($formData['alamat_bangunan'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="kecamatan">Kecamatan</label>
                <select name="kecamatan" id="kecamatan" class="form-control" required data-selected="<?= htmlspecialchars($formData['kecamatan'] ?? '') ?>">
                    <option value="">-- Pilih Kecamatan --</option>
                </select>
                <input type="hidden" name="nama_kecamatan" id="nama_kecamatan" value="<?= htmlspecialchars($formData['nama_kecamatan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="kelurahan">Kelurahan</label>
                <select name="kelurahan" id="kelurahan" class="form-control" required disabled data-selected="<?= htmlspecialchars($formData['kelurahan'] ?? '') ?>">
                    <option value="">Pilih Kecamatan Terlebih Dahulu</option>
                </select>
                <input type="hidden" name="nama_kelurahan" id="nama_kelurahan" value="<?= htmlspecialchars($formData['nama_kelurahan'] ?? '') ?>">
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Tentukan Lokasi di Peta</legend>
            <p style="text-align:left; font-size: 0.9em; color: #555;">Geser penanda atau klik pada peta untuk memilih lokasi yang akurat.</p>
            <div id="map"></div>
            <div class="form-group" style="margin-top:16px;">
                <label for="koordinat_lat">Latitude</label>
                <input type="text" id="koordinat_lat" name="koordinat_lat" class="form-control" placeholder="Latitude akan terisi otomatis" required readonly value="<?= htmlspecialchars($formData['koordinat_lat'] ?? '') ?>">
            </div>
             <div class="form-group" style="margin-top:8px;">
                <label for="koordinat_lon">Longitude</label>
                <input type="text" id="koordinat_lon" name="koordinat_lon" class="form-control" placeholder="Longitude akan terisi otomatis" required readonly value="<?= htmlspecialchars($formData['koordinat_lon'] ?? '') ?>">
            </div>
        </fieldset>
        
        <fieldset class="form-section">
            <legend>Data Teknis & Legalitas Bangunan</legend>
            <div class="form-group">
                <label>Jumlah Lantai</label>
                <input type="number" name="jumlah_lantai" class="form-control" placeholder="Contoh: 3" min="1" required value="<?= htmlspecialchars($formData['jumlah_lantai'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Total Luas Bangunan (m<sup>2</sup>)</label>
                <input type="number" name="total_luas_lantai" class="form-control" min="0" step="any" required value="<?= htmlspecialchars($formData['total_luas_lantai'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label for="foto_bangunan">Upload Foto Kondisi Bangunan (JPG/PNG, maks 3MB)</label>
                <?php display_upload_status('foto_bangunan'); ?>
                <div id="preview-container-foto_bangunan" class="js-file-preview"></div>
                <!-- Perubahan: data-max-size diubah ke 3MB -->
                <input type="file" id="foto_bangunan" name="foto_bangunan" class="form-control" accept="image/png, image/jpeg" data-max-size="3145728" <?= isset($_SESSION['temp_files']['foto_bangunan']) ? 'style="display:none;"' : '' ?>>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Apakah Memiliki IMB / PBG?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_imb" value="Ada" required <?= (($formData['punya_imb'] ?? '') == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_imb" value="Tidak Ada" <?= (($formData['punya_imb'] ?? 'Tidak Ada') == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
            <div id="kolom-imb" class="conditional-field" style="display:none;">
                <div class="form-group">
                    <label for="nomor_imb">Nomor IMB / PBG</label>
                    <input type="text" id="nomor_imb" name="nomor_imb" class="form-control" value="<?= htmlspecialchars($formData['nomor_imb'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_imb">Upload File IMB / PBG (HANYA PDF, maks 1MB)</label>
                    <?php display_upload_status('file_imb'); ?>
                    <div id="preview-container-file_imb" class="js-file-preview"></div>
                    <input type="file" id="file_imb" name="file_imb" class="form-control" accept="application/pdf" data-max-size="1048576" <?= isset($_SESSION['temp_files']['file_imb']) ? 'style="display:none;"' : '' ?>>
                </div>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Apakah Memiliki SLF?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_slf" value="Ada" required <?= (($formData['punya_slf'] ?? '') == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_slf" value="Tidak Ada" <?= (($formData['punya_slf'] ?? 'Tidak Ada') == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
             <div id="kolom-slf" class="conditional-field" style="display:none;">
                <div class="form-group">
                    <label for="nomor_slf">Nomor SLF</label>
                    <input type="text" id="nomor_slf" name="nomor_slf" class="form-control" value="<?= htmlspecialchars($formData['nomor_slf'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_slf">Upload File SLF (HANYA PDF, maks 1MB)</label>
                    <?php display_upload_status('file_slf'); ?>
                    <div id="preview-container-file_slf" class="js-file-preview"></div>
                    <input type="file" id="file_slf" name="file_slf" class="form-control" accept="application/pdf" data-max-size="1048576" <?= isset($_SESSION['temp_files']['file_slf']) ? 'style="display:none;"' : '' ?>>
                </div>
            </div>
        </fieldset>

        <!-- Fieldset baru untuk Riwayat Pemeliharaan -->
        <fieldset class="form-section">
            <legend>Riwayat Pemeliharaan/Rehabilitasi</legend>
            <div class="form-group">
                <label>Apakah ada riwayat terakhir pemeliharaan/rehabilitasi?</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="punya_riwayat_pemeliharaan" value="Ada" required <?= (($formData['punya_riwayat_pemeliharaan'] ?? '') == 'Ada') ? 'checked' : '' ?>> Ada</label>
                    <label><input type="radio" name="punya_riwayat_pemeliharaan" value="Tidak Ada" <?= (($formData['punya_riwayat_pemeliharaan'] ?? 'Tidak Ada') == 'Tidak Ada') ? 'checked' : '' ?>> Tidak Ada</label>
                </div>
            </div>
            <div id="kolom-riwayat-pemeliharaan" class="conditional-field" style="display:none;">
                <div class="form-group">
                    <label for="nama_kegiatan_pemeliharaan">Nama Kegiatan</label>
                    <input type="text" id="nama_kegiatan_pemeliharaan" name="nama_kegiatan_pemeliharaan" class="form-control" value="<?= htmlspecialchars($formData['nama_kegiatan_pemeliharaan'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="tahun_anggaran_pemeliharaan">Tahun Anggaran</label>
                    <input type="number" id="tahun_anggaran_pemeliharaan" name="tahun_anggaran_pemeliharaan" class="form-control" placeholder="Contoh: 2024" min="1900" max="2100" value="<?= htmlspecialchars($formData['tahun_anggaran_pemeliharaan'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="nilai_anggaran_pemeliharaan">Nilai Anggaran (Rp)</label>
                    <input type="number" id="nilai_anggaran_pemeliharaan" name="nilai_anggaran_pemeliharaan" class="form-control" placeholder="Contoh: 50000000" min="0" value="<?= htmlspecialchars($formData['nilai_anggaran_pemeliharaan'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-success" style="padding: 12px 20px; font-size: 1.1em;">Simpan Data Bangunan</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupInstantValidation(inputId) {
        const fileInput = document.getElementById(inputId);
        if (!fileInput) return;
        const allowedTypes = fileInput.getAttribute('accept').split(',').map(t => t.trim());
        const maxSize = parseInt(fileInput.getAttribute('data-max-size'), 10);
        fileInput.addEventListener('change', function(event) {
            const previewContainer = document.getElementById('preview-container-' + inputId);
            previewContainer.innerHTML = '';
            const [file] = event.target.files;
            if (!file) return;
            if (!allowedTypes.includes(file.type)) {
                const errorMsg = document.createElement('span');
                errorMsg.className = 'file-validation-error';
                errorMsg.textContent = 'Tipe file salah. Harap unggah file ' + allowedTypes.join(', ').replace(/application\/|image\//g, '').toUpperCase() + '.';
                previewContainer.appendChild(errorMsg);
                fileInput.value = '';
                return;
            }
            if (file.size > maxSize) {
                const errorMsg = document.createElement('span');
                errorMsg.className = 'file-validation-error';
                const maxSizeMb = (maxSize / 1024 / 1024).toFixed(1);
                errorMsg.textContent = 'Ukuran file terlalu besar. Maksimal ' + maxSizeMb + ' MB.';
                previewContainer.appendChild(errorMsg);
                fileInput.value = '';
                return;
            }
            if (file.type.startsWith('image/')) {
                const previewImg = document.createElement('img');
                previewImg.classList.add('file-preview-img');
                previewImg.src = URL.createObjectURL(file);
                previewContainer.appendChild(previewImg);
            } else {
                const fileInfo = document.createElement('span');
                fileInfo.textContent = 'File terpilih: ' + file.name;
                previewContainer.appendChild(fileInfo);
            }
        });
    }

    setupInstantValidation('foto_bangunan');
    setupInstantValidation('file_imb');
    setupInstantValidation('file_slf');
    setupInstantValidation('file_bukti');

    document.querySelectorAll('.ganti-file').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.dataset.target;
            const fileInput = document.getElementById(targetId);
            const infoDiv = document.getElementById('info-' + targetId);
            if (fileInput) fileInput.style.display = 'block';
            if (infoDiv) infoDiv.style.display = 'none';
            const previewContainer = document.getElementById('preview-container-' + targetId);
            if (previewContainer) previewContainer.innerHTML = '';
        });
    });

    const kecamatanSelect = document.getElementById('kecamatan');
    const kelurahanSelect = document.getElementById('kelurahan');
    const namaKecamatanInput = document.getElementById('nama_kecamatan');
    const namaKelurahanInput = document.getElementById('nama_kelurahan');
    const KOTA_ID = '3278';

    async function loadKecamatan() {
        try {
            const response = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${KOTA_ID}.json`);
            if (!response.ok) throw new Error('Gagal memuat data kecamatan');
            const districts = await response.json();
            kecamatanSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
            districts.forEach(district => {
                const option = document.createElement('option');
                option.value = district.id;
                option.textContent = district.name;
                kecamatanSelect.appendChild(option);
            });
            const selectedKecamatan = kecamatanSelect.getAttribute('data-selected');
            if (selectedKecamatan) {
                kecamatanSelect.value = selectedKecamatan;
                kecamatanSelect.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            console.error(error);
            kecamatanSelect.innerHTML = '<option value="">Gagal memuat data</option>';
        }
    }

    async function loadKelurahan(kecamatanId) {
        if (!kecamatanId) {
            kelurahanSelect.innerHTML = '<option value="">Pilih Kecamatan Terlebih Dahulu</option>';
            kelurahanSelect.disabled = true;
            namaKelurahanInput.value = '';
            return;
        }
        kelurahanSelect.disabled = true;
        kelurahanSelect.innerHTML = '<option value="">Memuat kelurahan...</option>';
        try {
            const response = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${kecamatanId}.json`);
            if (!response.ok) throw new Error('Gagal memuat data kelurahan');
            const villages = await response.json();
            kelurahanSelect.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
            villages.forEach(village => {
                const option = document.createElement('option');
                option.value = village.id;
                option.textContent = village.name;
                kelurahanSelect.appendChild(option);
            });
            kelurahanSelect.disabled = false;
            const selectedKelurahan = kelurahanSelect.getAttribute('data-selected');
            if (selectedKelurahan) {
                kelurahanSelect.value = selectedKelurahan;
                kelurahanSelect.dispatchEvent(new Event('change'));
            }
        } catch (error) {
            console.error(error);
            kelurahanSelect.innerHTML = '<option value="">Gagal memuat data</option>';
        }
    }

    kecamatanSelect.addEventListener('change', () => {
        const selectedOption = kecamatanSelect.options[kecamatanSelect.selectedIndex];
        namaKecamatanInput.value = selectedOption.value ? selectedOption.text : '';
        loadKelurahan(kecamatanSelect.value);
    });
    
    kelurahanSelect.addEventListener('change', () => {
        const selectedOption = kelurahanSelect.options[kelurahanSelect.selectedIndex];
        namaKelurahanInput.value = selectedOption.value ? selectedOption.text : '';
    });

    loadKecamatan();

    function setupConditionalDisplay(radioGroupName, targetElementId, additionalCondition = null) {
        const radios = document.querySelectorAll(`input[name="${radioGroupName}"]`);
        const targetElement = document.getElementById(targetElementId);
        function toggleVisibility() {
            let isVisible = false;
            if (additionalCondition) {
                isVisible = additionalCondition();
            } else {
                const checkedRadio = document.querySelector(`input[name="${radioGroupName}"]:checked`);
                if (checkedRadio) {
                    isVisible = checkedRadio.value === 'Ada';
                }
            }
            targetElement.style.display = isVisible ? 'block' : 'none';
        }
        radios.forEach(radio => radio.addEventListener('change', toggleVisibility));
        toggleVisibility(); // Initial check
    }

    // Setup untuk field kondisional
    setupConditionalDisplay('punya_bukti_tanah', 'kolom-bukti-tanah');
    setupConditionalDisplay('punya_imb', 'kolom-imb');
    setupConditionalDisplay('punya_slf', 'kolom-slf');
    setupConditionalDisplay('punya_riwayat_pemeliharaan', 'kolom-riwayat-pemeliharaan');
    
    // Setup untuk field "Jenis Bukti Lainnya"
    const jenisBuktiRadios = document.querySelectorAll('input[name="jenis_bukti_tanah"]');
    const kolomBuktiLainnya = document.getElementById('kolom-bukti-lainnya');
    function toggleJenisBuktiLainnya() {
        const isLainnyaChecked = document.querySelector('input[name="jenis_bukti_tanah"][value="Lainnya"]').checked;
        kolomBuktiLainnya.style.display = isLainnyaChecked ? 'block' : 'none';
    }
    jenisBuktiRadios.forEach(radio => radio.addEventListener('change', toggleJenisBuktiLainnya));
    toggleJenisBuktiLainnya(); // Initial check

    const latInput = document.getElementById('koordinat_lat');
    const lonInput = document.getElementById('koordinat_lon');
    const initialLat = parseFloat(latInput.value) || -7.3271;
    const initialLon = parseFloat(lonInput.value) || 108.2199;
    const map = L.map('map').setView([initialLat, initialLon], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    const marker = L.marker([initialLat, initialLon], { draggable: true }).addTo(map);
    function updateCoordinates(lat, lon) {
        latInput.value = lat.toFixed(6);
        lonInput.value = lon.toFixed(6);
    }
    if (!latInput.value) {
        updateCoordinates(initialLat, initialLon);
    }
    marker.on('dragend', function (e) {
        const latlng = e.target.getLatLng();
        updateCoordinates(latlng.lat, latlng.lng);
    });
    map.on('click', function(e) {
        const latlng = e.latlng;
        marker.setLatLng(latlng);
        updateCoordinates(latlng.lat, latlng.lng);
    });
});
</script>
<?php if (!empty($success)): ?>
<script>
    alert('<?= addslashes(htmlspecialchars($success)) ?>');
    window.location.href = '/putr/bangunan/skpd/daftar_bangunan';
</script>
<?php endif; ?>
