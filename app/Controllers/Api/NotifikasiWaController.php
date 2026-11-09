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

class NotifikasiWaController extends ResourceController
{
    protected $modelName = NotifikasiWaModel::class;
    protected $format    = 'json';

    /**
     * GET /api/notifikasi-wa?sekolah_id=2&status_kirim=gagal&tanggal=2026-10-29
     */
    public function index()
    {
        $sekolahId   = $this->request->getGet('sekolah_id');
        $statusKirim = $this->request->getGet('status_kirim');
        $tanggal     = $this->request->getGet('tanggal');

        return $this->respond([
            'status' => 200,
            'data'   => $this->model->getLogNotifikasi($sekolahId, $statusKirim, $tanggal),
        ]);
    }

    /**
     * POST /api/notifikasi-wa/resend-all
     * Fitur untuk Admin: Mengirim ulang SEMUA pesan WA yang gagal/pending pada tanggal tertentu
     */
    public function resendAll()
    {
        $payload   = $this->request->getJSON(true) ?? $this->request->getPost();
        $tanggal   = trim((string) ($payload['tanggal'] ?? ''));
        $sekolahId = ! empty($payload['sekolah_id']) ? (int) $payload['sekolah_id'] : null;
        $statusReq = ! empty($payload['status_kirim']) ? trim((string) $payload['status_kirim']) : null;

        if (empty($tanggal)) {
            return $this->fail('Tanggal wajib diisi untuk melakukan kirim ulang massal.', 400);
        }

        // Cari semua log notifikasi yang belum berhasil pada tanggal tersebut
        $builder = $this->model->select('
            notifikasi_wa.id as id,
            notifikasi_wa.absensi_mapel_siswa_id,
            notifikasi_wa.no_tujuan,
            notifikasi_wa.status_kirim,
            absensi_mapel_siswa.siswa_id,
            absensi_mapel_siswa.status as status_absen,
            absensi_mapel_siswa.nilai,
            absensi_mapel_siswa.catatan,
            absensi_mapel_siswa.keterangan,
            absensi_mapel_siswa.jurnal_mengajar_id,
            siswa.nama as nama_siswa,
            siswa.no_hp as siswa_no_hp,
            kelas.sekolah_id,
            sekolah.nama as nama_sekolah,
            sekolah.fonnte_token,
            sekolah.template_wa
        ')
        ->join('absensi_mapel_siswa', 'absensi_mapel_siswa.id = notifikasi_wa.absensi_mapel_siswa_id')
        ->join('siswa', 'siswa.id = absensi_mapel_siswa.siswa_id')
        ->join('jurnal_mengajar', 'jurnal_mengajar.id = absensi_mapel_siswa.jurnal_mengajar_id')
        ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
        ->join('sekolah', 'sekolah.id = kelas.sekolah_id')
        ->where('jurnal_mengajar.tanggal', $tanggal);

        if ($sekolahId) {
            $builder->where('kelas.sekolah_id', $sekolahId);
        }

        if ($statusReq) {
            $builder->where('notifikasi_wa.status_kirim', $statusReq);
        } else {
            $builder->whereIn('notifikasi_wa.status_kirim', ['gagal', 'pending']);
        }

        $list = $builder->findAll();

        if (empty($list)) {
            return $this->respond([
                'status'  => 200,
                'message' => 'Tidak ada notifikasi WhatsApp yang perlu dikirim ulang untuk tanggal ' . $tanggal . '.',
                'data'    => [
                    'total'    => 0,
                    'berhasil' => 0,
                    'gagal'    => 0,
                ],
            ]);
        }

        $jurnalModel = new JurnalMengajarModel();
        $jurnalCache = [];

        // Kelompokkan data per sekolah untuk pengiriman bulk via Fonnte token sekolah masing-masing
        $groupedBySchool = [];
        foreach ($list as $item) {
            $groupedBySchool[$item['sekolah_id']][] = $item;
        }

        $totalBerhasil = 0;
        $totalGagal    = 0;
        $now           = date('Y-m-d H:i:s');
        $fonnte        = new FonnteService();

        foreach ($groupedBySchool as $sId => $items) {
            $firstSekolah = $items[0];
            $token        = trim((string) ($firstSekolah['fonnte_token'] ?? ''));
            $namaSekolah  = $firstSekolah['nama_sekolah'] ?? 'Sekolah';
            $templateWa   = $firstSekolah['template_wa'] ?? null;

            if (empty($token) || str_starts_with($token, 'ISI_TOKEN_')) {
                foreach ($items as $item) {
                    $this->model->update($item['id'], [
                        'status_kirim'         => 'gagal',
                        'fonnte_status_detail' => 'Token Fonnte untuk ' . $namaSekolah . ' belum diatur.',
                        'waktu_kirim'          => $now,
                    ]);
                    $totalGagal++;
                }
                continue;
            }

            $bulkMessages = [];
            $itemMap      = [];

            foreach ($items as $item) {
                $noHp = trim((string) ($item['siswa_no_hp'] ?? $item['no_tujuan']));
                if (empty($noHp)) {
                    $this->model->update($item['id'], [
                        'status_kirim'         => 'gagal',
                        'fonnte_status_detail' => 'Nomor HP siswa masih kosong. Lengkapi di Data Siswa.',
                        'waktu_kirim'          => $now,
                    ]);
                    $totalGagal++;
                    continue;
                }

                $jurnalId = $item['jurnal_mengajar_id'];
                if (! isset($jurnalCache[$jurnalId])) {
                    $jurnalCache[$jurnalId] = $jurnalModel->getJurnalLengkap($jurnalId);
                }
                $jurnal = $jurnalCache[$jurnalId];

                $absenData = [
                    'id'         => $item['absensi_mapel_siswa_id'],
                    'nama_siswa' => $item['nama_siswa'],
                    'status'     => $item['status_absen'],
                    'nilai'      => $item['nilai'],
                    'keterangan' => $item['keterangan'],
                    'catatan'    => $item['catatan'],
                ];

                $isiPesanBaru = FonnteService::formatPesanAbsensi($jurnal, $absenData, $templateWa);

                $bulkIndex = count($bulkMessages);
                $bulkMessages[] = [
                    'target'  => $noHp,
                    'message' => $isiPesanBaru,
                    'delay'   => '2-4',
                ];

                $itemMap[$bulkIndex] = [
                    'id'        => $item['id'],
                    'no_tujuan' => $noHp,
                    'isi_pesan' => $isiPesanBaru,
                ];
            }

            if (empty($bulkMessages)) {
                continue;
            }

            $response  = $fonnte->sendBulk($token, $bulkMessages);
            $isSuccess = ! empty($response['status']) && $response['status'] === true;
            $detail    = $response['detail'] ?? ($response['reason'] ?? ($isSuccess ? 'Pesan berhasil dikirim' : 'Gagal mengirim'));
            $msgIds    = $response['id'] ?? [];

            foreach ($itemMap as $index => $mapped) {
                if ($isSuccess) {
                    $this->model->update($mapped['id'], [
                        'no_tujuan'            => $mapped['no_tujuan'],
                        'isi_pesan'            => $mapped['isi_pesan'],
                        'status_kirim'         => 'berhasil',
                        'fonnte_message_id'    => isset($msgIds[$index]) ? (string) $msgIds[$index] : null,
                        'fonnte_status_detail' => $detail,
                        'waktu_kirim'          => $now,
                    ]);
                    $totalBerhasil++;
                } else {
                    $this->model->update($mapped['id'], [
                        'no_tujuan'            => $mapped['no_tujuan'],
                        'isi_pesan'            => $mapped['isi_pesan'],
                        'status_kirim'         => 'gagal',
                        'fonnte_status_detail' => $detail,
                        'waktu_kirim'          => $now,
                    ]);
                    $totalGagal++;
                }
            }
        }

        $totalProses = $totalBerhasil + $totalGagal;

        return $this->respond([
            'status'  => 200,
            'message' => "Proses kirim ulang selesai: {$totalBerhasil} berhasil, {$totalGagal} gagal dari total {$totalProses} pesan.",
            'data'    => [
                'total'    => $totalProses,
                'berhasil' => $totalBerhasil,
                'gagal'    => $totalGagal,
            ],
        ]);
    }

    /**
     * POST /api/notifikasi-wa/resend/{id}
     * Fitur untuk Admin: Mengirim ulang 1 pesan WA yang sebelumnya gagal/pending
     * (Otomatis me-refresh nomor HP terbaru siswa & template_wa terbaru sekolah)
     */
    public function resend($id = null)
    {
        $notif = $this->model->find($id);
        if (! $notif) {
            return $this->failNotFound('Data log notifikasi tidak ditemukan.');
        }

        $absensiModel = new AbsensiMapelSiswaModel();
        $jurnalModel  = new JurnalMengajarModel();
        $kelasModel   = new KelasModel();
        $sekolahModel = new SekolahModel();
        $siswaModel   = new SiswaModel();

        $absen = $absensiModel->find($notif['absensi_mapel_siswa_id']);
        if (! $absen) {
            return $this->failNotFound('Data absensi terkait tidak ditemukan.');
        }

        $siswa               = $siswaModel->find($absen['siswa_id']);
        $absen['nama_siswa'] = $siswa['nama'] ?? '-';
        $noHp                = trim((string) ($siswa['no_hp'] ?? $notif['no_tujuan']));

        if (empty($noHp)) {
            return $this->fail('Nomor HP siswa masih kosong. Silakan lengkapi di menu Data Siswa terlebih dahulu.', 400);
        }

        $jurnal  = $jurnalModel->getJurnalLengkap($absen['jurnal_mengajar_id']);
        $kelas   = $kelasModel->find($jurnal['kelas_id']);
        $sekolah = $sekolahModel->find($kelas['sekolah_id']);

        $token = trim((string) ($sekolah['fonnte_token'] ?? ''));
        if (empty($token) || str_starts_with($token, 'ISI_TOKEN_')) {
            return $this->fail('Token Fonnte untuk ' . $sekolah['nama'] . ' belum diatur.', 400);
        }

        // Susun ulang pesan dengan template_wa sekolah terbaru
        $isiPesanBaru = FonnteService::formatPesanAbsensi($jurnal, $absen, $sekolah['template_wa'] ?? null);

        $fonnte   = new FonnteService();
        $response = $fonnte->sendBulk($token, [
            [
                'target'  => $noHp,
                'message' => $isiPesanBaru,
            ],
        ]);

        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Tidak ada respon Fonnte');
        $msgId     = $response['id'][0] ?? null;

        $this->model->update($id, [
            'no_tujuan'            => $noHp,
            'isi_pesan'            => $isiPesanBaru,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_message_id'    => $msgId ? (string) $msgId : null,
            'fonnte_status_detail' => $detail,
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'  => $isSuccess ? 200 : 400,
            'message' => $isSuccess ? 'Pesan WhatsApp berhasil dikirim ulang.' : 'Gagal mengirim ulang pesan: ' . $detail,
            'data'    => $this->model->find($id),
        ]);
    }
}