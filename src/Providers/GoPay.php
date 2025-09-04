<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Providers;

use zickkeen\PaylibGateway\Interfaces\PaymentProviderInterface;
use zickkeen\PaylibGateway\Exceptions\ProviderException;

/**
 * GoPay Payment Provider Implementation
 *
 * Implementasi provider untuk integrasi dengan API GoPay
 */
class GoPay implements PaymentProviderInterface
{
    private const BASE_URL = 'https://api.gopay.co.id';
    private const TIMEOUT = 30;

    /**
     * @var string Nomor telepon yang digunakan
     */
    private string $phone = '';

    /**
     * @var string Password untuk login
     */
    private string $password = '';

    /**
     * @var string Token autentikasi
     */
    private string $authToken = '';

    /**
     * @var string Session ID untuk request
     */
    private string $sessionId = '';

    /**
     * @var bool Status autentikasi
     */
    private bool $isAuthenticated = false;

    /**
     * GoPay constructor
     *
     * @param array $config Konfigurasi provider
     */
    public function __construct(array $config = [])
    {
        if (isset($config['phone'])) {
            $this->phone = $config['phone'];
        }
        if (isset($config['password'])) {
            $this->password = $config['password'];
        }
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
            'User-Agent: GoPay/1.0'
        ];

        if (!empty($this->authToken)) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->authToken;
        }

        if (!empty($this->sessionId)) {
            $defaultHeaders[] = 'Session-Id: ' . $this->sessionId;
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
                'gopay',
                $endpoint,
                [],
                ['curl_error' => $error]
            );
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400) {
            throw ProviderException::fromHttpResponse(
                $httpCode,
                'gopay',
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

        if (empty($this->password)) {
            throw new ProviderException(
                'Password not set. Please provide password in config.',
                0,
                'gopay',
                '/auth/login'
            );
        }

        $response = $this->makeRequest('POST', '/auth/login', [
            'phone' => $phone,
            'password' => $this->password
        ]);

        if (isset($response['session_id'])) {
            $this->sessionId = $response['session_id'];
        }

        return [
            'success' => true,
            'message' => 'Login request sent successfully',
            'requires_verification' => isset($response['requires_otp']) ? $response['requires_otp'] : false,
            'data' => $response
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function verifyCode(string $code): array
    {
        if (empty($this->sessionId)) {
            throw new ProviderException(
                'Session ID not set. Please call login() first.',
                0,
                'gopay',
                '/auth/verify'
            );
        }

        $response = $this->makeRequest('POST', '/auth/verify-otp', [
            'session_id' => $this->sessionId,
            'otp' => $code
        ]);

        if (isset($response['auth_token'])) {
            $this->authToken = $response['auth_token'];
            $this->isAuthenticated = true;
        }

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
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
            'limit' => $limit,
            'offset' => 0
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
        $this->sessionId = '';
        $this->isAuthenticated = false;

        return true;
    }

    /**
     * Send payment request
     *
     * @param string $recipient Penerima pembayaran
     * @param int $amount Jumlah yang akan dikirim
     * @param string $description Deskripsi pembayaran
     * @return array Response dari API
     * @throws ProviderException
     */
    public function sendPayment(string $recipient, int $amount, string $description = ''): array
    {
        $this->ensureAuthenticated();

        $response = $this->makeRequest('POST', '/payment/send', [
            'recipient' => $recipient,
            'amount' => $amount,
            'description' => $description,
            'timestamp' => time()
        ]);

        return [
            'success' => true,
            'message' => 'Payment sent successfully',
            'transaction_id' => $response['transaction_id'] ?? null,
            'data' => $response
        ];
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
                'User not authenticated. Please login and verify OTP first.',
                0,
                'gopay'
            );
        }
    }

    /**
     * Set password untuk login
     *
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set phone number
     *
     * @param string $phone
     * @return self
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
}