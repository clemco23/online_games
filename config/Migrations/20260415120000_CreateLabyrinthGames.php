<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateLabyrinthGames extends BaseMigration
{
    /**
     * Apply migration.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('labyrinth_games');
        $table->addColumn('session_game_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('board_game_id', 'integer', [
            'default' => null,
            'limit' => 11,
            'null' => false,
        ]);
        $table->addColumn('map_name', 'string', [
            'default' => 'default.txt',
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('treasure_pos', 'json', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('player_data', 'json', [
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
        $table->addForeignKey('session_game_id', 'sessionsgames', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ]);
        $table->addForeignKey('board_game_id', 'boardgames', 'id', [
            'delete' => 'CASCADE',
            'update' => 'NO_ACTION',
        ]);
        $table->addIndex(['session_game_id'], ['unique' => true]);
        $table->create();

        $now = date('Y-m-d H:i:s');
        $this->execute("
            INSERT INTO boardgames (name, description, picture, created, modified)
            SELECT
                'Labyrinth',
                'Traverse un labyrinthe en temps reel, gere tes points d action et atteins le tresor.',
                'labyrinthe.jpg',
                '{$now}',
                '{$now}'
            WHERE NOT EXISTS (
                SELECT 1 FROM boardgames WHERE name IN ('Labyrinth', 'Labyrinthe')
            )
        ");

        $this->execute("
            INSERT INTO boardgame_instructions (boardgame_id, step_order, content, created, modified)
            SELECT
                boardgames.id,
                1,
                'Chaque joueur commence sur une case adjacente.',
                '{$now}',
                '{$now}'
            FROM boardgames
            WHERE boardgames.name IN ('Labyrinth', 'Labyrinthe')
              AND NOT EXISTS (
                  SELECT 1 FROM boardgame_instructions
                  WHERE boardgame_instructions.boardgame_id = boardgames.id
                    AND boardgame_instructions.step_order = 1
              )
        ");

        $this->execute("
            INSERT INTO boardgame_instructions (boardgame_id, step_order, content, created, modified)
            SELECT
                boardgames.id,
                2,
                'Chaque deplacement coute 1 point d action. Les points remontent de 5 par minute.',
                '{$now}',
                '{$now}'
            FROM boardgames
            WHERE boardgames.name IN ('Labyrinth', 'Labyrinthe')
              AND NOT EXISTS (
                  SELECT 1 FROM boardgame_instructions
                  WHERE boardgame_instructions.boardgame_id = boardgames.id
                    AND boardgame_instructions.step_order = 2
              )
        ");

        $this->execute("
            INSERT INTO boardgame_instructions (boardgame_id, step_order, content, created, modified)
            SELECT
                boardgames.id,
                3,
                'Le premier joueur qui rejoint la case tresor gagne la partie.',
                '{$now}',
                '{$now}'
            FROM boardgames
            WHERE boardgames.name IN ('Labyrinth', 'Labyrinthe')
              AND NOT EXISTS (
                  SELECT 1 FROM boardgame_instructions
                  WHERE boardgame_instructions.boardgame_id = boardgames.id
                    AND boardgame_instructions.step_order = 3
              )
        ");
    }

    /**
     * Roll back migration.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('labyrinth_games')->drop()->save();

        $this->execute("
            DELETE boardgame_instructions FROM boardgame_instructions
            INNER JOIN boardgames ON boardgame_instructions.boardgame_id = boardgames.id
            WHERE boardgames.name = 'Labyrinth'
        ");
        $this->execute("DELETE FROM boardgames WHERE name = 'Labyrinth'");
    }
}
