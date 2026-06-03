<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SavedShoppingList;

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
        
        if (empty($sourceType) || $sourceId <= 0 || empty($items)) {
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
        $lists = (new SavedShoppingList())->findAllByUser($userId);
        
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
}
