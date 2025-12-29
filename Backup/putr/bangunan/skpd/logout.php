<?php
// Selalu mulai session di awal
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Alihkan pengguna ke halaman login
// Perubahan: Menggunakan path absolut untuk redirect
header("Location: /putr/bangunan/login");
exit;
?>
