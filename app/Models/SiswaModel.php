<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaModel extends Model
{
    protected $table            = 'siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama',
        'no_hp',
        'jenis_kelamin',
        'status',
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
        'nama'          => 'required|max_length[150]',
        'no_hp'         => 'permit_empty|max_length[20]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'status'        => 'permit_empty|in_list[aktif,lulus,pindah,nonaktif]',
    ];

    protected $validationMessages = [
        'nama' => [
            'required'   => 'Nama siswa wajib diisi.',
            'max_length' => 'Nama siswa maksimal 150 karakter.',
        ],
        'no_hp' => [
            'max_length' => 'Nomor HP maksimal 20 karakter.',
        ],
        'jenis_kelamin' => [
            'required' => 'Jenis kelamin wajib dipilih.',
            'in_list'  => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan).',
        ],
        'status' => [
            'in_list' => 'Status siswa hanya boleh berisi: aktif, lulus, pindah, atau nonaktif.',
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
}
