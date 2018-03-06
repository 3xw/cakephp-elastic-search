<?php
namespace Trois\ElasticSearch\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ElasticSearch\TypeRegistry;
use Cake\Datasource\EntityInterface;
use Cake\Core\Configure;

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
    if($this->_config['translate']) foreach(Configure::read('I18n.languages') as $locale) $this->items[] = $this->setEsEntity($entity, $locale);
    else $this->items[] = $this->setEsEntity($entity);
  }

  public function afterSave(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    foreach($this->_items as $item) $this->getType()->save($this->getType()->patchEntity($item, ['foreign_key' => $entity[$this->_config['mapping']['foreign_key']]]));
  }

  public function afterDelete(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    $where = ['foreign_key' => $entity[$this->_config['mapping']['foreign_key']],'model' => $this->_table->getAlias()];
    if($this->_config['translate']) $where['locale'] => $locale;
    $item = $this->getType()->find()->where($where)->first();
    $this->getType()->delete($item);
  }

  public function getType()
  {
    if($this->Type == null) $this->Type = TypeRegistry::get($this->_config['type']);
    return $this->Type;
  }

  public function retriveData($entity, $locale = null)
  {
    $data = ['locale' => $locale,'model' => $this->_table->getAlias()];
    switch($locale){
      case null: return $data + $this->_retriveData($entity);
      case Configure::read('App.defaultLocale'): return $data + $this->_retriveData($entity);
      default return $data + $this->_retriveData($entity->_transaltion[$locale]);
    }
  }

  protected function _retriveData($entity)
  {
    $data = [];
    foreach($this->_config['mapping '] as $prop => $fields ){
      if(!is_array($fields)) $data[$prop] = strip_tags($entiy->get($fields));
      else foreach($fields as $field) $data[$prop] = (empty($data[$prop]))? strip_tags($entiy->get($field)): $data[$prop] .strip_tags($entiy->get($field));
    }
  }

  public function setEsEntity($entity, $locale = null)
  {
    if($entity->isNew()) return $this->getNewOne($entity, $locale);
    $where = ['foreign_key' => $entity[$this->_config['mapping']['foreign_key']],'model' => $this->_table->getAlias()];
    if($this->_config['translate']) $where['locale'] => $locale;
    $item = $this->getType()->patchEntity($this->getType()->find()->where($where)->first(), $this->retriveData($entity, $locale));
    if(empty($item)) $item = $this->getNewOne($entity, $locale);
    return $item;
  }

  public function getNewOne($entity, $locale = null)
  {
    return $this->getType()->patchEntity($this->getType()->newEntity(), $this->retriveData($entity, $locale));
  }
}
