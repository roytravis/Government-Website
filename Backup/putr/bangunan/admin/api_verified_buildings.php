<?php
// (admin/api_verified_buildings.php) - Endpoint AJAX untuk data bangunan terverifikasi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "../includes/koneksi.php";

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

// --- PENGATURAN PAGINATION & FILTER ---
$limit = 5;
$kata_kunci = $_POST['query'] ?? '';
$halaman = isset($_POST['halaman']) ? (int)$_POST['halaman'] : 1;
$filter_imb = $_POST['filter_imb'] ?? '';
$filter_slf = $_POST['filter_slf'] ?? '';
$filter_bukti_kepemilikan = $_POST['filter_bukti_kepemilikan'] ?? '';
$offset = ($halaman - 1) * $limit;

$param_types = "";
$params = [];
$where_clauses = ["b.status_verifikasi = 'Diverifikasi'"];

if (!empty($kata_kunci)) {
    $where_clauses[] = "(b.nama_bangunan LIKE ? OR u.nama_instansi LIKE ?)";
    $params[] = "%" . $kata_kunci . "%";
    $params[] = "%" . $kata_kunci . "%";
    $param_types .= "ss";
}

if ($filter_bukti_kepemilikan === 'Tersedia') {
    $where_clauses[] = "b.file_bukti_tanah IS NOT NULL AND b.file_bukti_tanah != ''";
} elseif ($filter_bukti_kepemilikan === 'Tidak Tersedia') {
    $where_clauses[] = "(b.file_bukti_tanah IS NULL OR b.file_bukti_tanah = '')";
}

if ($filter_imb === 'Tersedia') {
    $where_clauses[] = "b.file_imb_pbg IS NOT NULL AND b.file_imb_pbg != ''";
} elseif ($filter_imb === 'Tidak Tersedia') {
    $where_clauses[] = "(b.file_imb_pbg IS NULL OR b.file_imb_pbg = '')";
}

if ($filter_slf === 'Tersedia') {
    $where_clauses[] = "b.file_slf IS NOT NULL AND b.file_slf != ''";
} elseif ($filter_slf === 'Tidak Tersedia') {
    $where_clauses[] = "(b.file_slf IS NULL OR b.file_slf = '')";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// --- QUERY 1: Menghitung TOTAL DATA ---
$sql_count = "SELECT COUNT(b.id) as total 
              FROM bg_data_bangunan b
              JOIN bg_users u ON b.user_id = u.id
              " . $where_sql;

$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$total_hasil = $stmt_count->get_result()->fetch_assoc()['total'];
$total_halaman = ceil($total_hasil / $limit);
$stmt_count->close();

// --- QUERY 2: Mengambil DATA untuk HALAMAN AKTIF ---
$sql_data = "SELECT b.id, b.nama_bangunan, u.nama_instansi, b.jenis_bukti_tanah, b.file_bukti_tanah, b.no_imb_pbg, b.file_imb_pbg, b.no_slf, b.file_slf, b.catatan_admin, b.riwayat_pemeliharaan_terakhir
             FROM bg_data_bangunan b
             JOIN bg_users u ON b.user_id = u.id
             " . $where_sql . "
             ORDER BY b.tanggal_diperbarui DESC
             LIMIT ? OFFSET ?";

$stmt_data = $conn->prepare($sql_data);
$param_types_data = $param_types . "ii";
$params_data = array_merge($params, [$limit, $offset]);

if (!empty($params_data)) {
    $stmt_data->bind_param($param_types_data, ...$params_data);
}
$stmt_data->execute();
$result = $stmt_data->get_result();

// --- MEMBANGUN OUTPUT HTML TABEL ---
$output_tabel = '';
$nomor_urut = $offset + 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // PERUBAHAN LOGIKA: Menampilkan jenis bukti kepemilikan
        if (!empty($row['jenis_bukti_tanah'])) {
            $bukti_kepemilikan_display = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">' . htmlspecialchars($row['jenis_bukti_tanah']) . '</span>';
        } else {
            $bukti_kepemilikan_display = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Tidak Ada</span>';
        }
        
        $imb_pbg_status = !empty($row['file_imb_pbg']) ? 
            '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Tersedia</span>' : 
            '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Tersedia</span>';
            
        $slf_status = !empty($row['file_slf']) ? 
            '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Tersedia</span>' : 
            '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Tidak Tersedia</span>';

        $keterangan_skpd_display = '<span class="text-gray-400">Tidak ada</span>';
        if (!empty($row['riwayat_pemeliharaan_terakhir'])) {
            $riwayat = json_decode($row['riwayat_pemeliharaan_terakhir'], true);
            if (is_array($riwayat) && !empty($riwayat['nama_kegiatan'])) {
                $nama_kegiatan = htmlspecialchars($riwayat['nama_kegiatan']);
                $tahun_anggaran = htmlspecialchars($riwayat['tahun_anggaran'] ?? '-');
                $nilai_anggaran = 'Rp ' . number_format($riwayat['nilai_anggaran'] ?? 0, 0, ',', '.');
                
                $keterangan_skpd_display = "<div class='whitespace-nowrap'>" .
                                           "<b>Kegiatan:</b> {$nama_kegiatan}<br>" .
                                           "<b>Tahun:</b> {$tahun_anggaran}<br>" .
                                           "<b>Anggaran:</b> {$nilai_anggaran}" .
                                           "</div>";
            }
        }

        $keterangan_admin_display = !empty($row['catatan_admin']) ? htmlspecialchars(substr($row['catatan_admin'], 0, 50)) . (strlen($row['catatan_admin']) > 50 ? '...' : '') : '<span class="text-gray-400">Tidak ada</span>';

        $output_tabel .= '<tr class="bg-white border-b hover:bg-gray-50">';
        $output_tabel .= '  <td class="px-6 py-4">' . $nomor_urut++ . '</td>';
        $output_tabel .= '  <td class="px-6 py-4 font-medium text-gray-900">' . htmlspecialchars($row['nama_bangunan']) . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . htmlspecialchars($row['nama_instansi']) . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . $bukti_kepemilikan_display . '</td>'; // Kolom yang diubah
        $output_tabel .= '  <td class="px-6 py-4">' . $imb_pbg_status . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . $slf_status . '</td>';
        $output_tabel .= '  <td class="px-6 py-4 text-gray-700">' . $keterangan_skpd_display . '</td>';
        $output_tabel .= '  <td class="px-6 py-4 text-gray-700">' . $keterangan_admin_display . '</td>';
        $output_tabel .= '</tr>';
    }
} else {
    $output_tabel = '<tr><td colspan="8" class="text-center py-4">Belum ada data bangunan yang diverifikasi.</td></tr>';
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
?>
