<?php
declare(strict_types=1);

namespace App\Services;

class UnitPreferenceService
{
    private const VALID_SYSTEMS = ['metric', 'imperial', 'us'];
    private const DEFAULT_SYSTEM = 'metric';

    private UserPreferenceService $userPrefService;

    public function __construct(?UserPreferenceService $userPrefService = null)
    {
        $this->userPrefService = $userPrefService ?? new UserPreferenceService();
    }

    public function getPreferredSystem(?int $userId): string
    {
        if ($userId !== null) {
            $prefs = $this->userPrefService->getPreferences($userId);
            return $prefs['unit_system'];
        }

        return self::DEFAULT_SYSTEM;
    }

    public function setPreferredSystem(int $userId, string $unitSystem): bool
    {
        if (!in_array($unitSystem, self::VALID_SYSTEMS, true)) {
            return false;
        }

        return $this->userPrefService->setPreference($userId, 'unit_system', $unitSystem);
    }

    public function isMetric(?int $userId): bool
    {
        return $this->getPreferredSystem($userId) === 'metric';
    }
}
