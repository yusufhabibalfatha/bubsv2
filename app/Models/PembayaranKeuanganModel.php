<?php

namespace App\Models;

use CodeIgniter\Model;

class PembayaranKeuanganModel extends Model
{
    protected $table            = 'pembayaran_keuangan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode_transaksi',
        'tagihan_siswa_id',
        'siswa_id',
        'pos_tagihan_id',
        'tanggal_bayar',
        'waktu_bayar',
        'nominal_bayar',
        'sisa_setelah_bayar',
        'status_setelah_bayar',
        'metode_bayar',
        'keterangan',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
