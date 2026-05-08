<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlanDaysTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plan_days');
        $table->addColumn('plan_id', 'integer')
              ->addColumn('day_index', 'integer')       // 1-based, máximo igual a duration_days del plan
              ->addColumn('total_calories', 'decimal', ['precision' => 7, 'scale' => 2, 'default' => 0])
              ->addColumn('total_protein', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 0])
              ->addColumn('total_carbs', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 0])
              ->addColumn('total_fat', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 0])
              ->addForeignKey('plan_id', 'plans', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addIndex(['plan_id', 'day_index'], ['unique' => true])
              ->create();
    }
}