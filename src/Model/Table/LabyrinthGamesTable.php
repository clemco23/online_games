<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class LabyrinthGamesTable extends Table
{
    /**
     * Initialize table configuration.
     *
     * @param array<string, mixed> $config Table config.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('labyrinth_games');
        $this->setDisplayField('map_name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SessionGames', [
            'foreignKey' => 'session_game_id',
            'className' => 'Sessionsgames',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Boardgames', [
            'foreignKey' => 'board_game_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('session_game_id')
            ->requirePresence('session_game_id', 'create')
            ->notEmptyString('session_game_id');

        $validator
            ->integer('board_game_id')
            ->requirePresence('board_game_id', 'create')
            ->notEmptyString('board_game_id');

        $validator
            ->scalar('map_name')
            ->maxLength('map_name', 255)
            ->requirePresence('map_name', 'create')
            ->notEmptyString('map_name');

        $validator
            ->notEmptyString('treasure_pos')
            ->requirePresence('treasure_pos', 'create');

        $validator
            ->notEmptyString('player_data')
            ->requirePresence('player_data', 'create');

        return $validator;
    }

    /**
     * Application integrity rules.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['session_game_id']), ['errorField' => 'session_game_id']);
        $rules->add($rules->existsIn(['session_game_id'], 'SessionGames'), ['errorField' => 'session_game_id']);
        $rules->add($rules->existsIn(['board_game_id'], 'Boardgames'), ['errorField' => 'board_game_id']);

        return $rules;
    }
}
