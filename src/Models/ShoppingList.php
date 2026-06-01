<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ShoppingList extends Model
{
    protected string $table = 'shopping_list_items';

    public function findById(int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, p.user_id 
             FROM shopping_list_items s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $itemId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

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

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT s.plan_id) 
             FROM shopping_list_items s 
             JOIN plans p ON p.id = s.plan_id 
             WHERE p.user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}