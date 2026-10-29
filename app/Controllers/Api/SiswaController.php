<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class SiswaController extends ResourceController
{
    protected $modelName = 'App\Models\SiswaModel';
    protected $format    = 'json';

    /**
     * Mendukung filter: GET /api/siswa?status=aktif&search=Fajar
     */
    public function index()
    {
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        $builder = $this->model;

        if ($status) {
            $builder = $builder->where('status', $status);
        }

        if ($search) {
            $builder = $builder->like('nama', $search);
        }

        return $this->respond([
            'status' => 200,
            'data'   => $builder->orderBy('nama', 'ASC')->findAll(),
        ]);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) {
            return $this->failNotFound('Data siswa tidak ditemukan.');
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
            'message' => 'Data siswa berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data siswa tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Data siswa berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data siswa tidak ditemukan.');
        }

        $this->model->delete($id);
        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Data siswa berhasil dihapus.',
        ]);
    }
}