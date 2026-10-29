<?php

namespace App\Controllers\Api;

use App\Models\AbsensiMapelSiswaModel;
use CodeIgniter\RESTful\ResourceController;

class AbsensiMapelSiswaController extends ResourceController
{
    protected $modelName = AbsensiMapelSiswaModel::class;
    protected $format    = 'json';

    /**
     * GET /api/absensi-mapel-siswa?jurnal_mengajar_id=2&siswa_id=7
     */
    public function index()
    {
        $jurnalId = $this->request->getGet('jurnal_mengajar_id');
        $siswaId  = $this->request->getGet('siswa_id');

        if ($jurnalId) {
            return $this->respond([
                'status' => 200,
                'data'   => $this->model->getAbsensiByJurnal($jurnalId),
            ]);
        }

        $builder = $this->model->select('
                            absensi_mapel_siswa.*,
                            siswa.nama as nama_siswa,
                            jurnal_mengajar.tanggal,
                            jurnal_mengajar.jam_ke,
                            mata_pelajaran.nama as nama_mapel,
                            kelas.nama as nama_kelas
                        ')
                        ->join('siswa', 'siswa.id = absensi_mapel_siswa.siswa_id')
                        ->join('jurnal_mengajar', 'jurnal_mengajar.id = absensi_mapel_siswa.jurnal_mengajar_id')
                        ->join('mata_pelajaran', 'mata_pelajaran.id = jurnal_mengajar.mata_pelajaran_id', 'left')
                        ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id');

        if ($siswaId) {
            $builder->where('absensi_mapel_siswa.siswa_id', $siswaId);
        }

        return $this->respond([
            'status' => 200,
            'data'   => $builder->orderBy('jurnal_mengajar.tanggal', 'DESC')->findAll(),
        ]);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) {
            return $this->failNotFound('Data absensi siswa tidak ditemukan.');
        }

        return $this->respond(['status' => 200, 'data' => $data]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            if (! $this->model->insert($payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('Siswa tersebut sudah memiliki data absensi pada jurnal mengajar ini.', 409);
        }

        $id = $this->model->getInsertID();
        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Data absensi siswa berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    /**
     * PUT /api/absensi-mapel-siswa/{id}
     * Digunakan jika guru ingin mengoreksi status hadir, nilai, catatan, atau keterangan siswa
     */
    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data absensi siswa tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data absensi siswa berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data absensi siswa tidak ditemukan.');
        }

        $this->model->delete($id);
        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Data absensi siswa berhasil dihapus.',
        ]);
    }
}