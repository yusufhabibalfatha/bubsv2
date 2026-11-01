<?php

namespace App\Controllers\Api;

use App\Libraries\FonnteService;
use App\Models\KelasModel;
use App\Models\NotifikasiWaKeuanganModel;
use App\Models\PembayaranKeuanganModel;
use App\Models\PengaturanKeuanganModel;
use App\Models\PosTagihanModel;
use App\Models\SekolahModel;
use App\Models\SiswaKelasModel;
use App\Models\SiswaModel;
use App\Models\TagihanSiswaModel;
use App\Models\TahunAjaranModel;
use CodeIgniter\RESTful\ResourceController;

class KeuanganController extends ResourceController
{
    protected $format = 'json';

    protected array $daftarBulanSpp = [
        ['kode' => '07', 'label' => 'Juli',      'urutan' => 1],
        ['kode' => '08', 'label' => 'Agustus',   'urutan' => 2],
        ['kode' => '09', 'label' => 'September', 'urutan' => 3],
        ['kode' => '10', 'label' => 'Oktober',   'urutan' => 4],
        ['kode' => '11', 'label' => 'November',  'urutan' => 5],
        ['kode' => '12', 'label' => 'Desember',  'urutan' => 6],
        ['kode' => '01', 'label' => 'Januari',   'urutan' => 7],
        ['kode' => '02', 'label' => 'Februari',  'urutan' => 8],
        ['kode' => '03', 'label' => 'Maret',     'urutan' => 9],
        ['kode' => '04', 'label' => 'April',     'urutan' => 10],
        ['kode' => '05', 'label' => 'Mei',       'urutan' => 11],
        ['kode' => '06', 'label' => 'Juni',      'urutan' => 12],
    ];

    /**
     * Helper: Ambil Tahun Ajaran Aktif jika parameter tahun_ajaran_id tidak dikirim
     */
    protected function resolveTahunAjaranId($requestedId = null): ?int
    {
        if (! empty($requestedId)) {
            return (int) $requestedId;
        }
        $taModel = new TahunAjaranModel();
        $aktif   = $taModel->where('is_aktif', 1)->first();
        return $aktif ? (int) $aktif['id'] : null;
    }

    /**
     * Helper: Validasi PIN Bendahara dari Header X-Finance-Pin
     */
    protected function checkPinBendahara(): bool
    {
        $pinHeader = trim((string) $this->request->getHeaderLine('X-Finance-Pin'));
        if ($pinHeader === '') {
            $payload   = $this->request->getJSON(true) ?? $this->request->getPost();
            $pinHeader = trim((string) ($payload['pin'] ?? ''));
        }

        $pengaturanModel = new PengaturanKeuanganModel();
        $setting         = $pengaturanModel->getPengaturanAktif();
        $validPin        = trim((string) ($setting['pin_bendahara'] ?? '123456'));

        return $pinHeader !== '' && hash_equals($validPin, $pinHeader);
    }

    protected function formatRupiahStr($angka): string
    {
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }

    /**
     * POST /api/keuangan/verify-pin
     */
    public function verifyPin()
    {
        $payload  = $this->request->getJSON(true) ?? $this->request->getPost();
        $inputPin = trim((string) ($payload['pin'] ?? ''));

        $pengaturanModel = new PengaturanKeuanganModel();
        $setting         = $pengaturanModel->getPengaturanAktif();
        $validPin        = trim((string) ($setting['pin_bendahara'] ?? '123456'));

        if ($inputPin === '' || ! hash_equals($validPin, $inputPin)) {
            return $this->failUnauthorized('PIN Bendahara yang Anda masukkan salah.');
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'Akses Mode Bendahara berhasil dibuka.',
        ]);
    }

    /**
     * GET /api/keuangan/dashboard?sekolah_id=&tahun_ajaran_id=
     */
    public function dashboard()
    {
        $sekolahId     = $this->request->getGet('sekolah_id');
        $tahunAjaranId = $this->resolveTahunAjaranId($this->request->getGet('tahun_ajaran_id'));

        $db       = \Config\Database::connect();
        $today    = date('Y-m-d');
        $monthStr = date('Y-m');

        // 1. Query Transaksi Kas Masuk (Hari Ini & Bulan Ini)
        $trxBuilder = $db->table('pembayaran_keuangan pk')
            ->select('pk.nominal_bayar, pk.metode_bayar, pk.tanggal_bayar')
            ->join('tagihan_siswa ts', 'ts.id = pk.tagihan_siswa_id')
            ->join('siswa_kelas sk', 'sk.siswa_id = pk.siswa_id AND sk.tahun_ajaran_id = ts.tahun_ajaran_id', 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left');

        if ($tahunAjaranId) {
            $trxBuilder->where('ts.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($sekolahId) {
            $trxBuilder->where('k.sekolah_id', $sekolahId);
        }

        $allTrx = $trxBuilder->get()->getResultArray();

        $pemasukanHariIni  = 0;
        $tunaiHariIni      = 0;
        $transferHariIni   = 0;
        $jmlTrxHariIni     = 0;
        $pemasukanBulanIni = 0;
        $jmlTrxBulanIni    = 0;

        foreach ($allTrx as $t) {
            $nom = (float) $t['nominal_bayar'];
            $tgl = (string) $t['tanggal_bayar'];

            if ($tgl === $today) {
                $pemasukanHariIni += $nom;
                $jmlTrxHariIni++;
                if (($t['metode_bayar'] ?? 'tunai') === 'transfer') {
                    $transferHariIni += $nom;
                } else {
                    $tunaiHariIni += $nom;
                }
            }
            if (str_starts_with($tgl, $monthStr)) {
                $pemasukanBulanIni += $nom;
                $jmlTrxBulanIni++;
            }
        }

        // 2. Query Ringkasan Tagihan & Progres per Pos Pembayaran
        $tagihanBuilder = $db->table('tagihan_siswa ts')
            ->select('ts.*, pt.nama_pos, pt.tipe')
            ->join('pos_tagihan pt', 'pt.id = ts.pos_tagihan_id')
            ->join('siswa_kelas sk', 'sk.siswa_id = ts.siswa_id AND sk.tahun_ajaran_id = ts.tahun_ajaran_id', 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left');

        if ($tahunAjaranId) {
            $tagihanBuilder->where('ts.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($sekolahId) {
            $tagihanBuilder->where('k.sekolah_id', $sekolahId);
        }

        $allTagihan = $tagihanBuilder->get()->getResultArray();

        $totalTagihanTa   = 0;
        $totalTerbayarTa  = 0;
        $jmlBelumLunas    = 0;
        $jmlDicicil       = 0;
        $jmlBelumBayar    = 0;
        $posProgressMap   = [];

        foreach ($allTagihan as $tg) {
            $nomTagihan  = (float) $tg['nominal_tagihan'];
            $nomTerbayar = (float) $tg['nominal_terbayar'];
            $sisa        = max(0, $nomTagihan - $nomTerbayar);
            $status      = $tg['status'];

            $totalTagihanTa  += $nomTagihan;
            $totalTerbayarTa += $nomTerbayar;

            if ($status !== 'lunas') {
                $jmlBelumLunas++;
                if ($status === 'dicicil') {
                    $jmlDicicil++;
                } else {
                    $jmlBelumBayar++;
                }
            }

            $pid = $tg['pos_tagihan_id'];
            if (! isset($posProgressMap[$pid])) {
                $posProgressMap[$pid] = [
                    'id'             => $pid,
                    'nama_pos'       => $tg['nama_pos'],
                    'tipe'           => $tg['tipe'],
                    'total_target'   => 0,
                    'total_terbayar' => 0,
                    'total_sisa'     => 0,
                    'jml_lunas'      => 0,
                    'jml_dicicil'    => 0,
                    'jml_belum'      => 0,
                ];
            }

            $posProgressMap[$pid]['total_target']   += $nomTagihan;
            $posProgressMap[$pid]['total_terbayar'] += $nomTerbayar;
            $posProgressMap[$pid]['total_sisa']     += $sisa;

            if ($status === 'lunas') {
                $posProgressMap[$pid]['jml_lunas']++;
            } elseif ($status === 'dicicil') {
                $posProgressMap[$pid]['jml_dicicil']++;
            } else {
                $posProgressMap[$pid]['jml_belum']++;
            }
        }

        // 3. 10 Transaksi Terbaru
        $recentBuilder = $db->table('pembayaran_keuangan pk')
            ->select('pk.*, s.nama as nama_siswa, pt.nama_pos as nama_tagihan, ts.bulan_label, ts.nominal_tagihan, k.nama as nama_kelas, sek.nama as nama_sekolah')
            ->join('siswa s', 's.id = pk.siswa_id')
            ->join('pos_tagihan pt', 'pt.id = pk.pos_tagihan_id')
            ->join('tagihan_siswa ts', 'ts.id = pk.tagihan_siswa_id')
            ->join('siswa_kelas sk', 'sk.siswa_id = pk.siswa_id AND sk.tahun_ajaran_id = ts.tahun_ajaran_id', 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left')
            ->join('sekolah sek', 'sek.id = k.sekolah_id', 'left');

        if ($tahunAjaranId) {
            $recentBuilder->where('ts.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($sekolahId) {
            $recentBuilder->where('k.sekolah_id', $sekolahId);
        }

        $transaksiTerbaru = $recentBuilder
            ->orderBy('pk.tanggal_bayar', 'DESC')
            ->orderBy('pk.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return $this->respond([
            'status' => 200,
            'data'   => [
                'summary' => [
                    'pemasukan_hari_ini'        => $pemasukanHariIni,
                    'tunai_hari_ini'            => $tunaiHariIni,
                    'transfer_hari_ini'         => $transferHariIni,
                    'jumlah_transaksi_hari_ini' => $jmlTrxHariIni,
                    'pemasukan_bulan_ini'       => $pemasukanBulanIni,
                    'jumlah_transaksi_bulan_ini'=> $jmlTrxBulanIni,
                    'total_tagihan_ta'          => $totalTagihanTa,
                    'total_terbayar_ta'         => $totalTerbayarTa,
                    'total_tunggakan_ta'        => max(0, $totalTagihanTa - $totalTerbayarTa),
                    'jumlah_item_belum_lunas'   => $jmlBelumLunas,
                    'jumlah_item_dicicil'       => $jmlDicicil,
                    'jumlah_item_belum_bayar'   => $jmlBelumBayar,
                ],
                'pos_tagihan'       => array_values($posProgressMap),
                'transaksi_terbaru' => $transaksiTerbaru,
            ],
        ]);
    }

    /**
     * GET /api/keuangan/pos-tagihan
     */
    public function listPosTagihan()
    {
        $tahunAjaranId = $this->resolveTahunAjaranId($this->request->getGet('tahun_ajaran_id'));
        $sekolahId     = $this->request->getGet('sekolah_id');

        $db      = \Config\Database::connect();
        $builder = $db->table('pos_tagihan pt')
            ->select('pt.*, s.nama as nama_sekolah, COUNT(DISTINCT ts.siswa_id) as jumlah_siswa_tertagih')
            ->join('sekolah s', 's.id = pt.sekolah_id', 'left')
            ->join('tagihan_siswa ts', 'ts.pos_tagihan_id = pt.id', 'left')
            ->groupBy('pt.id')
            ->orderBy('pt.id', 'ASC');

        if ($tahunAjaranId) {
            $builder->where('pt.tahun_ajaran_id', $tahunAjaranId);
        }
        if ($sekolahId) {
            $builder->groupStart()
                    ->where('pt.sekolah_id', $sekolahId)
                    ->orWhere('pt.sekolah_id IS NULL')
                    ->groupEnd();
        }

        return $this->respond([
            'status' => 200,
            'data'   => $builder->get()->getResultArray(),
        ]);
    }

    /**
     * POST /api/keuangan/pos-tagihan (Wajib PIN Bendahara)
     */
    public function createPosTagihan()
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $payload       = $this->request->getJSON(true) ?? $this->request->getPost();
        $tahunAjaranId = $this->resolveTahunAjaranId($payload['tahun_ajaran_id'] ?? null);

        if (! $tahunAjaranId) {
            return $this->fail('Tahun Ajaran Aktif belum ditemukan.', 400);
        }

        $posModel = new PosTagihanModel();
        $dataInsert = [
            'tahun_ajaran_id' => $tahunAjaranId,
            'sekolah_id'      => ! empty($payload['sekolah_id']) ? (int) $payload['sekolah_id'] : null,
            'nama_pos'        => trim((string) ($payload['nama_pos'] ?? '')),
            'tipe'            => $payload['tipe'] ?? 'bulanan',
            'nominal'         => (float) ($payload['nominal'] ?? 0),
            'keterangan'      => $payload['keterangan'] ?? null,
            'is_aktif'        => 1,
        ];

        if (! $posModel->insert($dataInsert)) {
            return $this->failValidationErrors($posModel->errors());
        }

        $posId         = (int) $posModel->getInsertID();
        $generatedInfo = 0;

        if (! empty($payload['auto_generate'])) {
            $generatedInfo = $this->internalGenerateTagihan($posId);
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => "Pos pembayaran berhasil dibuat dan diterapkan ke {$generatedInfo} siswa aktif.",
            'data'    => $posModel->find($posId),
        ]);
    }

    /**
     * PUT /api/keuangan/pos-tagihan/{id} (Wajib PIN Bendahara)
     */
    public function updatePosTagihan($id = null)
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $posModel = new PosTagihanModel();
        $existing = $posModel->find($id);
        if (! $existing) {
            return $this->failNotFound('Pos pembayaran tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $updateData = [
            'nama_pos'   => trim((string) ($payload['nama_pos'] ?? $existing['nama_pos'])),
            'nominal'    => (float) ($payload['nominal'] ?? $existing['nominal']),
            'sekolah_id' => ! empty($payload['sekolah_id']) ? (int) $payload['sekolah_id'] : null,
            'keterangan' => $payload['keterangan'] ?? $existing['keterangan'],
        ];

        $posModel->update($id, $updateData);

        // Update juga nominal_tagihan pada tagihan_siswa yang masih belum_bayar
        $tagihanModel = new TagihanSiswaModel();
        $tagihanModel->where('pos_tagihan_id', $id)
                     ->where('status', 'belum_bayar')
                     ->set(['nominal_tagihan' => $updateData['nominal']])
                     ->update();

        return $this->respond([
            'status'  => 200,
            'message' => 'Pos pembayaran berhasil diperbarui.',
            'data'    => $posModel->find($id),
        ]);
    }

    /**
     * DELETE /api/keuangan/pos-tagihan/{id} (Wajib PIN Bendahara)
     */
    public function deletePosTagihan($id = null)
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $posModel = new PosTagihanModel();
        if (! $posModel->find($id)) {
            return $this->failNotFound('Pos pembayaran tidak ditemukan.');
        }

        $pembayaranModel = new PembayaranKeuanganModel();
        $sudahAdaTrx     = $pembayaranModel->where('pos_tagihan_id', $id)->countAllResults();
        if ($sudahAdaTrx > 0) {
            return $this->fail('Pos pembayaran ini sudah memiliki riwayat transaksi pembayaran kas dan tidak dapat dihapus.', 400);
        }

        $tagihanModel = new TagihanSiswaModel();
        $tagihanModel->where('pos_tagihan_id', $id)->delete();
        $posModel->delete($id);

        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Pos pembayaran beserta tagihan yang belum dibayar berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/keuangan/pos-tagihan/{id}/generate (Wajib PIN Bendahara)
     */
    public function generateTagihanPos($id = null)
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $jmlSiswa = $this->internalGenerateTagihan((int) $id);

        return $this->respond([
            'status'  => 200,
            'message' => "Sinkronisasi selesai! Tagihan berhasil diterapkan ke {$jmlSiswa} siswa aktif baru.",
        ]);
    }

    /**
     * Internal: Generate tagihan pukul rata ke seluruh siswa aktif di kelas SMP & SMA
     */
    protected function internalGenerateTagihan(int $posId): int
    {
        $posModel = new PosTagihanModel();
        $pos      = $posModel->find($posId);
        if (! $pos) {
            return 0;
        }

        $db = \Config\Database::connect();
        $siswaBuilder = $db->table('siswa_kelas sk')
            ->select('sk.siswa_id, k.sekolah_id')
            ->join('siswa s', 's.id = sk.siswa_id')
            ->join('kelas k', 'k.id = sk.kelas_id')
            ->where('sk.tahun_ajaran_id', $pos['tahun_ajaran_id'])
            ->where('sk.is_aktif', 1)
            ->where('s.status', 'aktif');

        if (! empty($pos['sekolah_id'])) {
            $siswaBuilder->where('k.sekolah_id', $pos['sekolah_id']);
        }

        $siswaList = $siswaBuilder->get()->getResultArray();
        if (empty($siswaList)) {
            return 0;
        }

        $tagihanModel = new TagihanSiswaModel();
        $now          = date('Y-m-d H:i:s');
        $siswaCount   = 0;

        foreach ($siswaList as $sw) {
            $siswaId = (int) $sw['siswa_id'];

            // Cek apakah siswa sudah punya tagihan di pos ini
            $exists = $tagihanModel->where('pos_tagihan_id', $posId)
                                   ->where('siswa_id', $siswaId)
                                   ->countAllResults();

            if ($exists > 0) {
                continue;
            }

            $siswaCount++;
            if ($pos['tipe'] === 'bulanan') {
                $batch = [];
                foreach ($this->daftarBulanSpp as $bln) {
                    $batch[] = [
                        'pos_tagihan_id'   => $posId,
                        'siswa_id'         => $siswaId,
                        'tahun_ajaran_id'  => $pos['tahun_ajaran_id'],
                        'bulan_kode'       => $bln['kode'],
                        'bulan_label'      => $bln['label'],
                        'urutan_bulan'     => $bln['urutan'],
                        'nominal_tagihan'  => $pos['nominal'],
                        'nominal_terbayar' => 0,
                        'status'           => 'belum_bayar',
                        'keterangan'       => null,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
                $tagihanModel->insertBatch($batch);
            } else {
                $tagihanModel->insert([
                    'pos_tagihan_id'   => $posId,
                    'siswa_id'         => $siswaId,
                    'tahun_ajaran_id'  => $pos['tahun_ajaran_id'],
                    'bulan_kode'       => null,
                    'bulan_label'      => null,
                    'urutan_bulan'     => null,
                    'nominal_tagihan'  => $pos['nominal'],
                    'nominal_terbayar' => 0,
                    'status'           => 'belum_bayar',
                    'keterangan'       => $pos['keterangan'],
                ]);
            }
        }

        return $siswaCount;
    }

    /**
     * GET /api/keuangan/tagihan-siswa?siswa_id=1&tahun_ajaran_id=1
     */
    public function tagihanSiswa()
    {
        $siswaId       = (int) $this->request->getGet('siswa_id');
        $tahunAjaranId = $this->resolveTahunAjaranId($this->request->getGet('tahun_ajaran_id'));

        if (! $siswaId) {
            return $this->fail('Parameter siswa_id wajib diisi.', 400);
        }

        $db = \Config\Database::connect();

        // Pastikan semua pos_tagihan aktif sudah ter-generate untuk siswa ini
        if ($tahunAjaranId) {
            $posModel = new PosTagihanModel();
            $posAktif = $posModel->where('tahun_ajaran_id', $tahunAjaranId)->findAll();
            foreach ($posAktif as $p) {
                $this->internalGenerateTagihan((int) $p['id']);
            }
        }

        $builder = $db->table('tagihan_siswa ts')
            ->select('ts.*, pt.nama_pos, pt.tipe')
            ->join('pos_tagihan pt', 'pt.id = ts.pos_tagihan_id')
            ->where('ts.siswa_id', $siswaId);

        if ($tahunAjaranId) {
            $builder->where('ts.tahun_ajaran_id', $tahunAjaranId);
        }

        $allTagihan = $builder
            ->orderBy('pt.id', 'ASC')
            ->orderBy('ts.urutan_bulan', 'ASC')
            ->get()
            ->getResultArray();

        $tagihanBulanan = [];
        $tagihanBebas   = [];

        foreach ($allTagihan as $row) {
            if ($row['tipe'] === 'bulanan') {
                $tagihanBulanan[] = $row;
            } else {
                $tagihanBebas[] = $row;
            }
        }

        // Riwayat pembayaran / cicilan siswa
        $riwayat = $db->table('pembayaran_keuangan pk')
            ->select('pk.*, pt.nama_pos as nama_tagihan, ts.bulan_label, ts.nominal_tagihan')
            ->join('pos_tagihan pt', 'pt.id = pk.pos_tagihan_id')
            ->join('tagihan_siswa ts', 'ts.id = pk.tagihan_siswa_id')
            ->where('pk.siswa_id', $siswaId)
            ->orderBy('pk.tanggal_bayar', 'DESC')
            ->orderBy('pk.id', 'DESC')
            ->get()
            ->getResultArray();

        return $this->respond([
            'status' => 200,
            'data'   => [
                'tagihan_bulanan'    => $tagihanBulanan,
                'tagihan_bebas'      => $tagihanBebas,
                'riwayat_pembayaran' => $riwayat,
            ],
        ]);
    }

    /**
     * GET /api/keuangan/pembayaran
     * Buku Kas Masuk (Jurnal Pembayaran & Cicilan)
     */
    public function listPembayaran()
    {
        $tanggalMulai   = $this->request->getGet('tanggal_mulai');
        $tanggalSelesai = $this->request->getGet('tanggal_selesai');
        $sekolahId      = $this->request->getGet('sekolah_id');
        $posTagihanId   = $this->request->getGet('pos_tagihan_id');
        $metodeBayar    = $this->request->getGet('metode_bayar');

        $db      = \Config\Database::connect();
        $builder = $db->table('pembayaran_keuangan pk')
            ->select('pk.*, s.nama as nama_siswa, pt.nama_pos as nama_tagihan, ts.bulan_label, ts.nominal_tagihan, k.nama as nama_kelas, sek.nama as nama_sekolah')
            ->join('siswa s', 's.id = pk.siswa_id')
            ->join('pos_tagihan pt', 'pt.id = pk.pos_tagihan_id')
            ->join('tagihan_siswa ts', 'ts.id = pk.tagihan_siswa_id')
            ->join('siswa_kelas sk', 'sk.siswa_id = pk.siswa_id AND sk.tahun_ajaran_id = ts.tahun_ajaran_id', 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left')
            ->join('sekolah sek', 'sek.id = k.sekolah_id', 'left');

        if ($tanggalMulai) {
            $builder->where('pk.tanggal_bayar >=', $tanggalMulai);
        }
        if ($tanggalSelesai) {
            $builder->where('pk.tanggal_bayar <=', $tanggalSelesai);
        }
        if ($sekolahId) {
            $builder->where('k.sekolah_id', $sekolahId);
        }
        if ($posTagihanId) {
            $builder->where('pk.pos_tagihan_id', $posTagihanId);
        }
        if ($metodeBayar) {
            $builder->where('pk.metode_bayar', $metodeBayar);
        }

        $data = $builder
            ->orderBy('pk.tanggal_bayar', 'DESC')
            ->orderBy('pk.id', 'DESC')
            ->get()
            ->getResultArray();

        return $this->respond([
            'status' => 200,
            'data'   => $data,
        ]);
    }

    /**
     * POST /api/keuangan/pembayaran (Wajib PIN Bendahara)
     * Mencatat pembayaran lunas maupun cicilan + kirim Kwitansi WA Fonnte
     */
    public function simpanPembayaran()
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan untuk menyimpan pembayaran.');
        }

        $payload        = $this->request->getJSON(true) ?? $this->request->getPost();
        $tagihanSiswaId = (int) ($payload['tagihan_siswa_id'] ?? 0);
        $nominalBayar   = (float) ($payload['nominal_bayar'] ?? 0);
        $metodeBayar    = $payload['metode_bayar'] ?? 'tunai';
        $tanggalBayar   = $payload['tanggal_bayar'] ?? date('Y-m-d');
        $keterangan     = $payload['keterangan'] ?? null;
        $kirimWa        = ! empty($payload['kirim_wa']);

        if ($tagihanSiswaId <= 0 || $nominalBayar <= 0) {
            return $this->fail('Tagihan siswa dan nominal pembayaran wajib valid.', 400);
        }

        $tagihanModel = new TagihanSiswaModel();
        $tagihan      = $tagihanModel->find($tagihanSiswaId);
        if (! $tagihan) {
            return $this->failNotFound('Data tagihan siswa tidak ditemukan.');
        }

        $nominalTagihan    = (float) $tagihan['nominal_tagihan'];
        $terbayarSebelum   = (float) $tagihan['nominal_terbayar'];
        $sisaSebelum       = max(0, $nominalTagihan - $terbayarSebelum);

        if ($nominalBayar > $sisaSebelum) {
            return $this->fail('Nominal pembayaran melebihi sisa tagihan saat ini.', 400);
        }

        $terbayarBaru     = $terbayarSebelum + $nominalBayar;
        $sisaSetelahBayar = max(0, $nominalTagihan - $terbayarBaru);
        $statusBaru       = ($sisaSetelahBayar <= 0) ? 'lunas' : 'dicicil';
        $kodeTransaksi    = 'KW-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Update saldo tagihan_siswa
        $tagihanModel->update($tagihanSiswaId, [
            'nominal_terbayar' => $terbayarBaru,
            'status'           => $statusBaru,
        ]);

        // 2. Insert ke pembayaran_keuangan
        $pembayaranModel = new PembayaranKeuanganModel();
        $pembayaranModel->insert([
            'kode_transaksi'       => $kodeTransaksi,
            'tagihan_siswa_id'     => $tagihanSiswaId,
            'siswa_id'             => $tagihan['siswa_id'],
            'pos_tagihan_id'       => $tagihan['pos_tagihan_id'],
            'tanggal_bayar'        => $tanggalBayar,
            'waktu_bayar'          => date('H:i:s'),
            'nominal_bayar'        => $nominalBayar,
            'sisa_setelah_bayar'   => $sisaSetelahBayar,
            'status_setelah_bayar' => $statusBaru,
            'metode_bayar'         => $metodeBayar,
            'keterangan'           => $keterangan,
        ]);
        $pembayaranId = (int) $pembayaranModel->getInsertID();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError('Gagal menyimpan transaksi pembayaran ke database.');
        }

        // Ambil informasi lengkap siswa, kelas, sekolah, dan pos tagihan untuk Kwitansi & WA
        $infoLengkap = $db->table('siswa s')
            ->select('s.nama as nama_siswa, s.no_hp, k.nama as nama_kelas, sek.id as sekolah_id, sek.nama as nama_sekolah, sek.fonnte_token, sek.is_wa_aktif')
            ->join('siswa_kelas sk', 'sk.siswa_id = s.id AND sk.tahun_ajaran_id = ' . (int) $tagihan['tahun_ajaran_id'], 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left')
            ->join('sekolah sek', 'sek.id = k.sekolah_id', 'left')
            ->where('s.id', $tagihan['siswa_id'])
            ->get()
            ->getRowArray();

        $posModel = new PosTagihanModel();
        $pos      = $posModel->find($tagihan['pos_tagihan_id']);

        $waStatusText = 'Tidak dikirim';
        if ($kirimWa && ! empty($infoLengkap['no_hp'])) {
            $waStatusText = $this->kirimWaKwitansi(
                $pembayaranId,
                (int) $tagihan['siswa_id'],
                $infoLengkap,
                $pos['nama_pos'] ?? 'Pembayaran Sekolah',
                $tagihan['bulan_label'] ?? '',
                $kodeTransaksi,
                $tanggalBayar,
                $metodeBayar,
                $nominalBayar,
                $sisaSetelahBayar,
                $statusBaru
            );
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Pembayaran berhasil disimpan.',
            'data'    => [
                'id'                   => $pembayaranId,
                'kode_transaksi'       => $kodeTransaksi,
                'nama_siswa'           => $infoLengkap['nama_siswa'] ?? '-',
                'nama_kelas'           => $infoLengkap['nama_kelas'] ?? '-',
                'nama_sekolah'         => $infoLengkap['nama_sekolah'] ?? '-',
                'nama_tagihan'         => $pos['nama_pos'] ?? 'Pembayaran',
                'bulan_label'          => $tagihan['bulan_label'],
                'nominal_tagihan'      => $nominalTagihan,
                'nominal_bayar'        => $nominalBayar,
                'sisa_setelah_bayar'   => $sisaSetelahBayar,
                'status_setelah_bayar' => $statusBaru,
                'metode_bayar'         => $metodeBayar,
                'tanggal_bayar'        => $tanggalBayar,
                'waktu_bayar'          => date('H:i:s'),
                'keterangan'           => $keterangan,
                'wa_status'            => $waStatusText,
            ],
        ]);
    }

    /**
     * DELETE /api/keuangan/pembayaran/{id} (Wajib PIN Bendahara)
     * Membatalkan transaksi & mengembalikan saldo tagihan_siswa
     */
    public function hapusPembayaran($id = null)
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $pembayaranModel = new PembayaranKeuanganModel();
        $trx             = $pembayaranModel->find($id);
        if (! $trx) {
            return $this->failNotFound('Data transaksi tidak ditemukan.');
        }

        $tagihanModel = new TagihanSiswaModel();
        $tagihan      = $tagihanModel->find($trx['tagihan_siswa_id']);

        $db = \Config\Database::connect();
        $db->transStart();

        if ($tagihan) {
            $terbayarBaru = max(0, (float) $tagihan['nominal_terbayar'] - (float) $trx['nominal_bayar']);
            $statusBaru   = 'belum_bayar';
            if ($terbayarBaru >= (float) $tagihan['nominal_tagihan']) {
                $statusBaru = 'lunas';
            } elseif ($terbayarBaru > 0) {
                $statusBaru = 'dicicil';
            }

            $tagihanModel->update($tagihan['id'], [
                'nominal_terbayar' => $terbayarBaru,
                'status'           => $statusBaru,
            ]);
        }

        $pembayaranModel->delete($id);
        $db->transComplete();

        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Transaksi berhasil dibatalkan dan saldo tagihan siswa telah dikembalikan.',
        ]);
    }

    /**
     * GET /api/keuangan/matriks-kelas?kelas_id=1&pos_tagihan_id=&tahun_ajaran_id=
     */
    public function matriksKelas()
    {
        $kelasId       = (int) $this->request->getGet('kelas_id');
        $posTagihanId  = $this->request->getGet('pos_tagihan_id');
        $tahunAjaranId = $this->resolveTahunAjaranId($this->request->getGet('tahun_ajaran_id'));

        if (! $kelasId) {
            return $this->fail('Parameter kelas_id wajib diisi.', 400);
        }

        $db = \Config\Database::connect();
        $infoKelas = $db->table('kelas k')
            ->select('k.*, s.nama as nama_sekolah')
            ->join('sekolah s', 's.id = k.sekolah_id', 'left')
            ->where('k.id', $kelasId)
            ->get()
            ->getRowArray();

        $siswaKelasModel = new SiswaKelasModel();
        $siswaList       = $siswaKelasModel->getSiswaByKelas($kelasId, $tahunAjaranId);

        // Ambil seluruh tagihan siswa di kelas ini
        $tagihanBuilder = $db->table('tagihan_siswa ts')
            ->select('ts.*, pt.nama_pos, pt.tipe')
            ->join('pos_tagihan pt', 'pt.id = ts.pos_tagihan_id')
            ->where('ts.tahun_ajaran_id', $tahunAjaranId);

        if ($posTagihanId) {
            $tagihanBuilder->where('ts.pos_tagihan_id', $posTagihanId);
        }

        $allTagihan = $tagihanBuilder->get()->getResultArray();
        $tagihanBySiswa = [];
        foreach ($allTagihan as $tg) {
            $tagihanBySiswa[$tg['siswa_id']][] = $tg;
        }

        $rekapSiswa = [];
        foreach ($siswaList as $sw) {
            $sid          = (int) $sw['siswa_id'];
            $items        = $tagihanBySiswa[$sid] ?? [];
            $matriksBulan = [];
            $nonBulananTerbayar = 0;
            $nonBulananSisa     = 0;
            $totalTerbayar      = 0;
            $totalSisa          = 0;

            foreach ($items as $it) {
                $nomTagihan  = (float) $it['nominal_tagihan'];
                $nomTerbayar = (float) $it['nominal_terbayar'];
                $sisa        = max(0, $nomTagihan - $nomTerbayar);

                $totalTerbayar += $nomTerbayar;
                $totalSisa     += $sisa;

                if ($it['tipe'] === 'bulanan' && ! empty($it['bulan_kode'])) {
                    $kode = $it['bulan_kode'];
                    $matriksBulan[$kode] = [
                        'status'   => $it['status'],
                        'tagihan'  => $nomTagihan,
                        'terbayar' => $nomTerbayar,
                        'sisa'     => $sisa,
                    ];
                } else {
                    $nonBulananTerbayar += $nomTerbayar;
                    $nonBulananSisa     += $sisa;
                }
            }

            $rekapSiswa[] = [
                'siswa_id'             => $sid,
                'nama_siswa'           => $sw['nama_siswa'],
                'no_hp'                => $sw['no_hp'],
                'matriks_bulan'        => $matriksBulan,
                'non_bulanan_terbayar' => $nonBulananTerbayar,
                'non_bulanan_sisa'     => $nonBulananSisa,
                'total_terbayar'       => $totalTerbayar,
                'total_sisa'           => $totalSisa,
            ];
        }

        return $this->respond([
            'status' => 200,
            'data'   => [
                'info_kelas'  => $infoKelas,
                'rekap_siswa' => $rekapSiswa,
            ],
        ]);
    }

    /**
     * POST /api/keuangan/reminder-wa (Wajib PIN Bendahara)
     */
    public function reminderWaSiswa()
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $payload       = $this->request->getJSON(true) ?? $this->request->getPost();
        $siswaId       = (int) ($payload['siswa_id'] ?? 0);
        $tahunAjaranId = $this->resolveTahunAjaranId($payload['tahun_ajaran_id'] ?? null);

        $hasil = $this->kirimReminderTunggakanInternal($siswaId, $tahunAjaranId);
        if (! $hasil['success']) {
            return $this->fail($hasil['message'], 400);
        }

        return $this->respond([
            'status'  => 200,
            'message' => $hasil['message'],
        ]);
    }

    /**
     * POST /api/keuangan/reminder-wa-kelas (Wajib PIN Bendahara)
     */
    public function reminderWaKelas()
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $payload       = $this->request->getJSON(true) ?? $this->request->getPost();
        $kelasId       = (int) ($payload['kelas_id'] ?? 0);
        $tahunAjaranId = $this->resolveTahunAjaranId($payload['tahun_ajaran_id'] ?? null);

        $siswaKelasModel = new SiswaKelasModel();
        $siswaList       = $siswaKelasModel->getSiswaByKelas($kelasId, $tahunAjaranId);

        $terkirim = 0;
        foreach ($siswaList as $sw) {
            $res = $this->kirimReminderTunggakanInternal((int) $sw['siswa_id'], $tahunAjaranId);
            if ($res['success']) {
                $terkirim++;
            }
        }

        return $this->respond([
            'status'  => 200,
            'message' => "Berhasil memproses pengiriman WA pengingat untuk {$terkirim} siswa.",
        ]);
    }

    /**
     * GET /api/keuangan/notifikasi-wa
     */
    public function listNotifikasiWa()
    {
        $sekolahId = $this->request->getGet('sekolah_id');
        $db        = \Config\Database::connect();

        $builder = $db->table('notifikasi_wa_keuangan nw')
            ->select('nw.*, s.nama as nama_siswa')
            ->join('siswa s', 's.id = nw.siswa_id', 'left')
            ->orderBy('nw.id', 'DESC')
            ->limit(100);

        return $this->respond([
            'status' => 200,
            'data'   => $builder->get()->getResultArray(),
        ]);
    }

    /**
     * POST /api/keuangan/notifikasi-wa/resend/{id} (Wajib PIN Bendahara)
     */
    public function resendNotifikasiWa($id = null)
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $notifModel = new NotifikasiWaKeuanganModel();
        $notif      = $notifModel->find($id);
        if (! $notif) {
            return $this->failNotFound('Log notifikasi WA tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $info = $db->table('siswa s')
            ->select('s.no_hp, sek.fonnte_token')
            ->join('siswa_kelas sk', 'sk.siswa_id = s.id AND sk.is_aktif = 1', 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left')
            ->join('sekolah sek', 'sek.id = k.sekolah_id', 'left')
            ->where('s.id', $notif['siswa_id'])
            ->get()
            ->getRowArray();

        $token = trim((string) ($info['fonnte_token'] ?? ''));
        $noHp  = trim((string) ($info['no_hp'] ?? $notif['no_tujuan']));

        if (empty($token) || empty($noHp)) {
            return $this->fail('Token Fonnte sekolah atau nomor WA orang tua belum diatur.', 400);
        }

        $fonnte   = new FonnteService();
        $response = $fonnte->sendBulk($token, [
            [
                'target'  => $noHp,
                'message' => $notif['isi_pesan'],
            ],
        ]);

        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Resend Fonnte');

        $notifModel->update($id, [
            'no_tujuan'            => $noHp,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_status_detail' => $detail,
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'  => 200,
            'message' => $isSuccess ? 'Pesan WA berhasil dikirim ulang.' : 'Gagal mengirim WA: ' . $detail,
        ]);
    }

    /**
     * GET /api/keuangan/pengaturan
     */
    public function getPengaturan()
    {
        $model   = new PengaturanKeuanganModel();
        $setting = $model->getPengaturanAktif();

        // Jangan ekspos PIN asli jika belum unlock PIN Bendahara
        if (! $this->checkPinBendahara()) {
            $setting['pin_bendahara'] = '123456';
        }

        return $this->respond([
            'status' => 200,
            'data'   => $setting,
        ]);
    }

    /**
     * POST /api/keuangan/pengaturan (Wajib PIN Bendahara)
     */
    public function savePengaturan()
    {
        if (! $this->checkPinBendahara()) {
            return $this->failForbidden('Akses ditolak. PIN Bendahara diperlukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $model   = new PengaturanKeuanganModel();
        $current = $model->getPengaturanAktif();

        $model->update($current['id'], [
            'pin_bendahara'        => trim((string) ($payload['pin_bendahara'] ?? $current['pin_bendahara'])),
            'nama_bendahara'       => trim((string) ($payload['nama_bendahara'] ?? $current['nama_bendahara'])),
            'template_wa_bayar'    => $payload['template_wa_bayar'] ?? $current['template_wa_bayar'],
            'template_wa_reminder' => $payload['template_wa_reminder'] ?? $current['template_wa_reminder'],
        ]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Pengaturan keuangan berhasil disimpan.',
            'data'    => $model->find($current['id']),
        ]);
    }

    /**
     * Helper Internal: Kirim WA Kwitansi Pembayaran / Cicilan
     */
    protected function kirimWaKwitansi(
        int $pembayaranId,
        int $siswaId,
        array $infoSiswaSekolah,
        string $namaTagihan,
        string $bulanLabel,
        string $kodeTransaksi,
        string $tanggalBayar,
        string $metodeBayar,
        float $nominalBayar,
        float $sisaSetelahBayar,
        string $statusBaru
    ): string {
        $pengaturanModel = new PengaturanKeuanganModel();
        $setting         = $pengaturanModel->getPengaturanAktif();
        $template        = $setting['template_wa_bayar'];

        $statusLabel = ($statusBaru === 'lunas')
            ? 'LUNAS'
            : 'DICICIL (Masih Sisa ' . $this->formatRupiahStr($sisaSetelahBayar) . ')';

        $pesan = strtr($template, [
            '{nama_sekolah}'   => $infoSiswaSekolah['nama_sekolah'] ?? 'SMP & SMA Budi Utomo Tarakan',
            '{nama_siswa}'     => $infoSiswaSekolah['nama_siswa'] ?? '-',
            '{kelas}'          => $infoSiswaSekolah['nama_kelas'] ?? '-',
            '{kode_transaksi}' => $kodeTransaksi,
            '{nama_tagihan}'   => $namaTagihan,
            '{periode}'        => $bulanLabel !== '' ? "({$bulanLabel})" : '',
            '{nominal_bayar}'  => $this->formatRupiahStr($nominalBayar),
            '{sisa_tagihan}'   => $this->formatRupiahStr($sisaSetelahBayar),
            '{status}'         => $statusLabel,
            '{metode}'         => strtoupper($metodeBayar),
            '{tanggal}'        => date('d-m-Y', strtotime($tanggalBayar)),
        ]);

        $token      = trim((string) ($infoSiswaSekolah['fonnte_token'] ?? ''));
        $noHp       = trim((string) ($infoSiswaSekolah['no_hp'] ?? ''));
        $notifModel = new NotifikasiWaKeuanganModel();

        if ($token === '' || str_starts_with($token, 'ISI_TOKEN_')) {
            $notifModel->insert([
                'siswa_id'             => $siswaId,
                'pembayaran_id'        => $pembayaranId,
                'tipe_notif'           => 'kwitansi',
                'no_tujuan'            => $noHp,
                'isi_pesan'            => $pesan,
                'status_kirim'         => 'pending',
                'fonnte_status_detail' => 'Token Fonnte sekolah belum diatur.',
                'waktu_kirim'          => date('Y-m-d H:i:s'),
            ]);
            return 'Disimpan di Log WA (Token Fonnte belum diatur)';
        }

        $fonnte    = new FonnteService();
        $response  = $fonnte->sendBulk($token, [['target' => $noHp, 'message' => $pesan]]);
        $isSuccess = ! empty($response['status']) && $response['status'] === true;
        $detail    = $response['detail'] ?? ($response['reason'] ?? 'Fonnte Response');

        $notifModel->insert([
            'siswa_id'             => $siswaId,
            'pembayaran_id'        => $pembayaranId,
            'tipe_notif'           => 'kwitansi',
            'no_tujuan'            => $noHp,
            'isi_pesan'            => $pesan,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_message_id'    => isset($response['id'][0]) ? (string) $response['id'][0] : null,
            'fonnte_status_detail' => $detail,
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return $isSuccess ? 'Terkirim via Fonnte ✅' : 'Gagal kirim WA (Tercatat di Log WA)';
    }

    /**
     * Helper Internal: Kirim WA Pengingat Tunggakan Siswa
     */
    protected function kirimReminderTunggakanInternal(int $siswaId, ?int $tahunAjaranId): array
    {
        $db = \Config\Database::connect();
        $info = $db->table('siswa s')
            ->select('s.nama as nama_siswa, s.no_hp, k.nama as nama_kelas, sek.nama as nama_sekolah, sek.fonnte_token')
            ->join('siswa_kelas sk', 'sk.siswa_id = s.id' . ($tahunAjaranId ? ' AND sk.tahun_ajaran_id = ' . $tahunAjaranId : ''), 'left')
            ->join('kelas k', 'k.id = sk.kelas_id', 'left')
            ->join('sekolah sek', 'sek.id = k.sekolah_id', 'left')
            ->where('s.id', $siswaId)
            ->get()
            ->getRowArray();

        if (! $info || empty($info['no_hp'])) {
            return ['success' => false, 'message' => 'Nomor WA orang tua siswa belum diisi.'];
        }

        $tunggakanList = $db->table('tagihan_siswa ts')
            ->select('ts.*, pt.nama_pos')
            ->join('pos_tagihan pt', 'pt.id = ts.pos_tagihan_id')
            ->where('ts.siswa_id', $siswaId)
            ->where('ts.status !=', 'lunas')
            ->get()
            ->getResultArray();

        if (empty($tunggakanList)) {
            return ['success' => false, 'message' => 'Siswa ini tidak memiliki tunggakan.'];
        }

        $barisRincian = [];
        $totalSisa    = 0;
        foreach ($tunggakanList as $tg) {
            $sisa = max(0, (float) $tg['nominal_tagihan'] - (float) $tg['nominal_terbayar']);
            if ($sisa <= 0) {
                continue;
            }
            $totalSisa += $sisa;
            $periode = ! empty($tg['bulan_label']) ? ' (' . $tg['bulan_label'] . ')' : '';
            $statusKet = $tg['status'] === 'dicicil' ? ' [Sisa Cicilan]' : '';
            $barisRincian[] = '• ' . $tg['nama_pos'] . $periode . ': *' . $this->formatRupiahStr($sisa) . '*' . $statusKet;
        }

        $pengaturanModel = new PengaturanKeuanganModel();
        $setting         = $pengaturanModel->getPengaturanAktif();

        $pesan = strtr($setting['template_wa_reminder'], [
            '{nama_sekolah}'      => $info['nama_sekolah'] ?? 'SMP & SMA Budi Utomo Tarakan',
            '{nama_siswa}'        => $info['nama_siswa'],
            '{kelas}'             => $info['nama_kelas'] ?? '-',
            '{rincian_tunggakan}' => implode("\n", $barisRincian),
            '{total_tunggakan}'   => $this->formatRupiahStr($totalSisa),
        ]);

        $token      = trim((string) ($info['fonnte_token'] ?? ''));
        $notifModel = new NotifikasiWaKeuanganModel();

        if ($token === '' || str_starts_with($token, 'ISI_TOKEN_')) {
            $notifModel->insert([
                'siswa_id'             => $siswaId,
                'tipe_notif'           => 'reminder',
                'no_tujuan'            => $info['no_hp'],
                'isi_pesan'            => $pesan,
                'status_kirim'         => 'pending',
                'fonnte_status_detail' => 'Token Fonnte belum diatur.',
                'waktu_kirim'          => date('Y-m-d H:i:s'),
            ]);
            return ['success' => true, 'message' => 'Pengingat dicatat di Log WA (Token Fonnte belum diatur).'];
        }

        $fonnte    = new FonnteService();
        $response  = $fonnte->sendBulk($token, [['target' => $info['no_hp'], 'message' => $pesan]]);
        $isSuccess = ! empty($response['status']) && $response['status'] === true;

        $notifModel->insert([
            'siswa_id'             => $siswaId,
            'tipe_notif'           => 'reminder',
            'no_tujuan'            => $info['no_hp'],
            'isi_pesan'            => $pesan,
            'status_kirim'         => $isSuccess ? 'berhasil' : 'gagal',
            'fonnte_status_detail' => $response['detail'] ?? ($response['reason'] ?? 'Fonnte Reminder'),
            'waktu_kirim'          => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => $isSuccess,
            'message' => $isSuccess ? 'Pengingat WA berhasil dikirim.' : 'Gagal mengirim WA via Fonnte.',
        ];
    }
}
