<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BoardgamesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BoardgamesTable Test Case
 */
class BoardgamesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BoardgamesTable
     */
    protected $Boardgames;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $config = $this->getTableLocator()->exists('Boardgames') ? [] : ['className' => BoardgamesTable::class];
        $this->Boardgames = $this->getTableLocator()->get('Boardgames', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Boardgames);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\BoardgamesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
