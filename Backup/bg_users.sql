-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 15 Agu 2025 pada 19.28
-- Versi server: 5.7.44
-- Versi PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dputr_wp_database`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bg_users`
--

CREATE TABLE `bg_users` (
  `id` int(11) NOT NULL,
  `nip` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `nama_instansi` varchar(150) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `role` enum('skpd','admin') NOT NULL,
  `skpd_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `bg_users`
--

INSERT INTO `bg_users` (`id`, `nip`, `password`, `nama_lengkap`, `email`, `nomor_telepon`, `nama_instansi`, `jabatan`, `role`, `skpd_id`) VALUES
(7, 'admin', '$2y$10$5WvK0rXoej.8tLgJgSodI.GQHKuN.aO0kgoYKwjwFcwE9ytR8Pxvu', 'admin', '', '', '', '', 'admin', NULL),
(10, '19820209200911005', '$2y$10$euBJpzLjQLI1Cgti9k7NAeg4iV0NJNtNbFoMnCqx2fRaeEdgjqM7u', 'ANDI SUWANDI, SH', 'Suwandipraja82@gmail.com', '081295859112', 'SATPOL PP', 'PENATA LAPORAN KEUANGAN', 'skpd', NULL),
(11, '197311192010012002', '$2y$10$dmvlkgrIr0znS23zjiAZDeLqQxHy1DngX3d0L409nxdjrk8cP1BpG', 'POPI HERDIANTI, S.Sos', 'popydianty@gmail.com', '08977482310', 'Kecamatan Mangkubumi', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(12, '198212152009012004', '$2y$10$JbTUZWl99iOsbuGqISgaaedcxTEn6ycywzhlHQIHg90GUvC6kGYDO', 'ELIS SANDRANI, SE', 'elissandrani1982@gmail.com', '085318253547', 'KECAMATAN CIBEUREUM', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(13, '198111112014081001', '$2y$10$KJMMflcBy7Qbm0WPFlGn6e1cGetcjNHin28Tz2GqITHIDhyPnAuxC', 'AMIN NURDIN, Amd.Kes', 'adien1181@gmail.com', '082119382628', 'DINAS KESEHATAN', 'PENGELOLA DATA DAN INFORMASI', 'skpd', NULL),
(14, '197508142010011001', '$2y$10$/CLa5ldCdmU4Zg/z2s9k/OK2Fit6OephWvId1IXV.Bwsjhps118vO', 'ABDUL ROSID', 'abdulrosid1234@gmail.com', '081915859758', 'KECAMATAN KAWALU', 'PENATA KELOLA PEMERINTAHAN', 'skpd', NULL),
(15, '198207012009011008', '$2y$10$ZvuYceu5ymIWS134Bw4Q8.AnvyAsMkHv8ZBvP96u7jC2GD/G9eUPO', 'HERI, S.Sos', 'Herikami19820107@gmail.com', '082218499776', 'BADAN PENDAPATAN DAERAH', 'PENGURUS BARANG PENGGUNA', 'skpd', NULL),
(16, '198403292017042006', '$2y$10$jxM8E0bEUQWDH4todLw94.HwbbucWKvbdVmTZhOXyAlTV3tVpdN3e', 'DIAH DIANNITA MARGI FITRIANI', 'diahdiannitatugas@gmail.com', '0811208823', 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'PENGELOLA PEMANFAATAN BARANG MILIK DAERAH', 'skpd', NULL),
(17, '197104302007011006', '$2y$10$IhMD8HL1U3qZHlnA0wLHcu71Y/S0TjMNDX1p.oV7sOkQK59aDnAYO', 'PRAYIT HARSONO', 'prayitharsono2021@gmail.com', '085321224462', 'Dinas Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan, dan Perlindungan Anak', 'PENGELOLA BARANG PENGGUNA', 'skpd', NULL),
(19, '197805032010011002', '$2y$10$ZT5b.gd9VDGOKb8NWtZJYO8GUCgyVmwe/vxwqdE9rcS/60XN5ko56', 'HEDI SUKMAYADI, S.IP', 'Hedisukmayadi50@gmail.com', '085324577193', 'DINAS LINGKUNGAN HIDUP', 'PENGURUS BARANG', 'skpd', NULL),
(20, '197412032008012003', '$2y$10$ovUBXIwXw1rR59d9pxC8CeH12C8JQItXCDi.rCAd3norF2aHKnRNq', 'LILIS SRI RAHAYU, S.Sos', 'lilissri33@gmail.com', '082240091074', 'DINAS PERUMAHAN DAN KAWASAN PERMUKIMAN', 'PENELAAH TEKNIS KEBIJAKAN', 'skpd', NULL),
(21, '197902132009011007', '$2y$10$hTA3FSIYbYkSHdu7Q8Zqv.Of5zsekroeCSmjISgCcX4BUDiufvBEa', 'DEDE SONIH WARDANI', 'dsonaywardanyy@gmail.com', '085321648353', 'DINAS KOMUNIKASI DAN INFORMATIKA', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(22, '198512122005011003', '$2y$10$1NbksR4IrB/Xh4VWjmn/N.a/t/T2skG6wHvMY1ev83Wwqp.D7k4wO', 'ACENG FATHURROHMAN', 'Fathurrohman8585@gmail.com', '085223333885', 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'PENGELOLA PEMANFAATAN BARANG MILIK DAERAH', 'skpd', NULL),
(23, '197105222007011007', '$2y$10$23jJ3urt79fm7GkK6Rklt./QsrpcaIb7d8PMEzRPJU8BqZFtntlY6', 'DADANG KURNIA', 'dadangkurnia1971@gmail.com', '085194283282', 'KECAMATAN PURBARATU', 'PENGURUS BARANG PENGGUNA', 'skpd', NULL),
(24, '198210232010012001', '$2y$10$gjk8IcdDxfcm0Ohqv09NNufJE8hVgXfSwXeQW70hl1F7nFpsVsgla', 'VIOLA HAFIFAH', 'viola232019@gmail.com', '087843608831', 'BADAN PERENCANAAN PEMBANGUNAN, PENELITIAN DAN PENGEMBANGAN DAERAH', 'PENGELOLA DATA DAN INFORMASI', 'skpd', NULL),
(25, '199610182022032002', '$2y$10$OXL7q5jf30Fq2L8KRT1RouqOvtq9NXKWxC9STWH9eh4Mq4aPc0BEm', 'FACHREZA IMELDA, A.Md', 'fachrezaimelda13@gmail.com', '081378200452', 'DINAS PERPUSTAKAAN DAN KEARSIPAN DAERAH KOTA TASIKMALAYA', 'PENGELOLA DATA DAN INFORMASI', 'skpd', NULL),
(26, '197306252007011008', '$2y$10$3.A6e4VVn6DTZ2enuCAt4.jCkdeBkXBMi/tJagv2ehVckS91XT6uC', 'KARTONO', 'Cekartono@gmail.com', '085216834245', 'DINAS KOPERASI, USAHA MIKRO, KECIL DAN MENENGAH, PERINDUSTRIAN DAN PERDAGANGAN', 'JABATAN FUNGSIONAL UMUM', 'skpd', NULL),
(27, '196812072014081002', '$2y$10$5EWSkFaMFcVvoX6uZ2D6x.fzmOFkDipOr.GQZQQBQRQDiyi3Y/T4.', 'OOY WARSOYO', 'ooywarsoyo1968@gmail.com', '085287240964', 'INSPEKTORAT DAERAH', 'PENGELOLA DATA DAN INFORMASI', 'skpd', NULL),
(28, '198306062009011006', '$2y$10$MnjS8m5PcLZTlCNNfaRhlOF4240V46bkv3xs6f/6eXHjRCFs4ZAhG', 'ASEP ABDUL AJIJI, S.IP', 'asepabdulajiji5278@gmail.com', '087736568590', 'Dinas Kependudukan Dan Pencatatan Sipil Kota Tasikmalaya', 'PENGURUS BARANG PENGGUNA', 'skpd', NULL),
(29, '199208312025211005', '$2y$10$IbiQ6klwCq9Sw1IzATMbb..1UPNM0AdOsn9JEZMNwizY5tiiprtn2', 'ILHAM MAULANA, SE', 'ilhamjuventino21@gmail.com', '082120019970', 'KECAMATAN CIHIDEUNG', 'PENATA LAYANAN OPERASIONAL', 'skpd', NULL),
(30, '197112082008011001', '$2y$10$iCpmUSR.AeyXeY/amUfHGuivz4dp.TCOzgY2IcNBsNCwh/2b7Qqv6', 'BAI SYAEFUL BARRI', 'bsyaefulbarri.234@gmail.com', '085721651015', 'KECAMATAN INDIHIANG', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(31, '197109092007011008', '$2y$10$B3ZYhdl/LwDHSWypr0YAKu3NYxb.q8szXXXYkj/rUKCEJgxMclkMO', 'SEPTENDI, S.IP', 'septendi71@gmail.com', '081323252304', 'DINAS PERHUBUNGAN ', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(32, '198707132022031002', '$2y$10$eZzyug8glRs9p.KyLke5resXKUi7I/YLgW5otTpNVZLVUQucyZa6i', 'ALI BASYARAH. A. Md., Kep', 'arealirere1987@gmail.com', '085210497276', 'UPTD KHUSUS RSUD DEWI SARTIKA ', 'PERAWAT TERAMPIL', 'skpd', NULL),
(33, '197809012008011001', '$2y$10$Apg8Zge5pi8uLmlVr4o2tuwWDWCC5ox13Kc5dv3mdwmiS5YWXWss6', 'HENRI', 'heriheri886@gmail.com', '081323242986', 'Dinas Ketahanan Pangan, Pertanian, dan Perikanan', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(34, '198509222012121002', '$2y$10$XdbCfYSasy8ESzNHGtZTQO/TQIzUSkpazjO5p0SuftR8o.mNIip6y', 'FAJAR GUMILAR', 'gumilarfajar@gmail.com', '081227929779', 'Badan Kesatuan Bangsa dan Politik', 'PENGURUS BARANG', 'skpd', NULL),
(35, '198210052009021003', '$2y$10$1nTKmH7t.z3X21TbOOhg.OMgEzcg1ijsgF84.x4U9XmvhSDl8quwa', 'DERRY TAUFIK', 'farisuwais.fu@gmail.com', '082115667894', 'Badan Pengelola Keuangan dan Aset Daerah', 'PENGURUS BARANG PENGGUNA', 'skpd', NULL),
(36, '200012272022032003', '$2y$10$uGoUvAdx/CwFhLGSk0zEYuWRsA0urC8.mvLvbaJaaZ0gyQlKNsH.G', 'AMALIA FITRI NURUNISA', 'amaliaaafn@gmail.com', '081937602465', 'Badan Penanggulangan Bencana Daerah', 'PEMULA PEMADAM KEBAKARAN', 'skpd', NULL),
(37, '19831117201001001', '$2y$10$hAmpGY8JC.OxiQ4Jke/gfuqagy8rkkEgOh3PURbQB0kEYCm.F3iu.', 'RUDI YANTO', 'Ryanto592@yahoo.co.id', '085295472505', 'DINAS PEKERJAAN UMUM DAN TATA RUANG', 'PENGELOLA PEMANFAATAN BARANG MILIK DAERAH', 'skpd', NULL),
(38, '197209132010011003', '$2y$10$Kojzjr9STROQuC.y9MrlnemdNfFBzSeDlm40HXluguLOo94QcERqG', 'ANAN', 'ananwungkul@gmail.com', '082316454712', 'KECAMATAN TAMANSARI', 'PENGELOLA DATA DAN INFORMASI', 'skpd', NULL),
(39, '198004082014102001', '$2y$10$S9nQ4hR0jTQSm3tosedIdOjM78bmPrm.QMTBr4j5QhCo4/FovPVNK', 'TUTI CARWATI', 'tuticarwati88@gmail.com', '082115923115', 'DINAS TENAGA KERJA ', 'PENGURUS BARANG', 'skpd', NULL),
(40, '197011202007011009', '$2y$10$lo3rA1eq.FOrDjP/YZb75.KkF.saB/aOtXPTuL3mxTCzpF3jd2meq', 'IWAN WINASETIAWAN', 'iwanharsa@gmail.com', '081214143270', 'Dinas Kepemudaan, Olahraga, Kebudayaan dan Pariwisata', 'PENGELOLA DATA DAN INFORMASI', 'skpd', NULL),
(41, '197612072006042015', '$2y$10$tYWl5UX3YjDXQn09zxIQke7Bs3XipW944ta7t7PVSgJYSXy5t1a2u', 'YANTI HADIANTI, S.Kep., Ners', 'dyanti_noor@yahoo.com', '08122188100', 'UPTD KHUSUS RSUD DR. SOEKARDJO', 'KEPALA INSTALASI PENGELOLAAN ASET RSUD', 'skpd', NULL),
(42, '197503192019052001', '$2y$10$xNdQIZdxeDd1H0t0TdNrA.6IyAQ83cGGXZRJ/Ti9Vx32PS88hfxjS', 'ERNA MARVINA, A.Md., Keb', 'erna.marvina75@gmail.com', '082362539164', 'DINAS SOSIAL', 'PENGOLAH DATA DAN INFORMASI', 'skpd', NULL),
(43, '197701052014081001', '$2y$10$uRHA/V/Vr2PhYJncLKx4S.ap0qYr7nSZGe07JZgFqqpCCfapcuGYK', 'OJAT SUDRAJAT', 'sudrajatojat7@gmail.com', '082119288274', 'DINAS PENDIDIKAN', 'PENGURUS BARANG', 'skpd', NULL),
(44, '197105052008011027', '$2y$10$e2kTBcgInXMQkfK4nE1sEeOH8vdKEJG9tlmm0GC8pzUM3Zb3ZOSvW', 'Toni Alamsyah', 'Tonyalamsyah3@gmail.com', '082318048144', 'KECAMATAN CIPEDES', 'JABATAN FUNGSIONAL UMUM', 'skpd', NULL),
(45, '20252025', '$2y$10$fD3.q9TBCIO2fWFVUkif.ePkw4BNLyDxCivVDwwux.dkypROXczxW', 'admin_pentest', 'admin_pentest@gmail.com', '666666', '', '', 'admin', NULL),
(46, '20242024', '$2y$10$ruhdu7Jw1KG/VfdmPEnGo.hz0fAZgQJ3QhKtULt5znviq7LhxQC0q', 'user_pentest', '', '', '', '', 'skpd', NULL),
(47, '1337', '$2y$10$l6JqO18.a71ZOwLI9MnDC.DlR7pFUsBjDjJ.mO08zkyf.Zb7bt17i', 'kepo', 'aaaaa@gmail.copm', '086435353', 'aaaaaa', 'aaaa', 'skpd', NULL),
(48, '199208152020121001', '$2y$10$o8V7.gakRRrT2tv55GBOkOz2a0w/zAd439wiwRl7fwThTw9aUktey', 'Reiza Ginanjar', 'reizaginanjar67@gmail.com', '082120398404', 'DPUTR Kota Tasikmalaya', 'Penata Bangunan Gedung dan Permukiman', 'admin', NULL),
(49, '199509262022031005', '$2y$10$j5eyW/nsTnSSWJwYVP98cOp/dSc.GSMn6znUgRklGtBn8VBG3gzyO', 'M. Saepul Mikdar', 'msaepulm26@gmail.com', '0812-9899-3456', 'DPUTR Kota Tasikmalaya', 'Penelaah Teknis Kebijakan', 'admin', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bg_users`
--
ALTER TABLE `bg_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bg_users`
--
ALTER TABLE `bg_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
