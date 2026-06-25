<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\ShoppingList;

class AuthorizationService
{
    /**
     * Verifica que un plan pertenezca al usuario.
     */
    public function ownsPlan(int $userId, int $planId): bool
    {
        $plan = (new Plan())->find($planId);
        if ($plan === null) return false;
        return (int) $plan['user_id'] === $userId;
    }

    /**
     * Verifica que un item de shopping list pertenezca al usuario.
     */
    public function ownsShoppingItem(int $userId, int $itemId): bool
    {
        $item = (new ShoppingList())->findById($itemId);
        if ($item === null) return false;
        return (int) $item['user_id'] === $userId;
    }
}
