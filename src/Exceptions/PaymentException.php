<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Exceptions;

/**
 * Exception dasar untuk payment gateway
 */
class PaymentException extends \Exception
{
    /**
     * @var array Data tambahan terkait error
     */
    protected array $context = [];

    /**
     * @var string Provider yang menyebabkan error
     */
    protected string $provider = '';

    /**
     * PaymentException constructor
     *
     * @param string $message Pesan error
     * @param int $code Kode error
     * @param string $provider Nama provider
     * @param array $context Data tambahan
     * @param \Throwable|null $previous Exception sebelumnya
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        string $provider = '',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->provider = $provider;
        $this->context = $context;
    }

    /**
     * Mendapatkan nama provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Mendapatkan context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Menambahkan data ke context
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
}