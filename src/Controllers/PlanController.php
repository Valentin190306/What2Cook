<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Plan;
use App\Models\ShoppingList;
use App\Core\View;
use App\Services\UnitConversionService;
use App\Services\UnitPreferenceService;

class PlanController extends Controller
{
    public function myPlans(): void
    {
        $userId = $this->requireAuthWeb();
        $planModel = new Plan();
        $summaries = $planModel->findAllByUser($userId);
        $plans = [];
        foreach ($summaries as $p) {
            $full = $planModel->findWithDays((int) $p['id']);
            if ($full !== null) {
                $plans[] = $full;
            }
        }

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
            $convService = new UnitConversionService();
            $prefService = new UnitPreferenceService();
            $unitSystem = $prefService->getPreferredSystem($userId);
            foreach ($items as &$item) {
                $amount = (float) ($item['amount'] ?? 0);
                $unit = $item['unit'] ?? '';
                if ($amount > 0 && $unit !== '') {
                    $converted = $convService->convertToSystem($amount, $unit, $unitSystem);
                    $item['amount'] = $converted['amount'];
                    $item['unit'] = $converted['unit'];
                }
            }
            unset($item);
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
