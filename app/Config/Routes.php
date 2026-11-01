<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// =========================================================
// REST API ROUTES (Untuk Frontend Vue.js)
// =========================================================
$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {

    // Endpoint khusus Tahun Ajaran Aktif
    $routes->get('tahun-ajaran/aktif', 'TahunAjaranController::aktif');

    // Endpoint khusus Kirim Ulang (Resend) Notifikasi WA yang gagal/pending
    $routes->post('notifikasi-wa/resend/(:num)', 'NotifikasiWaController::resend/$1');

    // Endpoint khusus Statistik Dashboard & Rekap Laporan Kelas
    $routes->get('laporan/dashboard', 'LaporanController::dashboard');
    $routes->get('laporan/rekap-kelas', 'LaporanController::rekapKelas');

    // Resource Routes Data Master
    $routes->resource('jenjang', ['controller' => 'JenjangController']);
    $routes->resource('sekolah', ['controller' => 'SekolahController']);
    $routes->resource('tahun-ajaran', ['controller' => 'TahunAjaranController']);
    $routes->resource('guru', ['controller' => 'GuruController']);
    $routes->resource('mata-pelajaran', ['controller' => 'MataPelajaranController']);
    $routes->resource('kelas', ['controller' => 'KelasController']);
    $routes->resource('siswa', ['controller' => 'SiswaController']);
    $routes->resource('siswa-kelas', ['controller' => 'SiswaKelasController']);

    // Resource Routes Transaksi Jurnal, Absensi & Log WA
    $routes->resource('jurnal-mengajar', ['controller' => 'JurnalMengajarController']);
    $routes->resource('absensi-mapel-siswa', ['controller' => 'AbsensiMapelSiswaController']);
    $routes->resource('notifikasi-wa', ['controller' => 'NotifikasiWaController', 'only' => ['index']]);

    // =========================================================
    // ENDPOINT FITUR ABSENSI GURU (GPS & WA GRUP)
    // =========================================================
    $routes->get('absensi-guru/dashboard', 'AbsensiGuruController::dashboard');
    $routes->post('notifikasi-wa-guru/resend/(:num)', 'NotifikasiWaGuruController::resend/$1');

    $routes->resource('lokasi-sekolah', ['controller' => 'LokasiSekolahController']);
    $routes->resource('absensi-guru', ['controller' => 'AbsensiGuruController']);
    $routes->resource('notifikasi-wa-guru', ['controller' => 'NotifikasiWaGuruController', 'only' => ['index']]);

    // =========================================================
    // ENDPOINT FITUR MANAJEMEN KEUANGAN & SPP SISWA (/keuangan/)
    // =========================================================
    $routes->post('keuangan/verify-pin', 'KeuanganController::verifyPin');
    $routes->get('keuangan/dashboard', 'KeuanganController::dashboard');
    $routes->get('keuangan/pos-tagihan', 'KeuanganController::listPosTagihan');
    $routes->post('keuangan/pos-tagihan', 'KeuanganController::createPosTagihan');
    $routes->put('keuangan/pos-tagihan/(:num)', 'KeuanganController::updatePosTagihan/$1');
    $routes->delete('keuangan/pos-tagihan/(:num)', 'KeuanganController::deletePosTagihan/$1');
    $routes->post('keuangan/pos-tagihan/(:num)/generate', 'KeuanganController::generateTagihanPos/$1');
    $routes->get('keuangan/tagihan-siswa', 'KeuanganController::tagihanSiswa');
    $routes->get('keuangan/pembayaran', 'KeuanganController::listPembayaran');
    $routes->post('keuangan/pembayaran', 'KeuanganController::simpanPembayaran');
    $routes->delete('keuangan/pembayaran/(:num)', 'KeuanganController::hapusPembayaran/$1');
    $routes->get('keuangan/matriks-kelas', 'KeuanganController::matriksKelas');
    $routes->post('keuangan/reminder-wa', 'KeuanganController::reminderWaSiswa');
    $routes->post('keuangan/reminder-wa-kelas', 'KeuanganController::reminderWaKelas');
    $routes->get('keuangan/notifikasi-wa', 'KeuanganController::listNotifikasiWa');
    $routes->post('keuangan/notifikasi-wa/resend/(:num)', 'KeuanganController::resendNotifikasiWa/$1');
    $routes->get('keuangan/pengaturan', 'KeuanganController::getPengaturan');
    $routes->post('keuangan/pengaturan', 'KeuanganController::savePengaturan');
});