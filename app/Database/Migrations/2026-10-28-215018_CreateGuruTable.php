<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateGuruTable extends Migration
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
      'nip' => [
        'type'       => 'VARCHAR',
        'constraint' => 30,
        'null'       => true,
      ],
      'nama' => [
        'type'       => 'VARCHAR',
        'constraint' => 150,
      ],
      'email' => [
        'type'       => 'VARCHAR',
        'constraint' => 150,
        'null'       => true,
      ],
      'no_hp' => [
        'type'       => 'VARCHAR',
        'constraint' => 30,
        'null'       => true,
      ],
      'status' => [
        'type'       => 'ENUM',
        'constraint' => ['aktif', 'nonaktif'],
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
    $this->forge->addKey('sekolah_id', false, false, 'idx_guru_sekolah');
    $this->forge->addKey('nama', false, false, 'idx_guru_nama');
    $this->forge->addUniqueKey(['sekolah_id', 'nip'], 'uk_guru_sekolah_nip');
    $this->forge->addForeignKey('sekolah_id', 'sekolah', 'id', 'CASCADE', 'RESTRICT', 'fk_guru_sekolah');

    $this->forge->createTable('guru', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);
  }

  public function down()
  {
    $this->forge->dropTable('guru', true);
  }
}
