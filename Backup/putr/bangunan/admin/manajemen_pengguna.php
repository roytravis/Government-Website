<?php
// (admin/manajemen_pengguna.php)
session_start();
require "../includes/koneksi.php";

// Validasi Sesi dan Peran Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    // Perubahan: Menggunakan path absolut untuk redirect
    header("Location: /putr/bangunan/login");
    exit;
}

// Mengambil data admin untuk header
$user_id = $_SESSION['user_id'];
$admin_result = $conn->query("SELECT nama_lengkap FROM bg_users WHERE id = $user_id");
$admin = $admin_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Dashboard Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { transition: transform 0.3s ease-in-out; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1040; }
        .sidebar-overlay.active { display: block; }
        .modal-overlay { z-index: 1050; }
        .modal-box { z-index: 1060; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); position: fixed; height: 100%; z-index: 1070; }
            .sidebar.active { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="sidebar flex-shrink-0 w-64 bg-white border-r flex flex-col">
            <div class="flex items-center justify-between h-16 border-b px-4">
                <img src="https://dputr.tasikmalayakota.go.id/wp-content/uploads/2025/04/Logo-PUTR.png" alt="Logo PUTR" class="h-10">
                <button id="close-sidebar-btn" class="md:hidden p-2 text-gray-500 rounded-md hover:bg-gray-200">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="flex flex-col flex-grow p-4">
                <nav class="flex-grow">
                    <a href="/putr/bangunan/admin/dashboard" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="home" class="w-5 h-5"></i><span class="ml-3">Dashboard</span>
                    </a>
                    <a href="/putr/bangunan/admin/verifikasi_data" class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="database" class="w-5 h-5"></i><span class="ml-3">Verifikasi Data</span>
                    </a>
                    <a href="/putr/bangunan/admin/manajemen_pengguna" class="flex items-center px-4 py-2 mt-2 text-white bg-blue-600 rounded-lg">
                        <i data-feather="users" class="w-5 h-5"></i><span class="ml-3">Manajemen Pengguna</span>
                    </a>
                    <!-- START NEW HISTORY LINK -->
                    <a href="/putr/bangunan/admin/history"
                        class="flex items-center px-4 py-2 mt-2 text-gray-600 hover:bg-gray-200 rounded-lg">
                        <i data-feather="clock" class="w-5 h-5"></i><span class="ml-3">Riwayat Aktivitas</span>
                    </a>
                    <!-- END NEW HISTORY LINK -->
                </nav>
                <a href="/putr/bangunan/admin/logout" class="flex items-center px-4 py-2 mt-4 text-gray-600 hover:bg-red-100 hover:text-red-700 rounded-lg">
                    <i data-feather="log-out" class="w-5 h-5"></i><span class="ml-3">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <div class="flex flex-col flex-1 overflow-y-auto">
            <header class="flex items-center justify-between h-16 bg-white border-b px-4 md:px-8">
                <button id="hamburger-btn" class="md:hidden p-2 text-gray-500 rounded-md hover:bg-gray-200">
                    <i data-feather="menu" class="w-6 h-6"></i>
                </button>
                <div class="flex-1">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 text-center md:text-left">Manajemen Pengguna</h1>
                </div>
                <div class="hidden md:flex items-center">
                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($admin['nama_lengkap']) ?></span>
                </div>
            </header>

            <main class="p-4 md:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">Daftar Akun Pengguna</h2>
                    <button id="btn-add-user" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i data-feather="plus" class="w-5 h-5 mr-2"></i>Tambah Pengguna
                    </button>
                </div>

                <!-- Notifikasi Aksi -->
                <div id="notification" class="hidden mb-4 p-4 rounded-md text-sm"></div>

                <!-- Tabel Pengguna -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                                    <th scope="col" class="px-6 py-3">NIP</th>
                                    <th scope="col" class="px-6 py-3">Instansi</th>
                                    <th scope="col" class="px-6 py-3">Role</th>
                                    <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="user-table-body">
                                <!-- Data pengguna akan dimuat di sini oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal untuk Tambah/Edit Pengguna -->
    <div id="user-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center modal-overlay">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg max-h-screen overflow-y-auto modal-box">
            <form id="user-form">
                <input type="hidden" id="user-id" name="id">
                <h3 id="modal-title" class="text-xl font-bold mb-4">Tambah Pengguna Baru</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="nip" class="block text-sm font-medium text-gray-700">NIP</label>
                        <input type="text" name="nip" id="nip" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                     <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="nomor_telepon" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                        <input type="tel" name="nomor_telepon" id="nomor_telepon" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="nama_instansi" class="block text-sm font-medium text-gray-700">Nama Instansi</label>
                        <input type="text" name="nama_instansi" id="nama_instansi" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="skpd">SKPD</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Isi untuk mengubah">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" id="btn-cancel" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Konfirmasi Hapus -->
    <div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center modal-overlay">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm modal-box">
            <h3 class="text-lg font-bold">Konfirmasi Hapus</h3>
            <p class="my-4 text-gray-600">Apakah Anda yakin ingin menghapus pengguna <strong id="delete-user-name"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-end space-x-3">
                <button id="btn-cancel-delete" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button id="btn-confirm-delete" class="px-4 py-2 bg-red-600 text-white rounded-lg">Hapus</button>
            </div>
        </div>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();

        // Elemen UI
        const userTableBody = document.getElementById('user-table-body');
        const userModal = document.getElementById('user-modal');
        const userForm = document.getElementById('user-form');
        const modalTitle = document.getElementById('modal-title');
        const btnAddUser = document.getElementById('btn-add-user');
        const btnCancel = document.getElementById('btn-cancel');
        const notification = document.getElementById('notification');
        
        const deleteModal = document.getElementById('delete-modal');
        const btnCancelDelete = document.getElementById('btn-cancel-delete');
        const btnConfirmDelete = document.getElementById('btn-confirm-delete');
        let userIdToDelete = null;

        // Fungsi untuk menampilkan notifikasi
        function showNotification(message, isSuccess) {
            notification.textContent = message;
            notification.className = `p-4 rounded-md text-sm ${isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
            notification.classList.remove('hidden');
            setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }

        // Fungsi untuk memuat dan menampilkan pengguna
        async function loadUsers() {
            try {
                // Perubahan: Menggunakan path absolut untuk fetch AJAX
                const response = await fetch('/putr/bangunan/admin/api_pengguna.php?action=get_users');
                const users = await response.json();
                userTableBody.innerHTML = '';
                if (users.length > 0) {
                    users.forEach(user => {
                        const row = `
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">${user.nama_lengkap || '-'}</td>
                                <td class="px-6 py-4">${user.nip}</td>
                                <td class="px-6 py-4">${user.nama_instansi || '-'}</td>
                                <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold rounded-full ${user.role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">${user.role}</span></td>
                                <td class="px-6 py-4 text-center">
                                    <button class="p-1 text-blue-600 hover:text-blue-800 btn-edit" data-id="${user.id}"><i data-feather="edit" class="w-4 h-4"></i></button>
                                    <button class="p-1 text-red-600 hover:text-red-800 btn-delete" data-id="${user.id}" data-name="${user.nama_lengkap}"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        `;
                        userTableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    userTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Tidak ada data pengguna.</td></tr>';
                }
                feather.replace();
            } catch (error) {
                console.error('Gagal memuat pengguna:', error);
                showNotification('Gagal memuat data pengguna.', false);
            }
        }

        // Fungsi untuk membuka modal
        function openModal(mode = 'add', user = null) {
            userForm.reset();
            document.getElementById('user-id').value = '';
            if (mode === 'add') {
                modalTitle.textContent = 'Tambah Pengguna Baru';
                document.getElementById('password').setAttribute('required', 'required');
            } else {
                modalTitle.textContent = 'Edit Pengguna';
                document.getElementById('password').removeAttribute('required');
                document.getElementById('user-id').value = user.id;
                document.getElementById('nip').value = user.nip;
                document.getElementById('nama_lengkap').value = user.nama_lengkap;
                document.getElementById('email').value = user.email;
                document.getElementById('nomor_telepon').value = user.nomor_telepon;
                document.getElementById('nama_instansi').value = user.nama_instansi;
                document.getElementById('jabatan').value = user.jabatan;
                document.getElementById('role').value = user.role;
            }
            userModal.classList.remove('hidden');
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            userModal.classList.add('hidden');
        }
        
        function openDeleteModal(id, name) {
            userIdToDelete = id;
            document.getElementById('delete-user-name').textContent = name;
            deleteModal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            deleteModal.classList.add('hidden');
            userIdToDelete = null;
        }

        // Event Listeners
        btnAddUser.addEventListener('click', () => openModal('add'));
        btnCancel.addEventListener('click', closeModal);
        btnCancelDelete.addEventListener('click', closeDeleteModal);

        userForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const userId = formData.get('id');
            const action = userId ? 'update_user' : 'add_user';
            formData.append('action', action);

            try {
                // Perubahan: Menggunakan path absolut untuk fetch AJAX
                const response = await fetch('/putr/bangunan/admin/api_pengguna.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    closeModal();
                    loadUsers();
                    showNotification(result.message, true);
                } else {
                    showNotification(result.message, false);
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan pada sistem.', false);
            }
        });
        
        userTableBody.addEventListener('click', function(e) {
            const editButton = e.target.closest('.btn-edit');
            const deleteButton = e.target.closest('.btn-delete');

            if (editButton) {
                const userId = editButton.dataset.id;
                // Perubahan: Menggunakan path absolut untuk fetch AJAX
                fetch(`/putr/bangunan/admin/api_pengguna.php?action=get_user&id=${userId}`)
                    .then(res => res.json())
                    .then(user => {
                        if(user) openModal('edit', user);
                    });
            }

            if (deleteButton) {
                const userId = deleteButton.dataset.id;
                const userName = deleteButton.dataset.name;
                openDeleteModal(userId, userName);
            }
        });
        
        btnConfirmDelete.addEventListener('click', async function() {
            if (!userIdToDelete) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('id', userIdToDelete);

            try {
                // Perubahan: Menggunakan path absolut untuk fetch AJAX
                const response = await fetch('/putr/bangunan/admin/api_pengguna.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showNotification(result.message, true);
                } else {
                    showNotification(result.message, false);
                }
            } catch (error) {
                showNotification('Terjadi kesalahan pada sistem.', false);
            } finally {
                closeDeleteModal();
                loadUsers();
            }
        });

        // Sidebar responsive
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        hamburgerBtn.addEventListener('click', () => { sidebar.classList.add('active'); overlay.classList.add('active'); });
        closeSidebarBtn.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });

        // Muat pengguna saat halaman pertama kali dibuka
        loadUsers();
    });
    </script>
</body>
</html>
