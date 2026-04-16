<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCurrentTurnUserIdToFillers extends BaseMigration
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
        $table = $this->table('fillers');
        $table->addColumn('current_turn_user_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addIndex([
            'current_turn_user_id',
        
            ], [
            'name' => 'BY_CURRENT_TURN_USER_ID',
            'unique' => false,
        ]);
        $table->update();
    }
}
