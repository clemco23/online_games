<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Fillers Model
 *
 * @property \App\Model\Table\SessionsgamesTable&\Cake\ORM\Association\BelongsTo $SessionGames
 *
 * @method \App\Model\Entity\Filler newEmptyEntity()
 * @method \App\Model\Entity\Filler newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Filler> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Filler get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Filler findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Filler patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Filler> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Filler|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Filler saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Filler>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Filler>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Filler>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Filler> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Filler>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Filler>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Filler>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Filler> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FillersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('fillers');
        $this->setDisplayField('nb_colonne');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SessionGames', [
            'foreignKey' => 'session_game_id',
            'className' => 'Sessionsgames',
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
        // $validator
        //     ->scalar('nb_colonne')
        //     ->maxLength('nb_colonne', 255)
        //     ->requirePresence('nb_colonne', 'create')
        //     ->notEmptyString('nb_colonne');

        // $validator
        //     ->integer('session_game_id')
        //     ->notEmptyString('session_game_id')
        //     ->add('session_game_id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('grid')
            ->requirePresence('grid', 'create')
            ->notEmptyString('grid');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['session_game_id']), ['errorField' => 'session_game_id']);
        $rules->add($rules->existsIn(['session_game_id'], 'SessionGames'), ['errorField' => 'session_game_id']);

        return $rules;
    }
}
