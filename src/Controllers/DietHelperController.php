<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\ShoppingList;
use App\Services\DietHelperService;
use RuntimeException;

class DietHelperController extends Controller
{
    // ── Vistas ────────────────────────────────────────────────────────────────

    public function index(): void
    {
        \App\Core\View::render('DietHelper');
    }

    // ── API: Generación ───────────────────────────────────────────────────────

    /**
     * POST /api/diet-helper/generate
     *
     * Body JSON:
     * {
     *   "duration_days":   7,
     *   "target_calories": 2000,
     *   "target_protein":  150,
     *   "target_carbs":    200,
     *   "target_fat":      70,
     *   "diet_type":       "vegana"   // opcional
     * }
     *
     * Devuelve el plan generado sin persistirlo.
     * El cliente decide si lo guarda con POST /api/diet-helper/plan.
     */
    public function generate(): void
    {
        $this->requireJson();

        $body = $this->parseBody();

        $durationDays   = (int)    ($body['duration_days']   ?? 7);
        $targetCalories = (int)    ($body['target_calories'] ?? 0);
        $targetProtein  = (int)    ($body['target_protein']  ?? 0);
        $targetCarbs    = (int)    ($body['target_carbs']    ?? 0);
        $targetFat      = (int)    ($body['target_fat']      ?? 0);
        $dietType       = (string) ($body['diet_type']       ?? '');

        if (!in_array($durationDays, [7, 14, 30], true)) {
            $this->json(['error' => 'duration_days debe ser 7, 14 o 30.'], 422);
            return;
        }

        try {
            $service = new DietHelperService();
            $plan    = $service->generatePlan(
                $durationDays,
                $targetCalories,
                $targetProtein,
                $targetCarbs,
                $targetFat,
                $dietType
            );
            $this->json($plan);
        } catch (RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── API: Persistencia ─────────────────────────────────────────────────────

    /**
     * POST /api/diet-helper/plan
     *
     * Guarda en DB el plan previamente generado.
     * Requiere sesión activa.
     *
     * Body JSON: estructura completa devuelta por /generate
     */
    public function savePlan(): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();

        $planData = $this->parseBody();

        if (empty($planData['meta']) || empty($planData['days'])) {
            $this->json(['error' => 'Estructura de plan inválida.'], 422);
            return;
        }

        try {
            $model  = new Plan();
            $planId = $model->createFull($userId, $planData);
            $this->json(['plan_id' => $planId], 201);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Error al guardar el plan.'], 500);
        }
    }

    /**
     * GET /api/diet-helper/plan/{id}
     *
     * Devuelve un plan guardado con todos sus días y comidas.
     * Solo accesible por el dueño del plan.
     */
    public function getPlan(string $id): void
    {
        $userId = $this->requireAuthApi();
        $planId = (int) $id;

        $model = new Plan();
        $plan  = $model->findWithDays($planId);

        if ($plan === null) {
            $this->json(['error' => 'Plan no encontrado.'], 404);
            return;
        }

        if ((int) $plan['user_id'] !== $userId) {
            $this->json(['error' => 'Acceso denegado.'], 403);
            return;
        }

        $this->json($plan);
    }

    /**
     * GET /api/diet-helper/active
     *
     * Devuelve el plan activo del usuario con todos sus días y comidas.
     */
    public function getActivePlan(): void
    {
        $userId = $this->requireAuthApi();

        $model      = new Plan();
        $activePlan = $model->findActiveByUser($userId);

        if ($activePlan === null) {
            $this->json(['plan' => null]);
            return;
        }

        $plan = $model->findWithDays((int) $activePlan['id']);
        $this->json($plan);
    }

    /**
     * GET /api/diet-helper/shopping-list/{id}
     *
     * Devuelve la lista de compras de un plan.
     */
    public function getShoppingList(string $id): void
    {
        $userId = $this->requireAuthApi();
        $planId = (int) $id;

        $planModel = new Plan();
        $plan      = $planModel->find($planId);

        if ($plan === null) {
            $this->json(['error' => 'Plan no encontrado.'], 404);
            return;
        }

        if ((int) $plan['user_id'] !== $userId) {
            $this->json(['error' => 'Acceso denegado.'], 403);
            return;
        }

        $shoppingList = new ShoppingList();
        $items        = $shoppingList->findByPlan($planId);

        $this->json(['items' => $items]);
    }

    /**
     * PATCH /api/diet-helper/shopping-list/item/{id}
     *
     * Marca o desmarca un ítem de la lista de compras como comprado.
     *
     * Body JSON: { "purchased": true }
     */
    public function toggleShoppingItem(string $id): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();

        $itemId = (int) $id;
        $shoppingList = new ShoppingList();

        // Verificar que el item existe y pertenece al usuario
        $item = $shoppingList->findById($itemId);
        if ($item === null) {
            $this->json(['error' => 'Item no encontrado.'], 404);
        }
        if ((int) $item['user_id'] !== $userId) {
            $this->json(['error' => 'Acceso denegado.'], 403);
        }

        $body      = $this->parseBody();
        $purchased = (bool) ($body['purchased'] ?? false);
        $updated   = $shoppingList->togglePurchased($itemId, $purchased);

        if (!$updated) {
            $this->json(['error' => 'Item no encontrado.'], 404);
        }
        $this->json(['ok' => true]);
    }

}
