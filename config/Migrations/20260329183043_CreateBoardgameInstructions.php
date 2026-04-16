<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBoardgameInstructions extends BaseMigration
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
        $table = $this->table('boardgame_instructions');
        $table->addColumn('boardgame_id', 'biginteger', [
            'default' => null,
            'limit' => 20,
            'null' => false,
        ]);
        $table->addColumn('step_order', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
            'signed' => false,
        ]);
        $table->addColumn('content', 'text', [
            'default' => null,
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
        $table->addForeignKey('boardgame_id', 'boardgames', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ]);
        $table->addIndex(['boardgame_id', 'step_order'], ['unique' => true]);
        $table->create();
    }
}
