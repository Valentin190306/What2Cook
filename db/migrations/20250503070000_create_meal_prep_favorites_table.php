<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMealPrepFavoritesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('meal_prep_favorites');
        $table->addColumn('user_id', 'integer')
              ->addColumn('ingredients', 'text')
              ->addColumn('recipe_ids', 'text')
              ->addColumn('servings', 'text')
              ->addColumn('ingredients_hash', 'string', ['limit' => 32])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addIndex(['user_id', 'ingredients_hash'], ['unique' => true])
              ->create();
    }
}
