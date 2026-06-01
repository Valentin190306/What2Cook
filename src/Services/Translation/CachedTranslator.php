<?php
declare(strict_types=1);

namespace App\Services\Translation;

use App\Core\Log\LoggerInterface;

/**
 * Decorador que agrega una capa de caché a cualquier TranslatorInterface.
 *
 * Uso:
 *   $translator = new CachedTranslator(new GeminiTranslator());
 *   $text = $translator->translate('Hello', 'es'); // llama a Gemini solo la primera vez
 *
 * La clave de caché se genera a partir del nombre del translator subyacente,
 * el método ('translate' o 'translateArray'), el idioma destino y el contenido
 * del input — por lo que el mismo texto siempre reutiliza la misma entrada.
 *
 * TTL por defecto: 7 días (604 800 segundos). Pasar null para caché permanente.
 */
class CachedTranslator implements TranslatorInterface
{
    private string $cacheDir;
    private string $providerName;

    /** @param int|null $ttlSeconds null = sin expiración */
    public function __construct(
        private readonly TranslatorInterface $inner,
        private readonly ?int $ttlSeconds = 60 * 60 * 24 * 7,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->cacheDir  = __DIR__ . '/../../../log/cache/translations';
        $this->providerName = (new \ReflectionClass($inner))->getShortName();

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0700, true);
        }

        /** Manejar excepción en caso de error al crear el directorio */
    }

    // ── TranslatorInterface ───────────────────────────────────────────────────

    public function translate(string $text, string $targetLanguage = 'es'): string
    {
        $cacheKey  = $this->buildKey('translate', $targetLanguage, $text);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        $cached = $this->readCache($cacheFile);
        if ($cached !== null && is_string($cached)) {
            return $cached;
        }

        $result = $this->inner->translate($text, $targetLanguage);

        $this->writeCache($cacheFile, $result);

        return $result;
    }

    public function translateArray(array $data, string $targetLanguage = 'es'): array
    {
        $cacheKey  = $this->buildKey('translateArray', $targetLanguage, json_encode($data));
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        $cached = $this->readCache($cacheFile);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $result = $this->inner->translateArray($data, $targetLanguage);

        $this->writeCache($cacheFile, $result);

        return $result;
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Construye una clave única basada en el proveedor, método, idioma y contenido.
     */
    private function buildKey(string $method, string $language, string $content): string
    {
        return md5($this->providerName . '|' . $method . '|' . $language . '|' . $content);
    }

    /**
     * Lee un archivo de caché. Devuelve null si no existe, está expirado o es inválido.
     */
    private function readCache(string $file): string|array|null
    {
        if (!file_exists($file)) {
            return null;
        }

        // Verificar TTL
        if ($this->ttlSeconds !== null && (time() - filemtime($file)) >= $this->ttlSeconds) {
            @unlink($file);
            return null;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // El archivo puede guardar un string envuelto en un objeto {"v":"..."} o un array
        if (is_array($decoded) && array_key_exists('v', $decoded) && is_string($decoded['v'])) {
            return $decoded['v'];
        }

        return $decoded;
    }

    /**
     * Escribe un valor en caché. Los strings se envuelven en {"v":"..."} para
     * distinguirlos de arrays al deserializar.
     */
    private function writeCache(string $file, string|array $value): void
    {
        $payload = is_string($value) ? ['v' => $value] : $value;
        file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
