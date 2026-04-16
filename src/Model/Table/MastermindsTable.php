<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Masterminds Model
 *
 * @property \App\Model\Table\SessionsgamesTable&\Cake\ORM\Association\BelongsTo $SessionGames
 *
 * @method \App\Model\Entity\Mastermind newEmptyEntity()
 * @method \App\Model\Entity\Mastermind newEntity(array $data, array $options = [])
 */
class MastermindsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('masterminds');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SessionGames', [
            'foreignKey' => 'session_game_id',
            'className' => 'Sessionsgames',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('steps')
            ->requirePresence('steps', 'create')
            ->notEmptyString('steps');

       

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['session_game_id']), ['errorField' => 'session_game_id']);
        $rules->add($rules->existsIn(['session_game_id'], 'SessionGames'), ['errorField' => 'session_game_id']);

        return $rules;
    }
}
