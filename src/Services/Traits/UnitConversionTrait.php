<?php
declare(strict_types=1);

namespace App\Services\Traits;

trait UnitConversionTrait
{
    private const CONVERSION_TABLE = [
        // Base unit: grams (g)
        'g' => 1.0,
        'gram' => 1.0,
        'grams' => 1.0,
        'kg' => 1000.0,
        'kilogram' => 1000.0,
        'kilograms' => 1000.0,
        'mg' => 0.001,
        'milligram' => 0.001,
        'milligrams' => 0.001,
        
        // Base unit: milliliters (ml)
        'ml' => 1.0,
        'milliliter' => 1.0,
        'milliliters' => 1.0,
        'l' => 1000.0,
        'liter' => 1000.0,
        'liters' => 1000.0,
        'cl' => 10.0,
        'centiliter' => 10.0,
        'centiliters' => 10.0,
        
        // Volume (US customary)
        'tsp' => 4.92892, // teaspoon
        'teaspoon' => 4.92892,
        'teaspoons' => 4.92892,
        'tbsp' => 14.7868, // tablespoon
        'tablespoon' => 14.7868,
        'tablespoons' => 14.7868,
        'fl oz' => 29.5735, // fluid ounce
        'fluid ounce' => 29.5735,
        'fluid ounces' => 29.5735,
        'cup' => 236.588, // US cup
        'cups' => 236.588,
        'pint' => 473.176,
        'pints' => 473.176,
        'quart' => 946.353,
        'quarts' => 946.353,
        'gallon' => 3785.41,
        'gallons' => 3785.41,
        
        // Weight (US customary)
        'oz' => 28.3495, // ounce
        'ounce' => 28.3495,
        'ounces' => 28.3495,
        'lb' => 453.592, // pound
        'pound' => 453.592,
        'pounds' => 453.592,
        
        // Other common units
        'pinch' => 0.3, // approximate
        'dash' => 0.6, // approximate
        'drop' => 0.05, // approximate
    ];

    private const UNIT_SYSTEMS = [
        'metric' => ['g', 'kg', 'ml', 'l', 'mg', 'cl'],
        'imperial' => ['oz', 'lb', 'fl oz', 'pint', 'quart', 'gallon'],
        'us' => ['tsp', 'tbsp', 'cup', 'fl oz', 'oz', 'lb'],
    ];

    /**
     * Convierte una cantidad de una unidad a gramos (para peso) o mililitros (para volumen).
     * 
     * @param float $amount Cantidad a convertir
     * @param string $fromUnit Unidad de origen
     * @return float Cantidad en gramos o mililitros
     */
    private function convertToBase(float $amount, string $fromUnit): float
    {
        $normalizedUnit = strtolower(trim($fromUnit));
        $conversionFactor = self::CONVERSION_TABLE[$normalizedUnit] ?? 1.0;
        return $amount * $conversionFactor;
    }

    /**
     * Convierte desde gramos/mililitros a una unidad específica.
     * 
     * @param float $baseAmount Cantidad en gramos o mililitros
     * @param string $toUnit Unidad de destino
     * @return float Cantidad convertida
     */
    private function convertFromBase(float $baseAmount, string $toUnit): float
    {
        $normalizedUnit = strtolower(trim($toUnit));
        $conversionFactor = self::CONVERSION_TABLE[$normalizedUnit] ?? 1.0;
        
        if ($conversionFactor === 0) {
            return $baseAmount;
        }
        
        return $baseAmount / $conversionFactor;
    }

    /**
     * Convierte una cantidad entre dos unidades arbitrarias.
     * 
     * @param float $amount Cantidad a convertir
     * @param string $fromUnit Unidad de origen
     * @param string $toUnit Unidad de destino
     * @return float Cantidad convertida
     */
    public function convertUnit(float $amount, string $fromUnit, string $toUnit): float
    {
        $baseAmount = $this->convertToBase($amount, $fromUnit);
        return $this->convertFromBase($baseAmount, $toUnit);
    }

    /**
     * Convierte una cantidad al sistema métrico (gramos o mililitros).
     * 
     * @param float $amount Cantidad a convertir
     * @param string $fromUnit Unidad de origen
     * @param bool $isVolume true si es volumen, false si es peso
     * @return array{amount: float, unit: string}
     */
    public function convertToMetric(float $amount, string $fromUnit, bool $isVolume = false): array
    {
        $baseAmount = $this->convertToBase($amount, $fromUnit);
        
        if ($isVolume) {
            // Convertir a la unidad métrica más apropiada
            if ($baseAmount >= 1000) {
                return [
                    'amount' => round($baseAmount / 1000, 2),
                    'unit' => 'l'
                ];
            } elseif ($baseAmount >= 100) {
                return [
                    'amount' => round($baseAmount / 100, 2),
                    'unit' => 'cl'
                ];
            } else {
                return [
                    'amount' => round($baseAmount, 2),
                    'unit' => 'ml'
                ];
            }
        } else {
            // Convertir a la unidad métrica más apropiada
            if ($baseAmount >= 1000) {
                return [
                    'amount' => round($baseAmount / 1000, 2),
                    'unit' => 'kg'
                ];
            } else {
                return [
                    'amount' => round($baseAmount, 2),
                    'unit' => 'g'
                ];
            }
        }
    }

    /**
     * Convierte una cantidad al sistema imperial (onzas, libras, etc.).
     * 
     * @param float $amount Cantidad a convertir
     * @param string $fromUnit Unidad de origen
     * @param bool $isVolume true si es volumen, false si es peso
     * @return array{amount: float, unit: string}
     */
    public function convertToImperial(float $amount, string $fromUnit, bool $isVolume = false): array
    {
        $baseAmount = $this->convertToBase($amount, $fromUnit);
        
        if ($isVolume) {
            // Convertir a la unidad imperial más apropiada
            if ($baseAmount >= 3785.41) {
                return [
                    'amount' => round($baseAmount / 3785.41, 2),
                    'unit' => 'gallon'
                ];
            } elseif ($baseAmount >= 946.353) {
                return [
                    'amount' => round($baseAmount / 946.353, 2),
                    'unit' => 'quart'
                ];
            } elseif ($baseAmount >= 473.176) {
                return [
                    'amount' => round($baseAmount / 473.176, 2),
                    'unit' => 'pint'
                ];
            } elseif ($baseAmount >= 29.5735) {
                return [
                    'amount' => round($baseAmount / 29.5735, 2),
                    'unit' => 'fl oz'
                ];
            } else {
                return [
                    'amount' => round($baseAmount / 4.92892, 2),
                    'unit' => 'tsp'
                ];
            }
        } else {
            // Convertir a la unidad imperial más apropiada
            if ($baseAmount >= 453.592) {
                return [
                    'amount' => round($baseAmount / 453.592, 2),
                    'unit' => 'lb'
                ];
            } else {
                return [
                    'amount' => round($baseAmount / 28.3495, 2),
                    'unit' => 'oz'
                ];
            }
        }
    }

    /**
     * Convierte una cantidad al sistema US (tazas, cucharadas, etc.).
     * 
     * @param float $amount Cantidad a convertir
     * @param string $fromUnit Unidad de origen
     * @param bool $isVolume true si es volumen, false si es peso
     * @return array{amount: float, unit: string}
     */
    public function convertToUS(float $amount, string $fromUnit, bool $isVolume = false): array
    {
        $baseAmount = $this->convertToBase($amount, $fromUnit);
        
        if ($isVolume) {
            // Convertir a la unidad US más apropiada
            if ($baseAmount >= 236.588) {
                return [
                    'amount' => round($baseAmount / 236.588, 2),
                    'unit' => 'cup'
                ];
            } elseif ($baseAmount >= 14.7868) {
                return [
                    'amount' => round($baseAmount / 14.7868, 2),
                    'unit' => 'tbsp'
                ];
            } else {
                return [
                    'amount' => round($baseAmount / 4.92892, 2),
                    'unit' => 'tsp'
                ];
            }
        } else {
            // Para peso, US usa lo mismo que imperial
            return $this->convertToImperial($amount, $fromUnit, false);
        }
    }

    /**
     * Determina si una unidad es de volumen o peso.
     * 
     * @param string $unit Unidad a verificar
     * @return bool true si es volumen, false si es peso
     */
    public function isVolumeUnit(string $unit): bool
    {
        $volumeUnits = ['ml', 'l', 'cl', 'tsp', 'tbsp', 'fl oz', 'cup', 'pint', 'quart', 'gallon', 'drop', 'dash', 'pinch'];
        $normalizedUnit = strtolower(trim($unit));
        
        foreach ($volumeUnits as $vu) {
            if (str_starts_with($normalizedUnit, $vu) || str_contains($normalizedUnit, $vu)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Normaliza el nombre de una unidad a su forma estándar.
     * 
     * @param string $unit Unidad a normalizar
     * @return string Unidad normalizada
     */
    public function normalizeUnit(string $unit): string
    {
        $normalizedUnit = strtolower(trim($unit));
        
        // Mapeo de variaciones comunes
        $unitMap = [
            'g' => 'g',
            'gr' => 'g',
            'grm' => 'g',
            'kg' => 'kg',
            'kilo' => 'kg',
            'ml' => 'ml',
            'l' => 'l',
            'lt' => 'l',
            'tsp' => 'tsp',
            'tbsp' => 'tbsp',
            'tbl' => 'tbsp',
            'oz' => 'oz',
            'lb' => 'lb',
            'cup' => 'cup',
            'cups' => 'cup',
        ];
        
        return $unitMap[$normalizedUnit] ?? $normalizedUnit;
    }
}
