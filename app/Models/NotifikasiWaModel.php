<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiWaModel extends Model
{
    protected $table            = 'notifikasi_wa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'jurnal_mengajar_id',
        'no_tujuan',
        'isi_pesan',
        'status_kirim',
        'fonnte_message_id',
        'fonnte_status_detail',
        'waktu_kirim',
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
     * Mengambil log notifikasi lengkap untuk halaman Admin
     */
    public function getLogNotifikasi($sekolahId = null, $statusKirim = null, $tanggal = null)
    {
        $builder = $this->select('
                            notifikasi_wa.*,
                            jurnal_mengajar.tanggal,
                            jurnal_mengajar.jam_ke,
                            jurnal_mengajar.jam_mulai,
                            jurnal_mengajar.jam_selesai,
                            jurnal_mengajar.materi,
                            guru.nama as nama_guru,
                            mata_pelajaran.nama as nama_mapel,
                            kelas.nama as nama_kelas,
                            kelas.wa_group_id,
                            sekolah.nama as nama_sekolah
                        ')
                        ->join('jurnal_mengajar', 'jurnal_mengajar.id = notifikasi_wa.jurnal_mengajar_id')
                        ->join('guru', 'guru.id = jurnal_mengajar.guru_id', 'left')
                        ->join('mata_pelajaran', 'mata_pelajaran.id = jurnal_mengajar.mata_pelajaran_id', 'left')
                        ->join('kelas', 'kelas.id = jurnal_mengajar.kelas_id')
                        ->join('sekolah', 'sekolah.id = kelas.sekolah_id');

        if ($sekolahId) {
            $builder->where('kelas.sekolah_id', $sekolahId);
        }

        if ($statusKirim) {
            $builder->where('notifikasi_wa.status_kirim', $statusKirim);
        }

        if ($tanggal) {
            $builder->where('jurnal_mengajar.tanggal', $tanggal);
        }

        return $builder->orderBy('notifikasi_wa.id', 'DESC')->findAll();
    }
}
