<?php

namespace App\Controllers\Api;

use App\Models\LokasiSekolahModel;
use CodeIgniter\RESTful\ResourceController;

class LokasiSekolahController extends ResourceController
{
    protected $modelName = LokasiSekolahModel::class;
    protected $format    = 'json';

    public function index()
    {
        $onlyActive = $this->request->getGet('aktif');
        $builder    = $this->model;

        if ($onlyActive !== null) {
            $builder = $builder->where('is_aktif', (int) $onlyActive);
        }

        return $this->respond([
            'status' => 200,
            'data'   => $builder->orderBy('nama_lokasi', 'ASC')->findAll(),
        ]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        if (! $this->model->insert($payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Spot ruangan berhasil ditambahkan.',
            'data'    => $this->model->find($this->model->getInsertID()),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Spot ruangan tidak ditemukan.');
        }

        $payload       = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $payload['id'] = $id;

        if (! $this->model->update($id, $payload)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Spot ruangan berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Spot ruangan tidak ditemukan.');
        }

        $this->model->delete($id);
        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Spot ruangan berhasil dihapus.',
        ]);
    }
}