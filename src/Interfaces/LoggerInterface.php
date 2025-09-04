<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway\Interfaces;

/**
 * Simple Logger Interface
 *
 * Interface sederhana untuk logging, bisa diimplementasikan dengan PSR-3 atau logger custom
 */
interface LoggerInterface
{
    /**
     * Log emergency message
     *
     * @param string $message
     * @param array $context
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Log alert message
     *
     * @param string $message
     * @param array $context
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log notice message
     *
     * @param string $message
     * @param array $context
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Log message with specified level
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log(string $level, string $message, array $context = []): void;
}