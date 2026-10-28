<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMataPelajaranTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'BIGINT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'nama' => [
        'type'       => 'VARCHAR',
        'constraint' => 100,
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
    $this->forge->addUniqueKey('nama', 'uk_mapel_nama');

    $this->forge->createTable('mata_pelajaran', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);
  }

  public function down()
  {
    $this->forge->dropTable('mata_pelajaran', true);
  }
}
