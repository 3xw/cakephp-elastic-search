<?php
namespace Trois\ElasticSearch\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ElasticSearch\TypeRegistry;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;
use Cake\Core\Configure;
use Elastica\Query\Match;

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

  protected $_Type = null;

  protected $_items = [];

  public function beforeSave(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    if($this->_config['translate']) foreach(Configure::read('I18n.languages') as $locale) $this->_items[] = $this->setEsEntity($entity, $locale);
    else $this->_items[] = $this->setEsEntity($entity);
  }

  public function afterSave(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    foreach($this->_items as $item)
    {
      if(!$item->get('foreign_key')) $item->set('foreign_key', $entity->get($this->_config['mapping']['foreign_key']));
      $result = $this->getType()->save($item);
      if(!$result) debug($item->errors());
    }
  }

  public function afterDelete(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    $where = ['foreign_key' => $entity[$this->_config['mapping']['foreign_key']],'model' => $this->_table->getAlias()];
    if($this->_config['translate']) $where['locale'] = $locale;
    $item = $this->getType()->find()->where($where)->first();
    $this->getType()->delete($item);
  }

  public function getType()
  {
    if($this->_Type == null) $this->_Type = TypeRegistry::get($this->_config['type']);
    return $this->_Type;
  }

  public function retrieveData($entity, $locale = null)
  {
    $data = ['locale' => $locale,'model' => $this->_table->getAlias()];

    switch($locale){
      case null:
      case Configure::read('App.defaultLocale'): return array_merge($data, $this->_retrieveData($entity));
      default: return array_merge($data, $this->_retrieveData( $entity->get('_translations')[$locale] ));
    }
  }

  protected function _retrieveData($entity)
  {
    $data = [];
    foreach($this->_config['mapping'] as $prop => $fields ){
      if(!is_array($fields)) $data[$prop] = $entity->get($fields);
      else foreach($fields as $field) $data[$prop] = (empty($data[$prop]))? $entity->get($field): $data[$prop] .$entity->get($field);
    }
    return $data;
  }

  public function setEsEntity($entity, $locale = null)
  {
    if($entity->isNew()) return $this->getNewOne($entity, $locale);

    // construct Query
    $query = $this->getType()->find()
    ->queryMust(new Match('foreign_key', $entity->get($this->_config['mapping']['foreign_key']) ))
    ->queryMust(new Match('model', $this->_table->getAlias() ));
    if($locale) $query->queryMust(new Match('locale', $locale));

    // check items
    $document = $query->first();
    if(empty($document)) $item = $this->getNewOne($entity, $locale);
    else $item = $this->getType()->patchEntity($document, $this->retrieveData($entity, $locale));
    return $item;
  }

  public function getNewOne($entity, $locale = null)
  {
    return $this->getType()->patchEntity($this->getType()->newEntity(), $this->retrieveData($entity, $locale));
  }
}
