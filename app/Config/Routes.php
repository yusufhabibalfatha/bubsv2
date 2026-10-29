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

    // Endpoint khusus untuk mengambil tahun ajaran yang sedang aktif
    $routes->get('tahun-ajaran/aktif', 'TahunAjaranController::aktif');

    // Resource Routes untuk Data Master (Otomatis membuat GET, POST, PUT/PATCH, DELETE)
    $routes->resource('jenjang', ['controller' => 'JenjangController']);
    $routes->resource('sekolah', ['controller' => 'SekolahController']);
    $routes->resource('tahun-ajaran', ['controller' => 'TahunAjaranController']);
    $routes->resource('guru', ['controller' => 'GuruController']);
    $routes->resource('mata-pelajaran', ['controller' => 'MataPelajaranController']);
    $routes->resource('kelas', ['controller' => 'KelasController']);
    $routes->resource('siswa', ['controller' => 'SiswaController']);
    $routes->resource('siswa-kelas', ['controller' => 'SiswaKelasController']);

});