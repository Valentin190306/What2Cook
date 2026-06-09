<?php
declare(strict_types=1);

namespace App\Services\Translation;

use App\Core\Log\LoggerInterface;
use RuntimeException;

class LibreTranslateTranslator implements TranslatorInterface
{
    private string $baseUrl;
    private ?LoggerInterface $logger = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->baseUrl = rtrim($_ENV['LIBRETRANSLATE_URL'] ?? 'http://libretranslate:5000', '/');
    }

    public function translate(string $text, string $targetLanguage = 'es'): string
    {
        $sourceLanguage = $targetLanguage === 'es' ? 'en' : 'es';

        $ch = curl_init();
        $url = $this->baseUrl . '/translate';

        $payload = [
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            throw new RuntimeException("Error de red al llamar a LibreTranslate: cURL errno {$errno}");
        }

        $response = json_decode((string) $body, true);

        if ($httpCode >= 400) {
            $message = $response['error'] ?? 'Error desconocido';
            throw new RuntimeException("LibreTranslate respondió {$httpCode}: {$message}");
        }

        return $response['translatedText'] ?? $text;
    }

    public function translateArray(array $data, string $targetLanguage = 'es'): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $result[$key] = $this->translate($value, $targetLanguage);
            } elseif (is_array($value)) {
                $result[$key] = $this->translateArray($value, $targetLanguage);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $module = (new \ReflectionClass($this))->getShortName();
        $this->logger->log($level, "[{$module}] {$message}", $context);
    }
}
