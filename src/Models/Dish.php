<?php

namespace App\Models;

use App\Core\Model;

class Dish extends Model
{
    protected string $table = 'dishes';

    public function findByDiet(string $diet): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE diets @> :diet");
        $stmt->execute(['diet' => json_encode([$diet])]);
        return $stmt->fetchAll();
    }
}
