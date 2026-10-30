<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiWaGuruModel extends Model
{
    protected $table            = 'notifikasi_wa_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'absensi_guru_id',
        'group_tujuan',
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

    public function getLogLengkap($sekolahId = null, $statusKirim = null, $tanggal = null)
    {
        $builder = $this->select('
                            notifikasi_wa_guru.*,
                            absensi_guru.tanggal,
                            absensi_guru.tipe_absen,
                            absensi_guru.waktu_absen,
                            absensi_guru.status as status_absen,
                            guru.nama as nama_guru,
                            sekolah.nama as nama_sekolah,
                            jenjang.nama as nama_jenjang
                        ')
                        ->join('absensi_guru', 'absensi_guru.id = notifikasi_wa_guru.absensi_guru_id')
                        ->join('guru', 'guru.id = absensi_guru.guru_id')
                        ->join('sekolah', 'sekolah.id = guru.sekolah_id')
                        ->join('jenjang', 'jenjang.id = sekolah.jenjang_id');

        if ($sekolahId) {
            $builder->where('guru.sekolah_id', $sekolahId);
        }
        if ($statusKirim) {
            $builder->where('notifikasi_wa_guru.status_kirim', $statusKirim);
        }
        if ($tanggal) {
            $builder->where('absensi_guru.tanggal', $tanggal);
        }

        return $builder->orderBy('notifikasi_wa_guru.id', 'DESC')->findAll();
    }
}
