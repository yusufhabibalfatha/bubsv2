<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateJurnalMengajarTable extends Migration
{
  public function up()
  {
    $this->forge->addField([
      'id' => [
        'type'           => 'BIGINT',
        'unsigned'       => true,
        'auto_increment' => true,
      ],
      'kelas_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'tahun_ajaran_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'guru_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
      ],
      'mata_pelajaran_id' => [
        'type'     => 'BIGINT',
        'unsigned' => true,
        'null'     => true,
      ],
      'tanggal' => [
        'type' => 'DATE',
      ],
      'jam_ke' => [
        'type'       => 'VARCHAR',
        'constraint' => 20,
        'null'       => true,
      ],
      'jam_mulai' => [
        'type' => 'TIME',
        'null' => true,
      ],
      'jam_selesai' => [
        'type' => 'TIME',
        'null' => true,
      ],
      'materi' => [
        'type' => 'TEXT',
        'null' => true,
      ],
      'catatan' => [
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
    $this->forge->addKey('tanggal', false, false, 'idx_jurnal_tanggal');
    $this->forge->addKey(['kelas_id', 'tanggal'], false, false, 'idx_jurnal_kelas_tanggal');
    $this->forge->addKey(['guru_id', 'tanggal'], false, false, 'idx_jurnal_guru_tanggal');
    $this->forge->addKey('mata_pelajaran_id', false, false, 'idx_jurnal_mapel');
    $this->forge->addKey('tahun_ajaran_id', false, false, 'idx_jurnal_tahun');

    $this->forge->addForeignKey(['kelas_id', 'tahun_ajaran_id'], 'kelas', ['id', 'tahun_ajaran_id'], 'CASCADE', 'RESTRICT', 'fk_jurnal_kelas_tahun');
    $this->forge->addForeignKey('guru_id', 'guru', 'id', 'CASCADE', 'RESTRICT', 'fk_jurnal_guru');
    $this->forge->addForeignKey('mata_pelajaran_id', 'mata_pelajaran', 'id', 'CASCADE', 'SET NULL', 'fk_jurnal_mapel');

    $this->forge->createTable('jurnal_mengajar', true, [
      'ENGINE'         => 'InnoDB',
      'DEFAULT CHARSET' => 'utf8mb4',
      'COLLATE'        => 'utf8mb4_unicode_ci',
    ]);
  }

  public function down()
  {
    $this->forge->dropTable('jurnal_mengajar', true);
  }
}
