<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKeuanganSppTables extends Migration
{
    public function up()
    {
        // 1. Tabel pengaturan_keuangan (PIN Bendahara & Template WA Keuangan)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pin_bendahara' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '123456',
            ],
            'nama_bendahara' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Bendahara Sekolah',
            ],
            'template_wa_bayar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'template_wa_reminder' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pengaturan_keuangan', true);

        // Insert baris default pengaturan_keuangan
        $this->db->table('pengaturan_keuangan')->insert([
            'pin_bendahara'        => '123456',
            'nama_bendahara'       => 'Bendahara Sekolah',
            'template_wa_bayar'    => "*BUKTI PEMBAYARAN KEUANGAN SEKOLAH*\n*{nama_sekolah}*\n--------------------------------\nNo. Kwitansi : {kode_transaksi}\nNama Siswa : *{nama_siswa}* ({kelas})\nPembayaran : {nama_tagihan} {periode}\nTanggal : {tanggal} ({metode})\nJumlah Dibayar : *{nominal_bayar}*\nSisa Tagihan : *{sisa_tagihan}*\nStatus : *{status}*\n--------------------------------\nTerima kasih kepada Bapak/Ibu Orang Tua/Wali atas pembayarannya.",
            'template_wa_reminder' => "*INFORMASI TAGIHAN KEUANGAN SEKOLAH*\n*{nama_sekolah}*\n--------------------------------\nYth. Orang Tua/Wali dari *{nama_siswa}* (Kelas {kelas}),\n\nBerikut informasi rincian administrasi keuangan sekolah yang masih belum lunas / sedang dicicil:\n{rincian_tunggakan}\n\n*Total Sisa Tagihan: {total_tunggakan}*\n--------------------------------\nPembayaran dapat dilakukan secara lunas maupun dicicil melalui Bendahara Sekolah. Terima kasih.",
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        // 2. Tabel pos_tagihan (Master Jenis Pembayaran Bulanan / Bebas)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tahun_ajaran_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'sekolah_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // NULL = Berlaku untuk Semua Kelas (SMP & SMA)
            ],
            'nama_pos' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'tipe' => [
                'type'       => 'ENUM',
                'constraint' => ['bulanan', 'bebas'],
                'default'    => 'bulanan',
            ],
            'nominal' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tahun_ajaran_id');
        $this->forge->createTable('pos_tagihan', true);

        // 3. Tabel tagihan_siswa (Tagihan per Siswa & Periode Bulan + Tracking Cicilan)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pos_tagihan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'siswa_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tahun_ajaran_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'bulan_kode' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true, // '07'..'06' untuk bulanan, NULL untuk bebas
            ],
            'bulan_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true, // 'Juli'..'Juni'
            ],
            'urutan_bulan' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'null'       => true, // 1..12
            ],
            'nominal_tagihan' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'nominal_terbayar' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['belum_bayar', 'dicicil', 'lunas'],
                'default'    => 'belum_bayar',
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['pos_tagihan_id', 'siswa_id']);
        $this->forge->addKey(['siswa_id', 'tahun_ajaran_id']);
        $this->forge->createTable('tagihan_siswa', true);

        // 4. Tabel pembayaran_keuangan (Jurnal Kas Masuk & Riwayat Cicilan)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kode_transaksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'tagihan_siswa_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'siswa_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pos_tagihan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tanggal_bayar' => [
                'type' => 'DATE',
            ],
            'waktu_bayar' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'nominal_bayar' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'sisa_setelah_bayar' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'status_setelah_bayar' => [
                'type'       => 'ENUM',
                'constraint' => ['dicicil', 'lunas'],
                'default'    => 'dicicil',
            ],
            'metode_bayar' => [
                'type'       => 'ENUM',
                'constraint' => ['tunai', 'transfer'],
                'default'    => 'tunai',
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_transaksi');
        $this->forge->addKey('tagihan_siswa_id');
        $this->forge->addKey('siswa_id');
        $this->forge->addKey('tanggal_bayar');
        $this->forge->createTable('pembayaran_keuangan', true);

        // 5. Tabel notifikasi_wa_keuangan (Log WA Kwitansi & Pengingat Tunggakan)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'siswa_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pembayaran_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tipe_notif' => [
                'type'       => 'ENUM',
                'constraint' => ['kwitansi', 'reminder'],
                'default'    => 'kwitansi',
            ],
            'no_tujuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'isi_pesan' => [
                'type' => 'TEXT',
            ],
            'status_kirim' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'berhasil', 'gagal'],
                'default'    => 'pending',
            ],
            'fonnte_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'fonnte_status_detail' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'waktu_kirim' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('siswa_id');
        $this->forge->createTable('notifikasi_wa_keuangan', true);
    }

    public function down()
    {
        $this->forge->dropTable('notifikasi_wa_keuangan', true);
        $this->forge->dropTable('pembayaran_keuangan', true);
        $this->forge->dropTable('tagihan_siswa', true);
        $this->forge->dropTable('pos_tagihan', true);
        $this->forge->dropTable('pengaturan_keuangan', true);
    }
}
