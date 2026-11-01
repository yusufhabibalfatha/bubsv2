<?php

namespace App\Models;

use CodeIgniter\Model;

class PengaturanKeuanganModel extends Model
{
    protected $table            = 'pengaturan_keuangan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pin_bendahara',
        'nama_bendahara',
        'template_wa_bayar',
        'template_wa_reminder',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPengaturanAktif(): array
    {
        $row = $this->first();
        if (! $row) {
            $default = [
                'pin_bendahara'        => '123456',
                'nama_bendahara'       => 'Bendahara Sekolah',
                'template_wa_bayar'    => "*BUKTI PEMBAYARAN KEUANGAN SEKOLAH*\n*{nama_sekolah}*\n--------------------------------\nNo. Kwitansi : {kode_transaksi}\nNama Siswa : *{nama_siswa}* ({kelas})\nPembayaran : {nama_tagihan} {periode}\nTanggal : {tanggal} ({metode})\nJumlah Dibayar : *{nominal_bayar}*\nSisa Tagihan : *{sisa_tagihan}*\nStatus : *{status}*\n--------------------------------\nTerima kasih kepada Bapak/Ibu Orang Tua/Wali atas pembayarannya.",
                'template_wa_reminder' => "*INFORMASI TAGIHAN KEUANGAN SEKOLAH*\n*{nama_sekolah}*\n--------------------------------\nYth. Orang Tua/Wali dari *{nama_siswa}* (Kelas {kelas}),\n\nBerikut informasi rincian administrasi keuangan sekolah yang masih belum lunas / sedang dicicil:\n{rincian_tunggakan}\n\n*Total Sisa Tagihan: {total_tunggakan}*\n--------------------------------\nPembayaran dapat dilakukan secara lunas maupun dicicil melalui Bendahara Sekolah. Terima kasih.",
            ];
            $this->insert($default);
            $row = $this->first();
        }
        return $row;
    }
}
