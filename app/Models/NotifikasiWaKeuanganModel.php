<?php

namespace App\Models;

use CodeIgniter\Model;

class NotifikasiWaKeuanganModel extends Model
{
    protected $table            = 'notifikasi_wa_keuangan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'siswa_id',
        'pembayaran_id',
        'tipe_notif',
        'no_tujuan',
        'isi_pesan',
        'status_kirim',
        'fonnte_message_id',
        'fonnte_status_detail',
        'waktu_kirim',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
