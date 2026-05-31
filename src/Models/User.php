<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} (name, email, password, preferences, allergies) 
                VALUES (:name, :email, :password, :preferences, :allergies)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'preferences' => $data['preferences'] ?? null,
            'allergies' => $data['allergies'] ?? null
        ]);
    }

    public function updateProfile(int $id, array $data, ?string $newPlainPassword = null): bool
    {
        if ($newPlainPassword !== null && $newPlainPassword !== '') {
            $sql = "UPDATE {$this->table} 
                    SET name = :name, email = :email, preferences = :preferences, allergies = :allergies, password = :password 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'preferences' => $data['preferences'],
                'allergies' => $data['allergies'],
                'password' => password_hash($newPlainPassword, PASSWORD_BCRYPT),
                'id' => $id
            ]);
        } else {
            $sql = "UPDATE {$this->table} 
                    SET name = :name, email = :email, preferences = :preferences, allergies = :allergies 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'preferences' => $data['preferences'],
                'allergies' => $data['allergies'],
                'id' => $id
            ]);
        }
    }
}
