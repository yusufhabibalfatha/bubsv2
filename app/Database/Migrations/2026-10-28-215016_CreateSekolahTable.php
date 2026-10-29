<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSekolahTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'BIGINT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'jenjang_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'nama' => [
        'type'       => 'VARCHAR',
        'constraint' => 150,
      ],
      'alamat' => [
        'type' => 'TEXT',
        'null' => true,
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
      'status' => [
        'type'       => 'ENUM',
        'constraint' => ['aktif', 'nonaktif'],
        'default'    => 'aktif',
      ],
      'fonnte_token' => [
          'type'       => 'VARCHAR',
          'constraint' => 100,
          'null'       => true,
      ],
      'is_wa_aktif' => [
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
    $this->forge->addKey('jenjang_id', false, false, 'idx_sekolah_jenjang');
    $this->forge->addKey('status', false, false, 'idx_sekolah_status');
    $this->forge->addForeignKey('jenjang_id', 'jenjang', 'id', 'CASCADE', 'RESTRICT', 'fk_sekolah_jenjang');

    $this->forge->createTable('sekolah', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);
  }

  public function down()
  {
    $this->forge->dropTable('sekolah', true);
  }
}
