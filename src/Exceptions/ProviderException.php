<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Exceptions;

/**
 * Exception khusus untuk error yang berasal dari provider
 */
class ProviderException extends PaymentException
{
    /**
     * @var string Endpoint yang menyebabkan error
     */
    protected string $endpoint = '';

    /**
     * @var array Response dari provider
     */
    protected array $response = [];

    /**
     * ProviderException constructor
     *
     * @param string $message Pesan error
     * @param int $code Kode error
     * @param string $provider Nama provider
     * @param string $endpoint Endpoint yang error
     * @param array $response Response dari provider
     * @param array $context Data tambahan
     * @param \Throwable|null $previous Exception sebelumnya
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        string $provider = '',
        string $endpoint = '',
        array $response = [],
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $provider, $context, $previous);
        $this->endpoint = $endpoint;
        $this->response = $response;
    }

    /**
     * Mendapatkan endpoint yang error
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Mendapatkan response dari provider
     *
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Membuat ProviderException dari HTTP response
     *
     * @param int $httpCode Kode HTTP
     * @param string $provider Nama provider
     * @param string $endpoint Endpoint
     * @param array $response Response dari provider
     * @return self
     */
    public static function fromHttpResponse(
        int $httpCode,
        string $provider,
        string $endpoint,
        array $response = []
    ): self {
        $message = "HTTP {$httpCode} error from {$provider}";

        if (isset($response['message'])) {
            $message .= ": {$response['message']}";
        }

        return new self(
            $message,
            $httpCode,
            $provider,
            $endpoint,
            $response,
            ['http_code' => $httpCode]
        );
    }
}