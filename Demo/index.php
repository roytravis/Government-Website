<?php include "includes/header.php"; ?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Pelaporan Bangunan Gedung Pemerintah</title>
    <!-- Bootstrap CSS (CDN) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="container">
        <h1>Dashboard Pelaporan Bangunan Gedung Pemerintah</h1>
        <p style="text-align:center;">
            Selamat datang di sistem informasi pendataan bangunan gedung milik Pemerintah Kota Tasikmalaya
        </p>
        <div class="grafik-area grafik-flex">
            <div class="grafik-kiri">
                <strong>Grafik Jumlah Bangunan per Kecamatan</strong>
                <canvas id="chartKecamatan" height="150"></canvas>
            </div>
            <div class="grafik-kanan">
                <strong>Status Verifikasi Formulir</strong>
                <canvas id="chartVerifikasi" height="150"></canvas>
            </div>
        </div>
        <div style="text-align:center;">
            <a href="login.php" class="btn btn-primary">Login Petugas/Admin</a>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    fetch('includes/api_statistik.php')
        .then(response => response.json())
        .then(data => {
            // Grafik Bar per Kecamatan
            const kecLabels = data.kecamatan.map(item => item.kecamatan);
            const kecData = data.kecamatan.map(item => item.jumlah);

            new Chart(document.getElementById('chartKecamatan'), {
                type: 'bar',
                data: {
                    labels: kecLabels,
                    datasets: [{
                        label: 'Jumlah Bangunan',
                        data: kecData,
                        backgroundColor: '#007bff'
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Pie Chart Status Verifikasi
            const statLabels = Object.keys(data.verifikasi);
            const statData = Object.values(data.verifikasi);

            new Chart(document.getElementById('chartVerifikasi'), {
                type: 'pie',
                data: {
                    labels: statLabels,
                    datasets: [{
                        data: statData,
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                    }]
                }
            });
        });
    </script>

</body>

</html>