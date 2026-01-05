<?php
// Menggunakan __DIR__ untuk path yang lebih andal
require __DIR__ . "/includes/koneksi.php";

// Query jumlah bangunan per kecamatan
$sqlKec = "SELECT kecamatan, COUNT(*) AS jumlah FROM bg_data_bangunan GROUP BY kecamatan";
$resultKec = $conn->query($sqlKec);
$dataKec = [];
if ($resultKec) {
    while ($row = $resultKec->fetch_assoc()) {
        $dataKec[] = $row;
    }
}

// Query jumlah status verifikasi
$sqlStat = "SELECT status_verifikasi, COUNT(*) AS jumlah FROM bg_data_bangunan GROUP BY status_verifikasi";
$resultStat = $conn->query($sqlStat);
$dataStat = [];
if ($resultStat) {
    while ($row = $resultStat->fetch_assoc()) {
        // Memastikan status tidak null atau kosong sebelum dijadikan key
        if (!empty($row['status_verifikasi'])) {
            $dataStat[$row['status_verifikasi']] = $row['jumlah'];
        }
    }
}

// Gabung data
$response = [
    "kecamatan" => $dataKec,
    "verifikasi" => $dataStat
];

// Set header ke application/json sebelum output
header('Content-Type: application/json');
echo json_encode($response);

// Tutup koneksi
$conn->close();

// Perbaikan: Menghapus kurung kurawal tambahan yang menyebabkan error
?>
