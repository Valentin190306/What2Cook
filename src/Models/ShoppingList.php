<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ShoppingList extends Model
{
    protected string $table = 'shopping_list_items';

    public function findByPlan(int $planId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE plan_id = :plan_id ORDER BY purchased ASC, ingredient_name ASC"
        );
        $stmt->execute(['plan_id' => $planId]);
        return $stmt->fetchAll();
    }

    public function togglePurchased(int $itemId, bool $purchased): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET purchased = :purchased WHERE id = :id"
        );
        $stmt->execute(['purchased' => $purchased, 'id' => $itemId]);
        return $stmt->rowCount() > 0;
    }
}