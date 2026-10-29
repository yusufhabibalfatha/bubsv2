<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class SekolahController extends ResourceController
{
    protected $modelName = 'App\Models\SekolahModel';
    protected $format    = 'json';

    public function index()
    {
        return $this->respond([
            'status' => 200,
            'data'   => $this->model->getSekolahWithJenjang(),
        ]);
    }

    public function show($id = null)
    {
        $data = $this->model->select('sekolah.*, jenjang.nama as nama_jenjang')
                            ->join('jenjang', 'jenjang.id = sekolah.jenjang_id')
                            ->where('sekolah.id', $id)
                            ->first();

        if (! $data) {
            return $this->failNotFound('Data sekolah tidak ditemukan.');
        }

        return $this->respond(['status' => 200, 'data' => $data]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        if (! $this->model->insert($payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        $id = $this->model->getInsertID();
        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Data sekolah berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data sekolah tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data sekolah berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data sekolah tidak ditemukan.');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Data sekolah berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Tidak dapat menghapus sekolah karena masih memiliki data guru atau kelas.', 409);
        }
    }
}