<?php

namespace App\Core\Log;

use Psr\Log\LogLevel;

/**
 * Class AbstractLogger
 * 
 * Clase base que implementa los métodos de LoggerInterface.
 */
abstract class AbstractLogger implements LoggerInterface
{
    public function emergency(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context, $httpCode);
    }

    public function alert(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::ALERT, $message, $context, $httpCode);
    }

    public function critical(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context, $httpCode);
    }

    public function error(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::ERROR, $message, $context, $httpCode);
    }

    public function warning(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::WARNING, $message, $context, $httpCode);
    }

    public function notice(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::NOTICE, $message, $context, $httpCode);
    }

    public function info(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::INFO, $message, $context, $httpCode);
    }

    public function debug(string|\Stringable $message, array $context = [], ?int $httpCode = null): void
    {
        $this->log(LogLevel::DEBUG, $message, $context, $httpCode);
    }
}
