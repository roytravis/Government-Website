<?php
session_start();
require "../includes/koneksi.php";

// 1. Validasi Akses dan Input
// Pastikan user login sebagai SKPD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'skpd') {
    header("Location: ../login.php");
    exit;
}

// Pastikan ada ID bangunan yang dikirim
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: daftar_bangunan.php?status=error&pesan=ID tidak valid.");
    exit;
}

$id_bangunan = $_GET['id'];
$user_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // 2. Ambil Path File Sebelum Dihapus dari Database
    // Query ini juga memastikan user hanya bisa menghapus data miliknya sendiri
    $sql_select = "SELECT foto_bangunan, file_bukti_tanah, file_imb_pbg, file_slf FROM bg_data_bangunan WHERE id = ? AND user_id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("ii", $id_bangunan, $user_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows === 0) {
        // Jika data tidak ditemukan atau bukan milik user, gagalkan.
        throw new Exception("Data bangunan tidak ditemukan atau Anda tidak memiliki hak akses.");
    }

    $row = $result->fetch_assoc();
    $files_to_delete = [
        $row['foto_bangunan'],
        $row['file_bukti_tanah'],
        $row['file_imb_pbg'],
        $row['file_slf']
    ];
    $stmt_select->close();

    // 3. Hapus Record dari Database
    $sql_delete = "DELETE FROM bg_data_bangunan WHERE id = ? AND user_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $id_bangunan, $user_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Gagal menghapus data dari database.");
    }
    $stmt_delete->close();

    // 4. Hapus File Fisik dari Server
    foreach ($files_to_delete as $file) {
        // Pastikan path file tidak kosong dan file tersebut ada
        if (!empty($file) && file_exists("../" . $file)) {
            unlink("../" . $file);
        }
    }

    // Jika semua proses berhasil
    $conn->commit();
    header("Location: daftar_bangunan.php?status=sukses&pesan=Data bangunan berhasil dihapus.");

} catch (Exception $e) {
    // Jika terjadi error, batalkan semua perubahan
    $conn->rollback();
    header("Location: daftar_bangunan.php?status=error&pesan=" . urlencode($e->getMessage()));
}

exit;
?>
