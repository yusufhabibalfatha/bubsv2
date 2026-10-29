<?php

namespace App\Models;

use CodeIgniter\Model;

class SiswaKelasModel extends Model
{
    protected $table            = 'siswa_kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'siswa_id',
        'kelas_id',
        'tahun_ajaran_id',
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
        'siswa_id'        => 'required|is_natural_no_zero|is_not_unique[siswa.id]',
        'kelas_id'        => 'required|is_natural_no_zero|is_not_unique[kelas.id]',
        'tahun_ajaran_id' => 'required|is_natural_no_zero|is_not_unique[tahun_ajaran.id]',
        'is_aktif'        => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'siswa_id' => [
            'required'      => 'Siswa wajib dipilih.',
            'is_not_unique' => 'Data siswa tidak ditemukan.',
        ],
        'kelas_id' => [
            'required'      => 'Kelas wajib dipilih.',
            'is_not_unique' => 'Data kelas tidak ditemukan.',
        ],
        'tahun_ajaran_id' => [
            'required'      => 'Tahun ajaran wajib dipilih.',
            'is_not_unique' => 'Data tahun ajaran tidak ditemukan.',
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
     * Mengambil daftar siswa yang berada di kelas tertentu pada tahun ajaran tertentu
     */
    public function getSiswaByKelas($kelasId, $tahunAjaranId)
    {
        return $this->select('siswa_kelas.*, siswa.nama as nama_siswa, siswa.jenis_kelamin, siswa.no_hp, siswa.status as status_siswa')
                    ->join('siswa', 'siswa.id = siswa_kelas.siswa_id')
                    ->where('siswa_kelas.kelas_id', $kelasId)
                    ->where('siswa_kelas.tahun_ajaran_id', $tahunAjaranId)
                    ->where('siswa_kelas.is_aktif', 1)
                    ->orderBy('siswa.nama', 'ASC')
                    ->findAll();
    }
}
