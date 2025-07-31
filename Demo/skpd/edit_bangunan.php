<?php
session_start();
require "../includes/koneksi.php";

// Validasi Akses Pengguna
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    header("Location: ../login.php");
    exit;
}

// Validasi ID Bangunan dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: daftar_bangunan.php?status=error&pesan=Permintaan tidak valid.");
    exit;
}

$id_bangunan = $_GET['id'];
$user_id = $_SESSION['user_id'];
$error = $success = "";
$errorMessages = [];

// PROSES 1: Jika Form Disubmit (METHOD POST) untuk Update Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $formData = $_POST;
    // (Tambahkan validasi input di sini jika perlu, mirip dengan input_bangunan.php)

    // Ambil path file yang sudah ada dari hidden input
    $existing_files = [
        'foto_bangunan' => $_POST['existing_foto_bangunan'],
        'file_bukti_tanah' => $_POST['existing_file_bukti_tanah'],
        'file_imb_pbg' => $_POST['existing_file_imb_pbg'],
        'file_slf' => $_POST['existing_file_slf']
    ];

    $uploaded_paths = $existing_files;
    $target_dir = "../uploads/";

    // Fungsi untuk menangani upload file baru
    function handleFileUpload($fileKey, $target_dir, $old_file_path) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
            // (Tambahkan validasi tipe dan ukuran file di sini)
            $extension = strtolower(pathinfo($_FILES[$fileKey]["name"], PATHINFO_EXTENSION));
            $new_filename = str_replace('_', '-', $fileKey) . '-' . uniqid() . '.' . $extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], $target_file)) {
                // Hapus file lama jika ada
                if (!empty($old_file_path) && file_exists("../" . $old_file_path)) {
                    unlink("../" . $old_file_path);
                }
                return str_replace('../', '', $target_file); // Return path baru
            }
        }
        return $old_file_path; // Return path lama jika tidak ada file baru
    }

    // Proses setiap file
    $uploaded_paths['foto_bangunan'] = handleFileUpload('foto_bangunan', $target_dir, $existing_files['foto_bangunan']);
    $uploaded_paths['file_bukti_tanah'] = handleFileUpload('file_bukti', $target_dir, $existing_files['file_bukti_tanah']);
    $uploaded_paths['file_imb_pbg'] = handleFileUpload('file_imb', $target_dir, $existing_files['file_imb_pbg']);
    $uploaded_paths['file_slf'] = handleFileUpload('file_slf', $target_dir, $existing_files['file_slf']);

    // Update ke database
    $sql_update = "UPDATE bg_data_bangunan SET 
        tanggal_pendataan=?, jenis_kepemilikan_tanah=?, nama_pemilik_tanah=?, jenis_bukti_tanah=?, no_id_pemilik_tanah=?, file_bukti_tanah=?, luas_tanah=?, 
        nama_bangunan=?, alamat_bangunan=?, kecamatan=?, kelurahan=?, koordinat_lat=?, koordinat_lon=?, 
        jumlah_lantai=?, total_luas_lantai=?, foto_bangunan=?, no_imb_pbg=?, file_imb_pbg=?, no_slf=?, file_slf=?, status_verifikasi='Belum Diverifikasi'
        WHERE id=? AND user_id=?";

    $stmt_update = $conn->prepare($sql_update);

    $jenis_bukti_tanah = ($_POST['punya_bukti_tanah'] == 'Ada') ? $_POST['jenis_bukti_tanah'] : null;
    $nomor_bukti = ($_POST['punya_bukti_tanah'] == 'Ada') ? $_POST['nomor_bukti'] : null;
    $no_imb_pbg = ($_POST['punya_imb'] == 'Ada') ? $_POST['nomor_imb'] : null;
    $no_slf = ($_POST['punya_slf'] == 'Ada') ? $_POST['nomor_slf'] : null;

    $stmt_update->bind_param("ssssssdssssddissssssii",
        $_POST['tanggal_pendataan'], $_POST['jenis_kepemilikan_tanah'], $_POST['nama_pemilik_tanah'], $jenis_bukti_tanah, $nomor_bukti, $uploaded_paths['file_bukti_tanah'], $_POST['luas_tanah'],
        $_POST['nama_bangunan'], $_POST['alamat_bangunan'], $_POST['nama_kecamatan'], $_POST['nama_kelurahan'], $_POST['koordinat_lat'], $_POST['koordinat_lon'],
        $_POST['jumlah_lantai'], $_POST['total_luas_lantai'], $uploaded_paths['foto_bangunan'], $no_imb_pbg, $uploaded_paths['file_imb_pbg'], $no_slf, $uploaded_paths['file_slf'],
        $id_bangunan, $user_id
    );

    if ($stmt_update->execute()) {
        header("Location: daftar_bangunan.php?status=sukses&pesan=Data bangunan berhasil diperbarui.");
        exit;
    } else {
        $error = "Gagal memperbarui data: " . $stmt_update->error;
    }
    $stmt_update->close();
}

// PROSES 2: Ambil Data untuk Ditampilkan di Form (METHOD GET)
$sql_select = "SELECT * FROM bg_data_bangunan WHERE id = ? AND user_id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("ii", $id_bangunan, $user_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
if ($result->num_rows === 0) {
    header("Location: daftar_bangunan.php?status=error&pesan=Data tidak ditemukan atau Anda tidak punya hak akses.");
    exit;
}
$data = $result->fetch_assoc();
$stmt_select->close();

// Fungsi untuk menampilkan status upload file yang sudah ada
function display_existing_file($file_path, $file_key) {
    if (!empty($file_path)) {
        $fileName = basename($file_path);
        echo '<div class="file-upload-info" id="info-'.$file_key.'">';
        echo '<span class="file-name">File saat ini: <a href="../'.htmlspecialchars($file_path).'" target="_blank">'.htmlspecialchars($fileName).'</a></span>';
        echo '</div>';
    }
}
?>

<?php include "../includes/header.php"; ?>
<!-- Integrasi LeafletJS dan CSS tambahan -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map { height: 400px; width: 100%; border-radius: 8px; margin-top: 10px; }
    .file-upload-info { background-color: #e9f7ef; border: 1px solid #a3d9b8; padding: 10px; border-radius: 5px; margin-top: 5px; margin-bottom: 10px;}
    .file-upload-info .file-name a { font-weight: bold; color: #1d643b; }
    .form-group small { color: #6c757d; }
</style>

<div class="container" style="max-width:850px;">
    <h2 style="margin-bottom:24px;">Edit Data Bangunan Gedung</h2>
    <?php if ($error): ?>
    <div class="alert alert-gagal"><?= $error ?></div>
    <?php endif; ?>

    <!-- Formulir diisi dengan data dari database -->
    <form id="formBangunan" method="POST" enctype="multipart/form-data" novalidate>
        <!-- Informasi Umum -->
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Informasi Umum</legend>
            <div class="form-group">
                <label for="tanggal_pendataan">Tanggal Pendataan</label>
                <input type="date" name="tanggal_pendataan" id="tanggal_pendataan" class="form-control" required value="<?= htmlspecialchars($data['tanggal_pendataan']) ?>">
            </div>
        </fieldset>

        <!-- Profil Tanah -->
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Data Profil Tanah</legend>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Jenis Kepemilikan</label>
                <div class="radio-inline-group">
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Perorangan" required <?= ($data['jenis_kepemilikan_tanah'] == 'Perorangan') ? 'checked' : '' ?>>Perorangan</label>
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Badan Hukum" <?= ($data['jenis_kepemilikan_tanah'] == 'Badan Hukum') ? 'checked' : '' ?>>Badan Hukum</label>
                    <label><input type="radio" name="jenis_kepemilikan_tanah" value="Negara" <?= ($data['jenis_kepemilikan_tanah'] == 'Negara') ? 'checked' : '' ?>>Negara</label>
                </div>
            </div>

            <div class="form-group">
                <label for="nama_pemilik_tanah">Nama Pemilik Tanah</label>
                <input type="text" id="nama_pemilik_tanah" name="nama_pemilik_tanah" class="form-control" required value="<?= htmlspecialchars($data['nama_pemilik_tanah']) ?>">
            </div>
            
            <?php $punya_bukti_tanah = !empty($data['jenis_bukti_tanah']) ? 'Ada' : 'Tidak Ada'; ?>
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
                        <label><input type="radio" name="jenis_bukti_tanah" value="SHM" <?= ($data['jenis_bukti_tanah'] == 'SHM') ? 'checked' : '' ?>>SHM</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="HGB" <?= ($data['jenis_bukti_tanah'] == 'HGB') ? 'checked' : '' ?>>HGB</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Letter C" <?= ($data['jenis_bukti_tanah'] == 'Letter C') ? 'checked' : '' ?>>Letter C</label>
                        <label><input type="radio" name="jenis_bukti_tanah" value="Girik" <?= ($data['jenis_bukti_tanah'] == 'Girik') ? 'checked' : '' ?>>Girik</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nomor_bukti">Nomor Bukti / ID Pemilik Tanah</label>
                    <input type="text" id="nomor_bukti" name="nomor_bukti" class="form-control" value="<?= htmlspecialchars($data['no_id_pemilik_tanah']) ?>">
                </div>

                <div class="form-group" style="margin-top:12px;">
                    <label for="file_bukti">Upload File Bukti Kepemilikan Baru (PDF, maks 1MB)</label>
                    <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                    <?php display_existing_file($data['file_bukti_tanah'], 'file_bukti'); ?>
                    <input type="file" id="file_bukti" name="file_bukti" class="form-control" accept="application/pdf">
                    <input type="hidden" name="existing_file_bukti_tanah" value="<?= htmlspecialchars($data['file_bukti_tanah']) ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:12px;">
                <label>Luas Tanah (m<sup>2</sup>)</label>
                <input type="number" name="luas_tanah" class="form-control" min="0" step="any" required value="<?= htmlspecialchars($data['luas_tanah']) ?>">
            </div>
        </fieldset>

        <!-- Data Profil Bangunan -->
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Data Profil & Dokumen Bangunan</legend>
            <div class="form-group">
                <label>Nama Bangunan Gedung</label>
                <input type="text" name="nama_bangunan" class="form-control" required value="<?= htmlspecialchars($data['nama_bangunan']) ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Alamat Bangunan Gedung</label>
                <textarea name="alamat_bangunan" class="form-control" required><?= htmlspecialchars($data['alamat_bangunan']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="kecamatan">Kecamatan</label>
                <select name="kecamatan" id="kecamatan" class="form-control" required data-selected-name="<?= htmlspecialchars($data['kecamatan']) ?>">
                    <option value="">-- Pilih Kecamatan --</option>
                </select>
                <input type="hidden" name="nama_kecamatan" id="nama_kecamatan" value="<?= htmlspecialchars($data['kecamatan']) ?>">
            </div>

            <div class="form-group">
                <label for="kelurahan">Kelurahan</label>
                <select name="kelurahan" id="kelurahan" class="form-control" required disabled data-selected-name="<?= htmlspecialchars($data['kelurahan']) ?>">
                    <option value="">Pilih Kecamatan Terlebih Dahulu</option>
                </select>
                <input type="hidden" name="nama_kelurahan" id="nama_kelurahan" value="<?= htmlspecialchars($data['kelurahan']) ?>">
            </div>
        </fieldset>

        <!-- Peta -->
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Tentukan Lokasi di Peta</legend>
            <div id="map"></div>
            <div class="form-group" style="margin-top:16px;">
                <label for="koordinat_lat">Latitude</label>
                <input type="text" id="koordinat_lat" name="koordinat_lat" class="form-control" required readonly value="<?= htmlspecialchars($data['koordinat_lat']) ?>">
            </div>
             <div class="form-group" style="margin-top:8px;">
                <label for="koordinat_lon">Longitude</label>
                <input type="text" id="koordinat_lon" name="koordinat_lon" class="form-control" required readonly value="<?= htmlspecialchars($data['koordinat_lon']) ?>">
            </div>
        </fieldset>
        
        <!-- Data Teknis -->
        <fieldset style="border:1.5px solid #e3e3e3; border-radius:8px; margin-bottom:24px; padding:20px 16px;">
            <legend style="font-weight:700; color:#2176d2;">Data Teknis & Legalitas Bangunan</legend>
            <div class="form-group">
                <label>Jumlah Lantai</label>
                <input type="number" name="jumlah_lantai" class="form-control" min="1" required value="<?= htmlspecialchars($data['jumlah_lantai']) ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Total Luas Bangunan (m<sup>2</sup>)</label>
                <input type="number" name="total_luas_lantai" class="form-control" min="0" step="any" required value="<?= htmlspecialchars($data['total_luas_lantai']) ?>">
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label for="foto_bangunan">Upload Foto Kondisi Bangunan Baru (JPG/PNG, maks 300KB)</label>
                <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                <?php display_existing_file($data['foto_bangunan'], 'foto_bangunan'); ?>
                <input type="file" id="foto_bangunan" name="foto_bangunan" class="form-control" accept="image/png, image/jpeg">
                <input type="hidden" name="existing_foto_bangunan" value="<?= htmlspecialchars($data['foto_bangunan']) ?>">
            </div>
            
            <?php $punya_imb = !empty($data['no_imb_pbg']) ? 'Ada' : 'Tidak Ada'; ?>
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
                    <input type="text" id="nomor_imb" name="nomor_imb" class="form-control" value="<?= htmlspecialchars($data['no_imb_pbg']) ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_imb">Upload File IMB / PBG Baru (PDF/JPG/PNG, maks 1MB)</label>
                    <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                    <?php display_existing_file($data['file_imb_pbg'], 'file_imb'); ?>
                    <input type="file" id="file_imb" name="file_imb" class="form-control" accept="application/pdf,image/png,image/jpeg">
                    <input type="hidden" name="existing_file_imb_pbg" value="<?= htmlspecialchars($data['file_imb_pbg']) ?>">
                </div>
            </div>

            <?php $punya_slf = !empty($data['no_slf']) ? 'Ada' : 'Tidak Ada'; ?>
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
                    <input type="text" id="nomor_slf" name="nomor_slf" class="form-control" value="<?= htmlspecialchars($data['no_slf']) ?>">
                </div>
                <div class="form-group" style="margin-top:12px;">
                    <label for="file_slf">Upload File SLF Baru (PDF/JPG/PNG, maks 1MB)</label>
                    <small>Kosongkan jika tidak ingin mengubah file yang sudah ada.</small>
                    <?php display_existing_file($data['file_slf'], 'file_slf'); ?>
                    <input type="file" id="file_slf" name="file_slf" class="form-control" accept="application/pdf,image/png,image/jpeg">
                    <input type="hidden" name="existing_file_slf" value="<?= htmlspecialchars($data['file_slf']) ?>">
                </div>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-success" style="padding: 12px 20px; font-size: 1.1em;">Simpan Perubahan</button>
        <a href="daftar_bangunan.php" class="btn btn-secondary" style="margin-left: 10px;">Batal</a>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menampilkan/menyembunyikan kolom kondisional
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

    // Pengaturan Peta Leaflet
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

    // Pengaturan Dropdown Wilayah
    const kecamatanSelect = document.getElementById('kecamatan');
    const kelurahanSelect = document.getElementById('kelurahan');
    const namaKecamatanInput = document.getElementById('nama_kecamatan');
    const namaKelurahanInput = document.getElementById('nama_kelurahan');
    const KOTA_ID = '3278';

    async function loadWilayah() {
        // Load Kecamatan
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

        // Load Kelurahan jika kecamatan sudah terpilih
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
        kelurahanSelect.innerHTML = '<option value="">Memuat...</option>';
        if (!kecamatanId) return;

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
