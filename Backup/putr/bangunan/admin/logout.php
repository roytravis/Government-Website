<?php
// (admin/logout.php)

// Selalu mulai session di awal untuk mengaksesnya
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Alihkan pengguna ke halaman login yang berada satu level di atas direktori admin
// Perubahan: Menggunakan path absolut untuk redirect
header("Location: /putr/bangunan/login");
exit;
?>
