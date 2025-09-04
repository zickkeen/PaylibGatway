<?php

declare(strict_types=1);

/**
 * Contoh penggunaan Payment Gateway Library
 *
 * File ini menunjukkan cara menggunakan library untuk integrasi dengan OVO dan GoPay
 */

require_once __DIR__ . '/../tests/autoload.php';

use zickkeen\PaylibGateway\PaymentGateway;
use zickkeen\PaylibGateway\Config;
use zickkeen\PaylibGateway\Exceptions\PaymentException;

// Contoh 1: Penggunaan dasar dengan konfigurasi inline
echo "=== CONTOH 1: Penggunaan Dasar ===\n";

$config = [
    'ovo' => [
        'phone' => '08123456789'
    ],
    'gopay' => [
        'phone' => '08123456789',
        'password' => 'your_password_here'
    ]
];

try {
    $gateway = new PaymentGateway($config);

    // Login ke OVO
    echo "Login ke OVO...\n";
    $loginResult = $gateway->login('ovo', '08123456789');
    print_r($loginResult);

    // Jika memerlukan verifikasi kode
    if (isset($loginResult['requires_verification']) && $loginResult['requires_verification']) {
        echo "Masukkan kode verifikasi: ";
        $code = trim(fgets(STDIN));

        $verifyResult = $gateway->verifyCode('ovo', $code);
        print_r($verifyResult);
    }

    // Cek saldo OVO
    $balance = $gateway->getBalance('ovo');
    echo "Saldo OVO: " . ($balance['balance'] ?? 0) . " " . ($balance['currency'] ?? 'IDR') . "\n";

    // Lihat transaksi OVO
    $transactions = $gateway->getTransactions('ovo', 5);
    echo "Transaksi OVO terakhir:\n";
    print_r($transactions);

} catch (PaymentException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Contoh 2: Menggunakan Config class
echo "\n=== CONTOH 2: Menggunakan Config Class ===\n";

try {
    $configObj = new Config([
        'debug' => true,
        'providers' => [
            'gopay' => [
                'enabled' => true,
                'phone' => '08123456789',
                'password' => 'your_password_here'
            ],
            'ovo' => [
                'enabled' => true,
                'phone' => '08123456789'
            ]
        ]
    ]);

    $gateway = new PaymentGateway($configObj->all());

    // Cek provider yang tersedia
    $availableProviders = $gateway->getAvailableProviders();
    echo "Provider yang tersedia: " . implode(', ', $availableProviders) . "\n";

    // Login ke GoPay
    echo "Login ke GoPay...\n";
    $loginResult = $gateway->login('gopay', '08123456789');
    print_r($loginResult);

} catch (PaymentException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Contoh 3: Error handling
echo "\n=== CONTOH 3: Error Handling ===\n";

try {
    $gateway = new PaymentGateway();

    // Mencoba menggunakan provider yang tidak dikonfigurasi
    $gateway->login('unknown_provider', '08123456789');

} catch (PaymentException $e) {
    echo "Caught PaymentException: " . $e->getMessage() . "\n";
    echo "Provider: " . $e->getProvider() . "\n";
    echo "Context: " . json_encode($e->getContext()) . "\n";
}

// Contoh 4: Load konfigurasi dari file
echo "\n=== CONTOH 4: Load Config dari File ===\n";

// Buat file konfigurasi contoh
$configFile = __DIR__ . '/config.php';
$configContent = "<?php\nreturn [\n    'debug' => false,\n    'providers' => [\n        'ovo' => [\n            'enabled' => true,\n            'phone' => '08123456789'\n        ]\n    ]\n];";

file_put_contents($configFile, $configContent);

try {
    $configFromFile = Config::loadFromFile($configFile);
    $gateway = new PaymentGateway($configFromFile->all());

    echo "Konfigurasi berhasil dimuat dari file\n";
    echo "Provider yang diaktifkan: " . implode(', ', $configFromFile->getEnabledProviders()) . "\n";

} catch (PaymentException $e) {
    echo "Error loading config: " . $e->getMessage() . "\n";
} finally {
    // Cleanup
    if (file_exists($configFile)) {
        unlink($configFile);
    }
}

// Contoh 5: Advanced usage dengan custom logger
echo "\n=== CONTOH 5: Custom Logger ===\n";

class SimpleConsoleLogger implements \zickkeen\PaylibGateway\Interfaces\LoggerInterface
{
    public function emergency(string $message, array $context = []): void
    {
        echo "[EMERGENCY] {$message}\n";
    }

    public function alert(string $message, array $context = []): void
    {
        echo "[ALERT] {$message}\n";
    }

    public function critical(string $message, array $context = []): void
    {
        echo "[CRITICAL] {$message}\n";
    }

    public function error(string $message, array $context = []): void
    {
        echo "[ERROR] {$message}\n";
    }

    public function warning(string $message, array $context = []): void
    {
        echo "[WARNING] {$message}\n";
    }

    public function notice(string $message, array $context = []): void
    {
        echo "[NOTICE] {$message}\n";
    }

    public function info(string $message, array $context = []): void
    {
        echo "[INFO] {$message}\n";
    }

    public function debug(string $message, array $context = []): void
    {
        echo "[DEBUG] {$message}\n";
    }

    public function log(string $level, string $message, array $context = []): void
    {
        echo "[{$level}] {$message}\n";
    }
}

try {
    $logger = new SimpleConsoleLogger();
    $gateway = new PaymentGateway($config ?? [], $logger);

    echo "Mencoba login dengan logging...\n";
    // Ini akan gagal karena tidak ada konfigurasi, tapi akan menampilkan log
    $gateway->login('ovo', '08123456789');

} catch (PaymentException $e) {
    echo "Error (expected): " . $e->getMessage() . "\n";
}

echo "\n=== SELESAI ===\n";
echo "Library ini menyediakan interface yang konsisten untuk integrasi dengan OVO dan GoPay.\n";
echo "Pastikan untuk mengganti kredensial dengan yang valid sebelum production use.\n";