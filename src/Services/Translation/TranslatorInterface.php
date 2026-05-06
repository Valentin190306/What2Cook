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
}
