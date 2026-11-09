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
            notifikasi_wa.jurnal_mengajar_id,
            notifikasi_wa.no_tujuan,
            notifikasi_wa.status_kirim,
            kelas.id as kelas_id,
            kelas.nama as nama_kelas,
            kelas.wa_group_id,
            kelas.sekolah_id,
            sekolah.nama as nama_sekolah,
            sekolah.fonnte_token
        ')
        ->join('jurnal_mengajar', 'jurnal_mengajar.id = notifikasi_wa.jurnal_mengajar_id')
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

        $jurnalModel  = new JurnalMengajarModel();
        $absensiModel = new AbsensiMapelSiswaModel();

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
                $waGroupId = trim((string) ($item['wa_group_id'] ?? $item['no_tujuan']));
                if (empty($waGroupId)) {
                    $this->model->update($item['id'], [
                        'status_kirim'         => 'gagal',
                        'fonnte_status_detail' => 'ID Grup WA Kelas (' . ($item['nama_kelas'] ?? '-') . ') masih kosong. Lengkapi di Master Kelas.',
                        'waktu_kirim'          => $now,
                    ]);
                    $totalGagal++;
                    continue;
                }

                $jurnalId      = (int) $item['jurnal_mengajar_id'];
                $jurnal        = $jurnalModel->getJurnalLengkap($jurnalId);
                $daftarAbsensi = $absensiModel->getAbsensiByJurnal($jurnalId);

                $isiPesanBaru = FonnteService::formatPesanRingkasanKelas($jurnal, $daftarAbsensi);

                $bulkIndex = count($bulkMessages);
                $bulkMessages[] = [
                    'target'  => $waGroupId,
                    'message' => $isiPesanBaru,
                    'delay'   => '2-4',
                ];

                $itemMap[$bulkIndex] = [
                    'id'        => $item['id'],
                    'no_tujuan' => $waGroupId,
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
     * Fitur untuk Admin: Mengirim ulang 1 pesan ringkasan WA Grup Kelas yang sebelumnya gagal/pending
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

        $jurnalId = (int) $notif['jurnal_mengajar_id'];
        $jurnal   = $jurnalModel->getJurnalLengkap($jurnalId);
        if (! $jurnal) {
            return $this->failNotFound('Data jurnal mengajar terkait tidak ditemukan.');
        }

        $kelas     = $kelasModel->find($jurnal['kelas_id']);
        $sekolah   = $kelas ? $sekolahModel->find($kelas['sekolah_id']) : null;
        $waGroupId = trim((string) ($kelas['wa_group_id'] ?? $notif['no_tujuan']));

        if (empty($waGroupId)) {
            return $this->fail('ID Grup WhatsApp untuk kelas ' . ($kelas['nama'] ?? '-') . ' masih kosong. Silakan lengkapi di menu Master Kelas terlebih dahulu.', 400);
        }

        $token = trim((string) ($sekolah['fonnte_token'] ?? ''));
        if (empty($token) || str_starts_with($token, 'ISI_TOKEN_')) {
            return $this->fail('Token Fonnte untuk ' . ($sekolah['nama'] ?? 'Sekolah') . ' belum diatur.', 400);
        }

        $daftarAbsensi = $absensiModel->getAbsensiByJurnal($jurnalId);
        $isiPesanBaru  = FonnteService::formatPesanRingkasanKelas($jurnal, $daftarAbsensi);

        $fonnte   = new FonnteService();
        $response = $fonnte->sendBulk($token, [
            [
                'target'  => $waGroupId,
                'message' => $isiPesanBaru,
            ],
        ]);

        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Tidak ada respon Fonnte');
        $msgId     = $response['id'][0] ?? null;

        $this->model->update($id, [
            'no_tujuan'            => $waGroupId,
            'isi_pesan'            => $isiPesanBaru,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_message_id'    => $msgId ? (string) $msgId : null,
            'fonnte_status_detail' => $detail,
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'  => $isSuccess ? 200 : 400,
            'message' => $isSuccess ? 'Pesan ringkasan berhasil dikirim ulang ke Grup WA Kelas.' : 'Gagal mengirim ulang pesan: ' . $detail,
            'data'    => $this->model->find($id),
        ]);
    }
}