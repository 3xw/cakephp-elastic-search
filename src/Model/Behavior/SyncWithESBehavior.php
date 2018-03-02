<?php
namespace Trois\ElasticSearch\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

class SyncWithESBehavior extends Behavior
{
  protected $_defaultConfig = [
    'type' => 'Trois/ElasticSearch.Items',
    'translate' => false,
    'mapping' => [
      'foreign_key' => 'id',
      'title' => 'title',
      'slug' => 'slug',
      'content' => 'body'
    ]
  ];

  protected $_Model = null;

  public function initialize(array $config)
  {
    parent::initialize($config);
    $this->
  }
}
