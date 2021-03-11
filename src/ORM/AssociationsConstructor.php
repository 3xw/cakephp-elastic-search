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
      if(!$e = $source->find()
      ->contain($this->assoToContain($assoc))
      ->where(["$alias.$pKey" => $entity->get($pKey)])
      ->first()) continue;

      $ea = (new ArrayObject())->getArray($e->toArray());
      list($property, $table) = $this->assoToDotPropertyAndTable($assoc, $source);
      if(!$relation = Hash::get($ea, $property)) continue;

      if(is_int(array_keys($relation)[0])) foreach ($relation as $rel) $properties[] = $this->createRelation($entity, $fields, $rel, $table);
      else $properties[] = $this->createRelation($entity, $fileds, $relation, $table);
    }
    return $properties;
  }

  public function createRelation($entity, $fields, $related, $source)
  {
    $e = (object) $related;
    $obj = [
      'model' => $source->getAlias(),
      'foreign_key' => $e->{$source->getPrimaryKey()}
    ];
    foreach($fields as $field => $prop)
    {
      if(is_callable($prop)) $obj[$field] = $this->getValueOrCallable($prop, $entity, $e);
      else $obj[$field] = $e->{$prop};
    }
    return $obj;
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

  protected function getValueOrCallable($value, EntityInterface $entity, array $related)
  {
    if(is_callable($value)) return call_user_func($value, $entity);
    else return $value;
  }

}
