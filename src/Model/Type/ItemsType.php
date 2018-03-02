<?php
namespace Trois\ElasticSearch\Model\Type;

use Cake\ElasticSearch\Type;

class ItemsType extends Type
{
  public static function defaultConnectionName() {
    return 'elastic';
  }
}
