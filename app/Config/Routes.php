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

});