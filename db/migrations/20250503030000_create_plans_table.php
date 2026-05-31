<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlansTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plans');
        $table->addColumn('user_id', 'integer')
              ->addColumn('duration_days', 'integer')   // 7 | 14 | 30
              ->addColumn('diet_type', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('target_calories', 'integer', ['null' => true])
              ->addColumn('target_protein', 'integer', ['null' => true])
              ->addColumn('target_carbs', 'integer', ['null' => true])
              ->addColumn('target_fat', 'integer', ['null' => true])
              ->addColumn('active', 'boolean', ['default' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}