<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    public static function string(mixed $value, int $min = 1, int $max = PHP_INT_MAX): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            return null;
        }
        return $value;
    }

    public static function email(mixed $value): ?string
    {
        $value = self::string($value);
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function password(mixed $value, int $min = 8, int $max = 255): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            return null;
        }
        return $value;
    }

    public static function inList(mixed $value, array $allowed): ?string
    {
        $value = self::string($value);
        if ($value === null) {
            return null;
        }
        return in_array($value, $allowed, true) ? $value : null;
    }

    public static function stringArray(mixed $value, ?array $allowed = null, int $maxCount = 20): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            if ($allowed !== null && !in_array($item, $allowed, true)) {
                continue;
            }
            $result[] = $item;
            if (count($result) >= $maxCount) {
                break;
            }
        }

        return $result;
    }

    public static function integer(mixed $value, int $min = 0, int $max = PHP_INT_MAX): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        $value = (int) $value;
        if ($value < $min || $value > $max) {
            return null;
        }
        return $value;
    }

    public static function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
