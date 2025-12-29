<?php
/**
 * File ini berfungsi sebagai gerbang masuk untuk direktori /skpd.
 * Tujuannya adalah untuk langsung mengarahkan pengguna ke halaman dashboard utama skpd.
 * Ini mencegah server menampilkan daftar file (directory listing) dan memastikan
 * alur navigasi yang benar.
 */

// Langsung arahkan ke halaman dashboard skpd.
header('Location: ../');
exit;
