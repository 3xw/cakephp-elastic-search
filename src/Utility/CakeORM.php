<?php
namespace Trois\ElasticSearch\Utility;

use Cake\Utility\Hash;
use Cake\Datasource\EntityInterface;

class CakeORM
{
  public static function extractRelationFromDotProperty($entityOrArray, $property)
  {
    $props = explode('.', $property);
    $target = $entityOrArray;
    $prop = array_shift($props);

    if(is_array($target))
    {
      $t = [];
      foreach($target as $e)
      {
        $value = $e->{$prop};
        if(!$value) continue;
        if(is_array($value)) foreach($value as $v) $t[] = $v;
        else $t[] = $value;
      }
      $target = $t;
    }
    else $target = $target->{$prop};

    if(!count($props)) return $target;

    return self::extractRelationFromDotProperty($target, implode('.', $props));
  }

  public function isEntityArray($ea)
  {
    if(empty($ea)) return false;
    if(is_int(array_keys($ea)[0])) return false;
    return true;
  }

  public static function dotAssoToDotProperty($dotAsso, $source)
  {
    $assocs = explode('.', $dotAsso);
    $property = '';
    $pointer = $source;
    foreach ($assocs as $name)
    {
      $property .= $pointer->getAssociation($name)->getProperty().'.';
      $pointer = $pointer->{$name};
    }
    return substr($property, 0, -1);
  }

  public static function dotAssoToLastTable($dotAsso, $source)
  {
    $assocs = explode('.', $dotAsso);
    $pointer = $source;
    $table = null;
    foreach ($assocs as $name) $pointer = $pointer->{$name};
    return $pointer->getTarget();
  }

  public static function dotAssosToContain($dotAssos)
  {
    $contain = [];
    foreach ($dotAssos as $dotAsso) $contain = Hash::merge($contain, self::dotAssoToContain($dotAsso));
    return $contain;
  }

  public static function dotAssoToContain($dotAsso)
  {
    $assocs = explode('.', $dotAsso);
    $containments = [];
    $pointer = &$containments;
    foreach ($assocs as $name)
    {
      $pointer[$name] = [];
      $pointer = &$pointer[$name];
    }
    return $containments;
  }

  public static function getValueOrCallable($value, ...$args)
  {
    if(is_callable($value)) return call_user_func_array($value, $args);
    else if(!empty($args) && is_subclass_of($args[0], 'Cake\Datasource\EntityInterface')) return $args[0]->{$value};
    else return $value;
  }
}
