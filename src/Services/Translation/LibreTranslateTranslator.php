<?php
declare(strict_types=1);

namespace App\Services\Translation;

use App\Core\Log\LoggerInterface;
use RuntimeException;

class LibreTranslateTranslator implements TranslatorInterface
{
    private string $baseUrl;
    private ?LoggerInterface $logger = null;

    private const array SKIP_KEYS = [
        'image', 'imageType', 'sourceUrl', 'spoonacularSourceUrl',
        'hostedLargeUrl', 'hostedMediumUrl', 'originalImageUrl',
    ];

    private const int BATCH_SIZE = 25;
    private const int TIMEOUT = 60;
    private const int CONNECT_TIMEOUT = 10;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->baseUrl = rtrim($_ENV['LIBRETRANSLATE_URL'] ?? 'http://libretranslate:5000', '/');
    }

    public function translate(string $text, string $targetLanguage = 'es'): string
    {
        $results = $this->translateBatch([$text], $targetLanguage);
        return $results[0] ?? $text;
    }

    public function translateArray(array $data, string $targetLanguage = 'es'): array
    {
        $strings = [];
        $this->collectStrings($data, $strings);

        if (empty($strings)) {
            return $data;
        }

        $translated = $this->translateBatch($strings, $targetLanguage);

        $index = 0;
        return $this->mapTranslations($data, $translated, $index);
    }

    // ── Batch translation ────────────────────────────────────────────────────

    private function translateBatch(array $texts, string $targetLanguage): array
    {
        if (empty($texts)) return [];

        $sourceLanguage = $targetLanguage === 'es' ? 'en' : 'es';
        $chunks = array_chunk($texts, self::BATCH_SIZE);
        $allTranslated = [];

        $ch = curl_init();

        foreach ($chunks as $chunk) {
            $payload = [
                'q' => array_values($chunk),
                'source' => $sourceLanguage,
                'target' => $targetLanguage,
                'format' => 'html',
            ];

            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . '/translate',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            ]);

            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($errno !== 0 || $body === false) {
                curl_close($ch);
                throw new RuntimeException("Error de red al llamar a LibreTranslate: cURL errno {$errno}");
            }

            $response = json_decode((string) $body, true);

            if ($httpCode >= 400) {
                curl_close($ch);
                $message = $response['error'] ?? 'Error desconocido';
                throw new RuntimeException("LibreTranslate respondió {$httpCode}: {$message}");
            }

            $translated = $response['translatedText'] ?? [];
            $allTranslated = array_merge($allTranslated, is_array($translated) ? $translated : [$translated]);
        }

        curl_close($ch);

        return $allTranslated;
    }

    // ── String collection & mapping (preserves array structure) ───────────────

    private function collectStrings(array $data, array &$strings): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $prepared = $this->getTranslatable($key, $value);
                if ($prepared !== null) {
                    $strings[] = $prepared;
                }
            } elseif (is_array($value)) {
                $this->collectStrings($value, $strings);
            }
        }
    }

    private function mapTranslations(array $data, array $translations, int &$index): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                if ($this->getTranslatable($key, $value) !== null) {
                    $result[$key] = $translations[$index] ?? $value;
                    $index++;
                } else {
                    $result[$key] = $value;
                }
            } elseif (is_array($value)) {
                $result[$key] = $this->mapTranslations($value, $translations, $index);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    // ── Heuristics ────────────────────────────────────────────────────────────

    /**
     * Prepara el texto y determina si debe traducirse.
     * Retorna el texto preparado si es traducible, null si no.
     */
    private function getTranslatable(string|int $key, string $value): ?string
    {
        if (is_string($key) && in_array($key, self::SKIP_KEYS, true)) return null;

        $prepared = $this->prepareText($key, $value);

        if (strlen(trim($prepared)) <= 2) return null;
        if (str_contains($prepared, '://')) return null;

        return $prepared;
    }

    private function prepareText(string|int $key, string $value): string
    {
        if (is_string($key) && in_array($key, ['summary', 'instructions'], true)) {
            return html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $value;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $module = (new \ReflectionClass($this))->getShortName();
        $this->logger->log($level, "[{$module}] {$message}", $context);
    }
}
