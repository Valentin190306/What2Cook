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
}
