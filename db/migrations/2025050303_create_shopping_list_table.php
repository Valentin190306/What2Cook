<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateShoppingListItemsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('shopping_list_items');
        $table->addColumn('plan_id', 'integer')
              ->addColumn('ingredient_name', 'string', ['limit' => 255])
              ->addColumn('amount', 'decimal', ['precision' => 8, 'scale' => 2, 'default' => 0])
              ->addColumn('unit', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('purchased', 'boolean', ['default' => false])
              ->addForeignKey('plan_id', 'plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}