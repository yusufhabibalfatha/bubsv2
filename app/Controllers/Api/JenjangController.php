<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class JenjangController extends ResourceController
{
    protected $modelName = 'App\Models\JenjangModel';
    protected $format    = 'json';

    public function index()
    {
        return $this->respond([
            'status' => 200,
            'data'   => $this->model->findAll(),
        ]);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) {
            return $this->failNotFound('Data jenjang tidak ditemukan.');
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
            'message' => 'Data jenjang berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data jenjang tidak ditemukan.');
        }

        $payload       = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $payload['id'] = $id; // Untuk kebutuhan validasi {id} is_unique

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data jenjang berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data jenjang tidak ditemukan.');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Data jenjang berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Tidak dapat menghapus jenjang karena masih digunakan oleh data sekolah.', 409);
        }
    }
}