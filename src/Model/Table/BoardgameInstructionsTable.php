<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * BoardgameInstructions Model
 *
 * @property \App\Model\Table\BoardgamesTable&\Cake\ORM\Association\BelongsTo $Boardgames
 *
 * @method \App\Model\Entity\BoardgameInstruction newEmptyEntity()
 * @method \App\Model\Entity\BoardgameInstruction newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\BoardgameInstruction> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\BoardgameInstruction get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\BoardgameInstruction findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\BoardgameInstruction patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\BoardgameInstruction> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\BoardgameInstruction|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\BoardgameInstruction saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\BoardgameInstruction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BoardgameInstruction>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\BoardgameInstruction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BoardgameInstruction> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\BoardgameInstruction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BoardgameInstruction>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\BoardgameInstruction>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\BoardgameInstruction> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BoardgameInstructionsTable extends Table
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

        $this->setTable('boardgame_instructions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Boardgames', [
            'foreignKey' => 'boardgame_id',
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
            ->notEmptyString('boardgame_id');

        $validator
            ->integer('step_order')
            ->requirePresence('step_order', 'create')
            ->notEmptyString('step_order');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

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
        $rules->add($rules->isUnique(['boardgame_id', 'step_order']), ['errorField' => 'boardgame_id', 'message' => __('This combination of boardgame_id and step_order already exists')]);
        $rules->add($rules->existsIn(['boardgame_id'], 'Boardgames'), ['errorField' => 'boardgame_id']);

        return $rules;
    }
}
