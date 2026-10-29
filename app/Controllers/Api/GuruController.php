<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class GuruController extends ResourceController
{
    protected $modelName = 'App\Models\GuruModel';
    protected $format    = 'json';

    /**
     * Mendukung filter query string: GET /api/guru?sekolah_id=2
     */
    public function index()
    {
        $sekolahId = $this->request->getGet('sekolah_id');

        return $this->respond([
            'status' => 200,
            'data'   => $this->model->getGuruWithSekolah($sekolahId),
        ]);
    }

    public function show($id = null)
    {
        $data = $this->model->select('guru.*, sekolah.nama as nama_sekolah')
                            ->join('sekolah', 'sekolah.id = guru.sekolah_id')
                            ->where('guru.id', $id)
                            ->first();

        if (! $data) {
            return $this->failNotFound('Data guru tidak ditemukan.');
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
            return $this->fail('NIP guru sudah terdaftar pada sekolah yang sama.', 409);
        }

        $id = $this->model->getInsertID();
        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Data guru berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data guru tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        try {
            if (! $this->model->update($id, $payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('NIP guru sudah terdaftar pada sekolah yang sama.', 409);
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data guru berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data guru tidak ditemukan.');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Data guru berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Tidak dapat menghapus guru karena sudah memiliki riwayat jurnal mengajar.', 409);
        }
    }
}