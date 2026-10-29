<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsensiMapelSiswaModel extends Model
{
    protected $table            = 'absensi_mapel_siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'jurnal_mengajar_id',
        'siswa_id',
        'status',
        'nilai',
        'catatan',
        'keterangan',
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

    public function getAbsensiByJurnal($jurnalId)
    {
        return $this->select('absensi_mapel_siswa.*, siswa.nama as nama_siswa, siswa.jenis_kelamin')
                    ->join('siswa', 'siswa.id = absensi_mapel_siswa.siswa_id')
                    ->where('absensi_mapel_siswa.jurnal_mengajar_id', $jurnalId)
                    ->orderBy('siswa.nama', 'ASC')
                    ->findAll();
    }
}
