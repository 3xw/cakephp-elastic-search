<?php
namespace Trois\ElasticSearch\ORM;

use Cake\Datasource\EntityInterface;

use Trois\ElasticSearch\Utility\CakeORM;

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

    foreach($this->associations as $dotAsso => $fields)
    {
      if(!$e = $source->find()
      ->contain(CakeORM::dotAssoToContain($dotAsso))
      ->where(["$alias.$pKey" => $entity->get($pKey)])
      ->first()) continue;

      $property = CakeORM::dotAssoToDotProperty($dotAsso, $source);
      $table = CakeORM::dotAssoToLastTable($dotAsso, $source);

      if(!$relation = CakeORM::extractRelationFromDotProperty($e, $property)) continue;

      if(is_array($relation)) foreach ($relation as $rel) $properties[] = $this->createRelation($entity, $fields, $rel, $table);
      else $properties[] = $this->createRelation($entity, $fields, $relation, $table);
    }
    return $properties;
  }

  public function createRelation($entity, $fields, $related, $source)
  {
    $e = (object) $related;
    $obj = [ 'model' => $source->getAlias(), 'foreign_key' => $e->{$source->getPrimaryKey()}];

    foreach($fields as $field => $prop)
    {
      if(is_callable($prop)) $obj[$field] = CakeORM::getValueOrCallable($prop, $entity, $e);
      else $obj[$field] = $e->{$prop};
    }
    return $obj;
  }
}
