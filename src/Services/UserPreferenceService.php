<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class UserPreferenceService
{
    /**
     * Obtiene dieta, intolerancias y sistema de unidades de un usuario.
     *
     * @return array{diet: string, intolerances: array, unit_system: string}
     */
    public function getPreferences(int $userId): array
    {
        $user = (new User())->find($userId);
        if (!$user) {
            return ['diet' => '', 'intolerances' => [], 'unit_system' => 'metric'];
        }

        $diet = $user['preferences'] ?? '';
        $intolerances = [];
        $unitSystem = 'metric'; // Default

        if (!empty($user['allergies'])) {
            $decoded = json_decode($user['allergies'], true);
            if (is_array($decoded)) {
                $intolerances = $decoded;
            }
        }

        // Try to get unit_system from preferences JSON if it's stored there
        if (!empty($user['preferences'])) {
            $prefsDecoded = json_decode($user['preferences'], true);
            if (is_array($prefsDecoded) && isset($prefsDecoded['unit_system'])) {
                $unitSystem = $prefsDecoded['unit_system'];
            }
        }

        return [
            'diet' => $diet,
            'intolerances' => $intolerances,
            'unit_system' => in_array($unitSystem, ['metric', 'imperial', 'us'], true) ? $unitSystem : 'metric'
        ];
    }

    /**
     * Establece una preferencia individual para un usuario.
     *
     * @param int $userId ID del usuario
     * @param string $key Clave de la preferencia (ej: 'unit_system')
     * @param mixed $value Valor de la preferencia
     * @return bool true si se actualizó correctamente
     */
    public function setPreference(int $userId, string $key, $value): bool
    {
        $user = (new User())->find($userId);
        if (!$user) {
            return false;
        }

        // Get existing preferences as JSON
        $existingPrefs = [];
        if (!empty($user['preferences'])) {
            $decoded = json_decode($user['preferences'], true);
            if (is_array($decoded)) {
                $existingPrefs = $decoded;
            }
        }

        // Update the specific key
        $existingPrefs[$key] = $value;

        // Save back to database
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("UPDATE users SET preferences = :preferences WHERE id = :id");
        return $stmt->execute([
            'preferences' => json_encode($existingPrefs),
            'id' => $userId
        ]);
    }
}
