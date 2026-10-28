<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSiswaKelasTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'BIGINT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'siswa_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'kelas_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'tahun_ajaran_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'is_aktif' => [
        'type'       => 'TINYINT',
        'constraint' => 1,
        'default'    => 1,
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
    $this->forge->addUniqueKey(['siswa_id', 'tahun_ajaran_id'], 'uk_siswa_kelas_tahun');
    $this->forge->addKey('kelas_id', false, false, 'idx_siswa_kelas_kelas');
    $this->forge->addKey('tahun_ajaran_id', false, false, 'idx_siswa_kelas_tahun');

    $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'CASCADE', 'CASCADE', 'fk_siswa_kelas_siswa');
    $this->forge->addForeignKey(['kelas_id', 'tahun_ajaran_id'], 'kelas', ['id', 'tahun_ajaran_id'], 'CASCADE', 'RESTRICT', 'fk_siswa_kelas_kelas_tahun');

    $this->forge->createTable('siswa_kelas', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);

    $this->db->query('ALTER TABLE `siswa_kelas` ADD CONSTRAINT `chk_siswa_kelas_aktif` CHECK (`is_aktif` IN (0,1))');
  }

  public function down()
  {
    $this->forge->dropTable('siswa_kelas', true);
  }
}
