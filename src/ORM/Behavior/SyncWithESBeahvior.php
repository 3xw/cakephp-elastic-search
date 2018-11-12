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
    'mappings' => [ // properties to entity field(s) => Array || string for static value || callable
      //'model' => 'foreign_key',
      'title' => ['title'],
      'content' => ['header','body']
    ]
  ];

  public $index = null;

  public $items = [];

  public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    if($this->getConfig('translate')) foreach(Configure::read('I18n.languages') as $locale) $this->items[] = $this->setEsEntity($entity, $locale);
    else $this->items[] = $this->setEsEntity($entity);
  }

  public function setEsEntity($entity, $locale = null)
  {
    if($entity->isNew()) return $this->getNewOne($entity, $locale);

    // construct Query
    $query = $this->getIndex()->find()
    ->queryMust(new Match($this->getConfig('primaryKey'), $entity->get($this->getTable()->getPrimaryKey()) ));
    if($this->getConfig('staticMatching'))
    {
      foreach($this->getConfig('staticMatching') as $key => $valueOrCallable) $query->queryMust(new Match($key, $this->getValueOrCallable($valueOrCallable) ));
    }
    if($this->getConfig('translate')) $query->queryMust(new Match($this->getConfig('translate'), $locale));

    // check items
    $document = $query->first();
    if(empty($document)) $item = $this->getNewOne($entity, $locale);
    else $item = $this->getIndex()->patchEntity($document, $this->retrieveData($entity, $locale));
    return $item;
  }

  public function getNewOne($entity, $locale = null)
  {
    return $this->getIndex()->patchEntity($this->getIndex()->newEntity(), $this->retrieveData($entity, $locale));
  }

  public function retrieveData($entity, $locale = null)
  {
    return ($locale == null || $locale == Configure::read('App.defaultLocale') )? $this->_retrieveData($entity): $this->_retrieveData( $entity->get('_translations')[$locale] );
  }

  protected function _retrieveData($entity)
  {
    $data = [];
    $data[$this->getConfig('primaryKey')] = $this->getTable()->getPrimaryKey();
    foreach($this->getConfig('mapping') as $prop => $fields )
    {
      if(is_array($fields)) foreach($fields as $field) $data[$prop] = $entity->get($field);
      else $data[$prop] = $this->getValueOrCallable($fields);
    }
    return $data;
  }

  protected function getValueOrCallable($value)
  {
    else if(is_callable($value)) return call_user_func($value);
    else return $fields;
  }

  public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    foreach($this->items as $item)
    {
      if(!$item->get('foreign_key')) $item->set('foreign_key', $entity->get($this->_config['mapping']['foreign_key']));
      $result = $this->getIndex()->save($item);
      if(!$result) debug($item->errors());
    }
  }

  public function afterDelete(Event $event, EntityInterface $entity, ArrayObject $options)
  {
    // construct Query
    $query = $this->getIndex()->find()
    ->queryMust(new Match('foreign_key', $entity->get($this->_config['mapping']['foreign_key']) ))
    ->queryMust(new Match('model', $this->_table->getAlias() ));

    // get them
    $items = $query->toArray();
    foreach ($items as $item) $this->getIndex()->delete($item);
  }

  public function getIndex()
  {
    if($this->index == null) $this->index = IndexRegistry::get($this->_config['index']);
    return $this->index;
  }


}
