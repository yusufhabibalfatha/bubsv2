-- ============================================================================
-- SQL MIGRASI FITUR KEUANGAN & SPP SISWA (UNTUK PHPMYADMIN SHARED HOSTING)
-- Jalankan SQL ini di phpMyAdmin Hosting jika tidak menggunakan SSH `php spark migrate`
-- ============================================================================

CREATE TABLE IF NOT EXISTS `pengaturan_keuangan` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pin_bendahara` varchar(50) NOT NULL DEFAULT '123456',
  `nama_bendahara` varchar(100) NOT NULL DEFAULT 'Bendahara Sekolah',
  `template_wa_bayar` text DEFAULT NULL,
  `template_wa_reminder` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pengaturan_keuangan` (`id`, `pin_bendahara`, `nama_bendahara`, `template_wa_bayar`, `template_wa_reminder`, `created_at`, `updated_at`)
SELECT 1, '123456', 'Bendahara Sekolah',
'*BUKTI PEMBAYARAN KEUANGAN SEKOLAH*\n*{nama_sekolah}*\n--------------------------------\nNo. Kwitansi : {kode_transaksi}\nNama Siswa : *{nama_siswa}* ({kelas})\nPembayaran : {nama_tagihan} {periode}\nTanggal : {tanggal} ({metode})\nJumlah Dibayar : *{nominal_bayar}*\nSisa Tagihan : *{sisa_tagihan}*\nStatus : *{status}*\n--------------------------------\nTerima kasih kepada Bapak/Ibu Orang Tua/Wali atas pembayarannya.',
'*INFORMASI TAGIHAN KEUANGAN SEKOLAH*\n*{nama_sekolah}*\n--------------------------------\nYth. Orang Tua/Wali dari *{nama_siswa}* (Kelas {kelas}),\n\nBerikut informasi rincian administrasi keuangan sekolah yang masih belum lunas / sedang dicicil:\n{rincian_tunggakan}\n\n*Total Sisa Tagihan: {total_tunggakan}*\n--------------------------------\nPembayaran dapat dilakukan secara lunas maupun dicicil melalui Bendahara Sekolah. Terima kasih.',
NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `pengaturan_keuangan` WHERE `id` = 1);

CREATE TABLE IF NOT EXISTS `pos_tagihan` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tahun_ajaran_id` int(11) UNSIGNED NOT NULL,
  `sekolah_id` int(11) UNSIGNED DEFAULT NULL,
  `nama_pos` varchar(120) NOT NULL,
  `tipe` enum('bulanan','bebas') NOT NULL DEFAULT 'bulanan',
  `nominal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tahun_ajaran_id` (`tahun_ajaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tagihan_siswa` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pos_tagihan_id` int(11) UNSIGNED NOT NULL,
  `siswa_id` int(11) UNSIGNED NOT NULL,
  `tahun_ajaran_id` int(11) UNSIGNED NOT NULL,
  `bulan_kode` varchar(2) DEFAULT NULL,
  `bulan_label` varchar(30) DEFAULT NULL,
  `urutan_bulan` tinyint(2) DEFAULT NULL,
  `nominal_tagihan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nominal_terbayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('belum_bayar','dicicil','lunas') NOT NULL DEFAULT 'belum_bayar',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_tagihan_id_siswa_id` (`pos_tagihan_id`,`siswa_id`),
  KEY `siswa_id_tahun_ajaran_id` (`siswa_id`,`tahun_ajaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pembayaran_keuangan` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(40) NOT NULL,
  `tagihan_siswa_id` int(11) UNSIGNED NOT NULL,
  `siswa_id` int(11) UNSIGNED NOT NULL,
  `pos_tagihan_id` int(11) UNSIGNED NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `waktu_bayar` time DEFAULT NULL,
  `nominal_bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sisa_setelah_bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status_setelah_bayar` enum('dicicil','lunas') NOT NULL DEFAULT 'dicicil',
  `metode_bayar` enum('tunai','transfer') NOT NULL DEFAULT 'tunai',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `tagihan_siswa_id` (`tagihan_siswa_id`),
  KEY `siswa_id` (`siswa_id`),
  KEY `tanggal_bayar` (`tanggal_bayar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifikasi_wa_keuangan` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) UNSIGNED NOT NULL,
  `pembayaran_id` int(11) UNSIGNED DEFAULT NULL,
  `tipe_notif` enum('kwitansi','reminder') NOT NULL DEFAULT 'kwitansi',
  `no_tujuan` varchar(30) NOT NULL,
  `isi_pesan` text NOT NULL,
  `status_kirim` enum('pending','berhasil','gagal') NOT NULL DEFAULT 'pending',
  `fonnte_message_id` varchar(50) DEFAULT NULL,
  `fonnte_status_detail` varchar(255) DEFAULT NULL,
  `waktu_kirim` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `siswa_id` (`siswa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
