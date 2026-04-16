<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Boardgames Model
 *
 * @method \App\Model\Entity\Boardgame newEmptyEntity()
 * @method \App\Model\Entity\Boardgame newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Boardgame> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Boardgame get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Boardgame findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Boardgame patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Boardgame> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Boardgame|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Boardgame saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Boardgame>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Boardgame>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Boardgame>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Boardgame> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Boardgame>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Boardgame>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Boardgame>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Boardgame> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class BoardgamesTable extends Table
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

        $this->setTable('boardgames');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->hasMany('BoardgameInstructions', [
            'foreignKey' => 'boardgame_id',
        ]);
        $this->hasMany('Sessionsgames', [
            'foreignKey' => 'boardgames_id',
        ]);
        $this->hasMany('LabyrinthGames', [
            'foreignKey' => 'board_game_id',
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->scalar('picture')
            ->maxLength('picture', 255)
            ->requirePresence('picture', 'create')
            ->notEmptyString('picture');

        return $validator;
    }
}
