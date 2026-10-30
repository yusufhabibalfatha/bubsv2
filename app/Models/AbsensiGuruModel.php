<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsensiGuruModel extends Model
{
    protected $table            = 'absensi_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'guru_id',
        'tanggal',
        'tipe_absen',
        'waktu_absen',
        'kategori_posisi',
        'lokasi_sekolah_id',
        'status',
        'latitude',
        'longitude',
        'jarak_meter',
        'dalam_radius',
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
        'guru_id'           => 'required|is_natural_no_zero|is_not_unique[guru.id]',
        'tanggal'           => 'required|valid_date[Y-m-d]',
        'tipe_absen'        => 'required|in_list[datang,pulang]',
        'waktu_absen'       => 'required',
        'kategori_posisi'   => 'required|in_list[di_sekolah,di_luar]',
        'lokasi_sekolah_id' => 'permit_empty|is_natural_no_zero',
        'status'            => 'required|in_list[hadir,terlambat,dinas_luar,sakit,izin]',
        'latitude'          => 'permit_empty|decimal',
        'longitude'         => 'permit_empty|decimal',
        'jarak_meter'       => 'permit_empty|integer',
        'dalam_radius'      => 'permit_empty|in_list[0,1]',
        'keterangan'        => 'permit_empty|string',
    ];
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

    public function getAbsensiLengkap($id = null)
    {
        $builder = $this->select('
                            absensi_guru.*,
                            guru.nama as nama_guru,
                            guru.nip,
                            guru.sekolah_id,
                            sekolah.nama as nama_sekolah,
                            jenjang.nama as nama_jenjang,
                            lokasi_sekolah.nama_lokasi
                        ')
                        ->join('guru', 'guru.id = absensi_guru.guru_id')
                        ->join('sekolah', 'sekolah.id = guru.sekolah_id')
                        ->join('jenjang', 'jenjang.id = sekolah.jenjang_id')
                        ->join('lokasi_sekolah', 'lokasi_sekolah.id = absensi_guru.lokasi_sekolah_id', 'left');

        if ($id !== null) {
            return $builder->where('absensi_guru.id', $id)->first();
        }

        return $builder->orderBy('absensi_guru.tanggal', 'DESC')
                       ->orderBy('absensi_guru.waktu_absen', 'DESC')
                       ->findAll();
    }
}
