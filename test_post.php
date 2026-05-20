<?php
// Simple CLI tester for admin_data_api.php
chdir(__DIR__);
$_SERVER['REQUEST_METHOD'] = 'POST';
// Intentionally use 'siswa' to reproduce previous type mismatch
$_POST = [
    'action' => 'create',
    'type' => 'siswa',
    'nama_lengkap' => 'CLI Test',
    'nis' => '99999',
    'email' => 'cli-test@example.local',
    'kelas' => '9',
    'jurusan' => 'unknown',
    'password' => 'secret'
];
include 'admin_data_api.php';
