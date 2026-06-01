<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\ShoppingList;
use App\Core\View;

class PlanController extends Controller
{
    public function myPlans(): void
    {
        $userId = $this->requireAuthWeb();
        $plans  = (new Plan())->findAllByUser($userId);

        $this->log('info', 'Viendo mis planes', ['user_id' => $userId, 'count' => count($plans)]);
        View::render('MyPlans', ['plans' => $plans]);
    }

    public function shoppingList(): void
    {
        $userId = $this->requireAuthWeb();
        $plan   = (new Plan())->findActiveByUser($userId);

        $items = [];
        if ($plan !== null) {
            $items = (new ShoppingList())->findByPlan((int) $plan['id']);
        }

        $this->log('info', 'Viendo lista de compras', [
            'user_id' => $userId,
            'plan_id' => $plan !== null ? (int) $plan['id'] : null,
            'items' => count($items),
        ]);
        View::render('ShoppingList', [
            'plan'  => $plan,
            'items' => $items
        ]);
    }
}
