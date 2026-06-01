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
        
        View::render('ShoppingList', [
            'plan'  => $plan,
            'items' => $items
        ]);
    }
}
