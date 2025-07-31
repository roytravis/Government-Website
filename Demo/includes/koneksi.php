<?php
// Koneksi ke database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db   = "wp_database";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi, jika gagal tampilkan pesan error
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// Jika butuh, Anda bisa echo pesan sukses (tapi biasanya cukup diam saja di production)
?>
