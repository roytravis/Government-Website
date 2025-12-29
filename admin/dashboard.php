<?php
// (admin/dashboard.php) - VERSI DENGAN FITUR NOTIFIKASI DAN TAUTAN AKTIF
session_start();

// Validasi Titik Masuk
if (!isset($_SESSION['has_visited_index'])) {
    header('Location: ../');
    exit;
}

require "../includes/koneksi.php";

// 1. Validasi Sesi dan Peran (Role)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /putr/bangunan/login");
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
$total_bangunan = $conn->query("SELECT COUNT(id) as total FROM bg_data_bangunan")->fetch_assoc()['total'];
$total_pengguna_skpd = $conn->query("SELECT COUNT(id) as total FROM bg_users WHERE role = 'skpd'")->fetch_assoc()['total'];
$status_verifikasi_result = $conn->query("SELECT status_verifikasi, COUNT(id) as jumlah FROM bg_data_bangunan GROUP BY status_verifikasi");
$status_counts = [
    'Diverifikasi' => 0, 'Revisi Formulir' => 0,
    'Belum Diverifikasi' => 0, 'Menunggu Tinjauan Ulang' => 0,
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

// 6. Mengambil data untuk notifikasi
$sql_notifikasi = "SELECT b.id, b.nama_bangunan, u.nama_instansi, b.status_verifikasi
                   FROM bg_data_bangunan b
                   JOIN bg_users u ON b.user_id = u.id
                   WHERE b.status_verifikasi = 'Belum Diverifikasi' OR b.status_verifikasi = 'Menunggu Tinjauan Ulang'
                   ORDER BY b.tanggal_diperbarui DESC, b.tanggal_dibuat DESC
                   LIMIT 10";
$hasil_notifikasi = $conn->query($sql_notifikasi);
$daftar_notifikasi = [];
if ($hasil_notifikasi->num_rows > 0) {
    while($row = $hasil_notifikasi->fetch_assoc()) {
        $daftar_notifikasi[] = $row;
    }
}
$jumlah_notifikasi = count($daftar_notifikasi);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pelaporan Bangunan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .sidebar {
        transition: transform 0.3s ease-in-out;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .sidebar-overlay.active {
        display: block;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            position: fixed;
            height: 100%;
            z-index: 1000;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            width: 100%;
        }
    }

    .notification-dropdown {
        display: none;
    }

    .notification-dropdown.show {
        display: block;
    }
    </style>
</head>

<body>
    <div class="sidebar-overlay"></div>
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="sidebar flex-shrink-0 w-64 bg-white border-r flex flex-col md:relative">
            <div class="flex items-center justify-between h-16 border-b px-4">
                <img src="https://dputr.tasikmalayakota.go.id/wp-content/uploads/2025/04/Logo-PUTR.png" alt="Logo PUTR" class="h-10">
                <button id="close-sidebar-btn" class="md:hidden p-2 text-gray-500 rounded-md hover:bg-gray-200">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="flex flex-col flex-grow p-4">
                <nav class="flex-grow">
                    <a href="/putr/bangunan/admin/dashboard" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
                        <i data-feather="home" class="w-5 h-5"></i><span class="ml-3">Dashboard</span>
                    </a>
                    <a href="/putr/bangunan/admin/verifikasi_data"
                        class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="database" class="w-5 h-5"></i><span class="ml-3">Verifikasi Data</span>
                    </a>
                    <a href="/putr/bangunan/admin/manajemen_pengguna"
                        class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="users" class="w-5 h-5"></i><span class="ml-3">Manajemen Pengguna</span>
                    </a>
                    <a href="/putr/bangunan/admin/history"
                        class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="clock" class="w-5 h-5"></i><span class="ml-3">Riwayat Aktivitas</span>
                    </a>
                </nav>
                <a href="/putr/bangunan/admin/logout"
                    class="flex items-center px-4 py-2 mt-4 text-gray-600 hover:bg-red-100 hover:text-red-700 rounded-lg">
                    <i data-feather="log-out" class="w-5 h-5"></i><span class="ml-3">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <div class="flex flex-col flex-1 overflow-y-auto main-content">
            <header class="flex items-center justify-between h-16 bg-white border-b px-4 md:px-8">
                <button id="hamburger-btn" class="md:hidden p-2 text-gray-500 rounded-md hover:bg-gray-200">
                    <i data-feather="menu" class="w-6 h-6"></i>
                </button>
                <div class="flex-1 flex justify-between items-center">
                    <div class="flex-1">
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800 text-center md:text-left">Dashboard Admin
                        </h1>
                        <p class="text-sm text-gray-500 hidden md:block">Selamat datang kembali,
                            <?= htmlspecialchars($admin['nama_lengkap']) ?>!</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button id="notification-bell"
                                class="p-2 text-gray-500 rounded-full hover:bg-gray-200 hover:text-gray-700">
                                <i data-feather="bell" class="w-6 h-6"></i>
                                <?php if ($jumlah_notifikasi > 0): ?>
                                <span
                                    class="absolute top-0 right-0 block h-2 w-2 transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"></span>
                                <?php endif; ?>
                            </button>
                            <div id="notification-dropdown"
                                class="notification-dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl overflow-hidden z-20">
                                <div class="py-2 px-4 border-b">
                                    <h3 class="font-bold text-gray-800">Notifikasi</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <?php if ($jumlah_notifikasi > 0): ?>
                                    <?php foreach ($daftar_notifikasi as $notif): ?>
                                    <a href="/putr/bangunan/admin/detail_verifikasi/<?= $notif['id'] ?>"
                                        class="flex items-center px-4 py-3 border-b hover:bg-gray-100">
                                        <div class="w-full">
                                            <p class="text-gray-800 text-sm font-semibold truncate">
                                                <?= htmlspecialchars($notif['nama_bangunan']) ?></p>
                                            <p class="text-gray-600 text-xs">
                                                Dari: <?= htmlspecialchars($notif['nama_instansi']) ?>
                                                <span
                                                    class="font-bold <?= $notif['status_verifikasi'] == 'Menunggu Tinjauan Ulang' ? 'text-purple-600' : 'text-yellow-600' ?>">
                                                    (<?= htmlspecialchars($notif['status_verifikasi']) ?>)
                                                </span>
                                            </p>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <p class="text-center text-gray-500 py-4">Tidak ada notifikasi baru.</p>
                                    <?php endif; ?>
                                </div>
                                <a href="/putr/bangunan/admin/verifikasi_data"
                                    class="block bg-gray-50 text-center text-blue-600 font-semibold py-2 hover:bg-gray-100">Lihat
                                    Semua Verifikasi</a>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            <span class="font-semibold"><?= htmlspecialchars($admin['nama_lengkap']) ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-4 md:p-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full"><i data-feather="archive"
                                    class="w-6 h-6 text-blue-600"></i></div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Bangunan</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_bangunan ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full"><i data-feather="users"
                                    class="w-6 h-6 text-green-600"></i></div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pengguna </p>
                                <p class="text-2xl font-bold text-gray-900"><?= $total_pengguna_skpd ?></p>
                            </div>
                        </div>
                    </div>
                    <a href="/putr/bangunan/admin/verifikasi_data" class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-full"><i data-feather="inbox"
                                    class="w-6 h-6 text-yellow-600"></i></div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Belum Diverifikasi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $status_counts['Belum Diverifikasi'] ?>
                                </p>
                            </div>
                        </div>
                    </a>
                    <a href="/putr/bangunan/admin/verifikasi_data" class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-full"><i data-feather="edit"
                                    class="w-6 h-6 text-purple-600"></i></div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Tinjauan Ulang</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?= $status_counts['Menunggu Tinjauan Ulang'] ?></p>
                            </div>
                        </div>
                    </a>
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-indigo-100 rounded-full"><i data-feather="check-circle"
                                    class="w-6 h-6 text-indigo-600"></i></div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Diverifikasi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $status_counts['Diverifikasi'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-white rounded-xl shadow-md stat-card">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-full"><i data-feather="alert-circle"
                                    class="w-6 h-6 text-red-600"></i></div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Perlu Revisi</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $status_counts['Revisi Formulir'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 mt-8 lg:grid-cols-5">
                    <div class="col-span-1 p-6 bg-white rounded-xl shadow-md lg:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800">Status Verifikasi Data</h3>
                        <p class="text-sm text-gray-500 mb-4">Distribusi status data bangunan yang masuk.</p>
                        <div class="relative h-72"><canvas id="statusVerifikasiChart"></canvas></div>
                    </div>
                    <div class="col-span-1 p-6 bg-white rounded-xl shadow-md lg:col-span-3">
                        <h3 class="text-lg font-semibold text-gray-800">Data Masuk per Bulan</h3>
                        <p class="text-sm text-gray-500 mb-4">Jumlah bangunan yang didata dalam 6 bulan terakhir.</p>
                        <div class="relative h-72"><canvas id="dataMasukChart"></canvas></div>
                    </div>
                </div>
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
                                    <th scope="col" class="px-6 py-3">Perangkat Daerah</th>
                                    <th scope="col" class="px-6 py-3">Tanggal Dibuat</th>
                                </tr>
                            </thead>
                            <tbody id="latestActivityTableBody">
                                <?php if ($bangunan_terbaru->num_rows > 0): ?>
                                <?php while($row = $bangunan_terbaru->fetch_assoc()): ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        <?= htmlspecialchars($row['nama_bangunan']) ?></th>
                                    <td class="px-6 py-4"><?= htmlspecialchars($row['nama_instansi']) ?></td>
                                    <td class="px-6 py-4"><?= date("d F Y, H:i", strtotime($row['tanggal_dibuat'])) ?>
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

                <!-- Tabel Data Bangunan Terverifikasi -->
                <div class="mt-8 bg-white rounded-xl shadow-md">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Data Bangunan Terverifikasi</h3>
                        <p class="text-sm text-gray-500">Daftar bangunan yang telah berhasil diverifikasi oleh admin.</p>
                    </div>
                    <div class="p-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                        <div class="relative w-full sm:w-1/2">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i data-feather="search" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input type="text" id="searchVerifiedBuildings"
                                class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Cari nama bangunan atau SKPD...">
                        </div>
                        <div class="flex gap-4 w-full sm:w-auto">
                            <select id="filterBuktiKepemilikan"
                                class="block w-full sm:w-auto px-3 py-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Filter Bukti Kepemilikan</option>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                            <select id="filterImb"
                                class="block w-full sm:w-auto px-3 py-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Filter IMB/PBG</option>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                            <select id="filterSlf"
                                class="block w-full sm:w-auto px-3 py-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Filter SLF</option>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Tidak Tersedia">Tidak Tersedia</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">No.</th>
                                    <th scope="col" class="px-6 py-3">Nama Bangunan</th>
                                    <th scope="col" class="px-6 py-3">Perangkat Daerah</th>
                                    <!-- PERUBAHAN JUDUL KOLOM -->
                                    <th scope="col" class="px-6 py-3">Jenis Bukti Kepemilikan</th>
                                    <th scope="col" class="px-6 py-3">IMB/PBG</th>
                                    <th scope="col" class="px-6 py-3">SLF</th>
                                    <th scope="col" class="px-6 py-3">Keterangan Perangkat Daerah</th>
                                    <th scope="col" class="px-6 py-3">Keterangan Admin</th>
                                </tr>
                            </thead>
                            <tbody id="verifiedBuildingsTableBody">
                                <!-- Data akan dimuat di sini oleh AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200" id="verifiedBuildingsPagination">
                        <!-- Paginasi akan dimuat di sini oleh AJAX -->
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
    feather.replace();
    const statusData = {
        labels: ['Diverifikasi', 'Revisi Formulir', 'Belum Diverifikasi', 'Menunggu Tinjauan Ulang'],
        datasets: [{
            label: 'Jumlah Bangunan',
            data: [<?=$status_counts['Diverifikasi']?>, <?=$status_counts['Revisi Formulir']?>,
                <?=$status_counts['Belum Diverifikasi']?>,
                <?=$status_counts['Menunggu Tinjauan Ulang']?>
            ],
            backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#8b5cf6'],
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 8
        }]
    };
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
    new Chart(document.getElementById('statusVerifikasiChart'), configStatus);
    const dataMasuk = {
        labels: <?=json_encode($month_labels)?>,
        datasets: [{
            label: 'Jumlah Data Masuk',
            data: <?=json_encode($monthly_data)?>,
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 2,
            borderRadius: 5,
            barThickness: 30
        }]
    };
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
    new Chart(document.getElementById('dataMasukChart'), configDataMasuk);
    const hamburgerBtn = document.getElementById('hamburger-btn'),
        closeSidebarBtn = document.getElementById('close-sidebar-btn'),
        sidebar = document.querySelector('.sidebar'),
        overlay = document.querySelector('.sidebar-overlay');

    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active')
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active')
    }
    hamburgerBtn.addEventListener('click', openSidebar);
    closeSidebarBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    const notificationBell = document.getElementById('notification-bell'),
        notificationDropdown = document.getElementById('notification-dropdown');
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show')
    });
    window.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target))
            notificationDropdown.classList.remove('show')
    });

    const searchVerifiedBuildingsInput = document.getElementById('searchVerifiedBuildings');
    const filterBuktiKepemilikanSelect = document.getElementById('filterBuktiKepemilikan');
    const filterImbSelect = document.getElementById('filterImb');
    const filterSlfSelect = document.getElementById('filterSlf');
    const verifiedBuildingsTableBody = document.getElementById('verifiedBuildingsTableBody'); 
    const verifiedBuildingsPagination = document.getElementById('verifiedBuildingsPagination');

    function loadVerifiedBuildingsData(halaman = 1, query = '', filterBuktiKepemilikan = '', filterImb = '', filterSlf = '') {
        const formData = new FormData();
        formData.append('halaman', halaman);
        formData.append('query', query);
        formData.append('filter_bukti_kepemilikan', filterBuktiKepemilikan);
        formData.append('filter_imb', filterImb);
        formData.append('filter_slf', filterSlf);

        fetch('/putr/bangunan/admin/api_verified_buildings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                verifiedBuildingsTableBody.innerHTML = data.html_tabel;
                verifiedBuildingsPagination.innerHTML = data.html_paginasi;
                feather.replace();
            })
            .catch(error => {
                console.error('Error loading verified buildings data:', error);
                verifiedBuildingsTableBody.innerHTML =
                    `<tr><td colspan="8" class="text-center text-red-500 py-4">Terjadi kesalahan: ${error.message}</td></tr>`; // colspan diubah menjadi 8
                verifiedBuildingsPagination.innerHTML = '';
            });
    }

    let searchVerifiedBuildingsTimer;
    searchVerifiedBuildingsInput.addEventListener('keyup', function() {
        clearTimeout(searchVerifiedBuildingsTimer);
        const currentQuery = this.value;
        const currentFilterBukti = filterBuktiKepemilikanSelect.value;
        const currentFilterImb = filterImbSelect.value;
        const currentFilterSlf = filterSlfSelect.value;
        searchVerifiedBuildingsTimer = setTimeout(() => {
            loadVerifiedBuildingsData(1, currentQuery, currentFilterBukti, currentFilterImb, currentFilterSlf);
        }, 300);
    });

    function addFilterListener(element) {
        element.addEventListener('change', function() {
            const currentQuery = searchVerifiedBuildingsInput.value;
            const currentFilterBukti = filterBuktiKepemilikanSelect.value;
            const currentFilterImb = filterImbSelect.value;
            const currentFilterSlf = filterSlfSelect.value;
            loadVerifiedBuildingsData(1, currentQuery, currentFilterBukti, currentFilterImb, currentFilterSlf);
        });
    }

    addFilterListener(filterBuktiKepemilikanSelect);
    addFilterListener(filterImbSelect);
    addFilterListener(filterSlfSelect);

    verifiedBuildingsPagination.addEventListener('click', function(e) {
        if (e.target.matches('a.pagination-link') && !e.target.classList.contains('pointer-events-none')) {
            e.preventDefault();
            const targetPage = e.target.dataset.halaman;
            const currentQuery = searchVerifiedBuildingsInput.value;
            const currentFilterBukti = filterBuktiKepemilikanSelect.value;
            const currentFilterImb = filterImbSelect.value;
            const currentFilterSlf = filterSlfSelect.value;
            if (targetPage) {
                loadVerifiedBuildingsData(targetPage, currentQuery, currentFilterBukti, currentFilterImb, currentFilterSlf);
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        loadVerifiedBuildingsData(1, '', '', '', '');
    });
    </script>
</body>

</html>
