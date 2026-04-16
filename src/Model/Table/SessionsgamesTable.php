<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Sessionsgames Model
 *
 * @property \App\Model\Table\BoardgamesTable&\Cake\ORM\Association\BelongsTo $Boardgames
 * @property \App\Model\Table\MastermindsTable&\Cake\ORM\Association\HasOne $Masterminds
 * @property \App\Model\Table\LabyrinthGamesTable&\Cake\ORM\Association\HasOne $LabyrinthGames
 * @property \App\Model\Table\UsersSessionsgamesTable&\Cake\ORM\Association\HasMany $UsersSessionsgames
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsToMany $Users
 *
 * @method \App\Model\Entity\Sessionsgame newEmptyEntity()
 * @method \App\Model\Entity\Sessionsgame newEntity(array $data, array $options = [])
 */
class SessionsgamesTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sessionsgames');
        $this->setDisplayField('code');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Boardgames', [
            'foreignKey' => 'boardgames_id',
            'joinType' => 'INNER',
        ]);

        $this->hasOne('Fillers', [
            'foreignKey' => 'session_game_id',
        ]);
        $this->hasOne('Masterminds', [
            'foreignKey' => 'session_game_id',
        ]);
        $this->hasOne('LabyrinthGames', [
            'foreignKey' => 'session_game_id',
        ]);
        $this->hasMany('UsersSessionsgames', [
            'foreignKey' => 'session_game_id',
        ]);
        $this->belongsToMany('Users', [
            'foreignKey' => 'session_game_id',
            'targetForeignKey' => 'user_id',
            'joinTable' => 'users_sessionsgames',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('boardgames_id')
            ->requirePresence('boardgames_id', 'create')
            ->notEmptyString('boardgames_id');

        $validator
            ->boolean('isfinish')
            ->requirePresence('isfinish', 'create')
            ->notEmptyString('isfinish');

        $validator
            ->scalar('code')
            ->maxLength('code', 10)
            ->requirePresence('code', 'create')
            ->notEmptyString('code');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['boardgames_id'], 'Boardgames'), ['errorField' => 'boardgames_id']);

        return $rules;
    }
}
