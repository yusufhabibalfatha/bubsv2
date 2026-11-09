<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class UpdateWaGroupKelasAndNotifikasiWa extends Migration
{
    public function up()
    {
        // 1. Konversi data lama berstatus 'dispensasi' menjadi 'izin' lalu perbarui ENUM kolom status pada absensi_mapel_siswa
        if ($this->db->tableExists('absensi_mapel_siswa')) {
            $this->db->query("UPDATE `absensi_mapel_siswa` SET `status` = 'izin' WHERE `status` = 'dispensasi'");
            $this->db->query("ALTER TABLE `absensi_mapel_siswa` MODIFY COLUMN `status` ENUM('hadir','izin','sakit','alpa','terlambat') NOT NULL DEFAULT 'hadir'");
        }

        // 2. Tambahkan kolom wa_group_id pada tabel kelas jika belum ada
        if ($this->db->tableExists('kelas') && ! $this->db->fieldExists('wa_group_id', 'kelas')) {
            $this->forge->addColumn('kelas', [
                'wa_group_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'wali_guru_id',
                ],
            ]);
        }

        // 3. Perbarui struktur tabel notifikasi_wa agar berelasi ke jurnal_mengajar_id (1 log per jurnal ke Grup WA Kelas)
        if ($this->db->tableExists('notifikasi_wa') && ! $this->db->fieldExists('jurnal_mengajar_id', 'notifikasi_wa')) {
            $this->forge->dropTable('notifikasi_wa', true);

            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'jurnal_mengajar_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                ],
                'no_tujuan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
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
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => new RawSql('CURRENT_TIMESTAMP'),
                ],
                'updated_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => new RawSql('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('jurnal_mengajar_id', false, false, 'idx_notif_jurnal');
            $this->forge->addKey('status_kirim', false, false, 'idx_notif_status');

            $this->forge->addForeignKey(
                'jurnal_mengajar_id',
                'jurnal_mengajar',
                'id',
                'CASCADE',
                'CASCADE',
                'fk_notif_jurnal'
            );

            $this->forge->createTable('notifikasi_wa', true, [
                'ENGINE'          => 'InnoDB',
                'DEFAULT CHARSET' => 'utf8mb4',
                'COLLATE'         => 'utf8mb4_unicode_ci',
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('kelas') && $this->db->fieldExists('wa_group_id', 'kelas')) {
            $this->forge->dropColumn('kelas', 'wa_group_id');
        }
    }
}
