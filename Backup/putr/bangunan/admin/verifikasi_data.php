<?php
// (admin/verifikasi_data.php) - VERSI DENGAN TAUTAN SIDEBAR YANG DIPERBARUI
session_start();
require "../includes/koneksi.php";

// Validasi Sesi dan Peran
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /putr/bangunan/login");
    exit;
}

// Mengambil data admin
$user_id = $_SESSION['user_id'];
$admin_result = $conn->query("SELECT nama_lengkap FROM bg_users WHERE id = $user_id");
$admin = $admin_result->fetch_assoc();

// Notifikasi
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
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
    }

    .pagination-link.disabled {
        pointer-events: none;
        opacity: 0.5;
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
    </style>
</head>

<body>
    <div class="sidebar-overlay"></div>

    <div class="flex h-screen bg-gray-100">
        <aside class="sidebar flex-shrink-0 w-64 bg-white border-r flex flex-col">
            <div class="flex items-center justify-between h-16 border-b px-4">
                <img src="https://dputr.tasikmalayakota.go.id/wp-content/uploads/2025/04/Logo-PUTR.png" alt="Logo PUTR" class="h-10">
                <button id="close-sidebar-btn" class="md:hidden p-2 text-gray-500 rounded-md hover:bg-gray-200">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="flex flex-col flex-grow p-4">
                <nav class="flex-grow">
                    <a href="/putr/bangunan/admin/dashboard"
                        class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="home" class="w-5 h-5"></i><span class="ml-3">Dashboard</span>
                    </a>
                    <a href="/putr/bangunan/admin/verifikasi_data"
                        class="flex items-center px-4 py-2 mt-2 text-white bg-blue-600 rounded-lg">
                        <i data-feather="database" class="w-5 h-5"></i><span class="ml-3">Verifikasi Data</span>
                    </a>
                    <a href="/putr/bangunan/admin/manajemen_pengguna"
                        class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="users" class="w-5 h-5"></i><span class="ml-3">Manajemen Pengguna</span>
                    </a>
                    <!-- START NEW HISTORY LINK -->
                    <a href="/putr/bangunan/admin/history"
                        class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="clock" class="w-5 h-5"></i><span class="ml-3">Riwayat Aktivitas</span>
                    </a>
                    <!-- END NEW HISTORY LINK -->
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
                <div class="flex-1">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 text-center md:text-left">Verifikasi Data
                        Bangunan</h1>
                </div>
                <div class="hidden md:flex items-center">
                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($admin['nama_lengkap']) ?></span>
                </div>
            </header>

            <main class="p-4 md:p-8">

                <?php if ($notif_status == 'sukses'): ?>
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Berhasil!</p>
                    <p><?= htmlspecialchars($notif_pesan) ?></p>
                </div>
                <?php endif; ?>

                <div class="mb-6">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i data-feather="search" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="text" id="inputPencarian"
                            class="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Ketik nama bangunan atau SKPD untuk mencari...">
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Bangunan</th>
                                    <th scope="col" class="px-6 py-3">Perangkat Daerah</th>
                                    <th scope="col" class="px-6 py-3">Terakhir Diperbarui</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="hasilPencarian">
                                <!-- Konten tabel akan dimuat di sini oleh AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200" id="navigasiPaginasi">
                        <!-- Konten paginasi akan dimuat di sini oleh AJAX -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    feather.replace();

    const hamburgerBtn = document.getElementById('hamburger-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }

    hamburgerBtn.addEventListener('click', openSidebar);
    closeSidebarBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    const inputPencarian = document.getElementById('inputPencarian');
    const hasilPencarian = document.getElementById('hasilPencarian');
    const navigasiPaginasi = document.getElementById('navigasiPaginasi');

    function muatData(halaman = 1, kataKunci = '') {
        const formData = new FormData();
        formData.append('halaman', halaman);
        formData.append('query', kataKunci);

        // Perbaikan Final: Menggunakan path absolut yang benar dari root server
        fetch('/putr/bangunan/admin/ajax_cari_data.php', {
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
                hasilPencarian.innerHTML = data.html_tabel;
                navigasiPaginasi.innerHTML = data.html_paginasi;
            })
            .catch(error => {
                console.error('Error:', error);
                hasilPencarian.innerHTML =
                    `<tr><td colspan="5" class="text-center text-red-500 py-4">Terjadi kesalahan: ${error.message}</td></tr>`;
                navigasiPaginasi.innerHTML = '';
            });
    }

    let timerPencarian;
    inputPencarian.addEventListener('keyup', function() {
        clearTimeout(timerPencarian);
        const kataKunci = this.value;
        timerPencarian = setTimeout(() => {
            muatData(1, kataKunci);
        }, 300);
    });

    navigasiPaginasi.addEventListener('click', function(e) {
        if (e.target.matches('a.pagination-link') && !e.target.classList.contains('pointer-events-none')) {
            e.preventDefault();
            const halamanTujuan = e.target.dataset.halaman;
            const kataKunciSaatIni = inputPencarian.value;

            if (halamanTujuan) {
                muatData(halamanTujuan, kataKunciSaatIni);
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        muatData(1, '');
    });
    </script>
</body>

</html>
