<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateMasterminds extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('masterminds');
        $table->addColumn('steps', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('session_game_id', 'integer', [
            'default' => null,
            'limit' => 20,
            'null' => false,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => false,
        ]);
        $table->addForeignKey('session_game_id', 'sessionsgames', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ]);
        $table->addIndex(['session_game_id'], ['unique' => true]);
        $table->create();
    }
}
