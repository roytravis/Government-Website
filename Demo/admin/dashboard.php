<?php
// (admin/dashboard.php)
session_start();
require "../includes/koneksi.php";

// 1. Validasi Sesi dan Peran (Role)
// Memastikan hanya user dengan peran 'admin' yang bisa mengakses halaman ini.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    // Jika tidak, alihkan ke halaman login
    header("Location: ../login.php");
    exit;
}

// 2. Mengambil Data Admin yang Sedang Login
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT nama_lengkap FROM bg_users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$admin = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// 3. Mengambil Data Agregat untuk Statistik
// Menghitung total bangunan
$total_bangunan = $conn->query("SELECT COUNT(id) as total FROM bg_data_bangunan")->fetch_assoc()['total'];

// Menghitung total pengguna dengan peran 'skpd'
$total_pengguna_skpd = $conn->query("SELECT COUNT(id) as total FROM bg_users WHERE role = 'skpd'")->fetch_assoc()['total'];

// Menghitung data berdasarkan status verifikasi
$status_verifikasi_result = $conn->query("SELECT status_verifikasi, COUNT(id) as jumlah FROM bg_data_bangunan GROUP BY status_verifikasi");
$status_counts = [
    'Diverifikasi' => 0,
    'Revisi Formulir' => 0,
    'Belum Diverifikasi' => 0,
];
while ($row = $status_verifikasi_result->fetch_assoc()) {
    if (isset($status_counts[$row['status_verifikasi']])) {
        $status_counts[$row['status_verifikasi']] = $row['jumlah'];
    }
}

// 4. Mengambil Data untuk Grafik Bangunan per Bulan (6 bulan terakhir)
$monthly_data = [];
$month_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $date = new DateTime("first day of -$i month");
    $month_year = $date->format('Y-m');
    $month_name = $date->format('F Y');
    $month_labels[] = $month_name;

    $sql_monthly = "SELECT COUNT(id) as jumlah FROM bg_data_bangunan WHERE DATE_FORMAT(tanggal_dibuat, '%Y-%m') = ?";
    $stmt_monthly = $conn->prepare($sql_monthly);
    $stmt_monthly->bind_param("s", $month_year);
    $stmt_monthly->execute();
    $result = $stmt_monthly->get_result()->fetch_assoc();
    $monthly_data[] = $result['jumlah'] ?? 0;
    $stmt_monthly->close();
}


// 5. Mengambil Data Bangunan Terbaru (5 entri terakhir)
$sql_terbaru = "SELECT b.nama_bangunan, u.nama_instansi, b.tanggal_dibuat 
                FROM bg_data_bangunan b 
                JOIN bg_users u ON b.user_id = u.id 
                ORDER BY b.tanggal_dibuat DESC 
                LIMIT 5";
$bangunan_terbaru = $conn->query($sql_terbaru);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pelaporan Bangunan</title>
    
    <!-- Memuat Tailwind CSS dari CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Memuat Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Memuat Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Menggunakan font Inter sebagai font utama */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Warna latar belakang abu-abu muda */
        }
        /* Style tambahan untuk memastikan tampilan konsisten */
        .stat-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
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
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                        <i data-feather="home" class="w-5 h-5"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                    <a href="verifikasi_data.php" class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
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
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard Admin</h1>
                    <p class="text-sm text-gray-500">Selamat datang kembali, <?= htmlspecialchars($admin['nama_lengkap']) ?>!</p>
                </div>
                <div class="flex items-center">
                    <!-- Ikon Notifikasi -->
                    <button class="p-2 text-gray-500 rounded-full hover:bg-gray-200 hover:text-gray-700">
                        <i data-feather="bell" class="w-6 h-6"></i>
                    </button>
                    <!-- Profil Pengguna -->
                    <div class="ml-4">
                        <span class="font-semibold"><?= htmlspecialchars($admin['nama_lengkap']) ?></span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- Grid Statistik Utama -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Card Total Bangunan -->
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i data-feather="archive" class="w-6 h-6 text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Bangunan</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_bangunan ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Card Total Pengguna -->
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i data-feather="users" class="w-6 h-6 text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Pengguna SKPD</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_pengguna_skpd ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Card Sudah Diverifikasi -->
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-indigo-100 rounded-full">
                                <i data-feather="check-circle" class="w-6 h-6 text-indigo-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Sudah Diverifikasi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $status_counts['Diverifikasi'] ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Card Perlu Revisi -->
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-full">
                                <i data-feather="alert-circle" class="w-6 h-6 text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Perlu Revisi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $status_counts['Revisi Formulir'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Area Grafik -->
                <div class="grid grid-cols-1 gap-6 mt-8 lg:grid-cols-5">
                    <!-- Grafik Status Verifikasi -->
                    <div class="col-span-1 p-6 bg-white rounded-xl shadow-md lg:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Status Verifikasi Data</h3>
                        <p class="text-sm text-gray-500 mb-4">Distribusi status data bangunan yang masuk.</p>
                        <!-- Wrapper untuk membatasi tinggi grafik -->
                        <div class="relative h-72">
                            <canvas id="statusVerifikasiChart"></canvas>
                        </div>
                    </div>
                    <!-- Grafik Data Masuk per Bulan -->
                    <div class="col-span-1 p-6 bg-white rounded-xl shadow-md lg:col-span-3">
                        <h3 class="text-lg font-semibold text-gray-800">Data Masuk per Bulan</h3>
                        <p class="text-sm text-gray-500 mb-4">Jumlah bangunan yang didata dalam 6 bulan terakhir.</p>
                        <!-- Wrapper untuk membatasi tinggi grafik -->
                        <div class="relative h-72">
                            <canvas id="dataMasukChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabel Aktivitas Terbaru -->
                <div class="mt-8 bg-white rounded-xl shadow-md">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Aktivitas Terbaru</h3>
                        <p class="text-sm text-gray-500">Data bangunan yang baru saja ditambahkan oleh SKPD.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Bangunan</th>
                                    <th scope="col" class="px-6 py-3">SKPD Penginput</th>
                                    <th scope="col" class="px-6 py-3">Tanggal Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bangunan_terbaru->num_rows > 0): ?>
                                    <?php while($row = $bangunan_terbaru->fetch_assoc()): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            <?= htmlspecialchars($row['nama_bangunan']) ?>
                                        </th>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($row['nama_instansi']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= date("d F Y, H:i", strtotime($row['tanggal_dibuat'])) ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center">Belum ada aktivitas terbaru.</td>
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
        // Mengaktifkan Feather Icons
        feather.replace();

        // Data untuk Grafik Status Verifikasi
        const statusData = {
            labels: ['Diverifikasi', 'Revisi Formulir', 'Belum Diverifikasi'],
            datasets: [{
                label: 'Jumlah Bangunan',
                data: [
                    <?= $status_counts['Diverifikasi'] ?>,
                    <?= $status_counts['Revisi Formulir'] ?>,
                    <?= $status_counts['Belum Diverifikasi'] ?>
                ],
                backgroundColor: [
                    '#10b981', // Hijau (Emerald)
                    '#f59e0b', // Kuning (Amber)
                    '#6b7280'  // Abu-abu
                ],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 8
            }]
        };

        // Konfigurasi Grafik Doughnut
        const configStatus = {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            boxWidth: 12,
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        };

        // Render Grafik Doughnut
        const statusChart = new Chart(
            document.getElementById('statusVerifikasiChart'),
            configStatus
        );

        // Data untuk Grafik Data Masuk per Bulan
        const dataMasuk = {
            labels: <?= json_encode($month_labels) ?>,
            datasets: [{
                label: 'Jumlah Data Masuk',
                data: <?= json_encode($monthly_data) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                borderRadius: 5,
                barThickness: 30,
            }]
        };

        // Konfigurasi Grafik Bar
        const configDataMasuk = {
            type: 'bar',
            data: dataMasuk,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Memastikan hanya integer yang ditampilkan di sumbu Y
                            stepSize: 1 
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        };
        
        // Render Grafik Bar
        const dataMasukChart = new Chart(
            document.getElementById('dataMasukChart'),
            configDataMasuk
        );

    </script>
</body>
</html>
<?php
// Menutup koneksi database
$conn->close();
?>
