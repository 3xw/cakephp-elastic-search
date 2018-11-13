<?php
namespace Trois\ElasticSearch\ORM\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ElasticSearch\IndexRegistry;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\Core\Configure;
use Elastica\Query\Match;
use ArrayObject;

class SyncWithESBehavior extends Behavior
{
  protected $_defaultConfig = [
    'index' => 'Trois\ElasticSearch\Model\Index\ItemsIndex',
    'primaryKey' => 'foreign_key', // string or callable
    'translate' => false, // property name if yes ex: locale
    'staticMatching' => false, // or [keyN => valueN/callableN]
    'mapping' => false, // properties => 1. Array: entity field(s) || properties => 2. String: static value or callable
    'deleteDocument' => true
  ];

  public $index = null;

  public $documents = [];

  public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if($this->getConfig('translate')) foreach(Configure::read('I18n.languages') as $locale) $this->documents[] = $this->patchDocument($entity, $locale);
    else $this->documents[] = $this->patchDocument($entity);
  }

  public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    foreach($this->documents as $document)
    {
      if(!$document->get('foreign_key')) $document->set('foreign_key', $entity->get($this->getConfig('primaryKey')));
      if($this->getConfig('staticMatching')) foreach($this->getConfig('staticMatching') as $key => $valueOrCallable) $document->set($key, $this->getValueOrCallable($valueOrCallable));
      $result = $this->getIndex()->save($document);
      //if(!$result) debug($document->errors());
    }
  }

  public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if(!$this->getConfig('deleteDocument')) return;

    // get document(s) to delete
    $docs = $this->buildQuery($entity)->toArray();
    foreach ($docs as $doc) $this->getIndex()->delete($doc);
  }

  public function getIndex()
  {
    if($this->index == null) $this->index = IndexRegistry::get($this->getConfig('index'));
    return $this->index;
  }

  public function patchDocument($entity, $locale = null)
  {
    if($entity->isNew()) return $this->newDocument($entity, $locale);

    // construct Query
    $query = $this->buildQuery($entity, $locale);

    // check items
    $document = $query->first();
    if(empty($document)) $document = $this->newDocument($entity, $locale);
    else $document = $this->getIndex()->patchEntity($document, $this->newData($entity, $locale));
    return $document;
  }

  public function newDocument($entity, $locale = null)
  {
    return $this->getIndex()->patchEntity($this->getIndex()->newEntity(), $this->newData($entity, $locale));
  }

  public function buildQuery($entity, $locale = null)
  {
    $query = $this->getIndex()->find()->queryMust(new Match($this->getConfig('primaryKey'), $entity->get($this->getTable()->getPrimaryKey())));
    if($this->getConfig('staticMatching')) foreach($this->getConfig('staticMatching') as $key => $valueOrCallable) $query->queryMust(new Match($key, $this->getValueOrCallable($valueOrCallable)));
    if($this->getConfig('translate') && $locale) $query->queryMust(new Match($this->getConfig('translate'), $locale));
    return $query;
  }

  public function newData($entity, $locale = null)
  {
    return ($locale == null  || ($locale == Configure::read('App.defaultLocale')))? $this->_newData($entity): $this->_newData($entity->get('_translations')[$locale]);
  }

  protected function _newData($entity)
  {
    $data = [];
    $data[$this->getConfig('primaryKey')] = $entity->get($this->getTable()->getPrimaryKey());
    foreach($this->getConfig('mapping') as $prop => $fields )
    {
      $data[$prop] = '';
      if(is_array($fields)) foreach($fields as $field) $data[$prop] .= $entity->get($field);
      else $data[$prop] = $this->getValueOrCallable($fields);
    }
    return $data;
  }

  protected function getValueOrCallable($value)
  {
    if(is_callable($value)) return call_user_func($value);
    else return $value;
  }
}
