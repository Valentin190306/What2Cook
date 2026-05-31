<?php
declare(strict_types=1);
 
use Phinx\Migration\AbstractMigration;
 
final class CreateFavoritesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('favorites');
        $table->addColumn('user_id', 'integer')
              ->addColumn('spoonacular_id', 'integer')
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('image', 'text', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addIndex(['user_id', 'spoonacular_id'], ['unique' => true])
              ->create();
    }
}
