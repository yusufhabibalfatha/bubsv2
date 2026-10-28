<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSiswaTable extends Migration
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
        'constraint' => 150,
      ],
      'no_hp' => [
        'type'       => 'VARCHAR',
        'constraint' => 20,
        'null'       => true,
      ],
      'jenis_kelamin' => [
        'type'       => 'ENUM',
        'constraint' => ['L', 'P'],
      ],
      'status' => [
        'type'       => 'ENUM',
        'constraint' => ['aktif', 'lulus', 'pindah', 'nonaktif'],
        'default'    => 'aktif',
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
    $this->forge->addKey('nama', false, false, 'idx_siswa_nama');
    $this->forge->addKey('no_hp', false, false, 'idx_siswa_no_hp');
    $this->forge->addKey('status', false, false, 'idx_siswa_status');

    $this->forge->createTable('siswa', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);
  }

  public function down()
  {
    $this->forge->dropTable('siswa', true);
  }
}
