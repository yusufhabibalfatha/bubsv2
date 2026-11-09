<?php

namespace App\Libraries;

class FonnteService
{
    protected string $apiUrl = 'https://api.fonnte.com/send';

    /**
     * Mengirim banyak pesan sekaligus (Bulk) dalam 1 kali request menggunakan parameter 'data' Fonnte
     *
     * @param string $token Token Fonnte milik sekolah (SMP / SMA)
     * @param array  $messages Array berisi [['target' => '0812...', 'message' => '...', 'delay' => '2-4'], ...]
     * @return array Hasil decode response dari Fonnte
     */
    public function sendBulk(string $token, array $messages): array
    {
        if (empty($token) || empty($messages)) {
            return [
                'status' => false,
                'reason' => 'Token Fonnte atau daftar pesan kosong.',
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'data' => json_encode($messages),
            ],
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
            ],
        ]);

        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return [
                'status' => false,
                'reason' => 'cURL Error: ' . $error,
            ];
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            return [
                'status' => false,
                'reason' => 'Respons tidak valid dari server Fonnte.',
            ];
        }

        return $decoded;
    }
    /**
     * Menyusun teks ringkasan absensi kelas untuk dikirim ke Grup WhatsApp Kelas
     */
    public static function formatPesanRingkasanKelas(array $infoJurnal, array $daftarAbsensi): string
    {
        $namaKelas  = $infoJurnal['nama_kelas'] ?? '-';
        $namaMapel  = $infoJurnal['nama_mapel'] ?? '-';
        $namaGuru   = $infoJurnal['nama_guru'] ?? '-';
        $materi     = ! empty($infoJurnal['materi']) ? $infoJurnal['materi'] : '-';
        $jamMulai   = ! empty($infoJurnal['jam_mulai']) ? substr($infoJurnal['jam_mulai'], 0, 5) : '-';
        $jamSelesai = ! empty($infoJurnal['jam_selesai']) ? substr($infoJurnal['jam_selesai'], 0, 5) : '-';

        // Format Hari & Tanggal Bahasa Indonesia (contoh: Senin, 9 November 2026)
        $hariTgl = '-';
        if (! empty($infoJurnal['tanggal'])) {
            $ts        = strtotime($infoJurnal['tanggal']);
            $namaHari  = [
                'Sunday'    => 'Minggu',
                'Monday'    => 'Senin',
                'Tuesday'   => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday'  => 'Kamis',
                'Friday'    => 'Jumat',
                'Saturday'  => 'Sabtu',
            ];
            $namaBulan = [
                1  => 'Januari',
                2  => 'Februari',
                3  => 'Maret',
                4  => 'April',
                5  => 'Mei',
                6  => 'Juni',
                7  => 'Juli',
                8  => 'Agustus',
                9  => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];
            $hari    = $namaHari[date('l', $ts)] ?? date('l', $ts);
            $tgl     = (int) date('j', $ts);
            $bulan   = $namaBulan[(int) date('n', $ts)] ?? date('F', $ts);
            $tahun   = date('Y', $ts);
            $hariTgl = "{$hari}, {$tgl} {$bulan} {$tahun}";
        }

        $msg  = "📅 *Presensi Kelas {$namaKelas}*\n";
        $msg .= "*Hari/Tgl:* {$hariTgl}\n";
        $msg .= "*Mapel:* {$namaMapel}\n";
        $msg .= "*Jam:* {$jamMulai} - {$jamSelesai}\n";
        $msg .= "*Guru:* {$namaGuru}\n";
        $msg .= "*Materi:* {$materi}\n\n";

        $urutan = [
            ['key' => 'hadir', 'label' => 'Hadir', 'icon' => '✅'],
            ['key' => 'terlambat', 'label' => 'Terlambat', 'icon' => '🕐'],
            ['key' => 'izin', 'label' => 'Izin', 'icon' => '📋'],
            ['key' => 'sakit', 'label' => 'Sakit', 'icon' => '🤒'],
            ['key' => 'alpa', 'label' => 'Alpa', 'icon' => '❌'],
        ];

        foreach ($urutan as $kat) {
            $grup = array_filter($daftarAbsensi, static fn ($s) => ($s['status'] ?? '') === $kat['key']);
            if (! empty($grup)) {
                $jumlah = count($grup);
                $msg   .= "{$kat['icon']} *{$kat['label']} ({$jumlah}):*\n";
                foreach ($grup as $s) {
                    $namaSiswa = $s['nama_siswa'] ?? '-';
                    $ket       = ! empty($s['keterangan']) ? " ({$s['keterangan']})" : '';
                    $msg      .= "- {$namaSiswa}{$ket}\n";
                }
                $msg .= "\n";
            }
        }

        $totalSiswa = count($daftarAbsensi);
        $msg       .= "📊 *Total:* {$totalSiswa} Siswa";

        return $msg;
    }

    /**
     * Menyusun teks pesan WhatsApp berdasarkan template_wa milik sekolah masing-masing
     */
    public static function formatPesanAbsensi(array $infoJurnal, array $infoSiswa, ?string $customTemplate = null): string
    {
        // Template cadangan jika admin sekolah tidak sengaja menghapus habis template di database
        $defaultTemplate = "*LAPORAN KEHADIRAN KBM*\n"
                         . "*{nama_sekolah}*\n"
                         . "----------------------------------------\n"
                         . "Nama Siswa: *{nama_siswa}* ({kelas})\n"
                         . "Tanggal: {tanggal}\n"
                         . "Mapel: {mapel} (Jam ke-{jam_ke})\n"
                         . "Guru: {guru}\n"
                         . "Materi: {materi}\n"
                         . "Status: *{status}*\n"
                         . "Nilai: {nilai}\n"
                         . "Keterangan: {keterangan}\n"
                         . "Catatan: {catatan}";

        $template = ! empty(trim((string) $customTemplate)) ? $customTemplate : $defaultTemplate;

        // Daftar variabel yang bisa dipakai Admin di Frontend Vue.js
        $replacements = [
            '{nama_sekolah}' => $infoJurnal['nama_sekolah'] ?? '-',
            '{nama_siswa}'   => $infoSiswa['nama_siswa'] ?? '-',
            '{kelas}'        => $infoJurnal['nama_kelas'] ?? '-',
            '{tanggal}'      => ! empty($infoJurnal['tanggal']) ? date('d-m-Y', strtotime($infoJurnal['tanggal'])) : '-',
            '{mapel}'        => $infoJurnal['nama_mapel'] ?? '-',
            '{jam_ke}'       => ! empty($infoJurnal['jam_ke']) ? $infoJurnal['jam_ke'] : '-',
            '{jam_mulai}'    => ! empty($infoJurnal['jam_mulai']) ? substr($infoJurnal['jam_mulai'], 0, 5) : '-',
            '{jam_selesai}'  => ! empty($infoJurnal['jam_selesai']) ? substr($infoJurnal['jam_selesai'], 0, 5) : '-',
            '{guru}'         => $infoJurnal['nama_guru'] ?? '-',
            '{materi}'       => ! empty($infoJurnal['materi']) ? $infoJurnal['materi'] : '-',
            '{catatan_jurnal}'=> ! empty($infoJurnal['catatan']) ? $infoJurnal['catatan'] : '-',
            '{status}'       => strtoupper($infoSiswa['status'] ?? 'HADIR'),
            '{nilai}'        => ($infoSiswa['nilai'] !== null && $infoSiswa['nilai'] !== '') ? $infoSiswa['nilai'] : '-',
            '{keterangan}'   => ! empty($infoSiswa['keterangan']) ? $infoSiswa['keterangan'] : '-',
            '{catatan}'      => ! empty($infoSiswa['catatan']) ? $infoSiswa['catatan'] : '-',
        ];

        return strtr($template, $replacements);
    }

    /**
     * Menghitung jarak (dalam meter) antara 2 titik koordinat GPS (Haversine Formula)
     */
    public static function hitungJarakMeter(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // Meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }

    /**
     * Menyusun teks pesan WhatsApp Grup untuk Absensi Guru
     */
    public static function formatPesanAbsensiGuru(array $absenGuru, ?string $customTemplate = null): string
    {
        $defaultTemplate = "*LAPORAN PRESENSI GURU ({tipe_absen})*\n"
                         . "*{nama_sekolah}*\n"
                         . "----------------------------------------\n"
                         . "• *Nama Guru:* {nama_guru}\n"
                         . "• *Tanggal:* {tanggal}\n"
                         . "• *Waktu:* {waktu} WITA\n"
                         . "• *Status:* *{status}*\n"
                         . "• *Posisi:* {posisi} ({lokasi})\n"
                         . "• *Jarak GPS:* {jarak}\n"
                         . "• *Keterangan:* {keterangan}";

        $template = ! empty(trim((string) $customTemplate)) ? $customTemplate : $defaultTemplate;

        $posisiLabel = ($absenGuru['kategori_posisi'] ?? '') === 'di_sekolah'
            ? 'Di Area Sekolah'
            : 'Di Luar Sekolah';

        $lokasiLabel = ! empty($absenGuru['nama_lokasi'])
            ? $absenGuru['nama_lokasi']
            : 'Luar Kampus';

        $jarakLabel = ($absenGuru['jarak_meter'] !== null && $absenGuru['jarak_meter'] !== '')
            ? $absenGuru['jarak_meter'] . ' meter'
            : '-';

        $replacements = [
            '{nama_sekolah}' => $absenGuru['nama_sekolah'] ?? '-',
            '{jenjang}'      => $absenGuru['nama_jenjang'] ?? '-',
            '{nama_guru}'    => $absenGuru['nama_guru'] ?? '-',
            '{nip}'          => $absenGuru['nip'] ?: '-',
            '{tanggal}'      => ! empty($absenGuru['tanggal']) ? date('d-m-Y', strtotime($absenGuru['tanggal'])) : '-',
            '{tipe_absen}'   => strtoupper($absenGuru['tipe_absen'] ?? 'DATANG'),
            '{waktu}'        => ! empty($absenGuru['waktu_absen']) ? substr($absenGuru['waktu_absen'], 0, 5) : '-',
            '{status}'       => strtoupper(str_replace('_', ' ', $absenGuru['status'] ?? 'HADIR')),
            '{posisi}'       => $posisiLabel,
            '{lokasi}'       => $lokasiLabel,
            '{jarak}'        => $jarakLabel,
            '{keterangan}'   => ! empty($absenGuru['keterangan']) ? $absenGuru['keterangan'] : '-',
        ];

        return strtr($template, $replacements);
    }
}