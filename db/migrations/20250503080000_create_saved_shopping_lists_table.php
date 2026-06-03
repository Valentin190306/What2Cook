<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSavedShoppingListsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('saved_shopping_lists');
        $table->addColumn('user_id', 'integer')
              ->addColumn('source_type', 'string', ['limit' => 20]) // 'recipe', 'meal_prep', 'diet_plan'
              ->addColumn('source_id', 'integer')
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
