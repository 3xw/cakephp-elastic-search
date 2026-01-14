<?php
namespace Trois\ElasticSearch\ORM\Behavior;

use ArrayObject;

use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Datasource\EntityInterface;

use Trois\ElasticSearch\Utility\CakeORM;

class SyncRelatedWithESBehavior extends Behavior
{
  protected array $_defaultConfig = [
    'related' => []
  ];

  public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if(!$related = $this->getConfig('related')) return;

    $dotAssos = [];
    foreach ($related as $dotAsso => $fields)
    {
      foreach($fields as $field)
      {
        if(array_key_exists($dotAsso, $dotAssos)) continue;
        /*if($entity->hasChanged($field))*/ $dotAssos[$dotAsso] = $fields;
      }
    }

    $this->warnRelations($entity, $dotAssos);
  }

  public function warnRelations($entity, $dotAssos)
  {
    $contain = CakeORM::dotAssosToContain(array_keys($dotAssos));
    $alias = $this->getTable()->getAlias();
    $pKey = $this->getTable()->getPrimaryKey();

    if(!$e = $this->getTable()->find()
    ->contain($contain)
    ->where(["$alias.$pKey" => $entity->get($pKey)])
    ->first()) return;

    foreach ($dotAssos as $dotAsso => $fields) $this->warnRelation($e, $dotAsso, $fields);
  }

  public function warnRelation($entity, $dotAsso, $fields)
  {
    $property = CakeORM::dotAssoToDotProperty($dotAsso, $this->getTable());
    $table = CakeORM::dotAssoToLastTable($dotAsso, $this->getTable());
    $dirtyField = $table->getDisplayField();

    if(!$relation = CakeORM::extractRelationFromDotProperty($entity, $property)) return;

    if(is_array($relation)) foreach ($relation as $rel) $table->saveOrUpadteES($rel);
    else $table->saveOrUpadteES($relation);
  }
}
