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
    protected $validationRules = [
        'sekolah_id'      => 'required|is_natural_no_zero|is_not_unique[sekolah.id]',
        'tahun_ajaran_id' => 'required|is_natural_no_zero|is_not_unique[tahun_ajaran.id]',
        'nama'            => 'required|max_length[50]',
        'tingkat'         => 'permit_empty|max_length[30]',
        'wali_guru_id'    => 'permit_empty|is_natural_no_zero|is_not_unique[guru.id]',
        'is_aktif'        => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'sekolah_id' => [
            'required'      => 'Sekolah wajib dipilih.',
            'is_not_unique' => 'Data sekolah tidak valid.',
        ],
        'tahun_ajaran_id' => [
            'required'      => 'Tahun ajaran wajib dipilih.',
            'is_not_unique' => 'Data tahun ajaran tidak valid.',
        ],
        'nama' => [
            'required'   => 'Nama kelas wajib diisi (contoh: XI-1).',
            'max_length' => 'Nama kelas maksimal 50 karakter.',
        ],
        'wali_guru_id' => [
            'is_not_unique' => 'Guru wali kelas yang dipilih tidak ditemukan.',
        ],
        'is_aktif' => [
            'in_list' => 'Status aktif kelas hanya boleh bernilai 0 atau 1.',
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
