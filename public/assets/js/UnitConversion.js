const UnitConversion = (function () {
    const CONVERSION_TABLE = {
        'g': 1.0, 'gram': 1.0, 'grams': 1.0,
        'kg': 1000.0, 'kilogram': 1000.0, 'kilograms': 1000.0,
        'mg': 0.001, 'milligram': 0.001, 'milligrams': 0.001,
        'ml': 1.0, 'milliliter': 1.0, 'milliliters': 1.0,
        'l': 1000.0, 'liter': 1000.0, 'liters': 1000.0,
        'cl': 10.0, 'centiliter': 10.0, 'centiliters': 10.0,
        'tsp': 4.92892, 'teaspoon': 4.92892, 'teaspoons': 4.92892,
        'tbsp': 14.7868, 'tablespoon': 14.7868, 'tablespoons': 14.7868,
        'fl oz': 29.5735, 'fluid ounce': 29.5735, 'fluid ounces': 29.5735,
        'cup': 236.588, 'cups': 236.588,
        'pint': 473.176, 'pints': 473.176,
        'quart': 946.353, 'quarts': 946.353,
        'gallon': 3785.41, 'gallons': 3785.41,
        'oz': 28.3495, 'ounce': 28.3495, 'ounces': 28.3495,
        'lb': 453.592, 'pound': 453.592, 'pounds': 453.592,
        'pinch': 0.3, 'dash': 0.6, 'drop': 0.05
    };

    const VOLUME_UNITS = ['ml', 'l', 'cl', 'tsp', 'tbsp', 'fl oz', 'cup', 'pint', 'quart', 'gallon', 'drop', 'dash', 'pinch'];

    function normalizeUnit(unit) {
        return (unit || '').toLowerCase().trim();
    }

    function convertToBase(amount, unit) {
        var normalizedUnit = normalizeUnit(unit);
        if (CONVERSION_TABLE.hasOwnProperty(normalizedUnit)) {
            return amount * CONVERSION_TABLE[normalizedUnit];
        }
        var fuzzy = Object.keys(CONVERSION_TABLE).find(function (key) {
            return normalizedUnit.includes(key) || key.includes(normalizedUnit);
        });
        if (fuzzy) {
            return amount * CONVERSION_TABLE[fuzzy];
        }
        return amount;
    }

    function convertFromBase(baseAmount, unit) {
        var normalizedUnit = normalizeUnit(unit);
        if (CONVERSION_TABLE.hasOwnProperty(normalizedUnit)) {
            var factor = CONVERSION_TABLE[normalizedUnit];
            return factor === 0 ? baseAmount : baseAmount / factor;
        }
        var fuzzy = Object.keys(CONVERSION_TABLE).find(function (key) {
            return normalizedUnit.includes(key) || key.includes(normalizedUnit);
        });
        if (fuzzy) {
            var factor = CONVERSION_TABLE[fuzzy];
            return factor === 0 ? baseAmount : baseAmount / factor;
        }
        return baseAmount;
    }

    function convertUnit(amount, fromUnit, toUnit) {
        var baseAmount = convertToBase(amount, fromUnit);
        return convertFromBase(baseAmount, toUnit);
    }

    function isVolumeUnit(unit) {
        var normalizedUnit = normalizeUnit(unit);
        return VOLUME_UNITS.some(function (vu) { return normalizedUnit.includes(vu); });
    }

    function getBestUnit(baseAmount, targetSystem, isVolume) {
        if (targetSystem === 'metric') {
            if (isVolume) {
                if (baseAmount >= 1000) return { amount: baseAmount / 1000, unit: 'l' };
                if (baseAmount >= 100) return { amount: baseAmount / 100, unit: 'cl' };
                return { amount: baseAmount, unit: 'ml' };
            } else {
                if (baseAmount >= 1000) return { amount: baseAmount / 1000, unit: 'kg' };
                return { amount: baseAmount, unit: 'g' };
            }
        } else if (targetSystem === 'imperial') {
            if (isVolume) {
                if (baseAmount >= 3785.41) return { amount: baseAmount / 3785.41, unit: 'gallon' };
                if (baseAmount >= 946.353) return { amount: baseAmount / 946.353, unit: 'quart' };
                if (baseAmount >= 473.176) return { amount: baseAmount / 473.176, unit: 'pint' };
                if (baseAmount >= 29.5735) return { amount: baseAmount / 29.5735, unit: 'fl oz' };
                return { amount: baseAmount / 4.92892, unit: 'tsp' };
            } else {
                if (baseAmount >= 453.592) return { amount: baseAmount / 453.592, unit: 'lb' };
                return { amount: baseAmount / 28.3495, unit: 'oz' };
            }
        } else if (targetSystem === 'us') {
            if (isVolume) {
                if (baseAmount >= 236.588) return { amount: baseAmount / 236.588, unit: 'cup' };
                if (baseAmount >= 14.7868) return { amount: baseAmount / 14.7868, unit: 'tbsp' };
                return { amount: baseAmount / 4.92892, unit: 'tsp' };
            } else {
                if (baseAmount >= 453.592) return { amount: baseAmount / 453.592, unit: 'lb' };
                return { amount: baseAmount / 28.3495, unit: 'oz' };
            }
        }
        return { amount: baseAmount, unit: 'g' };
    }

    function convertAmount(amount, unit, targetSystem) {
        if (!amount || !unit) {
            return { amount: amount || 0, unit: unit || '' };
        }
        var vol = isVolumeUnit(unit);
        var baseAmount = convertToBase(amount, unit);
        return getBestUnit(baseAmount, targetSystem, vol);
    }

    function roundValue(val) {
        return Math.round(val * 100) / 100;
    }

    function convertNutrition(nutrients, targetSystem) {
        if (targetSystem === 'metric') return nutrients;

        var weightKeys = ['Protein', 'Carbohydrates', 'Fat', 'Sugar', 'Fiber', 'Saturated Fat', 'Trans Fat', 'Cholesterol', 'Sodium'];
        var converted = {};
        Object.keys(nutrients).forEach(function (key) {
            var val = nutrients[key];
            if (typeof val !== 'number') {
                converted[key] = val;
                return;
            }
            if (weightKeys.indexOf(key) !== -1) {
                var result = convertAmount(val, 'g', targetSystem);
                converted[key] = roundValue(result.amount);
                converted[key + '_unit'] = result.unit;
            } else {
                converted[key] = val;
            }
        });
        return converted;
    }

    function formatNutritionValue(amount, unit, targetSystem) {
        if (targetSystem === 'metric') {
            return roundValue(amount) + (unit || '');
        }
        var result = convertAmount(amount, unit || 'g', targetSystem);
        return roundValue(result.amount) + ' ' + result.unit;
    }

    return {
        convertToBase: convertToBase,
        convertFromBase: convertFromBase,
        convertUnit: convertUnit,
        isVolumeUnit: isVolumeUnit,
        getBestUnit: getBestUnit,
        convertAmount: convertAmount,
        convertNutrition: convertNutrition,
        formatNutritionValue: formatNutritionValue,
        roundValue: roundValue
    };
})();
