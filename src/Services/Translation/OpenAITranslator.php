<?php
declare(strict_types=1);

namespace App\Services\Translation;

use RuntimeException;

class OpenAITranslator implements TranslatorInterface
{
    private string $apiKey;
    private const BASE_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $key = $_ENV['OPENAI_API_KEY'] ?? '';
        if ($key === '') {
            throw new RuntimeException('OPENAI_API_KEY no está definida en las variables de entorno.');
        }
        $this->apiKey = $key;
    }

    public function translate(string $text, string $targetLanguage = 'es'): string
    {
        $ch = curl_init();

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a professional translator. Translate the following text to language code: {$targetLanguage}. Return ONLY the translation, no extra text."
                ],
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ],
            'temperature' => 0.3
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => self::BASE_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            throw new RuntimeException("Error de red al llamar a OpenAI: cURL errno {$errno}");
        }

        $response = json_decode((string) $body, true);

        if ($httpCode >= 400) {
            $message = $response['error']['message'] ?? 'Error desconocido';
            throw new RuntimeException("OpenAI respondió {$httpCode}: {$message}");
        }

        $translation = $response['choices'][0]['message']['content'] ?? '';

        return trim($translation);
    }
}
