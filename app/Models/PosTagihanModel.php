<?php

namespace App\Models;

use CodeIgniter\Model;

class PosTagihanModel extends Model
{
    protected $table            = 'pos_tagihan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tahun_ajaran_id',
        'sekolah_id',
        'nama_pos',
        'tipe',
        'nominal',
        'keterangan',
        'is_aktif',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tahun_ajaran_id' => 'required|is_natural_no_zero',
        'nama_pos'        => 'required|min_length[2]|max_length[120]',
        'tipe'            => 'required|in_list[bulanan,bebas]',
        'nominal'         => 'required|numeric|greater_than[0]',
    ];
}
