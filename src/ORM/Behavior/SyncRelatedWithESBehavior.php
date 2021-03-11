<?php
namespace Trois\ElasticSearch\ORM\Behavior;

use ArrayObject;

use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;

class SyncRelatedWithESBehavior extends Behavior
{
  protected $_defaultConfig = [
    'related' => []
  ];

  public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if(empty($this->getConfig('related'))) return;
  }

  public function assoToDotPropertyAndTable($assoc, $source)
  {
    $assocs = explode('.', $assoc);
    $property = '';
    $pointer = $source;
    $table = null;
    foreach ($assocs as $name)
    {
      $property .= $pointer->getAssociation($name)->getProperty();
      $pointer = $pointer->{$name};
    }
    return [$property, $pointer->getTarget()];
  }

  public function assoToContain($assoc)
  {
    $assocs = explode('.', $assoc);
    $containments = [];
    $pointer = &$containments;
    foreach ($assocs as $name)
    {
      $pointer[$name] = [];
      $pointer = &$pointer[$name];
    }
    return $containments;
  }
}
