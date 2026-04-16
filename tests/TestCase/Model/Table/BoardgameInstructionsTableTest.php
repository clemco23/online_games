<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BoardgameInstructionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BoardgameInstructionsTable Test Case
 */
class BoardgameInstructionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BoardgameInstructionsTable
     */
    protected $BoardgameInstructions;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.BoardgameInstructions',
        'app.Boardgames',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('BoardgameInstructions') ? [] : ['className' => BoardgameInstructionsTable::class];
        $this->BoardgameInstructions = $this->getTableLocator()->get('BoardgameInstructions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->BoardgameInstructions);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\BoardgameInstructionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\BoardgameInstructionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
