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
    protected $validationRules      = [];
    protected $validationMessages   = [];
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
