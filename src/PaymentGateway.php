<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway;

use zickkeen\PaylibGateway\Interfaces\PaymentProviderInterface;
use zickkeen\PaylibGateway\Interfaces\LoggerInterface;
use zickkeen\PaylibGateway\Providers\OVO;
use zickkeen\PaylibGateway\Providers\GoPay;
use zickkeen\PaylibGateway\Exceptions\PaymentException;
use zickkeen\PaylibGateway\Exceptions\ProviderException;

/**
 * Payment Gateway Main Class
 *
 * Kelas utama untuk mengelola integrasi dengan berbagai payment provider
 */
class PaymentGateway
{
    /**
     * @var array Konfigurasi gateway
     */
    private array $config;

    /**
     * @var array Instance provider yang tersedia
     */
    private array $providers = [];

    /**
     * @var LoggerInterface Logger instance
     */
    private LoggerInterface $logger;

    /**
     * PaymentGateway constructor
     *
     * @param array $config Konfigurasi untuk semua provider
     * @param LoggerInterface|null $logger Logger instance (optional)
     */
    public function __construct(array $config = [], ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();

        $this->initializeProviders();
    }

    /**
     * Inisialisasi provider berdasarkan konfigurasi
     */
    private function initializeProviders(): void
    {
        // Inisialisasi OVO provider
        if (isset($this->config['ovo'])) {
            $this->providers['ovo'] = new OVO($this->config['ovo']);
        }

        // Inisialisasi GoPay provider
        if (isset($this->config['gopay'])) {
            $this->providers['gopay'] = new GoPay($this->config['gopay']);
        }

        $this->logger->info('Payment providers initialized', [
            'available_providers' => array_keys($this->providers)
        ]);
    }

    /**
     * Mendapatkan instance provider
     *
     * @param string $provider Nama provider
     * @return PaymentProviderInterface
     * @throws PaymentException
     */
    private function getProvider(string $provider): PaymentProviderInterface
    {
        $provider = strtolower($provider);

        if (!isset($this->providers[$provider])) {
            throw new PaymentException(
                "Provider '{$provider}' is not configured or available",
                0,
                $provider,
                ['available_providers' => array_keys($this->providers)]
            );
        }

        return $this->providers[$provider];
    }

    /**
     * Login ke provider tertentu
     *
     * @param string $provider Nama provider (ovo/gopay)
     * @param string $phone Nomor telepon
     * @return array Response dari provider
     * @throws PaymentException
     */
    public function login(string $provider, string $phone): array
    {
        try {
            $this->logger->info("Attempting login to {$provider}", [
                'provider' => $provider,
                'phone' => $this->maskPhoneNumber($phone)
            ]);

            $providerInstance = $this->getProvider($provider);
            $result = $providerInstance->login($phone);

            $this->logger->info("Login to {$provider} successful", [
                'provider' => $provider,
                'requires_verification' => $result['requires_verification'] ?? false
            ]);

            return $result;

        } catch (ProviderException $e) {
            $this->logger->error("Login to {$provider} failed", [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'endpoint' => $e->getEndpoint()
            ]);

            throw new PaymentException(
                "Login failed: {$e->getMessage()}",
                $e->getCode(),
                $provider,
                $e->getContext(),
                $e
            );
        }
    }

    /**
     * Verifikasi kode OTP/SMS
     *
     * @param string $provider Nama provider
     * @param string $code Kode verifikasi
     * @return array Response dari provider
     * @throws PaymentException
     */
    public function verifyCode(string $provider, string $code): array
    {
        try {
            $this->logger->info("Verifying code for {$provider}");

            $providerInstance = $this->getProvider($provider);
            $result = $providerInstance->verifyCode($code);

            $this->logger->info("Code verification for {$provider} successful", [
                'provider' => $provider,
                'authenticated' => $result['authenticated'] ?? false
            ]);

            return $result;

        } catch (ProviderException $e) {
            $this->logger->error("Code verification for {$provider} failed", [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            throw new PaymentException(
                "Code verification failed: {$e->getMessage()}",
                $e->getCode(),
                $provider,
                $e->getContext(),
                $e
            );
        }
    }

    /**
     * Mendapatkan saldo dari provider
     *
     * @param string $provider Nama provider
     * @return array Response berisi informasi saldo
     * @throws PaymentException
     */
    public function getBalance(string $provider): array
    {
        try {
            $this->logger->info("Getting balance from {$provider}");

            $providerInstance = $this->getProvider($provider);
            $result = $providerInstance->getBalance();

            $this->logger->info("Balance retrieved from {$provider}", [
                'provider' => $provider,
                'balance' => $result['balance'] ?? 0
            ]);

            return $result;

        } catch (ProviderException $e) {
            $this->logger->error("Failed to get balance from {$provider}", [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            throw new PaymentException(
                "Failed to get balance: {$e->getMessage()}",
                $e->getCode(),
                $provider,
                $e->getContext(),
                $e
            );
        }
    }

    /**
     * Mendapatkan daftar transaksi/mutasi
     *
     * @param string $provider Nama provider
     * @param int $limit Jumlah maksimal transaksi
     * @return array Response berisi daftar transaksi
     * @throws PaymentException
     */
    public function getTransactions(string $provider, int $limit = 10): array
    {
        try {
            $this->logger->info("Getting transactions from {$provider}", [
                'provider' => $provider,
                'limit' => $limit
            ]);

            $providerInstance = $this->getProvider($provider);
            $result = $providerInstance->getTransactions($limit);

            $this->logger->info("Transactions retrieved from {$provider}", [
                'provider' => $provider,
                'transaction_count' => count($result['transactions'] ?? [])
            ]);

            return $result;

        } catch (ProviderException $e) {
            $this->logger->error("Failed to get transactions from {$provider}", [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            throw new PaymentException(
                "Failed to get transactions: {$e->getMessage()}",
                $e->getCode(),
                $provider,
                $e->getContext(),
                $e
            );
        }
    }

    /**
     * Mengecek apakah provider sudah terautentikasi
     *
     * @param string $provider Nama provider
     * @return bool Status autentikasi
     * @throws PaymentException
     */
    public function isAuthenticated(string $provider): bool
    {
        $providerInstance = $this->getProvider($provider);
        return $providerInstance->isAuthenticated();
    }

    /**
     * Logout dari provider
     *
     * @param string $provider Nama provider
     * @return bool Status logout
     * @throws PaymentException
     */
    public function logout(string $provider): bool
    {
        try {
            $this->logger->info("Logging out from {$provider}");

            $providerInstance = $this->getProvider($provider);
            $result = $providerInstance->logout();

            $this->logger->info("Logout from {$provider} successful");

            return $result;

        } catch (ProviderException $e) {
            $this->logger->warning("Logout from {$provider} failed, but continuing", [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Mendapatkan daftar provider yang tersedia
     *
     * @return array List of available providers
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Mask nomor telepon untuk logging
     *
     * @param string $phone Nomor telepon
     * @return string Nomor telepon yang sudah di-mask
     */
    private function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return $phone;
        }

        return substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 4) . substr($phone, -2);
    }

    /**
     * Set logger instance
     *
     * @param LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }
}