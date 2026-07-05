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

        $diet = '';
        $unitSystem = 'metric';
        $intolerances = [];

        if (!empty($user['allergies'])) {
            $decoded = json_decode($user['allergies'], true);
            if (is_array($decoded)) {
                $intolerances = $decoded;
            }
        }

        if (!empty($user['preferences'])) {
            $prefsDecoded = json_decode($user['preferences'], true);
            if (is_array($prefsDecoded)) {
                // New JSON format: { "diet": "...", "unit_system": "...", ... }
                $diet = $prefsDecoded['diet'] ?? '';
                if (isset($prefsDecoded['unit_system'])) {
                    $unitSystem = $prefsDecoded['unit_system'];
                }
            } else {
                // Legacy format: plain string with the diet value
                $diet = $user['preferences'];
            }
        }

        return [
            'diet'        => $diet,
            'intolerances' => $intolerances,
            'unit_system' => in_array($unitSystem, ['metric', 'imperial', 'us'], true) ? $unitSystem : 'metric',
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
            } else {
                // Legacy format: plain string is the diet value — preserve it
                $existingPrefs = ['diet' => $user['preferences']];
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
