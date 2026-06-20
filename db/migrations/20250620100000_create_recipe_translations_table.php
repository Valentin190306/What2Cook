<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRecipeTranslationsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('recipe_translations');
        $table->addColumn('spoonacular_id', 'integer', ['signed' => false])
              ->addColumn('title_en', 'text', ['null' => true])
              ->addColumn('title_es', 'text', ['null' => true])
              ->addColumn('summary_en', 'text', ['null' => true])
              ->addColumn('summary_es', 'text', ['null' => true])
              ->addColumn('raw_response_en', 'json', ['null' => true])
              ->addColumn('raw_response_es', 'json', ['null' => true])
              ->addColumn('image', 'text', ['null' => true])
              ->addColumn('ready_in_minutes', 'integer', ['null' => true])
              ->addColumn('servings', 'integer', ['null' => true])
              ->addColumn('cuisines', 'json', ['null' => true])
              ->addColumn('diets', 'json', ['null' => true])
              ->addColumn('dish_types', 'json', ['null' => true])
              ->addColumn('translated_at', 'timestamp', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['spoonacular_id'], ['unique' => true])
              ->create();
    }
}
