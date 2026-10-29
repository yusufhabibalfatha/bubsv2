<?php

namespace App\Models;

use CodeIgniter\Model;

class JurnalMengajarModel extends Model
{
    protected $table            = 'jurnal_mengajar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kelas_id',
        'tahun_ajaran_id',
        'guru_id',
        'mata_pelajaran_id',
        'tanggal',
        'jam_ke',
        'jam_mulai',
        'jam_selesai',
        'materi',
        'catatan',
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
        'kelas_id'          => 'required|is_natural_no_zero|is_not_unique[kelas.id]',
        'tahun_ajaran_id'   => 'required|is_natural_no_zero|is_not_unique[tahun_ajaran.id]',
        'guru_id'           => 'required|is_natural_no_zero|is_not_unique[guru.id]',
        'mata_pelajaran_id' => 'permit_empty|is_natural_no_zero|is_not_unique[mata_pelajaran.id]',
        'tanggal'           => 'required|valid_date[Y-m-d]',
        'jam_ke'            => 'permit_empty|max_length[20]',
        'jam_mulai'         => 'permit_empty',
        'jam_selesai'       => 'permit_empty',
        'materi'            => 'permit_empty|string',
        'catatan'           => 'permit_empty|string',
    ];

    protected $validationMessages = [
        'kelas_id' => [
            'required'      => 'Kelas wajib dipilih.',
            'is_not_unique' => 'Kelas tidak terdaftar.',
        ],
        'tahun_ajaran_id' => [
            'required'      => 'Tahun ajaran wajib dipilih.',
            'is_not_unique' => 'Tahun ajaran tidak terdaftar.',
        ],
        'guru_id' => [
            'required'      => 'Guru pengajar wajib dipilih.',
            'is_not_unique' => 'Data guru tidak ditemukan.',
        ],
        'mata_pelajaran_id' => [
            'is_not_unique' => 'Mata pelajaran tidak ditemukan.',
        ],
        'tanggal' => [
            'required'   => 'Tanggal mengajar wajib diisi.',
            'valid_date' => 'Format tanggal harus YYYY-MM-DD.',
        ],
        'jam_ke' => [
            'max_length' => 'Kolom jam ke maksimal 20 karakter.',
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
     * Mengambil detail jurnal mengajar beserta nama kelas, guru, dan mata pelajaran
     */
    public function getJurnalLengkap($jurnalId = null)
    {
        $builder = $this->select('jurnal_mengajar.*, kelas.nama as nama_kelas, sekolah.nama as nama_sekolah, guru.nama as nama_guru, mata_pelajaran.nama as nama_mapel, tahun_ajaran.nama_tahun, tahun_ajaran.semester')
                        ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
                        ->join('sekolah', 'sekolah.id = kelas.sekolah_id')
                        ->join('guru', 'guru.id = jurnal_mengajar.guru_id')
                        ->join('mata_pelajaran', 'mata_pelajaran.id = jurnal_mengajar.mata_pelajaran_id', 'left')
                        ->join('tahun_ajaran', 'tahun_ajaran.id = jurnal_mengajar.tahun_ajaran_id');

        if ($jurnalId !== null) {
            return $builder->where('jurnal_mengajar.id', $jurnalId)->first();
        }

        return $builder->orderBy('jurnal_mengajar.tanggal', 'DESC')
                       ->orderBy('jurnal_mengajar.jam_mulai', 'DESC')
                       ->findAll();
    }
}
