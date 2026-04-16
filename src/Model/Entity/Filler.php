<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Filler Entity
 *
 * @property int $id
 * @property string $nb_colonne
 * @property int $session_game_id
 * @property string $grid
 * @property int $current_turn_user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Sessionsgame $session_game
 */
class Filler extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'nb_colonne' => true,
        'session_game_id' => true,
        'grid' => true,
        'current_turn_user_id' => true,
        'created' => true,
        'modified' => true,
        'session_game' => true,
    ];
}
