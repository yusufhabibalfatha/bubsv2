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