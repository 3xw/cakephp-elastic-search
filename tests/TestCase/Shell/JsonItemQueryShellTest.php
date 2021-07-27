<?php
declare(strict_types=1);

namespace Trois\ElasticSearch\Test\TestCase\Shell;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Trois\ElasticSearch\Shell\JsonItemQueryShell;

/**
 * Trois\ElasticSearch\Shell\JsonItemQueryShell Test Case
 */
class JsonItemQueryShellTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * ConsoleIo mock
     *
     * @var \Cake\Console\ConsoleIo|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $io;

    /**
     * Test subject
     *
     * @var \Trois\ElasticSearch\Shell\JsonItemQueryShell
     */
    protected $JsonItemQuery;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        $this->JsonItemQuery = new JsonItemQueryShell($this->io);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->JsonItemQuery);

        parent::tearDown();
    }

    /**
     * Test getOptionParser method
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
