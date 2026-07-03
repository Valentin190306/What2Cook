<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddGoogleOAuthColumns extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('google_id', 'string', ['limit' => 255, 'null' => true])
              ->addIndex(['google_id'], ['unique' => true])
              ->addColumn('avatar_url', 'string', ['limit' => 500, 'null' => true])
              ->update();
    }
}
