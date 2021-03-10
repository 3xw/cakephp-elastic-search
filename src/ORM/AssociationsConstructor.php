<?php
namespace Trois\ElasticSearch\ORM;

use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;

use Trois\Utils\Core\ArrayObject;

class AssociationsConstructor
{
  use \Cake\Datasource\ModelAwareTrait;

  public $associations;

  public function __construct($associations = [])
  {
    $this->associations = $associations;
  }

  public function newProperty($entity, $separator = ' ')
  {
    $properties = [];
    $source = $this->loadModel($entity->getSource());
    $alias = $source->getAlias();
    $pKey = $source->getPrimaryKey();

    foreach($this->associations as $assoc => $fields)
    {
      $entityArray = (new ArrayObject(
        $source->find()
        ->contain($this->assoToContain($assoc))
        ->where(["$alias.$pKey" => $entity->get($pKey)])
        ->first()
      ))
      ->toArray();

      list($property, $table) = $this->assoToDotPropertyAndTable($assoc, $source);
      $ae = (object) Hash::get($entityArray, $property);

      $obj = [
        'model' => $table->getAlias(),
        'foreign_key' => $ae->{$table->getPrimaryKey()}
      ];
      foreach($fields as $field => $prop)
      {
        if(is_callable($prop)) $obj[$field] = $this->getValueOrCallable($prop, $entity);
        else $obj[$field] = $ae->{$prop};
      }
      
      $properties[] = $obj;
    }

    $entity->get($field);

    return $properties;
  }

  public function assoToDotPropertyAndTable($assoc, $source)
  {
    $assocs = explode('.', $assoc);
    $property = '';
    $pointer = $source;
    foreach ($assocs as $name)
    {
      $property .= $pointer->getAssociation('Attachments')->getProperty();
      $pointer = $pointer->{$name};
    }
    return [$property, $pointer];
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

  protected function getValueOrCallable($value, EntityInterface $entity)
  {
    if(is_callable($value)) return call_user_func($value, $entity);
    else return $value;
  }

}
