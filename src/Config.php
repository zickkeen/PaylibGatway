<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway;

use zickkeen\PaylibGateway\Exceptions\PaymentException;

/**
 * Configuration Management Class
 *
 * Mengelola konfigurasi untuk payment gateway dan provider
 */
class Config
{
    /**
     * @var array Konfigurasi default
     */
    private static array $defaultConfig = [
        'timeout' => 30,
        'retries' => 3,
        'debug' => false,
        'providers' => [
            'ovo' => [
                'enabled' => true,
                'base_url' => 'https://api.ovo.id',
                'timeout' => 30,
                'device_id' => null
            ],
            'gopay' => [
                'enabled' => true,
                'base_url' => 'https://api.gojekapi.com',
                'timeout' => 30
            ]
        ]
    ];

    /**
     * @var array Konfigurasi yang dimuat
     */
    private array $config;

    /**
     * Config constructor
     *
     * @param array $config Konfigurasi custom
     */
    public function __construct(array $config = [])
    {
        $this->config = $this->mergeConfig(self::$defaultConfig, $config);
        $this->validateConfig();
    }

    /**
     * Merge konfigurasi default dengan custom config
     *
     * @param array $default
     * @param array $custom
     * @return array
     */
    private function mergeConfig(array $default, array $custom): array
    {
        $result = $default;

        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = $this->mergeConfig($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Validasi konfigurasi
     *
     * @throws PaymentException
     */
    private function validateConfig(): void
    {
        // Validasi timeout
        if (isset($this->config['timeout']) && (!is_int($this->config['timeout']) || $this->config['timeout'] <= 0)) {
            throw new PaymentException('Timeout must be a positive integer');
        }

        // Validasi retries
        if (isset($this->config['retries']) && (!is_int($this->config['retries']) || $this->config['retries'] < 0)) {
            throw new PaymentException('Retries must be a non-negative integer');
        }

        // Validasi providers
        if (isset($this->config['providers']) && is_array($this->config['providers'])) {
            foreach ($this->config['providers'] as $provider => $settings) {
                $this->validateProviderConfig($provider, $settings);
            }
        }
    }

    /**
     * Validasi konfigurasi provider
     *
     * @param string $provider
     * @param array $config
     * @throws PaymentException
     */
    private function validateProviderConfig(string $provider, array $config): void
    {
        // Validasi base_url
        if (isset($config['base_url']) && !filter_var($config['base_url'], FILTER_VALIDATE_URL)) {
            throw new PaymentException("Invalid base_url for provider {$provider}");
        }

        // Validasi timeout provider
        if (isset($config['timeout']) && (!is_int($config['timeout']) || $config['timeout'] <= 0)) {
            throw new PaymentException("Invalid timeout for provider {$provider}");
        }
    }

    /**
     * Mendapatkan nilai konfigurasi
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * Set nilai konfigurasi
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->setNestedValue($this->config, $key, $value);
        $this->validateConfig();
        return $this;
    }

    /**
     * Mendapatkan semua konfigurasi
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Mendapatkan konfigurasi provider tertentu
     *
     * @param string $provider
     * @return array
     * @throws PaymentException
     */
    public function getProviderConfig(string $provider): array
    {
        $providerConfig = $this->get("providers.{$provider}", []);

        if (empty($providerConfig)) {
            throw new PaymentException("Provider '{$provider}' configuration not found");
        }

        return $providerConfig;
    }

    /**
     * Mengecek apakah provider diaktifkan
     *
     * @param string $provider
     * @return bool
     */
    public function isProviderEnabled(string $provider): bool
    {
        return $this->get("providers.{$provider}.enabled", false);
    }

    /**
     * Mendapatkan daftar provider yang diaktifkan
     *
     * @return array
     */
    public function getEnabledProviders(): array
    {
        $enabled = [];
        $providers = $this->get('providers', []);

        foreach ($providers as $name => $config) {
            if (isset($config['enabled']) && $config['enabled']) {
                $enabled[] = $name;
            }
        }

        return $enabled;
    }

    /**
     * Load konfigurasi dari file
     *
     * @param string $filePath
     * @return self
     * @throws PaymentException
     */
    public static function loadFromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new PaymentException("Configuration file not found: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'php':
                $config = require $filePath;
                break;
            case 'json':
                $config = json_decode(file_get_contents($filePath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new PaymentException('Invalid JSON configuration file');
                }
                break;
            default:
                throw new PaymentException("Unsupported configuration file format: {$extension}");
        }

        if (!is_array($config)) {
            throw new PaymentException('Configuration must be an array');
        }

        return new self($config);
    }

    /**
     * Mendapatkan nilai nested dari array
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getNestedValue(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    /**
     * Set nilai nested dalam array
     *
     * @param array &$array
     * @param string $key
     * @param mixed $value
     */
    private function setNestedValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Reset konfigurasi ke default
     *
     * @return self
     */
    public function reset(): self
    {
        $this->config = self::$defaultConfig;
        return $this;
    }
}