<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateKelasTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'BIGINT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'sekolah_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'tahun_ajaran_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'nama' => [
        'type'       => 'VARCHAR',
        'constraint' => 50,
      ],
      'tingkat' => [
        'type'       => 'VARCHAR',
        'constraint' => 30,
        'null'       => true,
      ],
      'wali_guru_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
        'null'     => true,
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
    $this->forge->addUniqueKey(['sekolah_id', 'tahun_ajaran_id', 'nama'], 'uk_kelas_sekolah_tahun_nama');
    $this->forge->addUniqueKey(['id', 'tahun_ajaran_id'], 'uk_kelas_id_tahun');
    $this->forge->addKey('tahun_ajaran_id', false, false, 'idx_kelas_tahun');
    $this->forge->addKey('wali_guru_id', false, false, 'idx_kelas_wali');

    $this->forge->addForeignKey('sekolah_id', 'sekolah', 'id', 'CASCADE', 'RESTRICT', 'fk_kelas_sekolah');
    $this->forge->addForeignKey('tahun_ajaran_id', 'tahun_ajaran', 'id', 'CASCADE', 'RESTRICT', 'fk_kelas_tahun_ajaran');
    $this->forge->addForeignKey('wali_guru_id', 'guru', 'id', 'CASCADE', 'SET NULL', 'fk_kelas_wali_guru');

    $this->forge->createTable('kelas', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);

    $this->db->query('ALTER TABLE `kelas` ADD CONSTRAINT `chk_kelas_aktif` CHECK (`is_aktif` IN (0,1))');
  }

  public function down()
  {
    $this->forge->dropTable('kelas', true);
  }
}
