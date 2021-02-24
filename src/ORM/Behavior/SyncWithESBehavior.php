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
use Trois\ElasticSearch\ORM\CompletionConstructor;

class SyncWithESBehavior extends Behavior
{
  protected $_defaultConfig = [
    'index' => 'Trois\ElasticSearch\Model\Index\ItemsIndex',
    'primaryKey' => 'foreign_key', // string or callable
    'translate' => false, // property name if yes ex: locale
    'staticMatching' => false, // or [keyN => valueN/callableN]
    'mapping' => false, // properties => 1. Array: entity field(s) || properties => 2. String: static value or callable || 3. CompletionConstructor
    'deleteDocument' => true,
    'separator' => ' - '
  ];

  protected $_primaryKey = null;

  public $index = null;

  public $documents = [];

  protected $_clonedEntity = null;

  public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if($this->getConfig('translate'))
    {
      $lng = Configure::read('I18n.languages');
      if(!is_array($lng)) throw new \Exception("I18n.languages should be an array!", 1);
      if(array_shift($lng) !=  Configure::read('App.defaultLocale')) throw new \Exception("First item of I18n.languages should equal to App.defaultLocale", 1);
      foreach(Configure::read('I18n.languages') as $locale) $this->documents[] = $this->patchDocument($entity, $locale);
    }
    else $this->documents[] = $this->patchDocument($entity);
  }

  public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    foreach($this->documents as $document)
    {
      if(!$document->get('foreign_key')) $document->set('foreign_key', $entity->get($this->getTable()->getPrimaryKey()));
      if($this->getConfig('staticMatching')) foreach($this->getConfig('staticMatching') as $key => $valueOrCallable) $document->set($key, $this->getValueOrCallable($valueOrCallable, $entity));
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
    return $this->getIndex()->patchEntity($this->getIndex()->newEmptyEntity(), $this->newData($entity, $locale));
  }

  public function buildQuery($entity, $locale = null)
  {
    $query = $this->getIndex()->find()->queryMust(new Match($this->getConfig('primaryKey'), $entity->get($this->getTable()->getPrimaryKey())));
    if($this->getConfig('staticMatching')) foreach($this->getConfig('staticMatching') as $key => $valueOrCallable) $query->queryMust(new Match($key, $this->getValueOrCallable($valueOrCallable, $entity)));
    if($this->getConfig('translate') && $locale) $query->queryMust(new Match($this->getConfig('translate'), $locale));

    return $query;
  }

  public function newData($entity, $locale = null)
  {
    if($locale == null) return $this->_newData($entity, $locale);
    if($locale == Configure::read('App.defaultLocale'))
    {
      if(!$this->_clonedEntity)
      {
        $this->_clonedEntity = clone $entity;
        $this->_clonedEntity->set('_translations', null);
      }
      return $this->_newData($entity, $locale);
    }

    // if transaltion document then create from original one...
    $entity = $this->getTable()->patchEntity($this->_clonedEntity, $entity->get('_translations')[$locale]->toArray());
    return $this->_newData($entity, $locale);
  }

  protected function _newData($entity, $locale)
  {
    $data = [];
    $pkey = $this->getConfig('primaryKey');
    $data[$pkey] = $entity->get($this->getTable()->getPrimaryKey());
    if($data[$pkey]) $this->_primaryKey = $data[$pkey];
    if(empty($data[$pkey]) && !empty($this->_primaryKey) && $this->getConfig('translate')) $data[$pkey] = $this->_primaryKey;

    if($this->getConfig('translate')) $data[$this->getConfig('translate')] = $locale;

    foreach($this->getConfig('mapping') as $prop => $fields )
    {
      if(is_array($fields))
      {
        $data[$prop] = '';
        foreach($fields as $field) $data[$prop] .= $entity->get($field).$this->getConfig('separator');
        $data[$prop] = substr($data[$prop], 0, strlen($data[$prop]) - strlen($this->getConfig('separator')));
      }else if($fields instanceof CompletionConstructor) $data[$prop] = $fields->newProperty($entity, $this->getConfig('separator'));
      else $data[$prop] = $this->getValueOrCallable($fields, $entity);
    }
    return $data;
  }

  protected function getValueOrCallable($value, EntityInterface $entity)
  {
    if(is_callable($value)) return call_user_func($value, $entity);
    else return $value;
  }
}
