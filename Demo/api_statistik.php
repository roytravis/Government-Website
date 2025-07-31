<?php
include "koneksi.php";

// Query jumlah bangunan per kecamatan
$sqlKec = "SELECT kecamatan, COUNT(*) AS jumlah FROM bg_data_bangunan GROUP BY kecamatan";
$resultKec = $conn->query($sqlKec);
$dataKec = [];
while ($row = $resultKec->fetch_assoc()) {
    $dataKec[] = $row;
}

// Query jumlah status verifikasi
$sqlStat = "SELECT status_verifikasi, COUNT(*) AS jumlah FROM bg_data_bangunan GROUP BY status_verifikasi";
$resultStat = $conn->query($sqlStat);
$dataStat = [];
while ($row = $resultStat->fetch_assoc()) {
    $dataStat[$row['status_verifikasi']] = $row['jumlah'];
}

// Gabung data
$response = [
    "kecamatan" => $dataKec,
    "verifikasi" => $dataStat
];

header('Content-Type: application/json');
echo json_encode($response);
