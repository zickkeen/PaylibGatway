<?php

declare(strict_types=1);

namespace zickkeen\PaylibGateway;

use zickkeen\PaylibGateway\Interfaces\LoggerInterface;

/**
 * Null Logger Implementation
 *
 * Logger yang tidak melakukan apa-apa, berguna untuk development atau testing
 */
class NullLogger implements LoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function emergency(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function alert(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function critical(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function error(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function warning(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function notice(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function info(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function debug(string $message, array $context = []): void
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Do nothing
    }
}