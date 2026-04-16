<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BoardgameInstruction Entity
 *
 * @property int $id
 * @property int $boardgame_id
 * @property int $step_order
 * @property string $content
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Boardgame $boardgame
 */
class BoardgameInstruction extends Entity
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
        'boardgame_id' => true,
        'step_order' => true,
        'content' => true,
        'created' => true,
        'modified' => true,
        'boardgame' => true,
    ];
}
