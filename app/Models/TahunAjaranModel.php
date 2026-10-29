<?php

namespace App\Models;

use CodeIgniter\Model;

class TahunAjaranModel extends Model
{
    protected $table            = 'tahun_ajaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_tahun',
        'semester',
        'is_aktif',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'nama_tahun' => 'required|max_length[20]',
        'semester'   => 'required|in_list[Ganjil,Genap]',
        'is_aktif'   => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'nama_tahun' => [
            'required'   => 'Nama tahun ajaran wajib diisi (contoh: 2026/2027).',
            'max_length' => 'Nama tahun ajaran maksimal 20 karakter.',
        ],
        'semester' => [
            'required' => 'Semester wajib dipilih.',
            'in_list'  => 'Semester hanya boleh diisi Ganjil atau Genap.',
        ],
        'is_aktif' => [
            'in_list' => 'Status aktif hanya boleh bernilai 0 atau 1.',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Mengambil satu tahun ajaran yang sedang aktif
     */
    public function getTahunAktif()
    {
        return $this->where('is_aktif', 1)->first();
    }
}
