<?php
// Selalu mulai session di awal
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Alihkan pengguna ke halaman login
header("Location: ../login.php");
exit;
?>