<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SavedShoppingList extends Model
{
    protected string $table = 'saved_shopping_lists';

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findRecentByUser(int $userId, int $limit = 3): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->execute(['user_id' => $userId, 'limit' => $limit]);
        return $stmt->fetchAll();
    }

    public function findWithItems(int $listId): ?array
    {
        $list = $this->find($listId);
        if ($list === null) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM saved_shopping_list_items WHERE shopping_list_id = :list_id ORDER BY ingredient_name ASC"
        );
        $stmt->execute(['list_id' => $listId]);
        $list['items'] = $stmt->fetchAll();

        return $list;
    }

    public function create(int $userId, string $sourceType, int $sourceId, array $items): int
    {
        $this->db->beginTransaction();

        try {
            // Insert shopping list
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (user_id, source_type, source_id) 
                 VALUES (:user_id, :source_type, :source_id) 
                 RETURNING id"
            );
            $stmt->execute([
                'user_id' => $userId,
                'source_type' => $sourceType, // 'recipe', 'meal_prep', or 'diet_plan'
                'source_id' => $sourceId
            ]);
            $listId = (int) $stmt->fetchColumn();

            // Insert items
            foreach ($items as $item) {
                $stmt = $this->db->prepare(
                    "INSERT INTO saved_shopping_list_items 
                        (shopping_list_id, ingredient_name, amount, unit, purchased) 
                     VALUES 
                        (:list_id, :name, :amount, :unit, false)"
                );
                $stmt->execute([
                    'list_id' => $listId,
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'unit' => $item['unit'] ?? ''
                ]);
            }

            $this->db->commit();
            return $listId;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id, int $userId = null): bool
    {
        if ($userId === null) {
            return parent::delete($id);
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
