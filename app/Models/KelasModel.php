<?php

namespace App\Models;

use CodeIgniter\Model;

class KelasModel extends Model
{
    protected $table            = 'kelas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sekolah_id',
        'tahun_ajaran_id',
        'nama',
        'tingkat',
        'wali_guru_id',
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
     * Mengambil daftar kelas lengkap dengan info sekolah, tahun ajaran, dan wali kelas
     */
    public function getKelasLengkap($sekolahId = null, $tahunAjaranId = null)
    {
        $builder = $this->select('kelas.*, sekolah.nama as nama_sekolah, tahun_ajaran.nama_tahun, tahun_ajaran.semester, guru.nama as nama_wali_kelas')
                        ->join('sekolah', 'sekolah.id = kelas.sekolah_id')
                        ->join('tahun_ajaran', 'tahun_ajaran.id = kelas.tahun_ajaran_id')
                        ->join('guru', 'guru.id = kelas.wali_guru_id', 'left');

        if ($sekolahId !== null) {
            $builder->where('kelas.sekolah_id', $sekolahId);
        }

        if ($tahunAjaranId !== null) {
            $builder->where('kelas.tahun_ajaran_id', $tahunAjaranId);
        }

        return $builder->findAll();
    }
}
