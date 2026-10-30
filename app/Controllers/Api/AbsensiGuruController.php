<?php

namespace App\Controllers\Api;

use App\Libraries\FonnteService;
use App\Models\AbsensiGuruModel;
use App\Models\GuruModel;
use App\Models\NotifikasiWaGuruModel;
use App\Models\SekolahModel;
use CodeIgniter\RESTful\ResourceController;

class AbsensiGuruController extends ResourceController
{
    protected $modelName = AbsensiGuruModel::class;
    protected $format    = 'json';

    /**
     * GET /api/absensi-guru?sekolah_id=2&tanggal_mulai=2026-10-01&tanggal_selesai=2026-10-31&guru_id=3
     */
    public function index()
    {
        $sekolahId      = $this->request->getGet('sekolah_id');
        $guruId         = $this->request->getGet('guru_id');
        $tanggal        = $this->request->getGet('tanggal');
        $tanggalMulai   = $this->request->getGet('tanggal_mulai');
        $tanggalSelesai = $this->request->getGet('tanggal_selesai');

        $builder = $this->model->select('
                            absensi_guru.*,
                            guru.nama as nama_guru,
                            guru.nip,
                            guru.sekolah_id,
                            sekolah.nama as nama_sekolah,
                            jenjang.nama as nama_jenjang,
                            lokasi_sekolah.nama_lokasi
                        ')
                        ->join('guru', 'guru.id = absensi_guru.guru_id')
                        ->join('sekolah', 'sekolah.id = guru.sekolah_id')
                        ->join('jenjang', 'jenjang.id = sekolah.jenjang_id')
                        ->join('lokasi_sekolah', 'lokasi_sekolah.id = absensi_guru.lokasi_sekolah_id', 'left');

        if ($sekolahId) {
            $builder->where('guru.sekolah_id', $sekolahId);
        }
        if ($guruId) {
            $builder->where('absensi_guru.guru_id', $guruId);
        }
        if ($tanggal) {
            $builder->where('absensi_guru.tanggal', $tanggal);
        }
        if ($tanggalMulai) {
            $builder->where('absensi_guru.tanggal >=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $builder->where('absensi_guru.tanggal <=', $tanggalSelesai);
        }

        $data = $builder->orderBy('absensi_guru.tanggal', 'DESC')
                        ->orderBy('absensi_guru.waktu_absen', 'DESC')
                        ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $data,
        ]);
    }

    /**
     * GET /api/absensi-guru/dashboard?tanggal=2026-10-30&sekolah_id=2
     * Ringkasan Beranda untuk Web Absen Guru (Siapa saja yang sudah absen Datang & Pulang hari ini)
     */
    public function dashboard()
    {
        $tanggal   = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $sekolahId = $this->request->getGet('sekolah_id');

        $guruModel = new GuruModel();
        $guruList  = $guruModel->getGuruWithSekolah($sekolahId);

        $absenHariIni = $this->model->select('absensi_guru.*, lokasi_sekolah.nama_lokasi')
                                    ->join('guru', 'guru.id = absensi_guru.guru_id')
                                    ->join('lokasi_sekolah', 'lokasi_sekolah.id = absensi_guru.lokasi_sekolah_id', 'left')
                                    ->where('absensi_guru.tanggal', $tanggal);

        if ($sekolahId) {
            $absenHariIni->where('guru.sekolah_id', $sekolahId);
        }

        $rows = $absenHariIni->findAll();

        // Petakan per guru_id untuk datang dan pulang
        $mapGuru = [];
        $countDatang = 0;
        $countPulang = 0;

        foreach ($rows as $r) {
            $gid = $r['guru_id'];
            if ($r['tipe_absen'] === 'datang') {
                $mapGuru[$gid]['datang'] = $r;
                $countDatang++;
            } else {
                $mapGuru[$gid]['pulang'] = $r;
                $countPulang++;
            }
        }

        $rekapHarianGuru = [];
        foreach ($guruList as $g) {
            if ($g['status'] !== 'aktif') {
                continue;
            }
            $rekapHarianGuru[] = [
                'guru_id'      => $g['id'],
                'nama_guru'    => $g['nama'],
                'nama_sekolah' => $g['nama_sekolah'],
                'datang'       => $mapGuru[$g['id']]['datang'] ?? null,
                'pulang'       => $mapGuru[$g['id']]['pulang'] ?? null,
            ];
        }

        return $this->respond([
            'status' => 200,
            'data'   => [
                'tanggal' => $tanggal,
                'summary' => [
                    'total_guru'   => count($rekapHarianGuru),
                    'sudah_datang' => $countDatang,
                    'belum_datang' => max(0, count($rekapHarianGuru) - $countDatang),
                    'sudah_pulang' => $countPulang,
                ],
                'daftar_guru' => $rekapHarianGuru,
            ],
        ]);
    }

    /**
     * POST /api/absensi-guru
     * Validasi Jarak GPS 500m (Jika di sekolah), Simpan Absen, & Kirim WA ke Grup Sekolah Induk
     */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        $guruId = $payload['guru_id'] ?? null;
        if (! $guruId) {
            return $this->failValidationErrors(['guru_id' => 'Nama guru wajib dipilih.']);
        }

        $guruModel    = new GuruModel();
        $sekolahModel = new SekolahModel();

        $guru = $guruModel->find($guruId);
        if (! $guru) {
            return $this->failNotFound('Data guru tidak ditemukan.');
        }

        $sekolah = $sekolahModel->find($guru['sekolah_id']);
        if (! $sekolah) {
            return $this->failNotFound('Sekolah induk guru tidak ditemukan.');
        }

        $tanggal        = $payload['tanggal'] ?? date('Y-m-d');
        $waktuAbsen     = $payload['waktu_absen'] ?? date('H:i:s');
        $tipeAbsen      = $payload['tipe_absen'] ?? 'datang';
        $kategoriPosisi = $payload['kategori_posisi'] ?? 'di_sekolah';

        // Cek apakah guru sudah absen datang/pulang hari ini
        $sudahAda = $this->model->where([
            'guru_id'    => $guruId,
            'tanggal'    => $tanggal,
            'tipe_absen' => $tipeAbsen,
        ])->first();

        if ($sudahAda) {
            return $this->fail("Anda sudah melakukan absen " . strtoupper($tipeAbsen) . " pada tanggal ini.", 409);
        }

        // Hitung jarak GPS ke koordinat sekolah induk
        $latGuru = isset($payload['latitude']) && $payload['latitude'] !== '' ? (float) $payload['latitude'] : null;
        $lonGuru = isset($payload['longitude']) && $payload['longitude'] !== '' ? (float) $payload['longitude'] : null;

        $jarakMeter  = null;
        $dalamRadius = 0;
        $batasRadius = (int) ($sekolah['radius_meter'] ?? 500);

        if ($latGuru !== null && $lonGuru !== null && ! empty($sekolah['latitude']) && ! empty($sekolah['longitude'])) {
            $jarakMeter  = FonnteService::hitungJarakMeter($latGuru, $lonGuru, (float) $sekolah['latitude'], (float) $sekolah['longitude']);
            $dalamRadius = ($jarakMeter <= $batasRadius) ? 1 : 0;
        }

        // Validasi Ketat: Jika memilih "di_sekolah", wajib menyalakan GPS dan berada <= radius_meter (500m)
        if ($kategoriPosisi === 'di_sekolah') {
            if ($jarakMeter === null) {
                return $this->fail('Lokasi GPS belum terdeteksi. Pastikan izin lokasi HP Anda aktif.', 400);
            }
            if (! $dalamRadius) {
                return $this->fail("Posisi Anda berada {$jarakMeter} meter dari sekolah (Melebihi batas radius {$batasRadius} meter). Jika sedang Dinas Luar/Sakit/Izin, silakan pilih kategori Di Luar Sekolah.", 400);
            }
            if (empty($payload['lokasi_sekolah_id'])) {
                return $this->failValidationErrors(['lokasi_sekolah_id' => 'Silakan pilih ruangan / spot posisi Anda di sekolah.']);
            }
        }

        // Tentukan status kehadiran otomatis jika di_sekolah & tidak dikirim eksplisit
        $status = $payload['status'] ?? 'hadir';
        if ($kategoriPosisi === 'di_sekolah' && $tipeAbsen === 'datang' && empty($payload['status'])) {
            $jamMasukBatas = $sekolah['jam_masuk_guru'] ?? '07:20:00';
            $status = ($waktuAbsen > $jamMasukBatas) ? 'terlambat' : 'hadir';
        }

        $insertData = [
            'guru_id'           => $guruId,
            'tanggal'           => $tanggal,
            'tipe_absen'        => $tipeAbsen,
            'waktu_absen'       => $waktuAbsen,
            'kategori_posisi'   => $kategoriPosisi,
            'lokasi_sekolah_id' => $kategoriPosisi === 'di_sekolah' ? ($payload['lokasi_sekolah_id'] ?? null) : null,
            'status'            => $status,
            'latitude'          => $latGuru,
            'longitude'         => $lonGuru,
            'jarak_meter'       => $jarakMeter,
            'dalam_radius'      => $dalamRadius,
            'keterangan'        => $payload['keterangan'] ?? null,
        ];

        if (! $this->model->insert($insertData)) {
            return $this->failValidationErrors($this->model->errors());
        }

        $absenId      = $this->model->getInsertID();
        $absenLengkap = $this->model->getAbsensiLengkap($absenId);

        // Kirim Notifikasi WA ke Grup Sekolah Induk Guru (SMP atau SMA)
        $hasilWa = $this->kirimWaGrupSekolah($absenLengkap, $sekolah);

        return $this->respondCreated([
            'status'        => 201,
            'message'       => 'Absensi guru berhasil disimpan.',
            'notifikasi_wa' => $hasilWa,
            'data'          => $absenLengkap,
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data absensi guru tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data absensi guru berhasil diperbarui.',
            'data'    => $this->model->getAbsensiLengkap($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data absensi guru tidak ditemukan.');
        }

        $this->model->delete($id);
        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Data absensi guru berhasil dihapus.',
        ]);
    }

    /**
     * Mengirim laporan absensi guru ke WhatsApp Group sekolah induknya (SMP / SMA)
     */
    protected function kirimWaGrupSekolah(array $absenLengkap, array $sekolah): array
    {
        $notifModel = new NotifikasiWaGuruModel();

        $isWaAktif = (int) ($sekolah['is_wa_aktif'] ?? 0) === 1;
        $token     = trim((string) ($sekolah['fonnte_token'] ?? ''));
        $groupId   = trim((string) ($sekolah['wa_group_guru_id'] ?? ''));
        $isiPesan  = FonnteService::formatPesanAbsensiGuru($absenLengkap, $sekolah['template_wa_guru'] ?? null);

        if (! $isWaAktif || empty($token) || str_starts_with($token, 'ISI_TOKEN_') || empty($groupId)) {
            $alasan = ! $isWaAktif
                ? 'Fitur WA Sekolah sedang dinonaktifkan.'
                : (empty($groupId) ? 'ID Grup WA Guru belum diatur.' : 'Token Fonnte belum diatur.');

            $notifModel->insert([
                'absensi_guru_id'      => $absenLengkap['id'],
                'group_tujuan'         => $groupId ?: null,
                'isi_pesan'            => $isiPesan,
                'status_kirim'         => 'pending',
                'fonnte_status_detail' => $alasan,
            ]);

            return [
                'dikirim'      => false,
                'keterangan'   => $alasan,
                'preview_teks' => $isiPesan,
            ];
        }

        $fonnte   = new FonnteService();
        $response = $fonnte->sendBulk($token, [
            [
                'target'  => $groupId,
                'message' => $isiPesan,
            ],
        ]);

        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Tidak ada detail dari Fonnte');
        $msgId     = $response['id'][0] ?? null;

        $notifModel->insert([
            'absensi_guru_id'      => $absenLengkap['id'],
            'group_tujuan'         => $groupId,
            'isi_pesan'            => $isiPesan,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_message_id'    => $msgId ? (string) $msgId : null,
            'fonnte_status_detail' => $detail,
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return [
            'dikirim'      => $isSuccess,
            'keterangan'   => $detail,
            'preview_teks' => $isiPesan,
        ];
    }
}