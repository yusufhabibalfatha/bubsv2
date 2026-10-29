<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class TahunAjaranController extends ResourceController
{
    protected $modelName = 'App\Models\TahunAjaranModel';
    protected $format    = 'json';

    public function index()
    {
        $data = $this->model->orderBy('nama_tahun', 'DESC')
                            ->orderBy('semester', 'ASC')
                            ->findAll();

        return $this->respond([
            'status' => 200,
            'data'   => $data,
        ]);
    }

    /**
     * Endpoint khusus: GET /api/tahun-ajaran/aktif
     */
    public function aktif()
    {
        $data = $this->model->getTahunAktif();
        if (! $data) {
            return $this->failNotFound('Belum ada tahun ajaran yang diaktifkan.');
        }

        return $this->respond(['status' => 200, 'data' => $data]);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (! $data) {
            return $this->failNotFound('Data tahun ajaran tidak ditemukan.');
        }

        return $this->respond(['status' => 200, 'data' => $data]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();

        // Jika diset aktif (1), nonaktifkan tahun ajaran lainnya
        if (isset($payload['is_aktif']) && $payload['is_aktif'] == 1) {
            $this->model->where('is_aktif', 1)->set(['is_aktif' => 0])->update();
        }

        try {
            if (! $this->model->insert($payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('Kombinasi tahun ajaran dan semester tersebut sudah ada.', 409);
        }

        $id = $this->model->getInsertID();
        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Tahun ajaran berhasil ditambahkan.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function update($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data tahun ajaran tidak ditemukan.');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();

        // Jika diset aktif (1), nonaktifkan tahun ajaran lainnya terlebih dahulu
        if (isset($payload['is_aktif']) && $payload['is_aktif'] == 1) {
            $this->model->where('id !=', $id)->set(['is_aktif' => 0])->update();
        }

        try {
            if (! $this->model->update($id, $payload)) {
                return $this->failValidationErrors($this->model->errors());
            }
        } catch (\Throwable $e) {
            return $this->fail('Kombinasi tahun ajaran dan semester tersebut sudah ada.', 409);
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Tahun ajaran berhasil diperbarui.',
            'data'    => $this->model->find($id),
        ]);
    }

    public function delete($id = null)
    {
        if (! $this->model->find($id)) {
            return $this->failNotFound('Data tahun ajaran tidak ditemukan.');
        }

        try {
            $this->model->delete($id);
            return $this->respondDeleted([
                'status'  => 200,
                'message' => 'Tahun ajaran berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Tidak dapat menghapus tahun ajaran karena sudah terkait dengan data kelas/jurnal.', 409);
        }
    }
}