<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class RecipeTranslation extends Model
{
    protected string $table = 'recipe_translations';

    public function findBySpoonacularId(int $spoonacularId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE spoonacular_id = :id"
        );
        $stmt->execute(['id' => $spoonacularId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function exists(int $spoonacularId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE spoonacular_id = :id"
        );
        $stmt->execute(['id' => $spoonacularId]);
        return (bool) $stmt->fetch();
    }

    public function upsert(array $data): void
    {
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (
                spoonacular_id, title_en, title_es, summary_en, summary_es,
                raw_response_en, raw_response_es, image, ready_in_minutes,
                servings, cuisines, diets, dish_types, translated_at,
                created_at, updated_at
            ) VALUES (
                :spoonacular_id, :title_en, :title_es, :summary_en, :summary_es,
                :raw_response_en, :raw_response_es, :image, :ready_in_minutes,
                :servings, :cuisines, :diets, :dish_types, :translated_at,
                :created_at, :updated_at
            ) ON CONFLICT (spoonacular_id) DO UPDATE SET
                title_en      = EXCLUDED.title_en,
                title_es      = EXCLUDED.title_es,
                summary_en    = EXCLUDED.summary_en,
                summary_es    = EXCLUDED.summary_es,
                raw_response_en = EXCLUDED.raw_response_en,
                raw_response_es = EXCLUDED.raw_response_es,
                image         = EXCLUDED.image,
                ready_in_minutes = EXCLUDED.ready_in_minutes,
                servings      = EXCLUDED.servings,
                cuisines      = EXCLUDED.cuisines,
                diets         = EXCLUDED.diets,
                dish_types    = EXCLUDED.dish_types,
                translated_at = EXCLUDED.translated_at,
                updated_at    = EXCLUDED.updated_at
        ");

        $stmt->execute([
            'spoonacular_id'   => $data['spoonacular_id'],
            'title_en'         => $data['title_en'] ?? null,
            'title_es'         => $data['title_es'] ?? null,
            'summary_en'       => $data['summary_en'] ?? null,
            'summary_es'       => $data['summary_es'] ?? null,
            'raw_response_en'  => $data['raw_response_en'] ?? null,
            'raw_response_es'  => $data['raw_response_es'] ?? null,
            'image'            => $data['image'] ?? null,
            'ready_in_minutes' => $data['ready_in_minutes'] ?? null,
            'servings'         => $data['servings'] ?? null,
            'cuisines'         => $data['cuisines'] ?? null,
            'diets'            => $data['diets'] ?? null,
            'dish_types'       => $data['dish_types'] ?? null,
            'translated_at'    => $now,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function findAllIds(): array
    {
        $stmt = $this->db->query("SELECT spoonacular_id FROM {$this->table} ORDER BY spoonacular_id");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findTranslatedIdsWithLimit(int $limit, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            "SELECT spoonacular_id FROM {$this->table}
             WHERE raw_response_es IS NOT NULL
             ORDER BY translated_at DESC
             LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findUntranslatedIds(): array
    {
        $stmt = $this->db->query(
            "SELECT spoonacular_id FROM {$this->table}
             WHERE raw_response_es IS NULL
             ORDER BY spoonacular_id"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Guarda datos crudos de Spoonacular (inglés) sin traducción.
     * Crea o actualiza el registro para el spoonacular_id dado.
     */
    public function saveRaw(int $spoonacularId, array $recipeData): void
    {
        $this->upsert([
            'spoonacular_id'   => $spoonacularId,
            'title_en'         => $recipeData['title'] ?? '',
            'summary_en'       => $recipeData['summary'] ?? '',
            'raw_response_en'  => json_encode($recipeData, JSON_UNESCAPED_UNICODE),
            'image'            => $recipeData['image'] ?? null,
            'ready_in_minutes' => $recipeData['readyInMinutes'] ?? null,
            'servings'         => $recipeData['servings'] ?? null,
            'cuisines'         => !empty($recipeData['cuisines']) ? json_encode($recipeData['cuisines']) : null,
            'diets'            => !empty($recipeData['diets']) ? json_encode($recipeData['diets']) : null,
            'dish_types'       => !empty($recipeData['dishTypes']) ? json_encode($recipeData['dishTypes']) : null,
        ]);
    }
}
