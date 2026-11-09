-- =========================================================
-- DATABASE ABSENSI DAN JURNAL MENGAJAR SEKOLAH
-- MySQL 8.0+ / MariaDB yang mendukung InnoDB dan CHECK
--
-- Prinsip desain:
-- 1. Satu database dapat menampung beberapa sekolah dan jenjang.
-- 2. Tidak ada tabel login/role, pondok, jadwal, atau pemetaan guru-mapel-kelas.
-- 3. Jurnal mengajar mencatat kegiatan yang benar-benar terjadi.
-- 4. Kelas dibuat per tahun ajaran agar riwayat tetap terjaga.
-- 5. Absensi siswa dapat menyimpan status, nilai (opsional), dan catatan siswa.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS absensi_mapel_siswa;
DROP TABLE IF EXISTS jurnal_mengajar;
DROP TABLE IF EXISTS siswa_kelas;
DROP TABLE IF EXISTS siswa;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS mata_pelajaran;
DROP TABLE IF EXISTS guru;
DROP TABLE IF EXISTS tahun_ajaran;
DROP TABLE IF EXISTS sekolah;
DROP TABLE IF EXISTS jenjang;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE jenjang (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_jenjang_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sekolah (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jenjang_id BIGINT UNSIGNED NOT NULL,
    nama VARCHAR(150) NOT NULL,
    alamat TEXT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sekolah_jenjang (jenjang_id),
    KEY idx_sekolah_status (status),
    CONSTRAINT fk_sekolah_jenjang
        FOREIGN KEY (jenjang_id) REFERENCES jenjang(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tahun_ajaran (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_tahun VARCHAR(20) NOT NULL,
    semester ENUM('Ganjil','Genap') NOT NULL,
    is_aktif TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_tahun_semester (nama_tahun, semester),
    KEY idx_tahun_aktif (is_aktif),
    CONSTRAINT chk_tahun_aktif CHECK (is_aktif IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guru (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sekolah_id BIGINT UNSIGNED NOT NULL,
    nip VARCHAR(30) NULL,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    no_hp VARCHAR(30) NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_guru_sekolah (sekolah_id),
    KEY idx_guru_nama (nama),
    UNIQUE KEY uk_guru_sekolah_nip (sekolah_id, nip),
    CONSTRAINT fk_guru_sekolah
        FOREIGN KEY (sekolah_id) REFERENCES sekolah(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mata_pelajaran (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_mapel_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kelas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sekolah_id BIGINT UNSIGNED NOT NULL,
    tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
    nama VARCHAR(50) NOT NULL,
    tingkat VARCHAR(30) NULL,
    wali_guru_id BIGINT UNSIGNED NULL,
    is_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_kelas_sekolah_tahun_nama (sekolah_id, tahun_ajaran_id, nama),
    UNIQUE KEY uk_kelas_id_tahun (id, tahun_ajaran_id),
    KEY idx_kelas_tahun (tahun_ajaran_id),
    KEY idx_kelas_wali (wali_guru_id),
    CONSTRAINT chk_kelas_aktif CHECK (is_aktif IN (0,1)),
    CONSTRAINT fk_kelas_sekolah
        FOREIGN KEY (sekolah_id) REFERENCES sekolah(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kelas_tahun_ajaran
        FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_kelas_wali_guru
        FOREIGN KEY (wali_guru_id) REFERENCES guru(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE siswa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(150) NOT NULL,
    no_hp VARCHAR(20) NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    status ENUM('aktif','lulus','pindah','nonaktif')
        NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_siswa_nama (nama),
    KEY idx_siswa_no_hp (no_hp),
    KEY idx_siswa_status (status)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE siswa_kelas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id BIGINT UNSIGNED NOT NULL,
    kelas_id BIGINT UNSIGNED NOT NULL,
    tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
    is_aktif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_siswa_kelas_tahun (siswa_id, tahun_ajaran_id),
    KEY idx_siswa_kelas_kelas (kelas_id),
    KEY idx_siswa_kelas_tahun (tahun_ajaran_id),
    CONSTRAINT chk_siswa_kelas_aktif CHECK (is_aktif IN (0,1)),
    CONSTRAINT fk_siswa_kelas_siswa
        FOREIGN KEY (siswa_id) REFERENCES siswa(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_siswa_kelas_kelas_tahun
        FOREIGN KEY (kelas_id, tahun_ajaran_id) REFERENCES kelas(id, tahun_ajaran_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jurnal_mengajar (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kelas_id BIGINT UNSIGNED NOT NULL,
    tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
    guru_id BIGINT UNSIGNED NOT NULL,
    mata_pelajaran_id BIGINT UNSIGNED NULL,
    tanggal DATE NOT NULL,
    jam_ke VARCHAR(20) NULL,
    jam_mulai TIME NULL,
    jam_selesai TIME NULL,
    materi TEXT NULL,
    catatan TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jurnal_tanggal (tanggal),
    KEY idx_jurnal_kelas_tanggal (kelas_id, tanggal),
    KEY idx_jurnal_guru_tanggal (guru_id, tanggal),
    KEY idx_jurnal_mapel (mata_pelajaran_id),
    KEY idx_jurnal_tahun (tahun_ajaran_id),
    CONSTRAINT fk_jurnal_kelas_tahun
        FOREIGN KEY (kelas_id, tahun_ajaran_id) REFERENCES kelas(id, tahun_ajaran_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_jurnal_guru
        FOREIGN KEY (guru_id) REFERENCES guru(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_jurnal_mapel
        FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE absensi_mapel_siswa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jurnal_mengajar_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    status ENUM('hadir','izin','sakit','alpa','terlambat') NOT NULL DEFAULT 'hadir',
    nilai DECIMAL(5,2) NULL,
    catatan TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_absensi_jurnal_siswa (jurnal_mengajar_id, siswa_id),
    KEY idx_absensi_siswa (siswa_id),
    KEY idx_absensi_status (status),
    CONSTRAINT chk_absensi_nilai CHECK (nilai IS NULL OR (nilai >= 0 AND nilai <= 100)),
    CONSTRAINT fk_absensi_jurnal
        FOREIGN KEY (jurnal_mengajar_id) REFERENCES jurnal_mengajar(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_absensi_siswa
        FOREIGN KEY (siswa_id) REFERENCES siswa(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- DATA CONTOH (opsional): dua sekolah berbeda jenjang
-- Hapus bagian INSERT ini jika ingin database kosong.
-- =========================================================

INSERT INTO jenjang (id, nama) VALUES
(1, 'SMP'),
(2, 'SMA');

INSERT INTO sekolah (id, jenjang_id, nama, alamat) VALUES
(1, 1, 'SMP Budi Utomo', 'Tarakan, Kalimantan Utara'),
(2, 2, 'SMA Budi Utomo', 'Tarakan, Kalimantan Utara');

INSERT INTO tahun_ajaran (id, nama_tahun, semester, is_aktif) VALUES
(1, '2025/2026', 'Ganjil', 0),
(2, '2025/2026', 'Genap', 0),
(3, '2026/2027', 'Ganjil', 1);

INSERT INTO guru (id, sekolah_id, nama, email, no_hp) VALUES
(1, 1, 'Ahmad Fauzi', NULL, NULL),
(2, 1, 'Siti Rahma', NULL, NULL),
(3, 2, 'Budi Santoso', NULL, NULL),
(4, 2, 'Dewi Lestari', NULL, NULL);

INSERT INTO mata_pelajaran (id, nama) VALUES
(1, 'Informatika'),
(2, 'Matematika'),
(3, 'Bahasa Indonesia');

INSERT INTO kelas (id, sekolah_id, tahun_ajaran_id, nama, tingkat, wali_guru_id) VALUES
(1, 1, 3, 'VII A', 'VII', 1),
(2, 1, 3, 'VIII A', 'VIII', 2),
(3, 2, 3, 'X IPA 1', 'X', 3),
(4, 2, 3, 'XI IPA 1', 'XI', 4),
(5, 1, 1, 'VII A', 'VII', 1);

INSERT INTO siswa (id, nik, nama, jenis_kelamin, status) VALUES
(1, 'SMP0001', 'Andi Pratama', 'L', 'aktif'),
(2, 'SMP0002', 'Budi Setiawan', 'L', 'aktif'),
(3, 'SMP0003', 'Citra Lestari', 'P', 'aktif'),
(4, 'SMA0001', 'Dimas Saputra', 'L', 'aktif'),
(5, 'SMA0002', 'Eka Putri', 'P', 'aktif'),
(6, 'SMA0003', 'Fajar Ramadhan', 'L', 'aktif');

INSERT INTO siswa_kelas (id, siswa_id, kelas_id, tahun_ajaran_id, is_aktif) VALUES
(1, 1, 1, 3, 1),
(2, 2, 1, 3, 1),
(3, 3, 2, 3, 1),
(4, 4, 3, 3, 1),
(5, 5, 3, 3, 1),
(6, 6, 4, 3, 1),
(7, 3, 5, 1, 0);

INSERT INTO jurnal_mengajar
    (id, kelas_id, tahun_ajaran_id, guru_id, mata_pelajaran_id, tanggal,
     jam_ke, jam_mulai, jam_selesai, materi, catatan)
VALUES
(1, 1, 3, 1, 1, '2026-07-11', '1-2', '07:00:00', '08:10:00',
 'Berpikir Komputasional', 'Pembelajaran pengenalan berpikir komputasional.');

INSERT INTO absensi_mapel_siswa
    (jurnal_mengajar_id, siswa_id, status, nilai, catatan)
VALUES
(1, 1, 'hadir', 90.00, 'Aktif bertanya dan mengikuti kegiatan dengan baik.'),
(1, 2, 'sakit', NULL, 'Tidak ada nilai karena tidak mengikuti praktik.'),
(1, 3, 'hadir', 85.50, 'Perlu lebih teliti saat menyelesaikan tugas.');
