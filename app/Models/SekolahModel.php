<?php

namespace App\Models;

use CodeIgniter\Model;

class SekolahModel extends Model
{
    protected $table            = 'sekolah';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['jenjang_id',
        'nama',
        'alamat',
        'latitude',
        'longitude',
        'status',
        'fonnte_token', // Tambahan baru
        'is_wa_aktif',  // Tambahan baru
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
        'jenjang_id' => 'required|is_natural_no_zero|is_not_unique[jenjang.id]',
        'nama'       => 'required|max_length[150]',
        'alamat'     => 'permit_empty|string',
        'latitude'   => 'permit_empty|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
        'longitude'  => 'permit_empty|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
        'status'     => 'permit_empty|in_list[aktif,nonaktif]',
        'fonnte_token' => 'permit_empty|max_length[100]',
        'is_wa_aktif'  => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'jenjang_id' => [
            'required'      => 'Jenjang sekolah wajib dipilih.',
            'is_not_unique' => 'Jenjang yang dipilih tidak ditemukan di database.',
        ],
        'nama' => [
            'required'   => 'Nama sekolah wajib diisi.',
            'max_length' => 'Nama sekolah maksimal 150 karakter.',
        ],
        'latitude' => [
            'decimal'               => 'Latitude harus berupa angka desimal.',
            'greater_than_equal_to' => 'Latitude minimal -90.',
            'less_than_equal_to'    => 'Latitude maksimal 90.',
        ],
        'longitude' => [
            'decimal'               => 'Longitude harus berupa angka desimal.',
            'greater_than_equal_to' => 'Longitude minimal -180.',
            'less_than_equal_to'    => 'Longitude maksimal 180.',
        ],
        'status' => [
            'in_list' => 'Status sekolah harus bernilai aktif atau nonaktif.',
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
     * Mengambil daftar sekolah beserta nama jenjangnya
     */
    public function getSekolahWithJenjang()
    {
        return $this->select('sekolah.*, jenjang.nama as nama_jenjang')
                    ->join('jenjang', 'jenjang.id = sekolah.jenjang_id')
                    ->findAll();
    }
}
