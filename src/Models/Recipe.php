<?php

namespace App\Models;

use App\Core\Model;

class Recipe extends Model
{
    protected string $table = 'recipes';

    public function findByDishId(int $dishId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE dish_id = :dish_id");
        $stmt->execute(['dish_id' => $dishId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
