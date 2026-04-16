<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * LabyrinthGame Entity
 *
 * @property int $id
 * @property int $session_game_id
 * @property int $board_game_id
 * @property string $map_name
 * @property string $treasure_pos
 * @property string $player_data
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Sessionsgame $session_game
 * @property \App\Model\Entity\Boardgame $boardgame
 */
class LabyrinthGame extends Entity
{
    protected array $_accessible = [
        'session_game_id' => true,
        'board_game_id' => true,
        'map_name' => true,
        'treasure_pos' => true,
        'player_data' => true,
        'created' => true,
        'modified' => true,
        'session_game' => true,
        'boardgame' => true,
    ];
}
