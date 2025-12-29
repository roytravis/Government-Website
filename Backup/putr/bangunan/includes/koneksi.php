<?php
// Koneksi ke database MySQL
$host = "localhost";
$user = "dputr_dputr";
$pass = "W@Oj8R!{kVVN";
$db   = "dputr_wp_database";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi, jika gagal tampilkan pesan error
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}


?>
