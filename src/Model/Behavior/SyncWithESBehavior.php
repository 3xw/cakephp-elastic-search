<?php
namespace Trois\ElasticSearch\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ElasticSearch\TypeRegistry;
use Cake\Datasource\EntityInterface;

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

  protected $_item = null;

  public function initialize(array $config)
  {
    parent::initialize($config);
  }

  protected function getType()
  {
    if($this->Type == null) $this->Type = TypeRegistry::get($this->_config['type']);
    return $this->Type;
  }

  public function beforeSave(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    $this->_item = $this->getType()->newEntity();
    if(!$entity->isNew())
    {
      $query = $this->getType()->find()
      ->where(['foreign_key' => $entity[$this->_config['mapping']['foreign_key ']]]);

      if($this->_config['translate']){
        //??????
        // deux objet en fait ... $this->itemSS ???
      }

      $item = $query->first();
      if(!empty($item)) $this->_item = $item;
    }

    $data = [
      'locale' => 'fr_CH'
    ];
    foreach($this->_config['mapping '] as $prop => $fields )
    {
      if(!is_array($fields))
      {
        $data[$prop] = strip_tags($entiy->get($fields));
      }else{
        $value = '';
        foreach($fields as $field){
          $value .= strip_tags($entiy->get($field));
        }
        $data[$prop] = $value;
      }
    }

    $this->_item = $this->getType()->patchEntity($this->_item, $data);
  }

  public function afterSave(Event $event, EntityInterface $entity, \ArrayObject $options)
  {
    $this->getType()->save($this->_item);
  }
}
