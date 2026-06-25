<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class UserPreferenceService
{
    /**
     * Obtiene dieta e intolerancias de un usuario.
     *
     * @return array{diet: string, intolerances: array}
     */
    public function getPreferences(int $userId): array
    {
        $user = (new User())->find($userId);
        if (!$user) {
            return ['diet' => '', 'intolerances' => []];
        }

        $diet = $user['preferences'] ?? '';
        $intolerances = [];
        if (!empty($user['allergies'])) {
            $decoded = json_decode($user['allergies'], true);
            if (is_array($decoded)) {
                $intolerances = $decoded;
            }
        }

        return ['diet' => $diet, 'intolerances' => $intolerances];
    }
}
