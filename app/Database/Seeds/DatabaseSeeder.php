<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  public function run()
  {
    // Nonaktifkan foreign key check sementara agar proses truncate bersih
    $this->db->disableForeignKeyChecks();

    $tables = [
      'notifikasi_wa',
      'absensi_mapel_siswa',
      'jurnal_mengajar',
      'siswa_kelas',
      'siswa',
      'kelas',
      'mata_pelajaran',
      'guru',
      'tahun_ajaran',
      'sekolah',
      'jenjang',
    ];

    foreach ($tables as $table) {
      $this->db->table($table)->truncate();
    }

    $this->db->enableForeignKeyChecks();

    // ==========================================
    // 1. SEED TABEL JENJANG
    // ==========================================
    $jenjangData = [
      ['id' => 1, 'nama' => 'SMP'],
      ['id' => 2, 'nama' => 'SMA'],
    ];
    $this->db->table('jenjang')->insertBatch($jenjangData);

    // ==========================================
    // 2. SEED TABEL SEKOLAH
    // ==========================================
    $alamatBudiUtomo = 'Jl. Rambutan No. 036 Rt. 10 Rw. 02 Kel. Kampung 4 Kec. Tarakan Timur, Tarakan, Kaltara';
    $latBudiUtomo    = 3.3016392;
    $longBudiUtomo   = 117.6232432;

    $templateSmp = "*LAPORAN KEHADIRAN SMP*\n"
                  . "*{nama_sekolah}*\n"
                  . "----------------------------------------\n"
                  . "Yth. Orang Tua/Wali dari *{nama_siswa}* ({kelas}),\n\n"
                  . "Berikut laporan KBM putra/putri Anda:\n"
                  . "• Tanggal: {tanggal}\n"
                  . "• Mapel: {mapel} (Jam ke-{jam_ke})\n"
                  . "• Guru: {guru}\n"
                  . "• Materi: {materi}\n"
                  . "• Status: *{status}*\n"
                  . "• Nilai: {nilai}\n"
                  . "• Keterangan: {keterangan}\n"
                  . "• Catatan: {catatan}\n"
                  . "----------------------------------------\n"
                  . "_Pesan otomatis Sistem Presensi SMP._";

    $templateSma = "*INFORMASI PRESENSI KBM SMA*\n"
                  . "*{nama_sekolah}*\n"
                  . "----------------------------------------\n"
                  . "Yth. Bapak/Ibu Orang Tua/Wali Siswa,\n"
                  . "• Nama: *{nama_siswa}*\n"
                  . "• Kelas: {kelas}\n\n"
                  . "Telah mengikuti pembelajaran pada:\n"
                  . "• Tanggal: {tanggal}\n"
                  . "• Mata Pelajaran: {mapel} (Jam ke-{jam_ke})\n"
                  . "• Pengajar: {guru}\n"
                  . "• Materi Pokok: {materi}\n"
                  . "• Kehadiran: *{status}*\n"
                  . "• Nilai Harian: {nilai}\n"
                  . "• Keterangan: {keterangan}\n"
                  . "• Catatan Guru: {catatan}\n"
                  . "----------------------------------------\n"
                  . "_Terima kasih atas perhatian dan kerja samanya._";

    $sekolahData = [
      [
        'id'         => 1,
        'jenjang_id' => 1, // SMP
        'nama'       => 'SMP Budi Utomo Boarding School',
        'alamat'     => $alamatBudiUtomo,
        'latitude'   => $latBudiUtomo,
        'longitude'  => $longBudiUtomo,
        'status'     => 'aktif',
        'fonnte_token' => 'ISI_TOKEN_FONNTE_SMP_DISINI',
        'is_wa_aktif'  => 1,
        'template_wa'  => $templateSmp,
      ],
      [
        'id'         => 2,
        'jenjang_id' => 2, // SMA
        'nama'       => 'SMA Budi Utomo Boarding School Tarakan',
        'alamat'     => $alamatBudiUtomo,
        'latitude'   => $latBudiUtomo,
        'longitude'  => $longBudiUtomo,
        'status'     => 'aktif',
        'fonnte_token' => 'ISI_TOKEN_FONNTE_SMA_DISINI',
        'is_wa_aktif'  => 1,
        'template_wa'  => $templateSma,
      ],
    ];
    $this->db->table('sekolah')->insertBatch($sekolahData);

    // ==========================================
    // 3. SEED TABEL TAHUN AJARAN
    // ==========================================
    $tahunAjaranData = [
      [
        'id'         => 1,
        'nama_tahun' => '2025/2026',
        'semester'   => 'Genap',
        'is_aktif'   => 0,
      ],
      [
        'id'         => 2,
        'nama_tahun' => '2026/2027',
        'semester'   => 'Ganjil',
        'is_aktif'   => 1, // Tahun ajaran aktif saat ini
      ],
    ];
    $this->db->table('tahun_ajaran')->insertBatch($tahunAjaranData);

    // ==========================================
    // 4. SEED TABEL GURU
    // ==========================================
    $guruData = [
      // Guru SMP (sekolah_id = 1)
      [
        'id'         => 1,
        'sekolah_id' => 1,
        'nip'        => '198801122015041001',
        'nama'       => 'Ahmad Fauzi, S.Pd.',
        'email'      => 'ahmad.fauzi@smpbudiutomo.sch.id',
        'no_hp'      => '6287865712625',
        'status'     => 'aktif',
      ],
      [
        'id'         => 2,
        'sekolah_id' => 1,
        'nip'        => '199205182019032002',
        'nama'       => 'Siti Rahmawati, S.Si.',
        'email'      => 'siti.rahma@smpbudiutomo.sch.id',
        'no_hp'      => '6287865712625',
        'status'     => 'aktif',
      ],
      // Guru SMA (sekolah_id = 2)
      [
        'id'         => 3,
        'sekolah_id' => 2,
        'nip'        => '199008242018011003',
        'nama'       => 'Budi Santoso, M.Pd.',
        'email'      => 'budi.santoso@smabudiutomo.sch.id',
        'no_hp'      => '6287865712625',
        'status'     => 'aktif',
      ],
      [
        'id'         => 4,
        'sekolah_id' => 2,
        'nip'        => '199511102022022004',
        'nama'       => 'Dewi Lestari, S.Kom.',
        'email'      => 'dewi.lestari@smabudiutomo.sch.id',
        'no_hp'      => '6287865712625',
        'status'     => 'aktif',
      ],
    ];
    $this->db->table('guru')->insertBatch($guruData);

    // ==========================================
    // 5. SEED TABEL MATA PELAJARAN
    // ==========================================
    $mapelData = [
      ['id' => 1, 'nama' => 'Pendidikan Pancasila'],
      ['id' => 2, 'nama' => 'Bahasa Indonesia'],
      ['id' => 3, 'nama' => 'Matematika'],
      ['id' => 4, 'nama' => 'Ilmu Pengetahuan Alam (IPA)'],
      ['id' => 5, 'nama' => 'Informatika'],
      ['id' => 6, 'nama' => 'Bahasa Inggris'],
    ];
    $this->db->table('mata_pelajaran')->insertBatch($mapelData);

    // ==========================================
    // 6. SEED TABEL KELAS (Tahun Ajaran 2026/2027 - ID: 2)
    // ==========================================
    $kelasData = [
      // Kelas SMP
      [
        'id'              => 1,
        'sekolah_id'      => 1,
        'tahun_ajaran_id' => 2,
        'nama'            => 'VII-A',
        'tingkat'         => '7',
        'wali_guru_id'    => 1,
        'is_aktif'        => 1,
      ],
      [
        'id'              => 2,
        'sekolah_id'      => 1,
        'tahun_ajaran_id' => 2,
        'nama'            => 'VIII-A',
        'tingkat'         => '8',
        'wali_guru_id'    => 2,
        'is_aktif'        => 1,
      ],
      // Kelas SMA
      [
        'id'              => 3,
        'sekolah_id'      => 2,
        'tahun_ajaran_id' => 2,
        'nama'            => 'X-1',
        'tingkat'         => '10',
        'wali_guru_id'    => 3,
        'is_aktif'        => 1,
      ],
      [
        'id'              => 4,
        'sekolah_id'      => 2,
        'tahun_ajaran_id' => 2,
        'nama'            => 'XI-1',
        'tingkat'         => '11',
        'wali_guru_id'    => 4,
        'is_aktif'        => 1,
      ],
    ];
    $this->db->table('kelas')->insertBatch($kelasData);

    // ==========================================
    // 7. SEED TABEL SISWA
    // ==========================================
    $siswaData = [
      // Siswa SMP (ID 1 - 4)
      ['id' => 1, 'nama' => 'Rizky Pratama', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'L', 'status' => 'aktif'],
      ['id' => 2, 'nama' => 'Aisyah Putri', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'P', 'status' => 'aktif'],
      ['id' => 3, 'nama' => 'Dimas Anggara', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'L', 'status' => 'aktif'],
      ['id' => 4, 'nama' => 'Nabila Azzahra', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'P', 'status' => 'aktif'],
      // Siswa SMA (ID 5 - 8)
      ['id' => 5, 'nama' => 'Fajar Ramadhan', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'L', 'status' => 'aktif'],
      ['id' => 6, 'nama' => 'Zahra Amalia', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'P', 'status' => 'aktif'],
      ['id' => 7, 'nama' => 'Bagas Saputra', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'L', 'status' => 'aktif'],
      ['id' => 8, 'nama' => 'Intan Permatasari', 'no_hp' => '6287865712625', 'jenis_kelamin' => 'P', 'status' => 'aktif'],
    ];
    $this->db->table('siswa')->insertBatch($siswaData);

    // ==========================================
    // 8. SEED TABEL SISWA_KELAS
    // ==========================================
    $siswaKelasData = [
      // Masuk Kelas VII-A SMP (kelas_id = 1)
      ['id' => 1, 'siswa_id' => 1, 'kelas_id' => 1, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      ['id' => 2, 'siswa_id' => 2, 'kelas_id' => 1, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      // Masuk Kelas VIII-A SMP (kelas_id = 2)
      ['id' => 3, 'siswa_id' => 3, 'kelas_id' => 2, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      ['id' => 4, 'siswa_id' => 4, 'kelas_id' => 2, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      // Masuk Kelas X-1 SMA (kelas_id = 3)
      ['id' => 5, 'siswa_id' => 5, 'kelas_id' => 3, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      ['id' => 6, 'siswa_id' => 6, 'kelas_id' => 3, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      // Masuk Kelas XI-1 SMA (kelas_id = 4)
      ['id' => 7, 'siswa_id' => 7, 'kelas_id' => 4, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
      ['id' => 8, 'siswa_id' => 8, 'kelas_id' => 4, 'tahun_ajaran_id' => 2, 'is_aktif' => 1],
    ];
    $this->db->table('siswa_kelas')->insertBatch($siswaKelasData);

    // ==========================================
    // 9. SEED TABEL JURNAL MENGAJAR
    // ==========================================
    $jurnalData = [
      // Jurnal 1: Kelas VII-A SMP (kelas_id = 1), Guru Ahmad Fauzi (id = 1), Mapel IPA (id = 4)
      [
        'id'                => 1,
        'kelas_id'          => 1,
        'tahun_ajaran_id'   => 2,
        'guru_id'           => 1,
        'mata_pelajaran_id' => 4,
        'tanggal'           => date('Y-m-d'),
        'jam_ke'            => '1-2',
        'jam_mulai'         => '07:30:00',
        'jam_selesai'       => '09:00:00',
        'materi'            => 'Klasifikasi Makhluk Hidup dan Ekosistem',
        'catatan'           => 'Siswa antusias melakukan diskusi kelompok.',
      ],
      // Jurnal 2: Kelas XI-1 SMA (kelas_id = 4), Guru Budi Santoso (id = 3), Mapel Pendidikan Pancasila (id = 1)
      [
        'id'                => 2,
        'kelas_id'          => 4,
        'tahun_ajaran_id'   => 2,
        'guru_id'           => 3,
        'mata_pelajaran_id' => 1,
        'tanggal'           => date('Y-m-d'),
        'jam_ke'            => '3-4',
        'jam_mulai'         => '09:15:00',
        'jam_selesai'       => '10:45:00',
        'materi'            => 'Konstitusi dalam Kehidupan Berbangsa dan Bernegara',
        'catatan'           => 'Presentasi berjalan lancar, tugas dikumpulkan tepat waktu.',
      ],
    ];
    $this->db->table('jurnal_mengajar')->insertBatch($jurnalData);

    // ==========================================
    // 10. SEED TABEL ABSENSI MAPEL SISWA
    // ==========================================
    $absensiData = [
      [
        'id'                 => 1,
        'jurnal_mengajar_id' => 1,
        'siswa_id'           => 1,
        'status'             => 'hadir',
        'nilai'              => 88.50,
        'catatan'            => 'Aktif bertanya saat sesi diskusi.',
        'keterangan'         => 'Hadir tepat waktu',
      ],
      [
        'id'                 => 2,
        'jurnal_mengajar_id' => 1,
        'siswa_id'           => 2,
        'status'             => 'sakit',
        'nilai'              => null,
        'catatan'            => 'Surat keterangan dokter menyusul dari wali asrama.',
        'keterangan'         => 'Dirawat di klinik asrama',
      ],
      [
        'id'                 => 3,
        'jurnal_mengajar_id' => 2,
        'siswa_id'           => 7,
        'status'             => 'hadir',
        'nilai'              => 92.00,
        'catatan'            => 'Menjadi moderator presentasi kelompok.',
        'keterangan'         => 'Hadir tepat waktu',
      ],
      [
        'id'                 => 4,
        'jurnal_mengajar_id' => 2,
        'siswa_id'           => 8,
        'status'             => 'hadir',
        'nilai'              => 90.00,
        'catatan'            => null,
        'keterangan'         => null,
      ],
    ];
    $this->db->table('absensi_mapel_siswa')->insertBatch($absensiData);
  }
}
