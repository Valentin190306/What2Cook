<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MealPrepFavorite extends Model
{
    protected string $table = 'meal_prep_favorites';

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function findRecentByUser(int $userId, int $limit = 4): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->execute(['user_id' => $userId, 'limit' => $limit]);
        return $stmt->fetchAll();
    }

    public function existsForUser(int $userId, string $ingredientsHash): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE user_id = :user_id AND ingredients_hash = :hash LIMIT 1");
        $stmt->execute([
            'user_id' => $userId,
            'hash' => $ingredientsHash
        ]);
        return $stmt->fetchColumn() !== false;
    }

    public function toggle(int $userId, array $mealPrepData): bool
    {
        $ingredients = $mealPrepData['ingredients'];
        $recipeIds = $mealPrepData['recipe_ids'];
        $servings = $mealPrepData['servings'];
        
        // Create a hash to identify unique meal preps
        $ingredientsHash = md5(implode(',', $ingredients));
        
        if ($this->existsForUser($userId, $ingredientsHash)) {
            $this->deleteForUser($userId, $ingredientsHash);
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (user_id, ingredients, recipe_ids, servings, ingredients_hash) 
             VALUES (:user_id, :ingredients, :recipe_ids, :servings, :hash)"
        );
        $stmt->execute([
            'user_id' => $userId,
            'ingredients' => json_encode($ingredients),
            'recipe_ids' => json_encode($recipeIds),
            'servings' => json_encode($servings),
            'hash' => $ingredientsHash
        ]);
        return true;
    }

    public function deleteForUser(int $userId, string $ingredientsHash): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id AND ingredients_hash = :hash");
        $stmt->execute([
            'user_id' => $userId,
            'hash' => $ingredientsHash
        ]);
        return $stmt->rowCount() > 0;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }
}
