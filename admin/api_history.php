<?php
// (admin/api_history.php) - Endpoint AJAX untuk data Riwayat Aktivitas
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
$limit = 10; // Jumlah data per halaman
$kata_kunci = $_POST['query'] ?? '';
$action_type_filter = $_POST['action_type'] ?? '';
$halaman = isset($_POST['halaman']) ? (int)$_POST['halaman'] : 1;
$offset = ($halaman - 1) * $limit;

$param_types = "";
$params = [];
$where_clauses = ["u.role = 'skpd'"]; // Hanya log aktivitas dari pengguna SKPD

// Filter Kata Kunci
if (!empty($kata_kunci)) {
    $where_clauses[] = "(u.nama_lengkap LIKE ? OR b.nama_bangunan LIKE ?)";
    $params[] = "%" . $kata_kunci . "%";
    $params[] = "%" . $kata_kunci . "%";
    $param_types .= "ss";
}

// Filter Tipe Aksi
if (!empty($action_type_filter)) {
    $where_clauses[] = "la.action_type = ?";
    $params[] = $action_type_filter;
    $param_types .= "s";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// --- QUERY 1: Menghitung TOTAL DATA yang cocok dengan pencarian dan filter ---
$sql_count = "SELECT COUNT(la.id) as total 
              FROM bg_activity_log la
              JOIN bg_users u ON la.user_id = u.id
              LEFT JOIN bg_data_bangunan b ON la.entity_id = b.id AND la.entity_type = 'bangunan'
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
$sql_data = "SELECT la.*, u.nama_lengkap, u.nama_instansi, b.nama_bangunan as nama_bangunan_terkait
             FROM bg_activity_log la
             JOIN bg_users u ON la.user_id = u.id
             LEFT JOIN bg_data_bangunan b ON la.entity_id = b.id AND la.entity_type = 'bangunan'
             " . $where_sql . "
             ORDER BY la.timestamp DESC
             LIMIT ? OFFSET ?";

$stmt_data = $conn->prepare($sql_data);
// Tambahkan tipe parameter untuk LIMIT dan OFFSET
$param_types_data = $param_types . "ii";
$params_data = array_merge($params, [$limit, $offset]);

if (!empty($params_data)) {
    $stmt_data->bind_param($param_types_data, ...$params_data);
}
$stmt_data->execute();
$result = $stmt_data->get_result();

// --- MEMBANGUN OUTPUT HTML TABEL ---
$output_tabel = '';
$base_url_files = '/putr/bangunan/'; // Base URL untuk file yang diunggah

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $action_type_display = '';
        $detail_perubahan = '';
        $entity_name = htmlspecialchars($row['nama_bangunan_terkait'] ?? 'N/A');

        switch ($row['action_type']) {
            case 'add':
                $action_type_display = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Tambah Data</span>';
                $detail_perubahan = '<div class="log-detail-box">Data baru bangunan ' . $entity_name . ' telah ditambahkan.</div>';
                break;
            case 'edit':
                $action_type_display = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Edit Data</span>';
                $changes = json_decode($row['changes'], true);
                if (!empty($changes)) {
                    $detail_perubahan = '<div class="log-detail-box">Perubahan pada bangunan ' . $entity_name . ':<ul>';
                    foreach ($changes as $field => $change) {
                        // Lewati perubahan pada 'status_verifikasi'
                        if ($field === 'status_verifikasi') {
                            continue;
                        }

                        $field_name = ucwords(str_replace('_', ' ', $field)); // Format nama field

                        $old_val = htmlspecialchars($change['old'] ?? 'Tidak Ada');
                        $new_val = htmlspecialchars($change['new'] ?? 'Tidak Ada');

                        // Cek apakah field adalah file
                        if (in_array($field, ['foto_bangunan', 'file_bukti_tanah', 'file_imb_pbg', 'file_slf'])) {
                            $old_file_link = '';
                            $new_file_link = '';
                            
                            // Tentukan tipe file untuk teks tautan
                            $file_type_text = 'File';
                            if (strpos($field, 'foto_') !== false) {
                                $file_type_text = 'Gambar';
                            } elseif (strpos($field, 'file_') !== false) {
                                $file_type_text = 'Dokumen';
                            }

                            if (!empty($change['old'])) {
                                $old_file_link = '<a href="' . $base_url_files . htmlspecialchars($change['old']) . '" target="_blank" class="text-blue-500 hover:underline">Unduh ' . $file_type_text . ' Lama</a>';
                            } else {
                                $old_file_link = 'Tidak Ada';
                            }

                            if (!empty($change['new'])) {
                                $new_file_link = '<a href="' . $base_url_files . htmlspecialchars($change['new']) . '" target="_blank" class="text-blue-500 hover:underline">Unduh ' . $file_type_text . ' Baru</a>';
                            } else {
                                $new_file_link = 'Tidak Ada';
                            }

                            $detail_perubahan .= '<li><strong>' . $field_name . ':</strong> <span class="old-value">Dari: ' . $old_file_link . '</span> <span class="new-value">Ke: ' . $new_file_link . '</span></li>';
                        } else {
                            // Untuk field non-file
                            $detail_perubahan .= '<li><strong>' . $field_name . ':</strong> <span class="old-value">Dari: ' . $old_val . '</span> <span class="new-value">Ke: ' . $new_val . '</span></li>';
                        }
                    }
                    $detail_perubahan .= '</ul></div>';
                } else {
                    $detail_perubahan = '<div class="log-detail-box">Data bangunan ' . $entity_name . ' telah diedit (tidak ada perubahan spesifik tercatat atau perubahan minor).</div>';
                }
                break;
            case 'delete':
                $action_type_display = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Hapus Data</span>';
                $detail_perubahan = '<div class="log-detail-box">Data bangunan ' . $entity_name . ' telah dihapus.</div>';
                break;
        }

        $output_tabel .= '<tr class="bg-white border-b hover:bg-gray-50">';
        $output_tabel .= '  <td class="px-6 py-4 whitespace-nowrap">' . date("d M Y, H:i", strtotime($row['timestamp'])) . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . htmlspecialchars($row['nama_lengkap']) . ' (' . htmlspecialchars($row['nama_instansi']) . ')</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . $action_type_display . '</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . htmlspecialchars($row['entity_type']) . ' (ID: ' . htmlspecialchars($row['entity_id']) . ')</td>';
        $output_tabel .= '  <td class="px-6 py-4">' . $detail_perubahan . '</td>';
        $output_tabel .= '</tr>';
    }
} else {
    $output_tabel = '<tr><td colspan="5" class="text-center py-4">Tidak ada riwayat aktivitas yang ditemukan.</td></tr>';
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
