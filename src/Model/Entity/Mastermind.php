<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Mastermind Entity
 *
 * @property int $id
 * @property string $steps
 * @property int $session_game_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Sessionsgame $session_game
 */
class Mastermind extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'steps' => true,
        'session_game_id' => true,
        'created' => true,
        'modified' => true,
        'session_game' => true,
    ];
}
