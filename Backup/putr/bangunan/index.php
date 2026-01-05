<?php
// Mulai sesi PHP
session_start();

// Set sebuah penanda sesi untuk menandakan bahwa pengguna telah mengunjungi halaman index.
$_SESSION['has_visited_index'] = true;

// Meng-include header yang berisi menu navigasi dinamis
include "includes/header.php";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Pendataan Bangunan Gedung - Pemkot Tasikmalaya</title>
    
    <!-- Memuat Tailwind CSS dari CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Memuat Font Inter dari Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Perubahan: Menggunakan path absolut untuk file CSS -->
    <link rel="stylesheet" href="/putr/bangunan/assets/style.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        .hero-section {
            background-color: #ffffff;
            padding: 4rem 1rem;
        }
        .stats-section {
            padding: 4rem 1rem;
        }
        .cta-section {
            background-color: #f1f5f9;
            padding: 4rem 1rem;
            text-align: center;
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Bagian Hero -->
    <div class="hero-section text-center">
        <h1 class="text-3xl md:text-5xl font-extrabold text-blue-600 tracking-tight">Sistem Informasi Pelaporan<br>Bangunan Gedung Pemerintah</h1>
        <p class="mt-4 max-w-3xl mx-auto text-lg text-gray-600">Selamat datang di portal publik untuk pelaporan dan pendataan aset bangunan gedung milik Pemerintah Kota Tasikmalaya.</p>
    </div>

    <!-- Bagian Statistik -->
    <div class="stats-section container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Statistik Data Bangunan</h2>
            <p class="mt-2 text-md text-gray-500">Data agregat dari seluruh bangunan yang telah terdata dalam sistem.</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Grafik Sebaran Bangunan per Kecamatan -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Jumlah Bangunan per Kecamatan</h3>
                <p class="text-sm text-gray-500 mb-4">Distribusi bangunan gedung yang terdata di setiap kecamatan.</p>
                <div class="chart-container">
                    <canvas id="grafikKecamatan"></canvas>
                </div>
            </div>

            <!-- Grafik Status Verifikasi -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Status Verifikasi Formulir</h3>
                <p class="text-sm text-gray-500 mb-4">Ringkasan status dari seluruh data yang masuk.</p>
                <div class="chart-container">
                    <canvas id="grafikVerifikasi"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian Call to Action (CTA) -->
    <div class="cta-section">
        <h2 class="text-3xl font-bold text-gray-800">Siap Mengelola Data?</h2>
        <p class="mt-2 text-lg text-gray-600">Masuk ke sistem untuk memulai pendataan, verifikasi, dan manajemen data bangunan gedung.</p>
        <div class="mt-8">
            <!-- Perubahan: Menggunakan path absolut untuk tautan login -->
            <a href="/putr/bangunan/login" class="inline-block bg-blue-600 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:bg-blue-700 transition-colors">
                Login Petugas / Admin
            </a>
        </div>
    </div>

    <!-- PENAMBAHAN: Bagian Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="container mx-auto px-4 py-6 text-center text-gray-600">
            <p class="font-semibold">&copy; <?php echo date("Y"); ?> - Dinas Pekerjaan Umum dan Tata Ruang</p>
            <p class="text-sm">Pemerintah Kota Tasikmalaya</p>
        </div>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Perubahan: Menggunakan path absolut untuk fetch API
        fetch('/putr/bangunan/api_statistik')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                const ctxKecamatan = document.getElementById('grafikKecamatan');
                const ctxVerifikasi = document.getElementById('grafikVerifikasi');

                if (!ctxKecamatan || !ctxVerifikasi) return;

                const kecLabels = data.kecamatan.map(item => item.kecamatan);
                const kecData = data.kecamatan.map(item => item.jumlah);
                
                const chartColors = {
                    blue: '#3b82f6', green: '#10b981', yellow: '#f59e0b',
                    red: '#ef4444', purple: '#8b5cf6', teal: '#14b8a6',
                    pink: '#ec4899', orange: '#f97316', indigo: '#6366f1',
                    gray: '#6b7280'
                };
                const colorPalette = Object.values(chartColors);

                new Chart(ctxKecamatan, {
                    type: 'bar',
                    data: {
                        labels: kecLabels,
                        datasets: [{
                            label: 'Jumlah Bangunan',
                            data: kecData,
                            backgroundColor: colorPalette,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });

                const statusColorMap = {
                    'Diverifikasi': chartColors.green,
                    'Revisi Formulir': chartColors.red,
                    'Belum Diverifikasi': chartColors.yellow,
                    'Menunggu Tinjauan Ulang': chartColors.purple
                };
                const statLabels = Object.keys(data.verifikasi);
                const statData = Object.values(data.verifikasi);
                const statColors = statLabels.map(label => statusColorMap[label] || chartColors.gray);

                new Chart(ctxVerifikasi, {
                    type: 'doughnut',
                    data: {
                        labels: statLabels,
                        datasets: [{ data: statData, backgroundColor: statColors, hoverOffset: 4 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    boxWidth: 12,
                                    font: { family: "'Inter', sans-serif", size: 12 }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            })
            .catch(error => console.error('Gagal memuat data statistik:', error));
    });
    </script>

</body>
</html>
