<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * UsersSessionsgame Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $session_game_id
 * @property int $final_score
 * @property \Cake\I18n\Time $time_session
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property bool $is_winner
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Sessionsgame $session_game
 */
class UsersSessionsgame extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'session_game_id' => true,
        'final_score' => true,
        'time_session' => true,
        'created' => true,
        'modified' => true,
        'is_winner' => true,
        'user' => true,
        'session_game' => true,
    ];
}
