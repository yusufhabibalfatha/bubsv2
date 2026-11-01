<?php

namespace App\Models;

use CodeIgniter\Model;

class TagihanSiswaModel extends Model
{
    protected $table            = 'tagihan_siswa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pos_tagihan_id',
        'siswa_id',
        'tahun_ajaran_id',
        'bulan_kode',
        'bulan_label',
        'urutan_bulan',
        'nominal_tagihan',
        'nominal_terbayar',
        'status',
        'keterangan',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
