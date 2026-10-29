<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class KelasController extends ResourceController
{
    protected $modelName = 'App\Models\KelasModel';
    protected $format    = 'json';

    /**
     * Mendukung filter: GET /api/kelas?sekolah_id=2&tahun_ajaran_id=2
     */
    public function index()
    {
        $sekolahId     = $this->request->getGet('sekolah_id');
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');

        return $this->respond([
            'status' => 200,
            'data'   => $this->model->getKelasLengkap($sekolahId, $tahunAjaranId),
        ]);
    }

    public function show($id = null)
    {
        $data = $this->model->select('kelas.*, sekolah.nama as nama_sekolah, tahun_ajaran.nama_tahun, tahun_ajaran.semester, guru.nama as nama_wali_kelas')
                            ->join('sekolah', 'sekolah.id = kelas.sekolah_id')
                            ->join('tahun_ajaran', 'tahun_ajaran.id = kelas.tahun_ajaran_id')
                            ->join('guru', 'guru.id = kelas.wali_guru_id', 'left')
                            ->where('kelas.id', $id)
                            ->first();

        if (! $data) {
            return $this->failNotFound('Data kelas tidak ditemukan.');
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
            return $this->fail('Nama kelas sudah ada pada sekolah dan tahun ajaran tersebut.', 409);
        }

        $id = $this->model->getInsertID();
        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Data kelas berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data kelas tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        try {
            if (! $this->model->update($id, $payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('Gagal memperbarui kelas (pastikan nama kelas tidak duplikat pada tahun ajaran yang sama).', 409);
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data kelas berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data kelas tidak ditemukan.');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Data kelas berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Tidak dapat menghapus kelas karena masih memiliki anggota siswa atau riwayat jurnal.', 409);
        }
    }
}