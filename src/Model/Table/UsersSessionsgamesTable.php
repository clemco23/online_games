<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsersSessionsgames Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\SessionsgamesTable&\Cake\ORM\Association\BelongsTo $SessionGames
 *
 * @method \App\Model\Entity\UsersSessionsgame newEmptyEntity()
 * @method \App\Model\Entity\UsersSessionsgame newEntity(array $data, array $options = [])
 */
class UsersSessionsgamesTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users_sessionsgames');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
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
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        

        $validator
            ->integer('final_score')
            ->requirePresence('final_score', 'create')
            ->notEmptyString('final_score');

        $validator
            ->time('time_session')
            ->requirePresence('time_session', 'create')
            ->notEmptyTime('time_session');

        $validator
            ->boolean('is_winner')
            ->requirePresence('is_winner', 'create')
            ->notEmptyString('is_winner');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['user_id', 'session_game_id']), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn(['session_game_id'], 'SessionGames'), ['errorField' => 'session_game_id']);

        return $rules;
    }
}
