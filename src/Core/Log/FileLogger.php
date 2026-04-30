<?php

namespace App\Core\Log;

/**
 * Class FileLogger
 * 
 * Un loogger basado en las especificaciones de PSR-3 que permite 
 * guardar los logs en un archivo.
 */
class FileLogger extends AbstractLogger
{
    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = $logPath;
        
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * Registra un mensaje con un nivel arbitrario.
     *
     * @param mixed  $level
     * @param string|\Stringable $message
     * @param array  $context
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $interpolatedMessage = $this->interpolate((string)$message, $context);
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = sprintf("[%s] %s: %s %s" . PHP_EOL, $timestamp, strtoupper($level), $interpolatedMessage, !empty($context) ? json_encode($context) : '');

        file_put_contents($this->logPath, $formattedMessage, FILE_APPEND);
    }

    /**
     * Interpola los valores del contexto en los placeholders del mensaje.
     * 
     * Ejemplo: mensaje "User {username} logged in" con contexto ['username' => 'jdoe']
     * se convierte en "User jdoe logged in".
     */
    private function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }
}
