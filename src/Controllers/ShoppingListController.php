<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\SavedShoppingList;
use App\Models\Plan;
use App\Models\ShoppingList;
use App\Services\UnitConversionService;
use App\Services\UnitPreferenceService;

class ShoppingListController extends Controller
{
    public function save(): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();
        
        $body = $this->parseBody();
        $sourceType = $body['source_type'] ?? '';
        $sourceId = (int) ($body['source_id'] ?? 0);
        $items = $body['items'] ?? [];
        
        $isValidSourceId = ($sourceType === 'meal_prep') ? ($sourceId >= 0) : ($sourceId > 0);
        
        if (empty($sourceType) || !$isValidSourceId || empty($items)) {
            $this->json(['error' => 'Datos inválidos.'], 422);
            return;
        }
        
        try {
            $listId = (new SavedShoppingList())->create($userId, $sourceType, $sourceId, $items);
            
            $this->log('info', 'Guardar lista de compras', [
                'user_id' => $userId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'list_id' => $listId,
            ]);

            $this->json(['success' => true, 'list_id' => $listId]);
        } catch (\Throwable $e) {
            $this->log('error', 'Error al guardar lista de compras', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $this->json(['error' => 'Error al guardar la lista de compras.'], 500);
        }
    }
    
    public function indexApi(): void
    {
        $userId = $this->requireAuthApi();
        $lists = (new SavedShoppingList())->findAllByUser($userId);
        
        $this->json(['lists' => $lists]);
    }
    
    public function index(): void
    {
        $userId = $this->requireAuthWeb();
        $savedListModel = new SavedShoppingList();
        $lists = $savedListModel->findAllByUser($userId);
        
        $prefService = new UnitPreferenceService();
        $convService = new UnitConversionService();
        $unitSystem = $prefService->getPreferredSystem($userId);
        
        foreach ($lists as &$list) {
            $listWithItems = $savedListModel->findWithItems((int) $list['id']);
            $items = $listWithItems['items'] ?? [];
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
            $list['items'] = $items;
        }
        unset($list);
        
        \App\Core\View::render('ShoppingLists', [
            'lists' => $lists,
        ]);
    }
    
    public function delete(): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();
        
        $body = $this->parseBody();
        $listId = (int) ($body['list_id'] ?? 0);
        
        if ($listId <= 0) {
            $this->json(['error' => 'ID inválido.'], 422);
            return;
        }
        
        try {
            (new SavedShoppingList())->delete($listId, $userId);
            
            $this->log('info', 'Eliminar lista de compras', [
                'user_id' => $userId,
                'list_id' => $listId,
            ]);

            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->log('error', 'Error al eliminar lista de compras', [
                'user_id' => $userId,
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            $this->json(['error' => 'Error al eliminar la lista de compras.'], 500);
        }
    }

    public function rename(): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();

        $body = $this->parseBody();
        $listId = (int) ($body['list_id'] ?? 0);
        $name = trim($body['name'] ?? '');

        if ($listId <= 0 || $name === '') {
            $this->json(['error' => 'Datos inválidos.'], 422);
            return;
        }

        try {
            $success = (new SavedShoppingList())->rename($listId, $userId, $name);
            if ($success) {
                $this->log('info', 'Lista de compras renombrada', [
                    'user_id' => $userId,
                    'list_id' => $listId,
                    'new_name' => $name
                ]);
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'No se pudo renombrar la lista o no tenés permisos.'], 403);
            }
        } catch (\Throwable $e) {
            $this->log('error', 'Error al renombrar lista de compras', [
                'user_id' => $userId,
                'list_id' => $listId,
                'error' => $e->getMessage()
            ]);
            $this->json(['error' => 'Error al renombrar la lista de compras.'], 500);
        }
    }
}
