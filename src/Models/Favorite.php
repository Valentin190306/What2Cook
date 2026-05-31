<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Favorite extends Model
{
    protected string $table = 'favorites';

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function existsForUser(int $userId, int $spoonacularId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM favorites WHERE user_id = :user_id AND spoonacular_id = :sid LIMIT 1");
        $stmt->execute([
            'user_id' => $userId,
            'sid' => $spoonacularId
        ]);
        return $stmt->fetchColumn() !== false;
    }

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM favorites WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function deleteForUser(int $userId, int $spoonacularId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM favorites WHERE user_id = :user_id AND spoonacular_id = :sid");
        $stmt->execute([
            'user_id' => $userId,
            'sid' => $spoonacularId
        ]);
        return $stmt->rowCount() > 0;
    }

    public function toggle(int $userId, array $recipe): bool
    {
        $spoonacularId = (int) $recipe['spoonacular_id'];
        if ($this->existsForUser($userId, $spoonacularId)) {
            $this->deleteForUser($userId, $spoonacularId);
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO favorites (user_id, spoonacular_id, title, image) VALUES (:user_id, :sid, :title, :image)");
        $stmt->execute([
            'user_id' => $userId,
            'sid' => $spoonacularId,
            'title' => $recipe['title'],
            'image' => $recipe['image']
        ]);
        return true;
    }
}
