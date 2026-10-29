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
}