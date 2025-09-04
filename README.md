# Paylib Gateway

Library PHP untuk integrasi dengan API pembayaran GoPay dan OVO dengan interface yang konsisten dan mudah digunakan.

## 🚀 Fitur Utama

- ✅ **Interface Konsisten**: API yang sama untuk semua provider pembayaran
- ✅ **Error Handling**: Exception handling yang komprehensif dengan custom exceptions
- ✅ **Logging**: Sistem logging yang fleksibel dengan PSR-3 compliance
- ✅ **Configuration Management**: Manajemen konfigurasi yang powerful
- ✅ **Type Safety**: Type hints dan attributes PHP 8.0+
- ✅ **Security**: Validasi input dan masking data sensitif
- ✅ **Extensible**: Mudah menambahkan provider baru

## 📦 Instalasi

```bash
composer require zickkeen/paylib-gateway
```

## ⚡ Penggunaan Dasar

### Konfigurasi Inline

```php
use zickkeen\PaylibGateway\PaymentGateway;

$config = [
    'ovo' => [
        'phone' => '08123456789'
    ],
    'gopay' => [
        'phone' => '08123456789',
        'password' => 'your_password'
    ]
];

$gateway = new PaymentGateway($config);

// Login ke provider
$result = $gateway->login('ovo', '08123456789');
print_r($result); // Cek apakah perlu verifikasi OTP

// Verifikasi kode OTP jika diperlukan
if ($result['requires_verification']) {
    $code = '123456'; // Kode dari SMS/APP
    $gateway->verifyCode('ovo', $code);
}

// Operasi lainnya
$balance = $gateway->getBalance('ovo');
$transactions = $gateway->getTransactions('ovo', 10);
```

### Menggunakan Config Class

```php
use zickkeen\PaylibGateway\Config;
use zickkeen\PaylibGateway\PaymentGateway;

$config = new Config([
    'debug' => true,
    'providers' => [
        'gopay' => [
            'enabled' => true,
            'phone' => '08123456789',
            'password' => 'your_password'
        ]
    ]
]);

$gateway = new PaymentGateway($config->all());
```

### Load dari File Konfigurasi

```php
use zickkeen\PaylibGateway\Config;
use zickkeen\PaylibGateway\PaymentGateway;

$config = Config::loadFromFile('/path/to/config.php');
$gateway = new PaymentGateway($config->all());
```

## 🔧 API Reference

### PaymentGateway Class

#### Metode Utama

```php
// Autentikasi
login(string $provider, string $phone): array
verifyCode(string $provider, string $code): array
logout(string $provider): bool
isAuthenticated(string $provider): bool

// Operasi
getBalance(string $provider): array
getTransactions(string $provider, int $limit = 10): array

// Utility
getAvailableProviders(): array
setLogger(LoggerInterface $logger): self
```

#### Response Format

Semua metode mengembalikan array dengan struktur konsisten:

```php
[
    'success' => true|false,
    'message' => 'Human readable message',
    'data' => [...], // Raw response dari provider
    // ... fields spesifik operasi
]
```

## 🛠️ Konfigurasi

### File Konfigurasi (config.php)

```php
return [
    'debug' => false,
    'timeout' => 30,
    'retries' => 3,

    'providers' => [
        'ovo' => [
            'enabled' => true,
            'phone' => '08123456789',
            'device_id' => null, // Auto-generated
            'base_url' => 'https://api.ovo.id',
            'timeout' => 30
        ],

        'gopay' => [
            'enabled' => true,
            'phone' => '08123456789',
            'password' => 'your_password',
            'base_url' => 'https://api.gopay.co.id',
            'timeout' => 30
        ]
    ]
];
```

### Environment Variables

```bash
# OVO Configuration
OVO_PHONE=08123456789
OVO_DEVICE_ID=your_device_id

# GoPay Configuration
GOPAY_PHONE=08123456789
GOPAY_PASSWORD=your_password

# Global Configuration
PAYLIB_DEBUG=false
PAYLIB_TIMEOUT=30
```

## 📝 Error Handling

Library menggunakan custom exceptions untuk error handling:

```php
use zickkeen\PaylibGateway\Exceptions\PaymentException;
use zickkeen\PaylibGateway\Exceptions\ProviderException;

try {
    $gateway->login('ovo', '08123456789');
} catch (ProviderException $e) {
    // Error dari provider (HTTP errors, API errors)
    echo "Provider Error: " . $e->getMessage();
    echo "Endpoint: " . $e->getEndpoint();
    echo "HTTP Code: " . $e->getCode();
} catch (PaymentException $e) {
    // Error umum payment gateway
    echo "Payment Error: " . $e->getMessage();
    echo "Provider: " . $e->getProvider();
}
```

## 🔒 Security Best Practices

1. **Jangan commit kredensial** ke version control
2. **Gunakan environment variables** untuk production
3. **Enable logging** untuk monitoring
4. **Validasi input** sebelum menggunakan
5. **Gunakan HTTPS** untuk semua komunikasi

## 📊 Logging

### Custom Logger

```php
use zickkeen\PaylibGateway\Interfaces\LoggerInterface;

class MyLogger implements LoggerInterface
{
    public function info(string $message, array $context = []): void
    {
        // Implementasi logging Anda
        file_put_contents('/var/log/payment.log', date('Y-m-d H:i:s') . " INFO: {$message}\n", FILE_APPEND);
    }

    // ... implementasi method lainnya
}

$logger = new MyLogger();
$gateway = new PaymentGateway($config, $logger);
```

### PSR-3 Logger (Coming Soon)

Dukungan untuk PSR-3 Logger interface akan ditambahkan di versi mendatang.

## 🧪 Testing

```bash
# Jalankan unit tests
vendor/bin/phpunit

# Jalankan dengan coverage
vendor/bin/phpunit --coverage-html=coverage
```

## 📋 Requirements

- **PHP**: 8.0 atau lebih tinggi
- **Composer**: 2.0+
- **Extensions**: curl, json, mbstring

## 🏗️ Arsitektur

```
paylib-gateway/
├── src/
│   ├── PaymentGateway.php          # Kelas utama
│   ├── Config.php                  # Manajemen konfigurasi
│   ├── Interfaces/
│   │   ├── PaymentProviderInterface.php
│   │   └── LoggerInterface.php
│   ├── Providers/
│   │   ├── OVO.php                 # OVO implementation
│   │   └── GoPay.php               # GoPay implementation
│   ├── Exceptions/
│   │   ├── PaymentException.php
│   │   └── ProviderException.php
│   ├── NullLogger.php              # Default logger
├── examples/
│   ├── usage.php                   # Contoh penggunaan
│   └── config.php                  # Contoh konfigurasi
├── tests/                          # Unit tests
├── composer.json
└── README.md
```

## 🤝 Contributing

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📄 Lisensi

Distributed under the MIT License. See `LICENSE` for more information.

## 📞 Support

- 📧 Email: team@zickkeen.com
- 🐛 Issues: [GitHub Issues](https://github.com/zickkeen/paylib-gateway/issues)
- 📖 Docs: [Documentation](https://github.com/zickkeen/paylib-gateway/wiki)

## 🙏 Acknowledgments

- Terima kasih kepada tim zickkeen
- Inspired by various payment gateway libraries
- Built with ❤️ for Indonesian developers