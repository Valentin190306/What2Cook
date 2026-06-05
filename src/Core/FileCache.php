<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Log\LoggerInterface;

class FileCache
{
    private string $cacheDir;

    /** @param int|null $ttlSeconds null = sin expiración */
    public function __construct(
        string $cacheDir,
        private readonly ?int $ttlSeconds = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->cacheDir = rtrim($cacheDir, '/');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0700, true);
        }
    }

    public function get(string $key): mixed
    {
        $file = $this->path($key);

        if (!file_exists($file)) {
            return null;
        }

        if ($this->ttlSeconds !== null && (time() - filemtime($file)) >= $this->ttlSeconds) {
            @unlink($file);
            return null;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    public function set(string $key, mixed $value): void
    {
        $file = $this->path($key);
        file_put_contents($file, json_encode($value, JSON_UNESCAPED_UNICODE));
    }

    public function has(string $key): bool
    {
        $file = $this->path($key);

        if (!file_exists($file)) {
            return false;
        }

        if ($this->ttlSeconds !== null && (time() - filemtime($file)) >= $this->ttlSeconds) {
            @unlink($file);
            return false;
        }

        return true;
    }

    public function delete(string $key): void
    {
        $file = $this->path($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    private function path(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.json';
    }
}
