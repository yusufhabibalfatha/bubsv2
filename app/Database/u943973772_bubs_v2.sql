-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 30, 2026 at 06:03 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u943973772_bubs_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi_guru`
--

CREATE TABLE `absensi_guru` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `guru_id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `tipe_absen` enum('datang','pulang') NOT NULL DEFAULT 'datang',
  `waktu_absen` time NOT NULL,
  `kategori_posisi` enum('di_sekolah','di_luar') NOT NULL DEFAULT 'di_sekolah',
  `lokasi_sekolah_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('hadir','terlambat','dinas_luar','sakit','izin') NOT NULL DEFAULT 'hadir',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `jarak_meter` int(10) UNSIGNED DEFAULT NULL,
  `dalam_radius` tinyint(1) NOT NULL DEFAULT 1,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `absensi_mapel_siswa`
--

CREATE TABLE `absensi_mapel_siswa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `jurnal_mengajar_id` bigint(20) UNSIGNED NOT NULL,
  `siswa_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('hadir','izin','sakit','alpa','terlambat','dispensasi') NOT NULL DEFAULT 'hadir',
  `nilai` decimal(5,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sekolah_id` bigint(20) UNSIGNED NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenjang`
--

CREATE TABLE `jenjang` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jurnal_mengajar`
--

CREATE TABLE `jurnal_mengajar` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kelas_id` bigint(20) UNSIGNED NOT NULL,
  `tahun_ajaran_id` bigint(20) UNSIGNED NOT NULL,
  `guru_id` bigint(20) UNSIGNED NOT NULL,
  `mata_pelajaran_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_ke` varchar(20) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `materi` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sekolah_id` bigint(20) UNSIGNED NOT NULL,
  `tahun_ajaran_id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(50) NOT NULL,
  `tingkat` varchar(30) DEFAULT NULL,
  `wali_guru_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `lokasi_sekolah`
--

CREATE TABLE `lokasi_sekolah` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_lokasi` varchar(100) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mata_pelajaran`
--

CREATE TABLE `mata_pelajaran` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_wa`
--

CREATE TABLE `notifikasi_wa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `absensi_mapel_siswa_id` bigint(20) UNSIGNED NOT NULL,
  `no_tujuan` varchar(30) DEFAULT NULL,
  `isi_pesan` text NOT NULL,
  `status_kirim` enum('pending','berhasil','gagal') NOT NULL DEFAULT 'pending',
  `fonnte_message_id` varchar(50) DEFAULT NULL,
  `fonnte_status_detail` varchar(255) DEFAULT NULL,
  `waktu_kirim` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_wa_guru`
--

CREATE TABLE `notifikasi_wa_guru` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `absensi_guru_id` bigint(20) UNSIGNED NOT NULL,
  `group_tujuan` varchar(100) DEFAULT NULL,
  `isi_pesan` text NOT NULL,
  `status_kirim` enum('pending','berhasil','gagal') NOT NULL DEFAULT 'pending',
  `fonnte_message_id` varchar(50) DEFAULT NULL,
  `fonnte_status_detail` varchar(255) DEFAULT NULL,
  `waktu_kirim` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sekolah`
--

CREATE TABLE `sekolah` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `jenjang_id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `fonnte_token` varchar(100) DEFAULT NULL,
  `is_wa_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `template_wa` text DEFAULT NULL,
  `radius_meter` int(10) UNSIGNED NOT NULL DEFAULT 500,
  `jam_masuk_guru` time NOT NULL DEFAULT '07:20:00',
  `jam_pulang_guru` time NOT NULL DEFAULT '11:30:00',
  `wa_group_guru_id` varchar(100) DEFAULT NULL,
  `template_wa_guru` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama` varchar(150) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `status` enum('aktif','lulus','pindah','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa_kelas`
--

CREATE TABLE `siswa_kelas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `siswa_id` bigint(20) UNSIGNED NOT NULL,
  `kelas_id` bigint(20) UNSIGNED NOT NULL,
  `tahun_ajaran_id` bigint(20) UNSIGNED NOT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nama_tahun` varchar(20) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_absen_guru_harian` (`guru_id`,`tanggal`,`tipe_absen`),
  ADD KEY `fk_absen_guru_lokasi` (`lokasi_sekolah_id`),
  ADD KEY `idx_absen_guru_tanggal` (`tanggal`),
  ADD KEY `idx_absen_guru_status` (`status`);

--
-- Indexes for table `absensi_mapel_siswa`
--
ALTER TABLE `absensi_mapel_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_absensi_jurnal_siswa` (`jurnal_mengajar_id`,`siswa_id`),
  ADD KEY `idx_absensi_siswa` (`siswa_id`),
  ADD KEY `idx_absensi_status` (`status`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_guru_sekolah_nip` (`sekolah_id`,`nip`),
  ADD KEY `idx_guru_sekolah` (`sekolah_id`),
  ADD KEY `idx_guru_nama` (`nama`);

--
-- Indexes for table `jenjang`
--
ALTER TABLE `jenjang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_jenjang_nama` (`nama`);

--
-- Indexes for table `jurnal_mengajar`
--
ALTER TABLE `jurnal_mengajar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jurnal_kelas_tahun` (`kelas_id`,`tahun_ajaran_id`),
  ADD KEY `idx_jurnal_tanggal` (`tanggal`),
  ADD KEY `idx_jurnal_kelas_tanggal` (`kelas_id`,`tanggal`),
  ADD KEY `idx_jurnal_guru_tanggal` (`guru_id`,`tanggal`),
  ADD KEY `idx_jurnal_mapel` (`mata_pelajaran_id`),
  ADD KEY `idx_jurnal_tahun` (`tahun_ajaran_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_kelas_sekolah_tahun_nama` (`sekolah_id`,`tahun_ajaran_id`,`nama`),
  ADD UNIQUE KEY `uk_kelas_id_tahun` (`id`,`tahun_ajaran_id`),
  ADD KEY `idx_kelas_tahun` (`tahun_ajaran_id`),
  ADD KEY `idx_kelas_wali` (`wali_guru_id`);

--
-- Indexes for table `lokasi_sekolah`
--
ALTER TABLE `lokasi_sekolah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_lokasi_nama` (`nama_lokasi`);

--
-- Indexes for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mapel_nama` (`nama`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi_wa`
--
ALTER TABLE `notifikasi_wa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_absensi` (`absensi_mapel_siswa_id`),
  ADD KEY `idx_notif_status` (`status_kirim`);

--
-- Indexes for table `notifikasi_wa_guru`
--
ALTER TABLE `notifikasi_wa_guru`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_guru_absen` (`absensi_guru_id`),
  ADD KEY `idx_notif_guru_status` (`status_kirim`);

--
-- Indexes for table `sekolah`
--
ALTER TABLE `sekolah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sekolah_jenjang` (`jenjang_id`),
  ADD KEY `idx_sekolah_status` (`status`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_siswa_nama` (`nama`),
  ADD KEY `idx_siswa_no_hp` (`no_hp`),
  ADD KEY `idx_siswa_status` (`status`);

--
-- Indexes for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_siswa_kelas_tahun` (`siswa_id`,`tahun_ajaran_id`),
  ADD KEY `fk_siswa_kelas_kelas_tahun` (`kelas_id`,`tahun_ajaran_id`),
  ADD KEY `idx_siswa_kelas_kelas` (`kelas_id`),
  ADD KEY `idx_siswa_kelas_tahun` (`tahun_ajaran_id`);

--
-- Indexes for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tahun_semester` (`nama_tahun`,`semester`),
  ADD KEY `idx_tahun_aktif` (`is_aktif`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `absensi_mapel_siswa`
--
ALTER TABLE `absensi_mapel_siswa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jenjang`
--
ALTER TABLE `jenjang`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jurnal_mengajar`
--
ALTER TABLE `jurnal_mengajar`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lokasi_sekolah`
--
ALTER TABLE `lokasi_sekolah`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifikasi_wa`
--
ALTER TABLE `notifikasi_wa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifikasi_wa_guru`
--
ALTER TABLE `notifikasi_wa_guru`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sekolah`
--
ALTER TABLE `sekolah`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD CONSTRAINT `fk_absen_guru_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_absen_guru_lokasi` FOREIGN KEY (`lokasi_sekolah_id`) REFERENCES `lokasi_sekolah` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `absensi_mapel_siswa`
--
ALTER TABLE `absensi_mapel_siswa`
  ADD CONSTRAINT `fk_absensi_jurnal` FOREIGN KEY (`jurnal_mengajar_id`) REFERENCES `jurnal_mengajar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `fk_guru_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `jurnal_mengajar`
--
ALTER TABLE `jurnal_mengajar`
  ADD CONSTRAINT `fk_jurnal_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_kelas_tahun` FOREIGN KEY (`kelas_id`,`tahun_ajaran_id`) REFERENCES `kelas` (`id`, `tahun_ajaran_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_mapel` FOREIGN KEY (`mata_pelajaran_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_sekolah` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kelas_tahun_ajaran` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_kelas_wali_guru` FOREIGN KEY (`wali_guru_id`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifikasi_wa`
--
ALTER TABLE `notifikasi_wa`
  ADD CONSTRAINT `fk_notif_absensi` FOREIGN KEY (`absensi_mapel_siswa_id`) REFERENCES `absensi_mapel_siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifikasi_wa_guru`
--
ALTER TABLE `notifikasi_wa_guru`
  ADD CONSTRAINT `fk_notif_wa_absensi_guru` FOREIGN KEY (`absensi_guru_id`) REFERENCES `absensi_guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sekolah`
--
ALTER TABLE `sekolah`
  ADD CONSTRAINT `fk_sekolah_jenjang` FOREIGN KEY (`jenjang_id`) REFERENCES `jenjang` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  ADD CONSTRAINT `fk_siswa_kelas_kelas_tahun` FOREIGN KEY (`kelas_id`,`tahun_ajaran_id`) REFERENCES `kelas` (`id`, `tahun_ajaran_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_siswa_kelas_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
