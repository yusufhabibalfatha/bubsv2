<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class SiswaKelasController extends ResourceController
{
    protected $modelName = 'App\Models\SiswaKelasModel';
    protected $format    = 'json';

    /**
     * Mengambil daftar siswa di kelas tertentu:
     * GET /api/siswa-kelas?kelas_id=4&tahun_ajaran_id=2
     */
    public function index()
    {
        $kelasId       = $this->request->getGet('kelas_id');
        $tahunAjaranId = $this->request->getGet('tahun_ajaran_id');

        if ($kelasId && $tahunAjaranId) {
            $data = $this->model->getSiswaByKelas($kelasId, $tahunAjaranId);
        } else {
            $data = $this->model->select('siswa_kelas.*, siswa.nama as nama_siswa, siswa.jenis_kelamin, kelas.nama as nama_kelas, tahun_ajaran.nama_tahun, tahun_ajaran.semester')
                                ->join('siswa', 'siswa.id = siswa_kelas.siswa_id')
                                ->join('kelas', 'kelas.id = siswa_kelas.kelas_id')
                                ->join('tahun_ajaran', 'tahun_ajaran.id = siswa_kelas.tahun_ajaran_id')
                                ->orderBy('kelas.nama', 'ASC')
                                ->orderBy('siswa.nama', 'ASC')
                                ->findAll();
        }

        return $this->respond([
            'status' => 200,
            'data'   => $data,
        ]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            if (! $this->model->insert($payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('Gagal menambahkan siswa ke kelas. Pastikan siswa belum terdaftar di kelas lain pada tahun ajaran yang sama, dan tahun ajaran sesuai dengan kelasnya.', 409);
        }

        $id = $this->model->getInsertID();
        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Siswa berhasil dimasukkan ke dalam kelas.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data penempatan kelas siswa tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        try {
            if (! $this->model->update($id, $payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('Gagal memperbarui kelas siswa.', 409);
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data kelas siswa berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data tidak ditemukan.');
        }

        $this->model->delete($id);
        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Siswa berhasil dikeluarkan dari kelas.',
        ]);
    }
}