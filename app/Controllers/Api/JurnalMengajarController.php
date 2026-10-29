<?php

namespace App\Controllers\Api;

use App\Libraries\FonnteService;
use App\Models\AbsensiMapelSiswaModel;
use App\Models\JurnalMengajarModel;
use App\Models\KelasModel;
use App\Models\NotifikasiWaModel;
use App\Models\SekolahModel;
use App\Models\SiswaModel;
use CodeIgniter\RESTful\ResourceController;

class JurnalMengajarController extends ResourceController
{
    protected $modelName = JurnalMengajarModel::class;
    protected $format    = 'json';

    /**
     * GET /api/jurnal-mengajar?sekolah_id=2&kelas_id=4&guru_id=3&tanggal=2026-10-29
     */
    public function index()
    {
        $sekolahId     = $this->request->getGet('sekolah_id');
        $kelasId       = $this->request->getGet('kelas_id');
        $guruId        = $this->request->getGet('guru_id');
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');
        $tanggal       = $this->request->getGet('tanggal');

        $builder = $this->model->select('
                            jurnal_mengajar.*,
                            kelas.nama as nama_kelas,
                            kelas.sekolah_id,
                            sekolah.nama as nama_sekolah,
                            guru.nama as nama_guru,
                            mata_pelajaran.nama as nama_mapel,
                            tahun_ajaran.nama_tahun,
                            tahun_ajaran.semester
                        ')
                        ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
                        ->join('sekolah', 'sekolah.id = kelas.sekolah_id')
                        ->join('guru', 'guru.id = jurnal_mengajar.guru_id')
                        ->join('mata_pelajaran', 'mata_pelajaran.id = jurnal_mengajar.mata_pelajaran_id', 'left')
                        ->join('tahun_ajaran', 'tahun_ajaran.id = jurnal_mengajar.tahun_ajaran_id');

        if ($sekolahId) {
            $builder->where('kelas.sekolah_id', $sekolahId);
        }
        if ($kelasId) {
            $builder->where('jurnal_mengajar.kelas_id', $kelasId);
        }
        if ($guruId) {
            $builder->where('jurnal_mengajar.guru_id', $guruId);
        }
        if ($tahunAjaranId) {
            $builder->where('jurnal_mengajar.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($tanggal) {
            $builder->where('jurnal_mengajar.tanggal', $tanggal);
        }

        $data = $builder->orderBy('jurnal_mengajar.tanggal', 'DESC')
                        ->orderBy('jurnal_mengajar.jam_mulai', 'DESC')
                        ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $data,
        ]);
    }

    /**
     * GET /api/jurnal-mengajar/{id}
     * Menampilkan detail jurnal beserta daftar absensi siswa di dalamnya
     */
    public function show($id = null)
    {
        $jurnal = $this->model->getJurnalLengkap($id);
        if (! $jurnal) {
            return $this->failNotFound('Data jurnal mengajar tidak ditemukan.');
        }

        $absensiModel      = new AbsensiMapelSiswaModel();
        $jurnal['absensi'] = $absensiModel->getAbsensiByJurnal($id);

        return $this->respond([
            'status' => 200,
            'data'   => $jurnal,
        ]);
    }

    /**
     * POST /api/jurnal-mengajar
     * Menyimpan Jurnal Mengajar + Daftar Absensi Siswa + Otomatis Kirim WA via Fonnte
     */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        // Ambil tahun_ajaran_id otomatis dari tabel kelas jika tidak dikirim dari frontend
        if (empty($payload['tahun_ajaran_id']) && ! empty($payload['kelas_id'])) {
            $kelasModel = new KelasModel();
            $kelas      = $kelasModel->find($payload['kelas_id']);
            if ($kelas) {
                $payload['tahun_ajaran_id'] = $kelas['tahun_ajaran_id'];
            }
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1. Simpan Jurnal Mengajar
            if (! $this->model->insert($payload)) {
                $db->transRollback();
                return $this->failValidationErrors($this->model->errors());
            }

            $jurnalId     = $this->model->getInsertID();
            $absensiModel = new AbsensiMapelSiswaModel();
            $listAbsensi  = $payload['absensi'] ?? [];

            // 2. Simpan Daftar Absensi Siswa (jika dikirim bersama jurnal)
            if (! empty($listAbsensi) && is_array($listAbsensi)) {
                foreach ($listAbsensi as $item) {
                    $rowAbsen = [
                        'jurnal_mengajar_id' => $jurnalId,
                        'siswa_id'           => $item['siswa_id'] ?? null,
                        'status'             => $item['status'] ?? 'hadir',
                        'nilai'              => ($item['nilai'] ?? '') !== '' ? $item['nilai'] : null,
                        'catatan'            => $item['catatan'] ?? null,
                        'keterangan'         => $item['keterangan'] ?? null,
                    ];

                    if (! $absensiModel->insert($rowAbsen)) {
                        $db->transRollback();
                        return $this->failValidationErrors($absensiModel->errors());
                    }
                }
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->fail('Gagal menyimpan jurnal dan absensi: ' . $e->getMessage(), 500);
        }

        // 3. Proses Pengiriman Notifikasi WhatsApp (di luar transaksi DB agar absen tetap aman jika internet/WA gangguan)
        $hasilWa = $this->prosesKirimWaJurnal($jurnalId);

        $jurnalLengkap            = $this->model->getJurnalLengkap($jurnalId);
        $jurnalLengkap['absensi'] = (new AbsensiMapelSiswaModel())->getAbsensiByJurnal($jurnalId);

        return $this->respondCreated([
            'status'        => 201,
            'message'       => 'Jurnal mengajar dan absensi berhasil disimpan.',
            'notifikasi_wa' => $hasilWa,
            'data'          => $jurnalLengkap,
        ]);
    }

    /**
     * PUT /api/jurnal-mengajar/{id}
     */
    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data jurnal mengajar tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data jurnal mengajar berhasil diperbarui.',
            'data'    => $this->model->getJurnalLengkap($id),
        ]);
    }

    /**
     * DELETE /api/jurnal-mengajar/{id}
     */
    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data jurnal mengajar tidak ditemukan.');
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Data jurnal mengajar beserta absensi terkait berhasil dihapus.',
        ]);
    }

    /**
     * Helper Internal: Merangkai pesan WA dari template_wa sekolah, menembak API Fonnte, & mencatat ke tabel notifikasi_wa
     */
    protected function prosesKirimWaJurnal(int $jurnalId): array
    {
        $jurnal = $this->model->getJurnalLengkap($jurnalId);
        if (! $jurnal) {
            return ['dikirim' => false, 'keterangan' => 'Jurnal tidak ditemukan.'];
        }

        // Ambil konfigurasi sekolah (token & template_wa) melalui tabel kelas
        $kelasModel   = new KelasModel();
        $sekolahModel = new SekolahModel();
        $absensiModel = new AbsensiMapelSiswaModel();
        $siswaModel   = new SiswaModel();
        $notifModel   = new NotifikasiWaModel();

        $kelas   = $kelasModel->find($jurnal['kelas_id']);
        $sekolah = $kelas ? $sekolahModel->find($kelas['sekolah_id']) : null;

        if (! $sekolah) {
            return ['dikirim' => false, 'keterangan' => 'Data sekolah tidak ditemukan.'];
        }

        $daftarAbsensi = $absensiModel->getAbsensiByJurnal($jurnalId);
        if (empty($daftarAbsensi)) {
            return ['dikirim' => false, 'keterangan' => 'Tidak ada daftar absensi siswa.'];
        }

        $isWaAktif  = (int) ($sekolah['is_wa_aktif'] ?? 0) === 1;
        $token      = trim((string) ($sekolah['fonnte_token'] ?? ''));
        $templateWa = $sekolah['template_wa'] ?? null;

        $bulkMessages = [];
        $notifRecords = []; // Menyimpan ID baris notifikasi_wa yang siap dikirim ke Fonnte

        foreach ($daftarAbsensi as $absen) {
            $siswa = $siswaModel->find($absen['siswa_id']);
            $noHp  = trim((string) ($siswa['no_hp'] ?? ''));

            $isiPesan = FonnteService::formatPesanAbsensi($jurnal, $absen, $templateWa);

            // Jika nomor HP siswa/ortu kosong, langsung catat gagal di log
            if (empty($noHp)) {
                $notifModel->insert([
                    'absensi_mapel_siswa_id' => $absen['id'],
                    'no_tujuan'              => null,
                    'isi_pesan'              => $isiPesan,
                    'status_kirim'           => 'gagal',
                    'fonnte_status_detail'   => 'Nomor HP siswa/orang tua kosong.',
                    'waktu_kirim'            => date('Y-m-d H:i:s'),
                ]);
                continue;
            }

            // Jika fitur WA sekolah dimatikan atau token belum diisi
            if (! $isWaAktif || empty($token) || str_starts_with($token, 'ISI_TOKEN_')) {
                $notifModel->insert([
                    'absensi_mapel_siswa_id' => $absen['id'],
                    'no_tujuan'              => $noHp,
                    'isi_pesan'              => $isiPesan,
                    'status_kirim'           => 'pending',
                    'fonnte_status_detail'   => ! $isWaAktif ? 'Fitur WA sekolah sedang dinonaktifkan.' : 'Token Fonnte sekolah belum diatur.',
                    'waktu_kirim'            => null,
                ]);
                continue;
            }

            // Simpan status awal pending sebelum ditembak ke Fonnte
            $notifModel->insert([
                'absensi_mapel_siswa_id' => $absen['id'],
                'no_tujuan'              => $noHp,
                'isi_pesan'              => $isiPesan,
                'status_kirim'           => 'pending',
            ]);
            $notifId = $notifModel->getInsertID();

            $bulkMessages[] = [
                'target'  => $noHp,
                'message' => $isiPesan,
                'delay'   => '2-4', // Jeda acak 2-4 detik antar siswa agar aman dari blokir WA
            ];
            $notifRecords[] = $notifId;
        }

        if (empty($bulkMessages)) {
            return [
                'dikirim'    => false,
                'keterangan' => 'Pesan dicatat ke log, namun tidak dikirim ke Fonnte (cek pengaturan token/nomor HP siswa).',
            ];
        }

        // Kirim 1 kali HTTP Request Bulk ke Fonnte
        $fonnte   = new FonnteService();
        $response = $fonnte->sendBulk($token, $bulkMessages);
        $now      = date('Y-m-d H:i:s');

        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Tidak ada detail dari Fonnte');
        $msgIds    = $response['id'] ?? [];

        foreach ($notifRecords as $index => $notifId) {
            $notifModel->update($notifId, [
                'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
                'fonnte_message_id'    => $isSuccess && isset($msgIds[$index]) ? (string) $msgIds[$index] : null,
                'fonnte_status_detail' => $detail,
                'waktu_kirim'          => $now,
            ]);
        }

        return [
            'dikirim'       => $isSuccess,
            'jumlah_target' => count($bulkMessages),
            'respon_fonnte' => $detail,
        ];
    }
}