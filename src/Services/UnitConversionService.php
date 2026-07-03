<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Traits\UnitConversionTrait;

class UnitConversionService
{
    use UnitConversionTrait;

    public function convertToSystem(float $amount, string $fromUnit, string $targetSystem): array
    {
        $targetSystem = strtolower(trim($targetSystem));
        $isVolume = $this->isVolumeUnit($fromUnit);

        return match ($targetSystem) {
            'metric' => $this->convertToMetric($amount, $fromUnit, $isVolume),
            'imperial' => $this->convertToImperial($amount, $fromUnit, $isVolume),
            'us' => $this->convertToUS($amount, $fromUnit, $isVolume),
            default => ['amount' => $amount, 'unit' => $fromUnit]
        };
    }

    public function convertUsingSpoonacularMeasures(array $ingredient, string $targetSystem): array
    {
        $measures = $ingredient['measures'] ?? [];

        if ($targetSystem === 'metric' && !empty($measures['metric']['amount'])) {
            return [
                'amount' => round((float) $measures['metric']['amount'], 2),
                'unit' => $measures['metric']['unitShort'] ?? $measures['metric']['unitLong'] ?? 'g'
            ];
        }

        if ($targetSystem === 'us' && !empty($measures['us']['amount'])) {
            return [
                'amount' => round((float) $measures['us']['amount'], 2),
                'unit' => $measures['us']['unitShort'] ?? $measures['us']['unitLong'] ?? ''
            ];
        }

        $amount = (float) ($ingredient['amount'] ?? 0);
        $unit = $ingredient['unit'] ?? '';

        return $this->convertToSystem($amount, $unit, $targetSystem);
    }
}
