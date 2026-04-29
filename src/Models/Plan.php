<?php

namespace App\Models;

use App\Core\Model;

class Plan extends Model
{
    protected string $table = 'plans';

    public function findActiveByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id AND active = true LIMIT 1");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
