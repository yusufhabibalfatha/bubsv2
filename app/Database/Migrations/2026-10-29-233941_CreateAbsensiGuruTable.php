<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAbsensiGuruTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'guru_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'tipe_absen' => [
                'type'       => 'ENUM',
                'constraint' => ['datang', 'pulang'],
                'default'    => 'datang',
            ],
            'waktu_absen' => [
                'type' => 'TIME',
            ],
            'kategori_posisi' => [
                'type'       => 'ENUM',
                'constraint' => ['di_sekolah', 'di_luar'],
                'default'    => 'di_sekolah',
            ],
            'lokasi_sekolah_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['hadir', 'terlambat', 'dinas_luar', 'sakit', 'izin'],
                'default'    => 'hadir',
            ],
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
            ],
            'jarak_meter' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'dalam_radius' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'keterangan' => [
                'type' => 'TEXT',
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
        // 1 Guru hanya boleh 1x absen datang dan 1x absen pulang di tanggal yang sama
        $this->forge->addUniqueKey(['guru_id', 'tanggal', 'tipe_absen'], 'uk_absen_guru_harian');
        $this->forge->addKey('tanggal', false, false, 'idx_absen_guru_tanggal');
        $this->forge->addKey('status', false, false, 'idx_absen_guru_status');

        $this->forge->addForeignKey('guru_id', 'guru', 'id', 'CASCADE', 'CASCADE', 'fk_absen_guru_guru');
        $this->forge->addForeignKey('lokasi_sekolah_id', 'lokasi_sekolah', 'id', 'CASCADE', 'SET NULL', 'fk_absen_guru_lokasi');

        $this->forge->createTable('absensi_guru', true, [
            'ENGINE'          => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE'         => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('absensi_guru', true);
    }
}