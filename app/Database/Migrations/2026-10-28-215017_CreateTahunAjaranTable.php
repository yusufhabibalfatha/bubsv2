<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTahunAjaranTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'BIGINT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'nama_tahun' => [
        'type'       => 'VARCHAR',
        'constraint' => 20,
      ],
      'semester' => [
        'type'       => 'ENUM',
        'constraint' => ['Ganjil', 'Genap'],
      ],
      'is_aktif' => [
        'type'       => 'TINYINT',
        'constraint' => 1,
        'default'    => 0,
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
    $this->forge->addUniqueKey(['nama_tahun', 'semester'], 'uk_tahun_semester');
    $this->forge->addKey('is_aktif', false, false, 'idx_tahun_aktif');

    $this->forge->createTable('tahun_ajaran', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);

    $this->db->query('ALTER TABLE `tahun_ajaran` ADD CONSTRAINT `chk_tahun_aktif` CHECK (`is_aktif` IN (0,1))');
  }

  public function down()
  {
    $this->forge->dropTable('tahun_ajaran', true);
  }
}
