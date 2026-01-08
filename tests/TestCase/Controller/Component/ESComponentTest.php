<?php
namespace Trois\ElasticSearch\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Trois\ElasticSearch\Controller\Component\ESComponent;

/**
 * Trois\ElasticSearch\Controller\Component\ESComponent Test Case
 */
class ESComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Trois\ElasticSearch\Controller\Component\ESComponent
     */
    public $ES;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->ES = new ESComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ES);

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
