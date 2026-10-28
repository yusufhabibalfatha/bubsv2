<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAbsensiMapelSiswaTable extends Migration
{
  public function up()
  {
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
      'siswa_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'status' => [
        'type'       => 'ENUM',
        'constraint' => ['hadir', 'izin', 'sakit', 'alpa', 'terlambat', 'dispensasi'],
        'default'    => 'hadir',
      ],
      'nilai' => [
        'type'       => 'DECIMAL',
        'constraint' => '5,2',
        'null'       => true,
      ],
      'catatan' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'keterangan' => [
        'type' => 'TEXT', // Bisa diganti VARCHAR (misal constraint 255) jika hanya teks pendek
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
    $this->forge->addUniqueKey(['jurnal_mengajar_id', 'siswa_id'], 'uk_absensi_jurnal_siswa');
    $this->forge->addKey('siswa_id', false, false, 'idx_absensi_siswa');
    $this->forge->addKey('status', false, false, 'idx_absensi_status');

    $this->forge->addForeignKey('jurnal_mengajar_id', 'jurnal_mengajar', 'id', 'CASCADE', 'CASCADE', 'fk_absensi_jurnal');
    $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'CASCADE', 'CASCADE', 'fk_absensi_siswa');

    $this->forge->createTable('absensi_mapel_siswa', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);

    $this->db->query('ALTER TABLE `absensi_mapel_siswa` ADD CONSTRAINT `chk_absensi_nilai` CHECK (`nilai` IS NULL OR (`nilai` >= 0 AND `nilai` <= 100))');
  }

  public function down()
  {
    $this->forge->dropTable('absensi_mapel_siswa', true);
  }
}
