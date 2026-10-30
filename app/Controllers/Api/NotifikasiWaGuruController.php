<?php

namespace App\Controllers\Api;

use App\Libraries\FonnteService;
use App\Models\AbsensiGuruModel;
use App\Models\NotifikasiWaGuruModel;
use App\Models\SekolahModel;
use CodeIgniter\RESTful\ResourceController;

class NotifikasiWaGuruController extends ResourceController
{
    protected $modelName = NotifikasiWaGuruModel::class;
    protected $format    = 'json';

    public function index()
    {
        $sekolahId   = $this->request->getGet('sekolah_id');
        $statusKirim = $this->request->getGet('status_kirim');
        $tanggal     = $this->request->getGet('tanggal');

        return $this->respond([
            'status' => 200,
            'data'   => $this->model->getLogLengkap($sekolahId, $statusKirim, $tanggal),
        ]);
    }

    /**
     * POST /api/notifikasi-wa-guru/resend/{id}
     */
    public function resend($id = null)
    {
        $notif = $this->model->find($id);
        if (! $notif) {
            return $this->failNotFound('Data log notifikasi guru tidak ditemukan.');
        }

        $absenModel   = new AbsensiGuruModel();
        $sekolahModel = new SekolahModel();

        $absenLengkap = $absenModel->getAbsensiLengkap($notif['absensi_guru_id']);
        if (! $absenLengkap) {
            return $this->failNotFound('Data absensi guru terkait tidak ditemukan.');
        }

        $sekolah = $sekolahModel->find($absenLengkap['sekolah_id']);
        $token   = trim((string) ($sekolah['fonnte_token'] ?? ''));
        $groupId = trim((string) ($sekolah['wa_group_guru_id'] ?? $notif['group_tujuan']));

        if (empty($token) || str_starts_with($token, 'ISI_TOKEN_') || empty($groupId)) {
            return $this->fail('Token Fonnte atau ID Grup WA Sekolah belum diatur.', 400);
        }

        $isiPesanBaru = FonnteService::formatPesanAbsensiGuru($absenLengkap, $sekolah['template_wa_guru'] ?? null);

        $fonnte   = new FonnteService();
        $response = $fonnte->sendBulk($token, [
            [
                'target'  => $groupId,
                'message' => $isiPesanBaru,
            ],
        ]);

        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Gagal mengirim');
        $msgId     = $response['id'][0] ?? null;

        $this->model->update($id, [
            'group_tujuan'         => $groupId,
            'isi_pesan'            => $isiPesanBaru,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_message_id'    => $msgId ? (string) $msgId : null,
            'fonnte_status_detail' => $detail,
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'  => $isSuccess ? 200 : 400,
            'message' => $isSuccess ? 'Laporan berhasil dikirim ulang ke Grup WA Sekolah.' : 'Gagal mengirim: ' . $detail,
            'data'    => $this->model->find($id),
        ]);
    }
}