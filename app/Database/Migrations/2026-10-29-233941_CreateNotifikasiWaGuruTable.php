<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateNotifikasiWaGuruTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'absensi_guru_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'group_tujuan' => [
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
        $this->forge->addKey('absensi_guru_id', false, false, 'idx_notif_guru_absen');
        $this->forge->addKey('status_kirim', false, false, 'idx_notif_guru_status');

        $this->forge->addForeignKey(
            'absensi_guru_id',
            'absensi_guru',
            'id',
            'CASCADE',
            'CASCADE',
            'fk_notif_wa_absensi_guru'
        );

        $this->forge->createTable('notifikasi_wa_guru', true, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('notifikasi_wa_guru', true);
    }
}