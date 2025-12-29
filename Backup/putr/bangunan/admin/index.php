<?php
/**
 * File ini berfungsi sebagai gerbang masuk untuk direktori /admin.
 * Tujuannya adalah untuk langsung mengarahkan pengguna ke halaman dashboard utama admin.
 * Ini mencegah server menampilkan daftar file (directory listing) dan memastikan
 * alur navigasi yang benar.
 */

// Langsung arahkan ke halaman dashboard admin.
header('Location: dashboard.php');
exit;
