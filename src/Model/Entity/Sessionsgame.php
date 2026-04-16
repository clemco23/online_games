<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Sessionsgame Entity
 *
 * @property int $id
 * @property int $boardgames_id
 * @property bool $isfinish
 * @property string $code
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Boardgame $boardgame
 * @property \App\Model\Entity\Filler $filler
 * @property \App\Model\Entity\Mastermind $mastermind
 * @property \App\Model\Entity\LabyrinthGame $labyrinth_game
 * @property \App\Model\Entity\UsersSessionsgame[] $users_sessionsgames
 * @property \App\Model\Entity\User[] $users
 */
class Sessionsgame extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'boardgames_id' => true,
        'isfinish' => true,
        'code' => true,
        'created' => true,
        'modified' => true,
        'boardgame' => true,
        'filler' => true,
        'mastermind' => true,
        'labyrinth_game' => true,
        'users_sessionsgames' => true,
        'users' => true,
    ];
}
