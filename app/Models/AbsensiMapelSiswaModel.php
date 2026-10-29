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
    protected $validationRules = [
        'jurnal_mengajar_id' => 'required|is_natural_no_zero|is_not_unique[jurnal_mengajar.id]',
        'siswa_id'           => 'required|is_natural_no_zero|is_not_unique[siswa.id]',
        'status'             => 'required|in_list[hadir,izin,sakit,alpa,terlambat,dispensasi]',
        'nilai'              => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        'catatan'            => 'permit_empty|string',
        'keterangan'         => 'permit_empty|string',
    ];

    protected $validationMessages = [
        'jurnal_mengajar_id' => [
            'required'      => 'ID Jurnal mengajar wajib ada.',
            'is_not_unique' => 'Data jurnal mengajar tidak ditemukan.',
        ],
        'siswa_id' => [
            'required'      => 'ID Siswa wajib diisi.',
            'is_not_unique' => 'Data siswa tidak ditemukan.',
        ],
        'status' => [
            'required' => 'Status kehadiran wajib dipilih.',
            'in_list'  => 'Status kehadiran hanya boleh berisi: hadir, izin, sakit, alpa, terlambat, atau dispensasi.',
        ],
        'nilai' => [
            'decimal'               => 'Nilai harus berupa angka.',
            'greater_than_equal_to' => 'Nilai tidak boleh kurang dari 0.',
            'less_than_equal_to'    => 'Nilai tidak boleh lebih dari 100.',
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

    public function getAbsensiByJurnal($jurnalId)
    {
        return $this->select('absensi_mapel_siswa.*, siswa.nama as nama_siswa, siswa.jenis_kelamin')
                    ->join('siswa', 'siswa.id = absensi_mapel_siswa.siswa_id')
                    ->where('absensi_mapel_siswa.jurnal_mengajar_id', $jurnalId)
                    ->orderBy('siswa.nama', 'ASC')
                    ->findAll();
    }
}
