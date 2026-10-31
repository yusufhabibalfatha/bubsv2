<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class LaporanController extends BaseController
{
    use ResponseTrait;

    /**
     * GET /api/laporan/dashboard?sekolah_id=2&tanggal=2026-10-29
     * Menyediakan angka ringkasan untuk kartu-kartu di halaman Dashboard Utama
     */
    public function dashboard()
    {
        $db        = \Config\Database::connect();
        $sekolahId = $this->request->getGet('sekolah_id');
        $tanggal   = $this->request->getGet('tanggal') ?: date('Y-m-d');

        // Ambil Tahun Ajaran Aktif
        $tahunAktif    = $db->table('tahun_ajaran')->where('is_aktif', 1)->get()->getRowArray();
        $tahunAjaranId = $tahunAktif['id'] ?? null;

        // 1. Total Guru Aktif
        $guruBuilder = $db->table('guru')->where('status', 'aktif');
        if ($sekolahId) {
            $guruBuilder->where('sekolah_id', $sekolahId);
        }
        $totalGuru = $guruBuilder->countAllResults();

        // 2. Total Kelas Aktif (pada tahun ajaran aktif)
        $kelasBuilder = $db->table('kelas')->where('is_aktif', 1);
        if ($sekolahId) {
            $kelasBuilder->where('sekolah_id', $sekolahId);
        }
        if ($tahunAjaranId) {
            $kelasBuilder->where('tahun_ajaran_id', $tahunAjaranId);
        }
        $totalKelas = $kelasBuilder->countAllResults();

        // 3. Total Siswa Aktif (terdaftar di kelas pada tahun ajaran aktif)
        $siswaBuilder = $db->table('siswa_kelas')
                           ->join('kelas', 'kelas.id = siswa_kelas.kelas_id')
                           ->join('siswa', 'siswa.id = siswa_kelas.siswa_id')
                           ->where('siswa_kelas.is_aktif', 1)
                           ->where('siswa.status', 'aktif');
        if ($sekolahId) {
            $siswaBuilder->where('kelas.sekolah_id', $sekolahId);
        }
        if ($tahunAjaranId) {
            $siswaBuilder->where('siswa_kelas.tahun_ajaran_id', $tahunAjaranId);
        }
        $totalSiswa = $siswaBuilder->countAllResults();

        // 4. Total Jurnal Mengajar Hari Ini
        $jurnalBuilder = $db->table('jurnal_mengajar')
                            ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
                            ->where('jurnal_mengajar.tanggal', $tanggal);
        if ($sekolahId) {
            $jurnalBuilder->where('kelas.sekolah_id', $sekolahId);
        }
        $totalJurnalHariIni = $jurnalBuilder->countAllResults();

        // 5. Statistik Status Kehadiran Hari Ini
        $absenBuilder = $db->table('absensi_mapel_siswa')
                           ->select('absensi_mapel_siswa.status, COUNT(absensi_mapel_siswa.id) as jumlah')
                           ->join('jurnal_mengajar', 'jurnal_mengajar.id = absensi_mapel_siswa.jurnal_mengajar_id')
                           ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
                           ->where('jurnal_mengajar.tanggal', $tanggal)
                           ->groupBy('absensi_mapel_siswa.status');
        if ($sekolahId) {
            $absenBuilder->where('kelas.sekolah_id', $sekolahId);
        }
        $rawAbsen = $absenBuilder->get()->getResultArray();

        $statistikAbsen = [
            'hadir'      => 0,
            'izin'       => 0,
            'sakit'      => 0,
            'alpa'       => 0,
            'terlambat'  => 0,
            'dispensasi' => 0,
        ];
        foreach ($rawAbsen as $row) {
            $statistikAbsen[$row['status']] = (int) $row['jumlah'];
        }

        // 6. Statistik Pengiriman Notifikasi WA Hari Ini
        $waBuilder = $db->table('notifikasi_wa')
                        ->select('notifikasi_wa.status_kirim, COUNT(notifikasi_wa.id) as jumlah')
                        ->join('absensi_mapel_siswa', 'absensi_mapel_siswa.id = notifikasi_wa.absensi_mapel_siswa_id')
                        ->join('jurnal_mengajar', 'jurnal_mengajar.id = absensi_mapel_siswa.jurnal_mengajar_id')
                        ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
                        ->where('jurnal_mengajar.tanggal', $tanggal)
                        ->groupBy('notifikasi_wa.status_kirim');
        if ($sekolahId) {
            $waBuilder->where('kelas.sekolah_id', $sekolahId);
        }
        $rawWa = $waBuilder->get()->getResultArray();

        $statistikWa = [
            'berhasil' => 0,
            'pending'  => 0,
            'gagal'    => 0,
        ];
        foreach ($rawWa as $row) {
            $statistikWa[$row['status_kirim']] = (int) $row['jumlah'];
        }

        return $this->respond([
            'status' => 200,
            'data'   => [
                'tanggal'          => $tanggal,
                'tahun_ajaran'     => $tahunAktif,
                'summary_master'   => [
                    'total_siswa_aktif' => $totalSiswa,
                    'total_guru_aktif'  => $totalGuru,
                    'total_kelas_aktif' => $totalKelas,
                ],
                'jurnal_hari_ini'  => $totalJurnalHariIni,
                'absensi_hari_ini' => $statistikAbsen,
                'wa_hari_ini'      => $statistikWa,
            ],
        ]);
    }

    /**
     * GET /api/laporan/rekap-kelas?kelas_id=4&mata_pelajaran_id=1&tanggal_mulai=2026-10-01&tanggal_selesai=2026-10-31
     * Menghasilkan tabel rekapitulasi kehadiran & rata-rata nilai per siswa dalam satu kelas
     */
    public function rekapKelas()
    {
        $kelasId        = $this->request->getGet('kelas_id');
        $mapelId        = $this->request->getGet('mata_pelajaran_id');
        $tanggalMulai   = $this->request->getGet('tanggal_mulai');
        $tanggalSelesai = $this->request->getGet('tanggal_selesai');

        if (! $kelasId) {
            return $this->failValidationErrors(['kelas_id' => 'Parameter kelas_id wajib diisi untuk melihat rekap kelas.']);
        }

        $db = \Config\Database::connect();

        // Ambil informasi kelas
        $infoKelas = $db->table('kelas')
                        ->select('kelas.*, sekolah.nama as nama_sekolah, tahun_ajaran.nama_tahun, tahun_ajaran.semester, guru.nama as nama_wali_kelas')
                        ->join('sekolah', 'sekolah.id = kelas.sekolah_id')
                        ->join('tahun_ajaran', 'tahun_ajaran.id = kelas.tahun_ajaran_id')
                        ->join('guru', 'guru.id = kelas.wali_guru_id', 'left')
                        ->where('kelas.id', $kelasId)
                        ->get()
                        ->getRowArray();

        if (! $infoKelas) {
            return $this->failNotFound('Data kelas tidak ditemukan.');
        }

        // Ambil daftar siswa di kelas tersebut
        $daftarSiswa = $db->table('siswa_kelas')
                          ->select('siswa.id as siswa_id, siswa.nama as nama_siswa, siswa.jenis_kelamin')
                          ->join('siswa', 'siswa.id = siswa_kelas.siswa_id')
                          ->where('siswa_kelas.kelas_id', $kelasId)
                          ->where('siswa_kelas.is_aktif', 1)
                          ->orderBy('siswa.nama', 'ASC')
                          ->get()
                          ->getResultArray();

        // Hitung agregasi absensi & nilai dari tabel absensi_mapel_siswa + jurnal_mengajar
        $rekapBuilder = $db->table('absensi_mapel_siswa')
                           ->select("
                               absensi_mapel_siswa.siswa_id,
                               COUNT(absensi_mapel_siswa.id) as total_pertemuan,
                               SUM(CASE WHEN absensi_mapel_siswa.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                               SUM(CASE WHEN absensi_mapel_siswa.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                               SUM(CASE WHEN absensi_mapel_siswa.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                               SUM(CASE WHEN absensi_mapel_siswa.status = 'izin' THEN 1 ELSE 0 END) as izin,
                               SUM(CASE WHEN absensi_mapel_siswa.status = 'dispensasi' THEN 1 ELSE 0 END) as dispensasi,
                               SUM(CASE WHEN absensi_mapel_siswa.status = 'alpa' THEN 1 ELSE 0 END) as alpa,
                               ROUND(AVG(absensi_mapel_siswa.nilai), 2) as rata_rata_nilai
                           ")
                           ->join('jurnal_mengajar', 'jurnal_mengajar.id = absensi_mapel_siswa.jurnal_mengajar_id')
                           ->where('jurnal_mengajar.kelas_id', $kelasId)
                           ->groupBy('absensi_mapel_siswa.siswa_id');

        if ($mapelId) {
            $rekapBuilder->where('jurnal_mengajar.mata_pelajaran_id', $mapelId);
        }
        if ($tanggalMulai) {
            $rekapBuilder->where('jurnal_mengajar.tanggal >=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $rekapBuilder->where('jurnal_mengajar.tanggal <=', $tanggalSelesai);
        }

        $hasilAgregasi = $rekapBuilder->get()->getResultArray();

        // Petakan hasil agregasi berdasarkan siswa_id agar siswa yang belum pernah diabsen tetap muncul dengan angka 0
        $mapAgregasi = [];
        foreach ($hasilAgregasi as $row) {
            $mapAgregasi[$row['siswa_id']] = $row;
        }

        $rekapSiswa = [];
        foreach ($daftarSiswa as $siswa) {
            $sid   = $siswa['siswa_id'];
            $stat  = $mapAgregasi[$sid] ?? null;
            $total = (int) ($stat['total_pertemuan'] ?? 0);
            $hadir = (int) ($stat['hadir'] ?? 0);
            $telat = (int) ($stat['terlambat'] ?? 0);

            // Kehadiran efektif = hadir + terlambat
            $persentaseHadir = $total > 0 ? round((($hadir + $telat) / $total) * 100, 1) : 0;

            $rekapSiswa[] = [
                'siswa_id'         => (int) $sid,
                'nama_siswa'       => $siswa['nama_siswa'],
                'jenis_kelamin'    => $siswa['jenis_kelamin'],
                'total_pertemuan'  => $total,
                'hadir'            => $hadir,
                'terlambat'        => $telat,
                'sakit'            => (int) ($stat['sakit'] ?? 0),
                'izin'             => (int) ($stat['izin'] ?? 0),
                'dispensasi'       => (int) ($stat['dispensasi'] ?? 0),
                'alpa'             => (int) ($stat['alpa'] ?? 0),
                'persentase_hadir' => $persentaseHadir,
                'rata_rata_nilai'  => isset($stat['rata_rata_nilai']) ? (float) $stat['rata_rata_nilai'] : null,
            ];
        }

        return $this->respond([
            'status' => 200,
            'data'   => [
                'info_kelas' => $infoKelas,
                'filter'     => [
                    'mata_pelajaran_id' => $mapelId,
                    'tanggal_mulai'     => $tanggalMulai,
                    'tanggal_selesai'   => $tanggalSelesai,
                ],
                'rekap_siswa' => $rekapSiswa,
            ],
        ]);
    }
}