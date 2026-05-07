<?php
declare(strict_types=1);

namespace App\Services\Translation;

interface TranslatorInterface
{
    /**
     * Translates the given text to the target language.
     *
     * @param string $text The text to translate.
     * @param string $targetLanguage The target language (e.g., 'es' for Spanish).
     * @return string The translated text.
     */
    public function translate(string $text, string $targetLanguage = 'es'): string;

    /**
     * Translates string values in an array (or deep array) to the target language.
     *
     * @param array $data The data to translate.
     * @param string $targetLanguage The target language (e.g., 'es' for Spanish).
     * @return array The translated data.
     */
    public function translateArray(array $data, string $targetLanguage = 'es'): array;
}
