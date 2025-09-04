<?php

/**
 * Contoh file konfigurasi untuk Payment Gateway Library
 *
 * Copy file ini dan sesuaikan dengan kredensial Anda
 */

return [
    // Pengaturan global
    'debug' => false,
    'timeout' => 30,
    'retries' => 3,

    // Konfigurasi provider
    'providers' => [
        'ovo' => [
            'enabled' => true,
            'phone' => '08123456789', // Ganti dengan nomor OVO Anda
            'device_id' => null, // Akan di-generate otomatis jika null
            'base_url' => 'https://api.ovo.id',
            'timeout' => 30
        ],

        'gopay' => [
            'enabled' => true,
            'phone' => '08123456789', // Ganti dengan nomor GoPay Anda
            'password' => 'your_password_here', // Ganti dengan password GoPay Anda
            'base_url' => 'https://api.gojekapi.com',
            'timeout' => 30
        ]
    ]
];