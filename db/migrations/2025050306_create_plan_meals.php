<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlanMealsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plan_meals');
        $table->addColumn('plan_day_id', 'integer')
              ->addColumn('meal_type', 'string', ['limit' => 20])  // breakfast | lunch | snack | dinner
              ->addColumn('spoonacular_id', 'integer')
              ->addColumn('title', 'string', ['limit' => 255])
              ->addColumn('image', 'text', ['null' => true])
              ->addColumn('ready_in_minutes', 'integer', ['null' => true])
              ->addColumn('servings', 'integer', ['null' => true])
              ->addColumn('calories', 'decimal', ['precision' => 7, 'scale' => 2, 'default' => 0])
              ->addColumn('protein', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 0])
              ->addColumn('carbs', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 0])
              ->addColumn('fat', 'decimal', ['precision' => 6, 'scale' => 2, 'default' => 0])
              ->addForeignKey('plan_day_id', 'plan_days', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addIndex(['plan_day_id', 'meal_type'], ['unique' => true])
              ->create();
    }
}