<?php

namespace App\Models;

use CodeIgniter\Model;

class GuruModel extends Model
{
    protected $table            = 'guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sekolah_id',
        'nip',
        'nama',
        'email',
        'no_hp',
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
        'sekolah_id' => 'required|is_natural_no_zero|is_not_unique[sekolah.id]',
        'nip'        => 'permit_empty|max_length[30]',
        'nama'       => 'required|max_length[150]',
        'email'      => 'permit_empty|valid_email|max_length[150]',
        'no_hp'      => 'permit_empty|max_length[30]',
        'status'     => 'permit_empty|in_list[aktif,nonaktif]',
    ];

    protected $validationMessages = [
        'sekolah_id' => [
            'required'      => 'Asal sekolah guru wajib dipilih.',
            'is_not_unique' => 'Sekolah tidak ditemukan.',
        ],
        'nip' => [
            'max_length' => 'NIP maksimal 30 karakter.',
        ],
        'nama' => [
            'required'   => 'Nama guru wajib diisi.',
            'max_length' => 'Nama guru maksimal 150 karakter.',
        ],
        'email' => [
            'valid_email' => 'Format email tidak valid.',
            'max_length'  => 'Email maksimal 150 karakter.',
        ],
        'status' => [
            'in_list' => 'Status guru harus aktif atau nonaktif.',
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
     * Mengambil data guru beserta nama sekolahnya
     */
    public function getGuruWithSekolah($sekolahId = null)
    {
        $builder = $this->select('guru.*, sekolah.nama as nama_sekolah')
                        ->join('sekolah', 'sekolah.id = guru.sekolah_id');

        if ($sekolahId !== null) {
            $builder->where('guru.sekolah_id', $sekolahId);
        }

        return $builder->findAll();
    }
}
