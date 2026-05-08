<?php
declare(strict_types=1);

namespace App\Services\Translation;

use RuntimeException;

class GeminiTranslator implements TranslatorInterface
{
    private string $apiKey;
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';

    public function __construct()
    {
        $key = $_ENV['GEMINI_API_KEY'] ?? '';
        if ($key === '') {
            throw new RuntimeException('GEMINI_API_KEY no está definida en las variables de entorno.');
        }
        $this->apiKey = $key;
    }

    public function translate(string $text, string $targetLanguage = 'es'): string
    {
        $ch = curl_init();
        $url = self::BASE_URL . '?key=' . $this->apiKey;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => "You are a professional translator. Translate the following text to language code: {$targetLanguage}. Return ONLY the translation, no extra text.\n\nText:\n{$text}"
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3
            ]
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            throw new RuntimeException("Error de red al llamar a Gemini: cURL errno {$errno}");
        }

        $response = json_decode((string) $body, true);

        if ($httpCode >= 400) {
            $message = $response['error']['message'] ?? 'Error desconocido';
            throw new RuntimeException("Gemini respondió {$httpCode}: {$message}");
        }

        $translation = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return trim($translation);
    }

    public function translateArray(array $data, string $targetLanguage = 'es'): array
    {
        $ch = curl_init();
        $url = self::BASE_URL . '?key=' . $this->apiKey;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => "You are a professional translator. Translate all textual values in the following JSON to language code: {$targetLanguage}. Preserve all keys and structural elements exactly. Return valid JSON only, without markdown formatting blocks.\n\nJSON:\n" . json_encode($data)
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'responseMimeType' => 'application/json'
            ]
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            throw new RuntimeException("Error de red al llamar a Gemini: cURL errno {$errno}");
        }

        $response = json_decode((string) $body, true);

        if ($httpCode >= 400) {
            $message = $response['error']['message'] ?? 'Error desconocido';
            throw new RuntimeException("Gemini respondió {$httpCode}: {$message}");
        }

        $translationString = $response['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        
        $translatedData = json_decode($translationString, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($translatedData)) {
            return $data;
        }

        return $translatedData;
    }
}
