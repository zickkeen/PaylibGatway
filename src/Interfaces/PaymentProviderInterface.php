<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Interfaces;

/**
 * Interface untuk payment provider
 *
 * Interface ini mendefinisikan kontrak yang harus diimplementasikan
 * oleh semua payment provider (GoPay, OVO, dll)
 */
interface PaymentProviderInterface
{
    /**
     * Melakukan login ke provider
     *
     * @param string $phone Nomor telepon yang akan digunakan untuk login
     * @return array Response dari provider
     * @throws \zickkeen\PaylibGateway\Exceptions\ProviderException
     */
    public function login(string $phone): array;

    /**
     * Verifikasi kode OTP/SMS
     *
     * @param string $code Kode verifikasi yang diterima
     * @return array Response dari provider
     * @throws \zickkeen\PaylibGateway\Exceptions\ProviderException
     */
    public function verifyCode(string $code): array;

    /**
     * Mendapatkan saldo rekening
     *
     * @return array Response berisi informasi saldo
     * @throws \zickkeen\PaylibGateway\Exceptions\ProviderException
     */
    public function getBalance(): array;

    /**
     * Mendapatkan daftar transaksi/mutasi
     *
     * @param int $limit Jumlah maksimal transaksi yang akan diambil
     * @return array Response berisi daftar transaksi
     * @throws \zickkeen\PaylibGateway\Exceptions\ProviderException
     */
    public function getTransactions(int $limit = 10): array;

    /**
     * Mengecek apakah provider sudah terautentikasi
     *
     * @return bool Status autentikasi
     */
    public function isAuthenticated(): bool;

    /**
     * Logout dari provider
     *
     * @return bool Status logout
     */
    public function logout(): bool;
}