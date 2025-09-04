<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Providers;

use zickkeen\PaylibGateway\Interfaces\PaymentProviderInterface;
use zickkeen\PaylibGateway\Exceptions\ProviderException;

/**
 * OVO Payment Provider Implementation
 *
 * Implementasi provider untuk integrasi dengan API OVO
 */
class OVO implements PaymentProviderInterface
{
    private const BASE_URL = 'https://api.ovo.id';
    private const TIMEOUT = 30;

    /**
     * @var string Nomor telepon yang digunakan
     */
    private string $phone = '';

    /**
     * @var string Token autentikasi
     */
    private string $authToken = '';

    /**
     * @var string Device ID untuk request
     */
    private string $deviceId = '';

    /**
     * @var bool Status autentikasi
     */
    private bool $isAuthenticated = false;

    /**
     * OVO constructor
     *
     * @param array $config Konfigurasi provider
     */
    public function __construct(array $config = [])
    {
        $this->deviceId = $config['device_id'] ?? $this->generateDeviceId();
    }

    /**
     * Generate device ID untuk request
     *
     * @return string
     */
    private function generateDeviceId(): string
    {
        return 'device_' . bin2hex(random_bytes(8));
    }

    /**
     * Melakukan HTTP request menggunakan cURL
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Data yang akan dikirim
     * @param array $headers HTTP headers
     * @return array Response dari API
     * @throws ProviderException
     */
    private function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        $url = self::BASE_URL . $endpoint;

        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: OVO/1.0',
            'Device-Id: ' . $this->deviceId
        ];

        if (!empty($this->authToken)) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->authToken;
        }

        $headers = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new ProviderException(
                "cURL error: {$error}",
                0,
                'ovo',
                $endpoint,
                [],
                ['curl_error' => $error]
            );
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            throw ProviderException::fromHttpResponse(
                $httpCode,
                'ovo',
                $endpoint,
                $responseData ?? []
            );
        }

        return $responseData ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function login(string $phone): array
    {
        $this->phone = $phone;

        $response = $this->makeRequest('POST', '/auth/login', [
            'phone' => $phone,
            'device_id' => $this->deviceId
        ]);

        if (isset($response['auth_token'])) {
            $this->authToken = $response['auth_token'];
        }

        return [
            'success' => true,
            'message' => 'Login request sent successfully',
            'requires_verification' => true,
            'data' => $response
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function verifyCode(string $code): array
    {
        if (empty($this->phone)) {
            throw new ProviderException(
                'Phone number not set. Please call login() first.',
                0,
                'ovo',
                '/auth/verify'
            );
        }

        $response = $this->makeRequest('POST', '/auth/verify', [
            'phone' => $this->phone,
            'code' => $code,
            'device_id' => $this->deviceId
        ]);

        if (isset($response['auth_token'])) {
            $this->authToken = $response['auth_token'];
            $this->isAuthenticated = true;
        }

        return [
            'success' => true,
            'message' => 'Code verified successfully',
            'authenticated' => $this->isAuthenticated,
            'data' => $response
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getBalance(): array
    {
        $this->ensureAuthenticated();

        $response = $this->makeRequest('GET', '/wallet/balance');

        return [
            'success' => true,
            'balance' => $response['balance'] ?? 0,
            'currency' => $response['currency'] ?? 'IDR',
            'data' => $response
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactions(int $limit = 10): array
    {
        $this->ensureAuthenticated();

        $response = $this->makeRequest('GET', '/transaction/history', [
            'limit' => $limit
        ]);

        return [
            'success' => true,
            'transactions' => $response['transactions'] ?? [],
            'total' => $response['total'] ?? 0,
            'data' => $response
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->isAuthenticated && !empty($this->authToken);
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): bool
    {
        if (!$this->isAuthenticated()) {
            return true;
        }

        try {
            $this->makeRequest('POST', '/auth/logout');
        } catch (ProviderException $e) {
            // Logout mungkin gagal, tapi kita tetap bersihkan state lokal
        }

        $this->authToken = '';
        $this->isAuthenticated = false;

        return true;
    }

    /**
     * Memastikan user sudah terautentikasi
     *
     * @throws ProviderException
     */
    private function ensureAuthenticated(): void
    {
        if (!$this->isAuthenticated()) {
            throw new ProviderException(
                'User not authenticated. Please login and verify code first.',
                0,
                'ovo'
            );
        }
    }

    /**
     * Konfirmasi security code untuk transaksi
     *
     * @param string $securityCode Kode keamanan
     * @return array Response dari API
     * @throws ProviderException
     */
    public function confirmSecurityCode(string $securityCode): array
    {
        $this->ensureAuthenticated();

        $response = $this->makeRequest('POST', '/auth/confirm-security', [
            'security_code' => $securityCode
        ]);

        return [
            'success' => true,
            'message' => 'Security code confirmed',
            'data' => $response
        ];
    }
}