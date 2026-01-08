<?php
namespace Trois\ElasticSearch\Test\TestCase\Shell\Task;

use Cake\TestSuite\TestCase;
use Trois\ElasticSearch\Shell\Task\DeleteTask;

/**
 * Trois\ElasticSearch\Shell\Task\DeleteTask Test Case
 */
class DeleteTaskTest extends TestCase
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
     * @var \Trois\ElasticSearch\Shell\Task\DeleteTask
     */
    public $Delete;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();

        $this->Delete = $this->getMockBuilder('Trois\ElasticSearch\Shell\Task\DeleteTask')
            ->setConstructorArgs([$this->io])
            ->getMock();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Delete);

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
