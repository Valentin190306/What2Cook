<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSavedShoppingListItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('saved_shopping_list_items');
        $table->addColumn('shopping_list_id', 'integer')
              ->addColumn('ingredient_name', 'string', ['limit' => 255])
              ->addColumn('amount', 'float')
              ->addColumn('unit', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('purchased', 'boolean', ['default' => false])
              ->addForeignKey('shopping_list_id', 'saved_shopping_lists', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
