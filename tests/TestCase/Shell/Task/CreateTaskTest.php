<?php
namespace Trois\ElasticSearch\Test\TestCase\Shell\Task;

use Cake\TestSuite\TestCase;
use Trois\ElasticSearch\Shell\Task\CreateTask;

/**
 * Trois\ElasticSearch\Shell\Task\CreateTask Test Case
 */
class CreateTaskTest extends TestCase
{

    /**
     * ConsoleIo mock
     *
     * @var \Cake\Console\ConsoleIo|\PHPUnit_Framework_MockObject_MockObject
     */
    public $io;

    /**
     * Test subject
     *
     * @var \Trois\ElasticSearch\Shell\Task\CreateTask
     */
    public $Create;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();

        $this->Create = $this->getMockBuilder('Trois\ElasticSearch\Shell\Task\CreateTask')
            ->setConstructorArgs([$this->io])
            ->getMock();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Create);

        parent::tearDown();
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
