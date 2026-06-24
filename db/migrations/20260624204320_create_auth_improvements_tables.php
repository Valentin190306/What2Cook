<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuthImprovementsTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Tabla: login_attempts
        $loginAttempts = $this->table('login_attempts');
        $loginAttempts->addColumn('ip_address', 'string', ['limit' => 45])
                      ->addColumn('attempted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                      ->addIndex(['ip_address'])
                      ->addIndex(['attempted_at'])
                      ->create();

        // Tabla: user_remember_tokens
        $rememberTokens = $this->table('user_remember_tokens');
        $rememberTokens->addColumn('user_id', 'integer')
                       ->addColumn('selector', 'string', ['limit' => 255])
                       ->addColumn('hashed_validator', 'string', ['limit' => 255])
                       ->addColumn('expires_at', 'timestamp')
                       ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                       ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                       ->addIndex(['selector'], ['unique' => true])
                       ->create();
    }
}
