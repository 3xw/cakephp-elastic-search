<?php
namespace Trois\ElasticSearch\ORM;

use Cake\Datasource\EntityInterface;

class CompletionConstructor {

  public $input;

  public $options;

  public function __construct($input, $options = [])
  {
      $this->input = $input;
      $this->options = $options;
  }

  public function newProperty($entity, $separator = ' ')
  {
    $property = [];

    // input field
    if(is_array($this->input))
    {
      $input = '';
      foreach($this->input as $field) $input .= $entity->get($field).$separator;
      $input = substr($input, 0, strlen($input) - strlen($separator));
    }
    else $input = $this->getValueOrCallable($this->input, $entity);

    $property['input'] = $input;

    // context
    if(!empty($this->options['contexts']))
    {
      $contexts = [];
      foreach($this->options['contexts'] as $ctx => $value )
      {
        if(is_array($value))
        {
          $contexts[$ctx] = [];
          foreach($value as $field) $contexts[$ctx][] = $entity->get($field);
        }
        else $contexts[$ctx] = $this->getValueOrCallable($value, $entity);
      }
      $property['contexts'] = $contexts;
    }

    return $property;
  }

  protected function getValueOrCallable($value, EntityInterface $entity)
  {
    if(is_callable($value)) return call_user_func($value, $entity);
    else return $value;
  }

}
