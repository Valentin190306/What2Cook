<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Plan extends Model
{
    protected string $table = 'plans';

    // ── Lectura ───────────────────────────────────────────────────────────────

    public function findActiveByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id AND active = true LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve un plan completo con sus días y comidas.
     */
    public function findWithDays(int $planId): ?array
    {
        $plan = $this->find($planId);
        if ($plan === null) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM plan_days WHERE plan_id = :plan_id ORDER BY day_index ASC"
        );
        $stmt->execute(['plan_id' => $planId]);
        $days = $stmt->fetchAll();

        foreach ($days as &$day) {
            $stmt = $this->db->prepare(
                "SELECT * FROM plan_meals WHERE plan_day_id = :plan_day_id 
                 ORDER BY CASE meal_type 
                     WHEN 'breakfast' THEN 1 
                     WHEN 'lunch'     THEN 2 
                     WHEN 'snack'     THEN 3 
                     WHEN 'dinner'    THEN 4 
                 END"
            );
            $stmt->execute(['plan_day_id' => $day['id']]);
            $day['meals'] = $stmt->fetchAll();
        }

        $plan['days'] = $days;
        return $plan;
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Persiste un plan completo (plan + días + comidas) en una transacción.
     * Desactiva cualquier plan activo previo del usuario antes de insertar.
     *
     * @param  int   $userId
     * @param  array $planData  Estructura devuelta por DietHelperService::generatePlan()
     * @return int   ID del plan creado
     */
    public function createFull(int $userId, array $planData): int
    {
        $meta = $planData['meta'];
        $days = $planData['days'];

        $this->db->beginTransaction();

        try {
            // Desactivar plan previo
            $this->db->prepare(
                "UPDATE {$this->table} SET active = false WHERE user_id = :user_id AND active = true"
            )->execute(['user_id' => $userId]);

            // Insertar plan
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} 
                    (user_id, duration_days, diet_type, target_calories, target_protein, target_carbs, target_fat, active)
                 VALUES 
                    (:user_id, :duration_days, :diet_type, :target_calories, :target_protein, :target_carbs, :target_fat, true)
                 RETURNING id"
            );
            $stmt->execute([
                'user_id'         => $userId,
                'duration_days'   => $meta['duration_days'],
                'diet_type'       => $meta['diet_type']       ?: null,
                'target_calories' => $meta['target_calories'] ?: null,
                'target_protein'  => $meta['target_protein']  ?: null,
                'target_carbs'    => $meta['target_carbs']    ?: null,
                'target_fat'      => $meta['target_fat']      ?: null,
            ]);
            $planId = (int) $stmt->fetchColumn();

            // Insertar días y comidas
            foreach ($days as $day) {
                $stmt = $this->db->prepare(
                    "INSERT INTO plan_days (plan_id, day_index, total_calories, total_protein, total_carbs, total_fat)
                     VALUES (:plan_id, :day_index, :total_calories, :total_protein, :total_carbs, :total_fat)
                     RETURNING id"
                );
                $stmt->execute([
                    'plan_id'        => $planId,
                    'day_index'      => $day['day_index'],
                    'total_calories' => $day['total_calories'],
                    'total_protein'  => $day['total_protein'],
                    'total_carbs'    => $day['total_carbs'],
                    'total_fat'      => $day['total_fat'],
                ]);
                $dayId = (int) $stmt->fetchColumn();

                foreach ($day['meals'] as $meal) {
                    $this->db->prepare(
                        "INSERT INTO plan_meals 
                            (plan_day_id, meal_type, spoonacular_id, title, image, ready_in_minutes, servings, calories, protein, carbs, fat)
                         VALUES 
                            (:plan_day_id, :meal_type, :spoonacular_id, :title, :image, :ready_in_minutes, :servings, :calories, :protein, :carbs, :fat)"
                    )->execute([
                        'plan_day_id'      => $dayId,
                        'meal_type'        => $meal['meal_type'],
                        'spoonacular_id'   => $meal['spoonacular_id'],
                        'title'            => $meal['title'],
                        'image'            => $meal['image'],
                        'ready_in_minutes' => $meal['ready_in_minutes'],
                        'servings'         => $meal['servings'],
                        'calories'         => $meal['calories'],
                        'protein'          => $meal['protein'],
                        'carbs'            => $meal['carbs'],
                        'fat'              => $meal['fat'],
                    ]);
                }
            }

            $this->db->commit();
            return $planId;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}