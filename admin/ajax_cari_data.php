<?php
// VERSI DEBUGGING: Menampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Menggunakan path relatif yang lebih sederhana untuk debugging
require "../includes/koneksi.php";

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

// --- PENGATURAN PAGINATION ---
$limit = 5; // Jumlah data per halaman
$kata_kunci = $_POST['query'] ?? '';
$halaman = isset($_POST['halaman']) ? (int)$_POST['halaman'] : 1;
$offset = ($halaman - 1) * $limit;

$param_kata_kunci = "%" . $kata_kunci . "%";

// --- QUERY 1: Menghitung TOTAL DATA yang cocok dengan pencarian ---
$sql_count = "SELECT COUNT(b.id) as total 
              FROM bg_data_bangunan b
              JOIN bg_users u ON b.user_id = u.id
              WHERE b.nama_bangunan LIKE ? OR u.nama_instansi LIKE ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("ss", $param_kata_kunci, $param_kata_kunci);
$stmt_count->execute();
$total_hasil = $stmt_count->get_result()->fetch_assoc()['total'];
$total_halaman = ceil($total_hasil / $limit);
$stmt_count->close();


// --- QUERY 2: Mengambil DATA untuk HALAMAN AKTIF ---
$sql_data = "SELECT b.id, b.nama_bangunan, b.status_verifikasi, b.tanggal_dibuat, b.tanggal_diperbarui, u.nama_instansi 
             FROM bg_data_bangunan b
             JOIN bg_users u ON b.user_id = u.id
             WHERE b.nama_bangunan LIKE ? OR u.nama_instansi LIKE ?
             ORDER BY b.status_verifikasi = 'Menunggu Tinjauan Ulang' DESC, b.status_verifikasi = 'Belum Diverifikasi' DESC, b.status_verifikasi = 'Revisi Formulir' DESC, b.tanggal_diperbarui DESC, b.tanggal_dibuat DESC
             LIMIT ? OFFSET ?";
$stmt_data = $conn->prepare($sql_data);
$stmt_data->bind_param("ssii", $param_kata_kunci, $param_kata_kunci, $limit, $offset);
$stmt_data->execute();
$result = $stmt_data->get_result();

// --- MEMBANGUN OUTPUT HTML ---
$output_tabel = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = $row['status_verifikasi'];
        
        $badge_style = '';
        switch ($status) {
            case 'Diverifikasi':
                $badge_style = 'display: inline-block; background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;';
                break;
            case 'Revisi Formulir':
                $badge_style = 'display: inline-block; background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;';
                break;
            case 'Menunggu Tinjauan Ulang':
                $badge_style = 'display: inline-block; background-color: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff;';
                break;
            case 'Belum Diverifikasi':
            default:
                $badge_style = 'display: inline-block; background-color: #fffbeb; color: #f59e0b; border: 1px solid #fde68a;';
                break;
        }

        $tanggal_tampil = !empty($row['tanggal_diperbarui']) ? $row['tanggal_diperbarui'] : $row['tanggal_dibuat'];
        $tanggal_format = date("d M Y, H:i", strtotime($tanggal_tampil));

        $output_tabel .= '<tr class="bg-white border-b hover:bg-gray-50">';
        $output_tabel .= '  <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">' . htmlspecialchars($row['nama_bangunan']) . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . htmlspecialchars($row['nama_instansi']) . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . $tanggal_format . '</td>';
        $output_tabel .= '  <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-medium rounded-full" style="' . $badge_style . '">' . htmlspecialchars($status) . '</span></td>';
        $output_tabel .= '  <td class="px-6 py-4 text-center"><a href="/putr/bangunan/admin/detail_verifikasi/' . $row['id'] . '" class="font-medium text-blue-600 hover:underline">Detail & Verifikasi</a></td>';
        $output_tabel .= '</tr>';
    }
} else {
    $output_tabel = '<tr><td colspan="5" class="text-center py-4">Data tidak ditemukan.</td></tr>';
}
$stmt_data->close();

// --- MEMBANGUN OUTPUT PAGINATION HTML ---
$output_paginasi = '';
if ($total_halaman > 1) {
    $output_paginasi .= '<nav class="flex items-center justify-between pt-4" aria-label="Table navigation">';
    $output_paginasi .= '  <span class="text-sm font-normal text-gray-500">Menampilkan <span class="font-semibold text-gray-900">' . ($offset + 1) . '-' . min($offset + $limit, $total_hasil) . '</span> dari <span class="font-semibold text-gray-900">' . $total_hasil . '</span></span>';
    $output_paginasi .= '  <ul class="inline-flex items-center -space-x-px">';
    
    $output_paginasi .= '<li><a href="#" data-halaman="' . ($halaman - 1) . '" class="pagination-link block px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 ' . ($halaman <= 1 ? 'pointer-events-none opacity-50' : '') . '">Sebelumnya</a></li>';

    for ($i = 1; $i <= $total_halaman; $i++) {
        $active_class = ($i == $halaman) ? 'text-blue-600 bg-blue-50 border-blue-300 hover:bg-blue-100 hover:text-blue-700' : 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700';
        $output_paginasi .= '<li><a href="#" data-halaman="' . $i . '" class="pagination-link px-3 py-2 leading-tight border border-gray-300 ' . $active_class . '">' . $i . '</a></li>';
    }

    $output_paginasi .= '<li><a href="#" data-halaman="' . ($halaman + 1) . '" class="pagination-link block px-3 py-2 leading-tight text-gray-500 bg-white border-border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 ' . ($halaman >= $total_halaman ? 'pointer-events-none opacity-50' : '') . '">Berikutnya</a></li>';
    
    $output_paginasi .= '  </ul>';
    $output_paginasi .= '</nav>';
}

$conn->close();

// --- MENGIRIMKAN RESPON JSON ---
header('Content-Type: application/json');
echo json_encode([
    'html_tabel' => $output_tabel,
    'html_paginasi' => $output_paginasi
]);
