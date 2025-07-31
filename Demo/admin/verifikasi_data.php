<?php
// (admin/verifikasi_data.php)
session_start();
require "../includes/koneksi.php";

// Validasi Sesi dan Peran (Role)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Mengambil data admin yang sedang login
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT nama_lengkap FROM bg_users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$admin = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Mengambil semua data bangunan untuk ditampilkan di tabel
$sql_bangunan = "SELECT b.id, b.nama_bangunan, b.status_verifikasi, b.tanggal_dibuat, u.nama_instansi 
                 FROM bg_data_bangunan b
                 JOIN bg_users u ON b.user_id = u.id
                 ORDER BY b.tanggal_dibuat DESC";
$result_bangunan = $conn->query($sql_bangunan);

// Menyiapkan pesan notifikasi dari URL (setelah proses verifikasi)
$notif_status = $_GET['status'] ?? '';
$notif_pesan = $_GET['pesan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Data - Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div class="hidden md:flex flex-col w-64 bg-white border-r">
            <div class="flex items-center justify-center h-16 border-b">
                <img src="http://localhost/putr/wp-content/uploads/2025/04/Logo-PUTR.png" alt="Logo PUTR" class="h-10">
            </div>
            <div class="flex flex-col flex-grow p-4">
                <nav class="flex-grow">
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="home" class="w-5 h-5"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                    <a href="verifikasi_data.php" class="flex items-center px-4 py-2 mt-2 text-white bg-blue-600 rounded-lg">
                        <i data-feather="database" class="w-5 h-5"></i>
                        <span class="ml-3">Verifikasi Data</span>
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="users" class="w-5 h-5"></i>
                        <span class="ml-3">Manajemen Pengguna</span>
                    </a>
                </nav>
                <a href="../logout.php" class="flex items-center px-4 py-2 mt-4 text-gray-600 hover:bg-red-100 hover:text-red-700 rounded-lg">
                    <i data-feather="log-out" class="w-5 h-5"></i>
                    <span class="ml-3">Logout</span>
                </a>
            </div>
        </div>

        <!-- Konten Utama -->
        <div class="flex flex-col flex-1 overflow-y-auto">
            <div class="flex items-center justify-between h-16 bg-white border-b px-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Verifikasi Data Bangunan</h1>
                    <p class="text-sm text-gray-500">Tinjau dan kelola status data yang masuk.</p>
                </div>
                <div class="flex items-center">
                    <span class="font-semibold"><?= htmlspecialchars($admin['nama_lengkap']) ?></span>
                </div>
            </div>

            <div class="p-8">
                <!-- Notifikasi -->
                <?php if ($notif_status == 'sukses'): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                        <p class="font-bold">Berhasil!</p>
                        <p><?= htmlspecialchars($notif_pesan) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Tabel Data -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Bangunan</th>
                                    <th scope="col" class="px-6 py-3">SKPD Penginput</th>
                                    <th scope="col" class="px-6 py-3">Tanggal Input</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_bangunan->num_rows > 0): ?>
                                    <?php while($row = $result_bangunan->fetch_assoc()): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            <?= htmlspecialchars($row['nama_bangunan']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($row['nama_instansi']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= date("d M Y", strtotime($row['tanggal_dibuat'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                                $status = $row['status_verifikasi'];
                                                $badge_class = '';
                                                if ($status == 'Diverifikasi') {
                                                    $badge_class = 'bg-green-100 text-green-800';
                                                } elseif ($status == 'Revisi Formulir') {
                                                    $badge_class = 'bg-red-100 text-red-800';
                                                } else {
                                                    $badge_class = 'bg-yellow-100 text-yellow-800';
                                                }
                                            ?>
                                            <span class="px-3 py-1 text-xs font-medium rounded-full <?= $badge_class ?>">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="detail_verifikasi.php?id=<?= $row['id'] ?>" class="font-medium text-blue-600 hover:underline">
                                                Detail & Verifikasi
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center">Belum ada data bangunan yang diinput.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        feather.replace();
    </script>
</body>
</html>
<?php
$conn->close();
?>
