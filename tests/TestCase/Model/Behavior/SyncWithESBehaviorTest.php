<?php
namespace Trois\ElasticSearch\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Trois\ElasticSearch\Model\Behavior\SyncWithESBehavior;

/**
 * Trois\ElasticSearch\Model\Behavior\SyncWithESBehavior Test Case
 */
class SyncWithESBehaviorTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Trois\ElasticSearch\Model\Behavior\SyncWithESBehavior
     */
    public $SyncWithES;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->SyncWithES = new SyncWithESBehavior();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->SyncWithES);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
